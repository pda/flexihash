<?php

/**
 * Uses CRC32 to hash a value into a 32bit binary string data address space.
 * @author Paul Annesley
 */
class Flexihash_Hasher_Md5Hasher
	implements Flexihash_Hasher
{

	/* (non-phpdoc)
	 * @see Flexihash_Hasher::hash()
	 */
	public function hash($string)
	{
		//return substr(md5($string), 0, 8); // 8 hexits = 32bit
		return substr(md5($string, true), 0, 4); // 4 bytes = 32bit
	}

}

