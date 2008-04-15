<?php

/**
 * Uses CRC32 to hash a value into a signed 32bit int address space.
 * @author Paul Annesley
 */
class Flexihash_Hasher_Crc32Hasher
	implements Flexihash_Hasher
{

	/* (non-phpdoc)
	 * @see Flexihash_Hasher::hash()
	 */
	public function hash($string)
	{
		return crc32($string);
	}

}

