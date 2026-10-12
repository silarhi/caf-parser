# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`silarhi/caf-parser` is a PHP library for parsing CAF (Caisse d'Allocations Familiales) payment slip files, specifically the LA44ZZ format. Published on Packagist under MIT license.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit
vendor/bin/phpunit tests/Parser/PaymentSlipParserTest.php  # Single file
vendor/bin/phpunit --filter testParsing                     # Single method

# Static analysis (level max)
vendor/bin/phpstan analyse

# Code style
vendor/bin/php-cs-fixer fix

# Rector upgrades
vendor/bin/rector process
```

## Architecture

### Parser (`src/Parser/`)

- `PaymentSlipParser`: Parses LA44ZZ file content: locates the table between its 121-dash borders with `strpos` (a regex hit `pcre.backtrack_limit` on large files), then extracts rows, dates (cached per `parse()` call, cloned per line), amounts, and metadata (CAF name/address, recipient info, bank details, totals)

### Models (`src/Model/`)

- `PaymentSlip`: Container for parsed payment slip data (dates, recipient, CAF info, bank details, total, and collection of lines)
- `PaymentSlipLine`: Individual payment line (reference, beneficiary, date range, amounts: gross/deduction/net)

### Exceptions (`src/Exceptions/`)

- `ParseException`: Thrown when parsing fails (invalid format, unparseable rows, bad dates)

## Code Standards

- PHP 8.2+ required
- PHPStan level max
- Symfony PHP-CS-Fixer ruleset with `@Symfony:risky`
- `declare(strict_types=1)` in all files
- Header comment required in all source files

## Changelog & Releases

- **`CHANGELOG.md`** follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Every user-facing change gets an entry in the top section, titled with the next release, never a bare `[Unreleased]`: the next minor (e.g. `[3.2.0] - Unreleased`), or the next major for a BC break. Prefix breaking changes with **BC break:** and say what users must change. Skip dependency bumps, CI and tooling changes.
- **Releases**: date the section and point its compare link at the tag (`compare/<previous>...<tag>`, no `v` prefix), then tag `X.Y.Z` on `origin/main` once CI is green, with a GitHub release whose notes follow that section. Packagist updates from the GitHub hook.
