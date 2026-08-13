<?php

declare(strict_types=1);

namespace Flexihash;

use Flexihash\Hasher\Crc32Hasher;
use Flexihash\Hasher\HasherInterface;

/**
 * A simple consistent hashing implementation with pluggable hash algorithms.
 *
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash implements \Stringable
{
    /**
     * The number of positions to hash each target to.
     */
    private int $replicas = 64;

    /**
     * The hash algorithm, encapsulated in a HasherInterface implementation.
     */
    private readonly HasherInterface $hasher;

    /**
     * Internal counter for current number of targets.
     */
    private int $targetCount = 0;

    /**
     * Internal map of positions (hash outputs) to targets.
     *
     * @var array<int, string> { position => target, ... }
     */
    private array $positionToTarget = [];

    /**
     * Internal map of targets to lists of positions that target is hashed to.
     *
     * @var array<string, list<int|float>> { target => [ position, position, ... ], ... }
     */
    private array $targetToPositions = [];

    /**
     * Whether the internal map of positions to targets is already sorted.
     */
    private bool $positionToTargetSorted = false;

    /**
     * Sorted positions.
     *
     * @var list<int>
     */
    private array $sortedPositions = [];

    /**
     * Internal counter for current number of positions.
     */
    private int $positionCount = 0;

    /**
     * @param int|null $replicas Amount of positions to hash each target to.
     */
    public function __construct(?HasherInterface $hasher = null, ?int $replicas = null)
    {
        $this->hasher = $hasher ?? new Crc32Hasher();
        if (!empty($replicas)) {
            $this->replicas = $replicas;
        }
    }

    /**
     * Add a target.
     *
     * @return static fluent
     * @throws Exception when target already exists
     */
    public function addTarget(string $target, int|float $weight = 1): static
    {
        if (isset($this->targetToPositions[$target])) {
            throw new Exception("Target '$target' already exists.");
        }

        $this->targetToPositions[$target] = [];

        // hash the target into multiple positions
        for ($i = 0; $i < round($this->replicas * $weight); ++$i) {
            $position = $this->hasher->hash($target . $i);
            // @phpstan-ignore offsetAccess.invalidOffset (32bit hashers may return float; PHP coerces keys to int)
            $this->positionToTarget[$position] = $target; // lookup
            $this->targetToPositions[$target][] = $position; // target removal
        }

        $this->positionToTargetSorted = false;
        ++$this->targetCount;

        return $this;
    }

    /**
     * Add a list of targets.
     *
     * @param list<string> $targets
     * @return static fluent
     */
    public function addTargets(array $targets, int|float $weight = 1): static
    {
        foreach ($targets as $target) {
            $this->addTarget($target, $weight);
        }

        return $this;
    }

    /**
     * Remove a target.
     *
     * @return static fluent
     * @throws Exception when target does not exist
     */
    public function removeTarget(string $target): static
    {
        if (!isset($this->targetToPositions[$target])) {
            throw new Exception("Target '$target' does not exist.");
        }

        foreach ($this->targetToPositions[$target] as $position) {
            // @phpstan-ignore offsetAccess.invalidOffset (32bit hashers may return float; PHP coerces keys to int)
            unset($this->positionToTarget[$position]);
        }

        unset($this->targetToPositions[$target]);

        $this->positionToTargetSorted = false;
        --$this->targetCount;

        return $this;
    }

    /**
     * A list of all potential targets.
     *
     * @return list<string>
     */
    public function getAllTargets(): array
    {
        return array_keys($this->targetToPositions);
    }

    /**
     * Looks up the target for the given resource.
     *
     * @throws Exception when no targets defined
     */
    public function lookup(string $resource): string
    {
        $targets = $this->lookupList($resource, 1);
        if ($targets === []) {
            throw new Exception('No targets exist');
        }

        return $targets[0];
    }

    /**
     * Get a list of targets for the resource, in order of precedence.
     * Up to $requestedCount targets are returned, less if there are fewer in total.
     *
     * @param int $requestedCount The length of the list to return
     * @return list<string> List of targets
     * @throws Exception when count is invalid
     */
    public function lookupList(string $resource, int $requestedCount): array
    {
        if ($requestedCount <= 0) {
            throw new Exception('Invalid count requested');
        }

        // handle no targets
        if ($this->positionToTarget === []) {
            return [];
        }

        // optimize single target
        if ($this->targetCount === 1) {
            return array_values(array_unique($this->positionToTarget));
        }

        // hash resource to a position
        $resourcePosition = $this->hasher->hash($resource);

        $this->sortPositionTargets();

        // binary search for the first position greater than the resource
        // position, wrapping around to the start of the ring if there is none
        $positions = $this->sortedPositions;
        $low = 0;
        $high = $this->positionCount;
        while ($low < $high) {
            $mid = ($low + $high) >> 1;
            if ($positions[$mid] <= $resourcePosition) {
                $low = $mid + 1;
            } else {
                $high = $mid;
            }
        }
        $offset = ($low === $this->positionCount) ? 0 : $low;

        // walk the ring collecting distinct targets until there are enough,
        // or until every position has been considered
        $results = [];
        for ($i = 0; $i < $this->positionCount; ++$i) {
            $target = $this->positionToTarget[$positions[($offset + $i) % $this->positionCount]];
            if (!in_array($target, $results, true)) {
                $results[] = $target;
                if (count($results) === $requestedCount || count($results) === $this->targetCount) {
                    break;
                }
            }
        }

        return $results;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s{targets:[%s]}',
            static::class,
            implode(',', $this->getAllTargets())
        );
    }

    // ----------------------------------------
    // private methods

    /**
     * Sorts the internal mapping (positions to targets) by position.
     */
    private function sortPositionTargets(): void
    {
        // sort by key (position) if not already
        if (!$this->positionToTargetSorted) {
            ksort($this->positionToTarget, SORT_REGULAR);
            $this->positionToTargetSorted = true;
            $this->sortedPositions = array_keys($this->positionToTarget);
            $this->positionCount = count($this->sortedPositions);
        }
    }
}
