<?php

/**
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_ClassLoaderTest extends UnitTestCase
{

	public function testClassLoaderLoadsFlexihashClass()
	{
		// this test is fragile when run in a grouptest/testsuite environment
		$this->assertFalse(in_array('Flexihash_Exception', get_declared_classes()),
			'Flexihash_Exception should not be declared yet');

		$e = new Flexihash_Exception();

		$this->assertTrue(in_array('Flexihash_Exception', get_declared_classes()),
			'Flexihash_Exception should be declared after autoload');
	}

	public function testClassLoaderDoesNotCauseErrorsForNonFlexihashClasses()
	{
		$this->assertFalse(in_array('MissingClass', get_declared_classes()),
			'MissingClass should not be declared');

		// add another autoload to prevent fatal class not found error.
		spl_autoload_register(create_function('$c',
			'if ($c == "MissingClass") eval("class MissingClass{}");'));

		$e = new MissingClass();
	}

}

