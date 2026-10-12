# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.2] - 2026-10-12

### Changed

- Parsing is 2.7 to 3.2 times faster, with identical output: the table is located with `strpos()` instead of a regex, each distinct row date is parsed once per `parse()` call (every line still gets its own instance), and only the fields that are read are trimmed.

### Fixed

- Files with more than about 5,000 rows (about 600 KB) threw `Input CAF LA44 could not be parsed`: the regex locating the table hit `pcre.backtrack_limit`. They parse again, whatever their size.

## [3.1.1] - 2026-10-01

### Fixed

- The start and end months of the lines depended on the day the parser ran: from the 29th to the 31st, short months overflowed (`02 2021` parsed on the 30th gave March 2021), and an invalid month such as `13 2021` rolled over to the next year. They are now always the 1st of the month at midnight, and an invalid month throws `ParseException`.
- Amounts with thousands separators were misread: `1 298,00` gave `1.0` and `1.298,00` gave `1.298`. Spaces, including no-break and narrow no-break spaces, are ignored, and with a decimal comma the dots are thousands separators. This applies to the line amounts and to `PaymentSlip::getTotalAmount()`; `1298.00` still gives `1298.0`.
- An invalid processing or payment date (`31 02 2021`) silently rolled over to another day. It now throws `ParseException`, like an invalid line date. A slip without these headers still gets `null`.

## [3.1.0] - 2025-12-09

### Added

- `PaymentSlip` exposes the header of the slip, each with its setter: `getProcessingDate()`, `getPaymentDate()`, `getCafName()`, `getCafAddress()`, `getRecipientName()`, `getRecipientAddress()`, `getReference()`, `getBic()`, `getIban()` and `getTotalAmount()`. The parser fills them when the header holds the information, and leaves them `null` otherwise.
- `PaymentSlipParser::FULL_DATE_FORMAT` (`d m Y`), the format of the processing and payment dates.

### Changed

- The start and end dates of the lines are `DateTimeImmutable` instead of `DateTime` (the getters still declare `DateTimeInterface`): code that changed them in place with `modify()` or `setDate()` must use the returned instance instead.

## [3.0.0] - 2025-01-06

### Changed

- **BC break:** PHP 8.2 or later is required.
- **BC break:** `PaymentSlipParser`, `PaymentSlip`, `PaymentSlipLine` and `ParseException` are `final`: wrap them instead of extending them.

## [2.0.0] - 2024-02-09

### Changed

- **BC break:** PHP 8.0 or later is required (PHP 7.2 to 7.4 are no longer supported).

## [1.0.1] - 2022-01-07

No functional changes: the test fixtures were anonymised.

## [1.0.0] - 2022-01-06

### Added

- `PaymentSlipParser::parse()` reads the content of a CAF LA44ZZ payment slip and returns a `PaymentSlip` whose `PaymentSlipLine`s carry the reference, the beneficiary reference and name, the start and end months, and the gross, deduction and net amounts. It throws `ParseException` when the table, a row or a date cannot be parsed. Requires PHP 7.2 or later.

[3.1.2]: https://github.com/silarhi/caf-parser/compare/3.1.1...3.1.2
[3.1.1]: https://github.com/silarhi/caf-parser/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/silarhi/caf-parser/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/silarhi/caf-parser/compare/2.0.0...3.0.0
[2.0.0]: https://github.com/silarhi/caf-parser/compare/1.0.1...2.0.0
[1.0.1]: https://github.com/silarhi/caf-parser/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/silarhi/caf-parser/releases/tag/1.0.0
