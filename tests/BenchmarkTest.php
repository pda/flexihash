<?php

declare(strict_types=1);

namespace Flexihash\Tests;

use Flexihash\Flexihash;
use Flexihash\Hasher\Crc32Hasher;
use Flexihash\Hasher\Md5Hasher;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Benchmarks, not really tests.
 *
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
#[Group('benchmark')]
#[DoesNotPerformAssertions]
class BenchmarkTest extends TestCase
{
    private int $targets = 10;

    private int $lookups = 1000;

    public function dump(string $message): void
    {
        echo $message . "\n";
    }

    public function testAddTargetWithNonConsistentHash(): void
    {
        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $this->basicHash("t$i", 10);
        }

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $this->basicHash("t$i", 11);
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] !== $results2[$i]) {
                ++$differences;
            }
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump("NonConsistentHash: {$percent}% of lookups changed " .
            "after adding a target to the existing {$this->targets}");
    }

    public function testRemoveTargetWithNonConsistentHash(): void
    {
        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $this->basicHash("t$i", 10);
        }

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $this->basicHash("t$i", 9);
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] !== $results2[$i]) {
                ++$differences;
            }
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump("NonConsistentHash: {$percent}% of lookups changed " .
            "after removing 1 of {$this->targets} targets");
    }

    public function testHopeAddingTargetDoesNotChangeMuchWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher()
        );
        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $hashSpace->lookup("t$i");
        }

        $hashSpace->addTarget('target-new');

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $hashSpace->lookup("t$i");
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] !== $results2[$i]) {
                ++$differences;
            }
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump("ConsistentHash: {$percent}% of lookups changed " .
            "after adding a target to the existing {$this->targets}");
    }

    public function testHopeRemovingTargetDoesNotChangeMuchWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher()
        );
        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $hashSpace->lookup("t$i");
        }

        $hashSpace->removeTarget('target1');

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $hashSpace->lookup("t$i");
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] !== $results2[$i]) {
                ++$differences;
            }
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump("ConsistentHash: {$percent}% of lookups changed " .
            "after removing 1 of {$this->targets} targets");
    }

    public function testHashDistributionWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher()
        );

        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $results = [];
        foreach (range(1, $this->lookups) as $i) {
            $results[$i] = $hashSpace->lookup("t$i");
        }

        $distribution = [];
        foreach ($hashSpace->getAllTargets() as $target) {
            $distribution[$target] = count(array_keys($results, $target));
        }

        $this->dump(sprintf(
            'Distribution of %d lookups per target (min/max/median/avg): %d/%d/%d/%d',
            $this->lookups / $this->targets,
            min($distribution), // @phpstan-ignore argument.type (there is always at least one target)
            max($distribution), // @phpstan-ignore argument.type (there is always at least one target)
            round($this->median($distribution)),
            round(array_sum($distribution) / count($distribution))
        ));
    }

    public function testHasherSpeed(): void
    {
        $hashCount = 100000;

        $md5Hasher = new Md5Hasher();
        $crc32Hasher = new Crc32Hasher();

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; ++$i) {
            $md5Hasher->hash("test$i"); // @phpstan-ignore method.resultUnused (timing the call)
        }
        $timeMd5 = microtime(true) - $start;

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; ++$i) {
            $crc32Hasher->hash("test$i"); // @phpstan-ignore method.resultUnused (timing the call)
        }
        $timeCrc32 = microtime(true) - $start;

        $this->dump(sprintf(
            'Hashers timed over %d hashes (MD5 / CRC32): %f / %f',
            $hashCount,
            $timeMd5,
            $timeCrc32
        ));
    }

    // ----------------------------------------

    private function basicHash(string $value, int $targets): int
    {
        return abs(crc32($value) % $targets);
    }

    /**
     * @param array<int> $values list of numeric values
     */
    private function median(array $values): float
    {
        $values = array_values($values);
        sort($values);

        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
