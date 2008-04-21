<?php
/**
 * Flexihash bundler.
 * Bundles code required to use flexihash into a single PHP file.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */

error_reporting(E_ALL);
ini_set('display_errors', true);

require(dirname(__FILE__).'/../include/init.php');

// ----------------------------------------

// declaration-level dependencies first
$classFiles = array(
	'classes/Flexihash.php',
	'classes/Flexihash/Hasher.php',
	'classes/Flexihash/Crc32Hasher.php',
	'classes/Flexihash/Md5Hasher.php',
	'classes/Flexihash/Exception.php',
);

$baseDir = realpath(dirname(__FILE__).'/..');
$classDir = "$baseDir/classes";
$licenceFile = "$baseDir/LICENCE";
$buildDir = "$baseDir/build";
$outFile = "$buildDir/flexihash.php";

// ----------------------------------------
// set up build environment

if (is_dir($buildDir))
{
	flexihash_build_log("Build directory exists: $buildDir");
}
else
{
	flexihash_build_log("Creating build directory: $buildDir");
	mkdir($buildDir);
}

// ----------------------------------------
// open bundle file, write header

if (!$fpOut = fopen($outFile, 'w'))
	throw new Exception("Unable to open file for writing: $outFile");

flexihash_build_log("Writing header to $outFile");

// Open PHP tag
fwrite($fpOut, "<?php\n");

// Main file docblock
$docBlock = new Pda_Docblock();
$docBlock
	->setShortDescription('Flexihash - A simple consistent hashing implementation for PHP.')
	->setLongDescription(trim(file_get_contents($licenceFile))."\n")
	->addTag('author', 'Paul Annesley')
	->addTag('link', 'http://paul.annesley.cc/')
	->addTag('copyright', 'Paul Annesley, 2008')
	;

fwrite($fpOut, $docBlock);

// counters
$countFiles = 0;

foreach ($classFiles as $classFile)
{
	$countFiles++;
	flexihash_build_log("Adding $classFile...");

	// open file, discard first line - PHP open tag
	$fpIn = fopen($classFile, 'r');
	fgets($fpIn);
	while (!feof($fpIn)) fwrite($fpOut, fgets($fpIn));
	fclose($fpIn);
}

$pos = ftell($fpOut);
fclose($fpOut);

flexihash_build_log("Bundled $pos bytes from $countFiles files into $outFile");


// ----------------------------------------

/**
 * Logs a message to the console.
 * @param string $message
 */
function flexihash_build_log($message)
{
	printf("%s\n", $message);
}

// ----------------------------------------
// docblock helpers.
// should probably move to a separate library.

/**
 * A block of PHPDoc documentation.
 * @author Paul Annesley
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pda_Docblock
{

	const DOCBLOCK_OPEN  = "/**\n";
	const DOCBLOCK_BODY  = ' * ';
	const DOCBLOCK_CLOSE = " */\n";
	const DOCBLOCK_NEWLINE = "\n";
	const DOCBLOCK_TAGSIGIL = '@';

	private $_shortDescription;
	private $_longDescription;
	private $_tags = array();
	private $_indent = '';

	/**
	 * The short description, up to three lines, terminated by a period.
	 * @param string $shortDescription
	 */
	public function setShortDescription($shortDescription)
	{
		$this->_shortDescription = $shortDescription;
		return $this;
	}

	/**
	 * The long description, up to three lines, terminated by a period.
	 * @param string $longDescription
	 */
	public function setLongDescription($longDescription)
	{
		$this->_longDescription = $longDescription;
		return $this;
	}

	/**
	 * @param string $name The name of the tag
	 * @param string $value The value of the tag
	 */
	public function addTag($name, $value = '')
	{
		$this->_tags []= new Pda_Docblock_Tag($name, $value);
		return $this;
	}

	/**
	 * The indentation to apply when serializing, e.g. "\t\t"
	 * @param string $indent
	 */
	public function setIndent($indent)
	{
		$this->_indent = $indent;
		return $this;
	}

	/**
	 * @return string
	 */
	public function serialize()
	{
		$i = $this->_indent;
		$output = $i . self::DOCBLOCK_OPEN;

		if (isset($this->_shortDescription))
		{
			// TODO: handle multi-line short descriptions
			$output .= $i .
				self::DOCBLOCK_BODY .
				$this->_shortDescription .
				self::DOCBLOCK_NEWLINE;
		}

		if (isset($this->_longDescription))
		{
			if (isset($this->_shortDescription))
			{
				// blank line between long & short descriptions
				$output .= $i . self::DOCBLOCK_BODY . self::DOCBLOCK_NEWLINE;
			}

			// TODO: handle wrapping long lines to correct length.
			$output .= $i .
				self::DOCBLOCK_BODY .
				preg_replace(
					'#\n#',
					self::DOCBLOCK_NEWLINE . $i . self::DOCBLOCK_BODY,
					$this->_longDescription
				) .
				self::DOCBLOCK_NEWLINE;
		}

		foreach ($this->_tags as $tag)
		{
			// TODO: handle multi-line tag values
			$output .= $i .
				self::DOCBLOCK_BODY .
				self::DOCBLOCK_TAGSIGIL .
				$tag->getName() .
				' ' .
				$tag->getValue()  .
				self::DOCBLOCK_NEWLINE;
		}

		$output .= $i . self::DOCBLOCK_CLOSE;

		return $output;
	}

	/**
	 * Alias for self::serialize()
	 */
	public function __toString()
	{
		return $this->serialize();
	}

}

/**
 * A tag in a block of PHPDoc documentation.
 * @author Paul Annesley
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pda_Docblock_Tag
{
	private $_name;
	private $_value;

	/**
	 * @param string $name The name of the tag
	 * @param string $value The value of the tag
	 */
	public function __construct($name, $value = '')
	{
		$this->_name = $name;
		$this->_value = $value;
	}

	/**
	 * The name of the tag
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * The value of the tag
	 * @return string
	 */
	public function getValue()
	{
		return $this->_value;
	}

}

