<?php

/**
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_HasherTest extends UnitTestCase
{

	public function testCrc32Hash()
	{
		$hasher = new Flexihash_Crc32Hasher();
		$result1 = $hasher->hash('test');
		$result2 = $hasher->hash('test');
		$result3 = $hasher->hash('different');

		$this->assertEqual($result1, $result2);
		$this->assertNotEqual($result1, $result3); // fragile but worthwhile
	}

	public function testMd5Hash()
	{
		$hasher = new Flexihash_Md5Hasher();
		$result1 = $hasher->hash('test');
		$result2 = $hasher->hash('test');
		$result3 = $hasher->hash('different');

		$this->assertEqual($result1, $result2);
		$this->assertNotEqual($result1, $result3); // fragile but worthwhile
	}

}
