# Change Log
All notable changes to this project will be documented in this file
which adheres to the guidelines at http://keepachangelog.com/.

This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [4.0.0] - 2026-08-14
Hash values and position derivation are unchanged: upgrading does **not**
remap keys to different targets.

### Added
- GitHub Actions CI, testing PHP 8.1 through 8.5.
- Static analysis with PHPStan (level 9).
- Automated refactoring with Rector, with PHP sets derived from the
  supported PHP floor in composer.json.
- Full parameter, return, and property type declarations throughout.
- Composer scripts: `test`, `benchmark`, `cs`, `cs:fix`, `stan`, `check`.
- Regression tests for `lookupList()` with duplicate adjacent ring positions.

### Changed
- **BC break:** PHP 8.1 minimum (was 7.2).
- **BC break:** `HasherInterface::hash()` is now declared as
  `hash(string $string): int|float`. Custom hasher implementations must add
  a compatible return type declaration.
- **BC break:** `Flexihash` methods now declare parameter types
  (e.g. `addTarget(string $target, int|float $weight = 1)`). Callers in
  `strict_types` files passing non-string targets must cast them.
- **BC break:** `lookupList()` now also rejects negative counts
  (previously only zero was rejected).
- Coding standard upgraded from PSR-2 to PSR-12.
- PHPUnit upgraded from 8 to 10/11/12.

### Fixed
- `lookupList()` could return fewer targets than requested when adjacent
  positions on the ring belonged to the same target: duplicates are now
  skipped instead of being counted towards the requested number. This was
  a regression in the unreleased binary-search lookup (#24); released
  versions are not affected.
- Deprecation warning on PHP 8.4 due to the implicitly nullable
  constructor parameters (`Flexihash::__construct()`).
- `BenchmarkTest::median()` picked the wrong elements for even-sized data
  sets and could fail with a `TypeError` under `strict_types`.

### Removed
- Travis CI and Coveralls integration (replaced by GitHub Actions).
- The committed `composer.lock` (libraries should resolve dependencies fresh).

## [3.0.0] - 2020-08-07
### Changed
- PHP 7.2 minimum.
- PHPUnit 8.
- Enable strict typing mode for all PHP files.

## [2.0.2] - 2016-04-22
### Changed
- Pinned symfony component version to pass tests on 5.4.x.
- Updated coveralls config for new version.
- Tweaked README.md to recommend install version 2.
- Sorted phpcs errors.

## [2.0.1] - 2016-04-22
### Changed
- Make MD5 hasher return an integer to prevent incorrect remapping
due to PHP treating numeric string array keys as integers.

## [2.0.0] - 2015-10-08
### Added
- This CHANGELOG.md file.
- A ROADMAP.md file.
- PSR-4 autoloading.
- Introduce namespacing.
- Full PSR-2 support.

### Changed
- Reorganisation of files.
- Updated readme to reflect composer installation recommendation.

### Removed
- PHP<5.4 support

## [1.0.0] - 2015-10-16
### Added
- Setup automatic testing with Travis.
- Monitor code coverage with Coveralls.
- Get as close to PSR-2 as possible without changing class names.

### Changed
- Migrate tests to PHPUnit.

### Removed
- Legacy autoloader.

## [0.1.0] - 2012-04-04
Posterity release


[Unreleased]: https://github.com/pda/flexihash/compare/v4.0.0...master
[4.0.0]: https://github.com/pda/flexihash/compare/v3.0.0...v4.0.0
[3.0.0]: https://github.com/pda/flexihash/compare/v2.0.2...v3.0.0
[2.0.2]: https://github.com/pda/flexihash/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/pda/flexihash/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/pda/flexihash/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/pda/flexihash/compare/v0.1.0...v1.0.0
[0.1.0]: https://github.com/pda/flexihash/tree/v0.1.0
