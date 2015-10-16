<?php

/**
 * @author Paul Annesley
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_FlexihashTest extends PHPUnit_Framework_TestCase
{
    public function testGetAllTargetsEmpty()
    {
        $hashSpace = new Flexihash();
        $this->assertEquals($hashSpace->getAllTargets(), []);
    }

    public function testAddTargetThrowsExceptionOnDuplicateTarget()
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('t-a');
        $this->setExpectedException('Flexihash_Exception');
        $hashSpace->addTarget('t-a');
    }

    public function testAddTargetAndGetAllTargets()
    {
        $hashSpace = new Flexihash();
        $hashSpace
            ->addTarget('t-a')
            ->addTarget('t-b')
            ->addTarget('t-c')
            ;

        $this->assertEquals($hashSpace->getAllTargets(), ['t-a', 't-b', 't-c']);
    }

    public function testAddTargetsAndGetAllTargets()
    {
        $targets = ['t-a', 't-b', 't-c'];

        $hashSpace = new Flexihash();
        $hashSpace->addTargets($targets);
        $this->assertEquals($hashSpace->getAllTargets(), $targets);
    }

    public function testRemoveTarget()
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

    public function testRemoveTargetFailsOnMissingTarget()
    {
        $hashSpace = new Flexihash();
        $this->setExpectedException('Flexihash_Exception');
        $hashSpace->removeTarget('not-there');
    }

    public function testHashSpaceRepeatableLookups()
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $this->assertEquals($hashSpace->lookup('t1'), $hashSpace->lookup('t1'));
        $this->assertEquals($hashSpace->lookup('t2'), $hashSpace->lookup('t2'));
    }

    public function testHashSpaceLookupsAreValidTargets()
    {
        $targets = [];
        foreach (range(1, 10) as $i) {
            $targets [] = "target$i";
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

    public function testHashSpaceConsistentLookupsAfterAddingAndRemoving()
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1 [] = $hashSpace->lookup("t$i");
        }

        $hashSpace
            ->addTarget('new-target')
            ->removeTarget('new-target')
            ->addTarget('new-target')
            ->removeTarget('new-target')
            ;

        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2 [] = $hashSpace->lookup("t$i");
        }

        // This is probably optimistic, as adding/removing a target may
        // clobber existing targets and is not expected to restore them.
        $this->assertEquals($results1, $results2);
    }

    public function testHashSpaceConsistentLookupsWithNewInstance()
    {
        $hashSpace1 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace1->addTarget("target$i");
        }
        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1 [] = $hashSpace1->lookup("t$i");
        }

        $hashSpace2 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace2->addTarget("target$i");
        }
        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2 [] = $hashSpace2->lookup("t$i");
        }

        $this->assertEquals($results1, $results2);
    }

    public function testGetMultipleTargets()
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget("target$i");
        }

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertInternalType('array', $targets);
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsWithOnlyOneTarget()
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('single-target');

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertInternalType('array', $targets);
        $this->assertEquals(count($targets), 1);
        $this->assertEquals($targets[0], 'single-target');
    }

    public function testGetMoreTargetsThanExist()
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('target1');
        $hashSpace->addTarget('target2');

        $targets = $hashSpace->lookupList('resource', 4);

        $this->assertInternalType('array', $targets);
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsNeedingToLoopToStart()
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

    public function testGetMultipleTargetsWithoutGettingAnyBeforeLoopToStart()
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

    public function testGetMultipleTargetsWithoutNeedingToLoopToStart()
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

    public function testFallbackPrecedenceWhenServerRemoved()
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
}

class MockHasher implements Flexihash_Hasher
{
    private $_hashValue;

    public function setHashValue($hash)
    {
        $this->_hashValue = $hash;
    }

    public function hash($value)
    {
        return $this->_hashValue;
    }
}
