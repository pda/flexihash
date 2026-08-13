# Flexihash

[![CI](https://github.com/pda/flexihash/actions/workflows/ci.yml/badge.svg)](https://github.com/pda/flexihash/actions/workflows/ci.yml)

Flexihash is a small PHP library which implements [consistent hashing](https://en.wikipedia.org/wiki/Consistent_hashing), which is most useful in distributed caching.

## Requirements

Flexihash 4.x requires PHP 8.1 or later, and has no other dependencies.
Older releases remain available for older PHP versions:

| Flexihash | PHP        |
|-----------|------------|
| 4.x       | >= 8.1     |
| 3.x       | >= 7.2     |
| 2.x       | >= 5.4     |

## Installation

[Composer](https://getcomposer.org/) is the recommended installation technique. You can find flexihash on [Packagist](https://packagist.org/packages/flexihash/flexihash) so installation is as easy as

```
composer require flexihash/flexihash
```

or in your `composer.json`

```json
{
    "require": {
        "flexihash/flexihash": "^4.0"
    }
}
```

## Usage

```php
use Flexihash\Flexihash;

$hash = new Flexihash();

// bulk add
$hash->addTargets(['cache-1', 'cache-2', 'cache-3']);

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
```

The hash algorithm (CRC32 by default) and the number of replicas
(64 by default) can be configured on construction:

```php
use Flexihash\Flexihash;
use Flexihash\Hasher\Md5Hasher;

$hash = new Flexihash(new Md5Hasher(), 128);
```

Custom hash algorithms can be used by implementing
`Flexihash\Hasher\HasherInterface`.

## Development

```
composer install
```

| Command              | Description                                        |
|----------------------|----------------------------------------------------|
| `composer test`      | Run the unit test suite (PHPUnit)                  |
| `composer cs`        | Check coding standards (PSR-12)                    |
| `composer stan`      | Run static analysis (PHPStan)                      |
| `composer rector`    | Check for automated refactorings (Rector, dry-run) |
| `composer check`     | Run all of the above                               |
| `composer benchmark` | Run the benchmark suite                            |

## Further Reading

  * https://www.spiteful.com/2008/03/17/programmers-toolbox-part-3-consistent-hashing/
  * https://web.archive.org/web/20120605030524/http://weblogs.java.net/blog/tomwhite/archive/2007/11/consistent_hash.html
