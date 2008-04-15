<?php

/**
 * @author Paul Annesley
 */
class Flexihash_HashSpaceTest extends UnitTestCase
{

	public function testGetAllTargetsEmpty()
	{
		$hashSpace = new Flexihash_HashSpace();
		$this->assertEqual($hashSpace->getAllTargets(), array());
	}

	public function testAddTargetThrowsExceptionOnDuplicateTarget()
	{
		$hashSpace = new Flexihash_HashSpace();
		$hashSpace->addTarget('t-a');
		$this->expectException('Flexihash_Exception');
		$hashSpace->addTarget('t-a');
	}

	public function testAddTargetAndGetAllTargets()
	{
		$hashSpace = new Flexihash_HashSpace();
		$hashSpace
			->addTarget('t-a')
			->addTarget('t-b')
			->addTarget('t-c')
			;

		$this->assertEqual($hashSpace->getAllTargets(), array('t-a', 't-b', 't-c'));
	}

	public function testAddTargetsAndGetAllTargets()
	{
		$targets = array('t-a', 't-b', 't-c');

		$hashSpace = new Flexihash_HashSpace();
		$hashSpace->addTargets($targets);
		$this->assertEqual($hashSpace->getAllTargets(), $targets);
	}

	public function testRemoveTarget()
	{
		$hashSpace = new Flexihash_HashSpace();
		$hashSpace
			->addTarget('t-a')
			->addTarget('t-b')
			->addTarget('t-c')
			->removeTarget('t-b')
			;
		$this->assertEqual($hashSpace->getAllTargets(), array('t-a', 't-c'));
	}

	public function testRemoveTargetFailsOnMissingTarget()
	{
		$hashSpace = new Flexihash_HashSpace();
		$this->expectException('Flexihash_Exception');
		$hashSpace->removeTarget('not-there');
	}

	public function testHashSpaceRepeatableLookups()
	{
		$hashSpace = new Flexihash_HashSpace();
		foreach (range(1,10) as $i) $hashSpace->addTarget("target$i");

		$this->assertEqual($hashSpace->lookup('t1'), $hashSpace->lookup('t1'));
		$this->assertEqual($hashSpace->lookup('t2'), $hashSpace->lookup('t2'));
	}

	public function testHashSpaceLookupsAreValidTargets()
	{
		$targets = array();
		foreach (range(1,10) as $i) $targets []= "target$i";

		$hashSpace = new Flexihash_HashSpace();
		$hashSpace->addTargets($targets);

		foreach (range(1,10	) as $i)
		{
			$this->assertTrue(in_array($hashSpace->lookup("r$i"), $targets),
				'target must be in list of targets');
		}
	}

	public function testHashSpaceConsistentLookupsAfterAddingAndRemoving()
	{
		$hashSpace = new Flexihash_HashSpace();
		foreach (range(1,10) as $i) $hashSpace->addTarget("target$i");

		$results1 = array();
		foreach (range(1, 100) as $i) $results1 []= $hashSpace->lookup("t$i");

		$hashSpace
			->addTarget('new-target')
			->removeTarget('new-target')
			->addTarget('new-target')
			->removeTarget('new-target')
			;

		$results2 = array();
		foreach (range(1, 100) as $i) $results2 []= $hashSpace->lookup("t$i");

		// This is probably optimistic, as adding/removing a target may
		// clobber existing targets and is not expected to restore them.
		$this->assertEqual($results1, $results2);
	}

	public function testHashSpaceConsistentLookupsWithNewInstance()
	{
		$hashSpace1 = new Flexihash_HashSpace();
		foreach (range(1,10) as $i) $hashSpace1->addTarget("target$i");
		$results1 = array();
		foreach (range(1, 100) as $i) $results1 []= $hashSpace1->lookup("t$i");

		$hashSpace2 = new Flexihash_HashSpace();
		foreach (range(1,10) as $i) $hashSpace2->addTarget("target$i");
		$results2 = array();
		foreach (range(1, 100) as $i) $results2 []= $hashSpace2->lookup("t$i");

		$this->assertEqual($results1, $results2);
	}

	public function testGetMultipleTargets()
	{
		$hashSpace = new Flexihash_HashSpace();
		foreach (range(1,10) as $i) $hashSpace->addTarget("target$i");

		$targets = $hashSpace->lookupList('resource', 2);

		$this->assertIsA($targets, 'array');
		$this->assertEqual(count($targets), 2);
		$this->assertNotEqual($targets[0], $targets[1]);
	}

	public function testGetMultipleTargetsWithOnlyOneTarget()
	{
		$hashSpace = new Flexihash_HashSpace();
		$hashSpace->addTarget("single-target");

		$targets = $hashSpace->lookupList('resource', 2);

		$this->assertIsA($targets, 'array');
		$this->assertEqual(count($targets), 1);
		$this->assertEqual($targets[0], 'single-target');
	}

	public function testGetMoreTargetsThanExist()
	{
		$hashSpace = new Flexihash_HashSpace();
		$hashSpace->addTarget("target1");
		$hashSpace->addTarget("target2");

		$targets = $hashSpace->lookupList('resource', 4);

		$this->assertIsA($targets, 'array');
		$this->assertEqual(count($targets), 2);
		$this->assertNotEqual($targets[0], $targets[1]);
	}

	public function testGetMultipleTargetsNeedingToLoopToStart()
	{
		$mockHasher = new MockHasher();

		$hashSpace = new Flexihash_HashSpace(null, 1);
		$hashSpace->setHasher($mockHasher);

		$mockHasher->setHashValue(10);
		$hashSpace->addTarget("t1");

		$mockHasher->setHashValue(20);
		$hashSpace->addTarget("t2");

		$mockHasher->setHashValue(30);
		$hashSpace->addTarget("t3");

		$mockHasher->setHashValue(40);
		$hashSpace->addTarget("t4");

		$mockHasher->setHashValue(50);
		$hashSpace->addTarget("t5");

		$mockHasher->setHashValue(35);
		$targets = $hashSpace->lookupList('resource', 4);

		$this->assertEqual($targets, array('t4', 't5', 't1', 't2'));
	}

	public function testGetMultipleTargetsWithoutGettingAnyBeforeLoopToStart()
	{
		$mockHasher = new MockHasher();

		$hashSpace = new Flexihash_HashSpace(null, 1);
		$hashSpace->setHasher($mockHasher);

		$mockHasher->setHashValue(10);
		$hashSpace->addTarget("t1");

		$mockHasher->setHashValue(20);
		$hashSpace->addTarget("t2");

		$mockHasher->setHashValue(30);
		$hashSpace->addTarget("t3");

		$mockHasher->setHashValue(100);
		$targets = $hashSpace->lookupList('resource', 2);

		$this->assertEqual($targets, array('t1', 't2'));
	}

	public function testGetMultipleTargetsWithoutNeedingToLoopToStart()
	{
		$mockHasher = new MockHasher();

		$hashSpace = new Flexihash_HashSpace(null, 1);
		$hashSpace->setHasher($mockHasher);

		$mockHasher->setHashValue(10);
		$hashSpace->addTarget("t1");

		$mockHasher->setHashValue(20);
		$hashSpace->addTarget("t2");

		$mockHasher->setHashValue(30);
		$hashSpace->addTarget("t3");

		$mockHasher->setHashValue(15);
		$targets = $hashSpace->lookupList('resource', 2);

		$this->assertEqual($targets, array('t2', 't3'));
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

