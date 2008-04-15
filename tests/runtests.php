<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require(dirname(__FILE__).'/../include/init.php');

$basedir = realpath(dirname(__FILE__).'/..');
unshift_include_path(array(
	"$basedir/lib/simpletest",
	"$basedir/tests")
);

if (in_array('--help', $argv))
{
	echo <<<EOM

CLI test runner.

Available options:

  --testfile <path>  Only run the specified test file.
  --with-benchmark   Run benchmarks.
  --help             This documentation.


EOM;

	exit(0);
}


require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

/**
 * Return array of files matched, decending into subdirectories
 */
function globr($dir, $pattern)
{
		$dir = escapeshellcmd($dir);

		// list of all matching files currently in the directory.
		$files = glob("$dir/$pattern");

		// get a list of all directories in this directory
		foreach (glob("$dir/*", GLOB_ONLYDIR) as $subdir)
		{
				$subfiles = globr($subdir, $pattern);
				$files = array_merge($files, $subfiles);
		}

		return $files;
}

$withBenchmark = in_array('--with-benchmark', $argv);

if (($testFileFlagIndex = array_search('--testfile', $argv)) !== false)
{
	$testFile = $argv[$testFileFlagIndex + 1];

	$existingClasses = get_declared_classes();
	require_once($testFile);
	$newClasses = array_diff(get_declared_classes(), $existingClasses);
	if (!$testClass = array_shift($newClasses))
		die('No classes declared in file: '.$testFile);

	$test = new $testClass($testFile);
}
else
{
	$test = new TestSuite('All Tests');
	foreach (globr(dirname(__FILE__), '*Test.php') as $testFile)
	{
		if (!$withBenchmark && preg_match('#BenchmarkTest#', $testFile)) continue;

		$test->addFile($testFile);
	}
}

$test->run(new TextReporter());

