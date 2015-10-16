#Flexihash

Flexihash is a small PHP library which implements [http://en.wikipedia.org/wiki/Consistent_hashing consistent hashing], which is most useful in distributed caching.  It requires PHP5 and uses [http://simpletest.org/ SimpleTest] for unit testing.

This is a fork from PDA's [flexihash](https://github.com/pda/flexihash) created to add composer support and meet PSR standards.

##Usage Example

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


##Roadmap
- [ ] v1 Initial packagist release
  - [ ] Composer support
  - [ ] PSR2
- [ ] v2 API breaking refactor
  - [ ] Migrate tests to PHPUnit
  - [ ] Introduce namespacing
  - [ ] PSR4 autoloading
  - [ ] Automoated testing

##Further Reading

  * http://www.spiteful.com/2008/03/17/programmers-toolbox-part-3-consistent-hashing/
  * http://weblogs.java.net/blog/tomwhite/archive/2007/11/consistent_hash.html
