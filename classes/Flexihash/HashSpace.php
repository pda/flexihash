<?php

/**
 * @author Paul Annesley
 */
class Flexihash_Hashspace
{

	private $_replicas = 64; // positions per target
	private $_hasher; // the Flexihash_Hasher
	private $_targetCount = 0; // number of targets
	private $_positionToTarget = array(); // Map position => target
	private $_targetToPositions = array(); // Map target => [position, ...]
	private $_positionToTargetSorted = false;

	/**
	 * Constructor
	 * @param object $hasher Flexihash_Hasher
	 * @param int $replicas Amount of positions to hash each target to.
	 */
	public function __construct(Flexihash_Hasher $hasher = null, $replicas = null)
	{
		$this->_hasher = $hasher ? $hasher : new Flexihash_Hasher_Crc32Hasher();
		if (!empty($replicas)) $this->_replicas = $replicas;
	}

	/**
	 * Add a target.
	 * @param string $target
	 * @chainable
	 */
	public function addTarget($target)
	{
		if (isset($this->_targetToPositions[$target]))
		{
			throw new Flexihash_Exception("Target '$target' already exists.");
		}

		$this->_targetToPositions[$target] = array();

		// hash the target into multiple positions
		for ($i = 0; $i < $this->_replicas; $i++)
		{
			$position = $this->_hasher->hash($target . $i);
			$this->_positionToTarget[$position] = $target; // lookup
			$this->_targetToPositions[$target] []= $position; // target removal
		}

		$this->_positionToTargetSorted = false;
		$this->_targetCount++;

		return $this;
	}

	/**
	 * Add a list of targets.
	 * @param array $targets
	 * @chainable
	 */
	public function addTargets($targets)
	{
		foreach ($targets as $target)
		{
			$this->addTarget($target);
		}

		return $this;
	}

	/**
	 * Remove a target.
	 * @param string $target
	 * @chainable
	 */
	public function removeTarget($target)
	{
		if (!isset($this->_targetToPositions[$target]))
		{
			throw new Flexihash_Exception("Target '$target' does not exist.");
		}

		foreach ($this->_targetToPositions[$target] as $position)
		{
			unset($this->_positionToTarget[$position]);
		}

		unset($this->_targetToPositions[$target]);

		$this->_targetCount--;

		return $this;
	}

	/**
	 * A list of all potential targets
	 * @return array
	 */
	public function getAllTargets()
	{
		return array_keys($this->_targetToPositions);
	}

	/**
	 * Looks up the target for the given resource.
	 * @param string $resource
	 * @return string
	 */
	public function lookup($resource)
	{
		$targets = $this->lookupList($resource, 1);
		if (empty($targets)) throw new Flexihash_Exception('No targets exist');
		return $targets[0];
	}

	/**
	 * Get a list of targets for the resource, in order of precedence.
	 * Up to $requestedCount targets are returned, less if there are fewer in total.
	 * @param string $resource
	 * @param int $requestedCount The length of the list to return
	 * @return array List of targets
	 */
	public function lookupList($resource, $requestedCount)
	{
		if (!$requestedCount)
			throw new Flexihash_Exception('Invalid count requested');

		// handle no targets
		if (empty($this->_positionToTarget))
			return array();

		// optimize single target
		if ($this->_targetCount == 1)
			return array_unique(array_values($this->_positionToTarget));

		// hash resource to a position
		$resourcePosition = $this->_hasher->hash($resource);

		$results = array();
		$collect = false;

		$this->_sortPositionTargets();

		// search values above the resourcePosition
		foreach ($this->_positionToTarget as $key => $value)
		{
			// start collecting targets after passing resource position
			if (!$collect && $key > $resourcePosition)
			{
				$collect = true;
			}

			// only collect the first instance of any target
			if ($collect && !in_array($value, $results))
			{
				$results []= $value;
			}

			// return when enough results, or list exhausted
			if (count($results) == $requestedCount || count($results) == $this->_targetCount)
			{
				return $results;
			}
		}

		// loop to start - search values below the resourcePosition
		foreach ($this->_positionToTarget as $key => $value)
		{
			if (!in_array($value, $results))
			{
				$results []= $value;
			}

			// return when enough results, or list exhausted
			if (count($results) == $requestedCount || count($results) == $this->_targetCount)
			{
				return $results;
			}
		}

		// return results after iterating through both "parts"
		return $results;
	}

	/**
	 * Sets a new hasher.
	 * The hasher shouldn't be changed after instantiation except in testing.
	 * @param object $hasher Flexihash_Hasher
	 */
	public function setHasher(Flexihash_Hasher $hasher)
	{
		$this->_hasher = $hasher;
	}

	// ----------------------------------------
	// private methods

	/**
	 * Sorts the internal mapping (positions to targets) by position
	 */
	private function _sortPositionTargets()
	{
		// sort by key (position) if not already
		if (!$this->_positionToTargetSorted)
		{
			ksort($this->_positionToTarget, SORT_REGULAR);
			$this->_positionToTargetSorted = true;
		}
	}

}

