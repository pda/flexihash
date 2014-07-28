Flexihash
=========

Flexihash is a small PHP library which implements [http://en.wikipedia.org/wiki/Consistent_hashing consistent hashing], which is most useful in distributed caching.  It requires PHP5 and uses [http://simpletest.org/ SimpleTest] for unit testing.

Usage Example
-------------

<pre>
&lt;?php

$hash = new Flexihash();

// bulk add
$hash->addTargets(array('cache-1', 'cache-2', 'cache-3'));

// simple lookup
$hash->lookup('object-a'); // "cache-1"
$hash->lookup('object-b'); // "cache-2"

// add and remove
$hash
  ->addTarget('cache-4')
  ->removeTarget('cache-1');

// lookup with next-best fallback (for redundant writes)
$hash->lookupList('object', 2); // ["cache-2", "cache-4"]

// remove cache-2, expect object to hash to cache-4
$hash->removeTarget('cache-2');
$hash->lookup('object'); // "cache-4"
</pre>

Installation
------------

If you're using composer, try creating a composor.json like :

<pre>
{ 
    "repositories" : [
        { 
            "type" : "vcs",
            "url": "https://github.com/pda/flexihash.git"
        }
    ],
    "require" : {
            "pda/flexihash" : "dev-master"
    }
}
</pre>

 * php composer.phar update
 * add require_once('vendor/autoload.php') to your code....



Further Reading
---------------

  * http://www.spiteful.com/2008/03/17/programmers-toolbox-part-3-consistent-hashing/
  * http://weblogs.java.net/blog/tomwhite/archive/2007/11/consistent_hash.html
