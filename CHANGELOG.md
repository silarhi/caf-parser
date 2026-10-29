# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.3.0] - 2026-10-29

### Changed

- Amounts take exactly 2 decimals, as in LA44ZZ files, and at most 12 digits before them. A single comma or dot followed by 3 digits is ambiguous and throws `"<amount>" amount value could not be parsed` instead of being read as decimals: `1,298` and `1.298` gave `1.298`. 1 or 3 decimals (`12,5`, `1298,505`) and 13 digits or more throw too: 400 digits gave `INF`. Amounts without decimals (`272`) and with repeated thousands separators (`12.345.678`) are still read.
- A metadata pattern that PHP fails to match (e.g. on `pcre.backtrack_limit`) throws `Input CAF LA44 metadata could not be parsed: <error>` instead of leaving its field `null`.

### Fixed

- Crafted headers made `parse()` backtrack for seconds to minutes (15 s for a 400 KB file): the patterns reading the CAF name, the CAF street, the payment date, the recipient name and the recipient address now take a time linear in the size of the content. They read the same values from LA44ZZ files.
- `getIban()` appended the digits of the next line when it started with digits.

## [3.2.0] - 2026-10-13

### Added

- Amounts formatted the English way are read: with both separators, the last one is the decimal separator (`1,298.50` gives `1298.5`, as `1.298,50` does), and a separator repeated without decimals is a thousands separator (`12,345,678` and `12.345.678` give `12345678.0`). A single comma or dot is still the decimal separator: `1,298` and `1.298` give `1.298`.

### Changed

- A malformed amount throws `ParseException` instead of being silently truncated: `1,298.50` gave `1.2985` and `1.298.50` gave `1.298`. A row amount that is empty, has letters, two decimal separators or a thousands separator not grouping 3 digits throws `CAF Row n°<row> : "<amount>" amount value could not be parsed`; a malformed total throws `"<amount>" amount value could not be parsed`. The total row is read whatever its value: a total not starting with a digit gave a `null` `getTotalAmount()`, `-12,50` now gives `-12.5` and `ABC` or `12,50-` throw.
- A blank total (`TOTAL :          :`) gives a `null` `getTotalAmount()` instead of `0.0`, like a missing one.

### Fixed

- `getTotalAmount()` read the period of a row whose beneficiary name ends with `TOTAL` (`SCI TOTAL`) as the total (`01 2022` gave `12022.0`): only the row below the table is read now.
- `getTotalAmount()` returned `null` when the total used no-break or narrow no-break spaces as thousands separators (`1 298,00`), which 3.1.1 announced but only applied to the line amounts.
- `getCafAddress()` returned `null` for streets with an `N` after `RUE` (`12 RUE DU PONT`) or not of the form `<number> RUE` (`8 AVENUE DES CANUTS`, `PLACE DE LA GARE`), and for cities with a `T` after their first letter (`42000 SAINT ETIENNE`). The street and the city are now whatever precedes their label on their line, the city starting with its postal code.
- `getCafName()` returned `null` when the name had an apostrophe or a hyphen (`DE L'AIN`, `DE SEINE-SAINT-DENIS`).
- `getRecipientName()` returned `null` when the name had an apostrophe, an ampersand or lowercase letters (`SCI L'ETOILE`, `DUPONT & FILS`, `Mme Dupont`).
- `getRecipientAddress()` returned `null` when the street had no leading number or other digits (`LIEU-DIT LES PINS`, `3 RUE DU 11 NOVEMBRE`), or the city had a hyphen or digits (`42000 SAINT-ETIENNE CEDEX 2`).

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

[3.3.0]: https://github.com/silarhi/caf-parser/compare/3.2.0...3.3.0
[3.2.0]: https://github.com/silarhi/caf-parser/compare/3.1.2...3.2.0
[3.1.2]: https://github.com/silarhi/caf-parser/compare/3.1.1...3.1.2
[3.1.1]: https://github.com/silarhi/caf-parser/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/silarhi/caf-parser/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/silarhi/caf-parser/compare/2.0.0...3.0.0
[2.0.0]: https://github.com/silarhi/caf-parser/compare/1.0.1...2.0.0
[1.0.1]: https://github.com/silarhi/caf-parser/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/silarhi/caf-parser/releases/tag/1.0.0
