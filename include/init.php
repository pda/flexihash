<?php
/**
 * Flexihash init script.
 * Sets up include paths based on the directory this file is in.
 * Registers an SPL class autoload function.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @param mixed $items Path or paths as string or array
 */
function flexihash_unshift_include_path($items)
{
	$elements = explode(PATH_SEPARATOR, get_include_path());

	if (is_array($items))
	{
		set_include_path(implode(PATH_SEPARATOR, array_merge($items, $elements)));
	}
	else
	{
		array_unshift($elements, $items);
		set_include_path(implode(PATH_SEPARATOR, $elements));
	}
}

/**
 * SPL autoload function, loads a flexihash class file based on the class name.
 *
 * @param string
 */
function flexihash_autoload($className)
{
	if (preg_match('#^Flexihash#', $className))
	{
		require_once(preg_replace('#_#', '/', $className).'.php');
	}
}


$basedir = realpath(dirname(__FILE__).'/..');
flexihash_unshift_include_path(array("$basedir/classes", "$basedir/lib"));
spl_autoload_register('flexihash_autoload');

