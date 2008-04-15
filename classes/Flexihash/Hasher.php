<?php

/**
 * Hashes given values into a sortable fixed size address space.
 * @author Paul Annesley
 */
interface Flexihash_Hasher
{
	/**
	 * @param string
	 * @return mixed A sortable format with 0xFFFF possible values
	 */
	public function hash($string);

}

