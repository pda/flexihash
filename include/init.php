<?php

if (!function_exists('unshift_include_path'))
{
	/**
	 * @param mixed $items Path or paths as string or array
	 */
	function unshift_include_path($items)
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
}

// TODO: move into class loader class
function flexihash_autoload($classname)
{
	require_once(preg_replace('#_#', '/', $classname).'.php');
}


$basedir = realpath(dirname(__FILE__).'/..');
unshift_include_path(array("$basedir/classes", "$basedir/lib"));
spl_autoload_register('flexihash_autoload');

