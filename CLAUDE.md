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

# Static analysis (level 9)
vendor/bin/phpstan analyse

# Code style
vendor/bin/php-cs-fixer fix

# Rector upgrades
vendor/bin/rector process
```

## Architecture

### Parser (`src/Parser/`)

- `PaymentSlipParser`: Parses LA44ZZ file content using regex patterns to extract table data, dates, amounts, and metadata (CAF name/address, recipient info, bank details, totals)

### Models (`src/Model/`)

- `PaymentSlip`: Container for parsed payment slip data (dates, recipient, CAF info, bank details, total, and collection of lines)
- `PaymentSlipLine`: Individual payment line (reference, beneficiary, date range, amounts: gross/deduction/net)

### Exceptions (`src/Exceptions/`)

- `ParseException`: Thrown when parsing fails (invalid format, unparseable rows, bad dates)

## Code Standards

- PHP 8.2+ required
- PHPStan level 9
- Symfony PHP-CS-Fixer ruleset with `@Symfony:risky`
- `declare(strict_types=1)` in all files
- Header comment required in all source files
