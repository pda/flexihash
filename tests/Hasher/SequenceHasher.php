<?php

declare(strict_types=1);

namespace Flexihash\Tests\Hasher;

use Flexihash\Hasher\HasherInterface;

/**
 * A hasher returning values from a queue, so consecutive calls (e.g. the
 * replica positions of a single target) can be given distinct positions.
 */
class SequenceHasher implements HasherInterface
{
    /** @var list<int> */
    public array $queue = [];

    public int $fallback = 0;

    public function hash(string $string): int
    {
        return array_shift($this->queue) ?? $this->fallback;
    }
}
