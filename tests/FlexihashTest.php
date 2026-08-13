<?php

declare(strict_types=1);

namespace Flexihash\Tests;

use Flexihash\Exception;
use Flexihash\Flexihash;
use Flexihash\Tests\Hasher\MockHasher;
use Flexihash\Tests\Hasher\SequenceHasher;
use PHPUnit\Framework\TestCase;

/**
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class FlexihashTest extends TestCase
{
    public function testGetAllTargetsEmpty(): void
    {
        $hashSpace = new Flexihash();
        $this->assertEquals($hashSpace->getAllTargets(), []);
    }

    public function testAddTargetThrowsExceptionOnDuplicateTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('t-a');
        $this->expectException(Exception::class);
        $hashSpace->addTarget('t-a');
    }

    public function testAddTargetAndGetAllTargets(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace
            ->addTarget('t-a')
            ->addTarget('t-b')
            ->addTarget('t-c')
            ;

        $this->assertEquals($hashSpace->getAllTargets(), ['t-a', 't-b', 't-c']);
    }

    public function testAddTargetsAndGetAllTargets(): void
    {
        $targets = ['t-a', 't-b', 't-c'];

        $hashSpace = new Flexihash();
        $hashSpace->addTargets($targets);
        $this->assertEquals($hashSpace->getAllTargets(), $targets);
    }

    public function testRemoveTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace
            ->addTarget('t-a')
            ->addTarget('t-b')
            ->addTarget('t-c')
            ->removeTarget('t-b')
            ;
        $this->assertEquals($hashSpace->getAllTargets(), ['t-a', 't-c']);
    }

    public function testRemoveTargetFailsOnMissingTarget(): void
    {
        $hashSpace = new Flexihash();
        $this->expectException(Exception::class);
        $hashSpace->removeTarget('not-there');
    }

    public function testHashSpaceRepeatableLookups(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $this->assertEquals($hashSpace->lookup('t1'), $hashSpace->lookup('t1'));
        $this->assertEquals($hashSpace->lookup('t2'), $hashSpace->lookup('t2'));
    }

    public function testHashSpaceLookupListEmpty(): void
    {
        $hashSpace = new Flexihash();
        $this->assertEmpty($hashSpace->lookupList('t1', 2));
    }

    public function testHashSpaceLookupListNoTargets(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No targets exist');
        $hashSpace = new Flexihash();
        $hashSpace->lookup('t1');
    }

    public function testHashSpaceLookupListNo(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid count requested');
        $hashSpace = new Flexihash();
        $hashSpace->lookupList('t1', 0);
    }

    public function testHashSpaceLookupListNegativeCount(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid count requested');
        $hashSpace = new Flexihash();
        $hashSpace->lookupList('t1', -1);
    }

    public function testHashSpaceLookupsAreValidTargets(): void
    {
        $targets = [];
        foreach (range(1, 10) as $i) {
            $targets[] = "target$i";
        }

        $hashSpace = new Flexihash();
        $hashSpace->addTargets($targets);

        foreach (range(1, 10) as $i) {
            $this->assertTrue(
                in_array($hashSpace->lookup("r$i"), $targets),
                'target must be in list of targets'
            );
        }
    }

    public function testHashSpaceConsistentLookupsAfterAddingAndRemoving(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1[] = $hashSpace->lookup("t$i");
        }

        $hashSpace
            ->addTarget('new-target')
            ->removeTarget('new-target')
            ->addTarget('new-target')
            ->removeTarget('new-target')
            ;

        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2[] = $hashSpace->lookup("t$i");
        }

        // This is probably optimistic, as adding/removing a target may
        // clobber existing targets and is not expected to restore them.
        $this->assertEquals($results1, $results2);
    }

    public function testHashSpaceConsistentLookupsWithNewInstance(): void
    {
        $hashSpace1 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace1->addTarget("target$i");
        }
        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1[] = $hashSpace1->lookup("t$i");
        }

        $hashSpace2 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace2->addTarget("target$i");
        }
        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2[] = $hashSpace2->lookup("t$i");
        }

        $this->assertEquals($results1, $results2);
    }

    public function testGetMultipleTargets(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertCount(2, $targets);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsWithOnlyOneTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('single-target');

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertCount(1, $targets);
        $this->assertEquals($targets[0], 'single-target');
    }

    public function testGetMoreTargetsThanExist(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('target1');
        $hashSpace->addTarget('target2');

        $targets = $hashSpace->lookupList('resource', 4);

        $this->assertCount(2, $targets);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsNeedingToLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(40);
        $hashSpace->addTarget('t4');

        $mockHasher->setHashValue(50);
        $hashSpace->addTarget('t5');

        $mockHasher->setHashValue(35);
        $targets = $hashSpace->lookupList('resource', 4);

        $this->assertEquals($targets, ['t4', 't5', 't1', 't2']);
    }

    public function testGetMultipleTargetsWithoutGettingAnyBeforeLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(100);
        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertEquals($targets, ['t1', 't2']);
    }

    public function testGetMultipleTargetsWithoutNeedingToLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(15);
        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertEquals($targets, ['t2', 't3']);
    }

    /**
     * Adjacent positions on the ring may belong to the same target when a
     * target has more than one replica. Duplicates must be skipped, not
     * counted towards the requested number of targets.
     *
     * Regression test for the binary search lookup.
     */
    public function testGetMultipleTargetsWithDuplicateAdjacentPositions(): void
    {
        $sequenceHasher = new SequenceHasher();

        $hashSpace = new Flexihash($sequenceHasher, 2);

        $sequenceHasher->queue = [10, 20];
        $hashSpace->addTarget('t1'); // positions 10 and 20

        $sequenceHasher->queue = [30, 40];
        $hashSpace->addTarget('t2'); // positions 30 and 40

        $sequenceHasher->fallback = 5;
        // ring order from position 5 is t1(10), t1(20), t2(30), t2(40);
        // the duplicate t1 at position 20 must not exhaust the requested count
        $this->assertEquals(['t1', 't2'], $hashSpace->lookupList('resource', 2));
    }

    public function testGetMultipleTargetsWithDuplicatePositionsAcrossWrapAround(): void
    {
        $sequenceHasher = new SequenceHasher();

        $hashSpace = new Flexihash($sequenceHasher, 2);

        $sequenceHasher->queue = [10, 40];
        $hashSpace->addTarget('t1'); // positions 10 and 40

        $sequenceHasher->queue = [20, 30];
        $hashSpace->addTarget('t2'); // positions 20 and 30

        $sequenceHasher->fallback = 35;
        // ring order from position 35 is t1(40), then wrapping to t1(10), t2(20), t2(30)
        $this->assertEquals(['t1', 't2'], $hashSpace->lookupList('resource', 2));
    }

    public function testFallbackPrecedenceWhenServerRemoved(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(15);

        $this->assertEquals($hashSpace->lookup('resource'), 't2');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t2', 't3', 't1']
        );

        $hashSpace->removeTarget('t2');

        $this->assertEquals($hashSpace->lookup('resource'), 't3');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t3', 't1']
        );

        $hashSpace->removeTarget('t3');

        $this->assertEquals($hashSpace->lookup('resource'), 't1');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t1']
        );
    }

    /**
     * Does the __toString method behave as we expect.
     *
     * @author Dom Morgan <dom@d3r.com>
     */
    public function testHashSpaceToString(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);
        $hashSpace->addTarget('t1');
        $hashSpace->addTarget('t2');

        $this->assertSame(
            $hashSpace->__toString(),
            'Flexihash\Flexihash{targets:[t1,t2]}'
        );
    }
}
