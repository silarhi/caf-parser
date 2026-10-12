<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/v/silarhi/caf-parser?style=for-the-badge&label=stable&color=0d6efd&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/v/silarhi/caf-parser?style=for-the-badge&label=stable&color=0d6efd"
            alt="Latest Stable Version">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/dt/silarhi/caf-parser?style=for-the-badge&color=198754&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/dt/silarhi/caf-parser?style=for-the-badge&color=198754" alt="Total Downloads">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/l/silarhi/caf-parser?style=for-the-badge&color=6f42c1&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/l/silarhi/caf-parser?style=for-the-badge&color=6f42c1" alt="License">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/php-v/silarhi/caf-parser?style=for-the-badge&color=777bb4&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/php-v/silarhi/caf-parser?style=for-the-badge&color=777bb4" alt="PHP Version">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/github/actions/workflow/status/silarhi/caf-parser/continuous-integration.yml?style=for-the-badge&label=CI&color=20c997&labelColor=1a1a2e">
        <img src="https://img.shields.io/github/actions/workflow/status/silarhi/caf-parser/continuous-integration.yml?style=for-the-badge&label=CI&color=20c997"
            alt="CI Status">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fsilarhi%2Fcaf-parser%2Fbadges%2Fcoverage.json&style=for-the-badge&labelColor=1a1a2e">
        <img src="https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fsilarhi%2Fcaf-parser%2Fbadges%2Fcoverage.json&style=for-the-badge" alt="Coverage">
    </picture>
</p>

<h1 align="center">CAF Parser</h1>

<p align="center">
    <strong>A PHP parser for CAF LA44ZZ payment slips.</strong><br>
    Turn the plain-text "bordereau de paiement" sent by the Caisse d'Allocations Familiales into typed PHP objects.
</p>

<p align="center">
    📖 <a href="https://caf-parser.silarhi.dev"><strong>Documentation</strong></a>
</p>

---

## Features

- **LA44ZZ payment slips**: every payment line of the table (beneficiary, period, gross, deduction and net amounts)
- **Slip metadata**: processing and payment dates, CAF name and address, recipient name and address, reference, BIC, IBAN and total
- **Typed results**: dates as `DateTimeInterface`, amounts as `float`, French and English formats such as `1 298,50` or `1,298.50`
- **Strict validation**: malformed rows, malformed amounts and invalid or out-of-range dates throw a `ParseException`
- **No dependencies**: plain PHP, Windows (`\r\n`) and Unix line endings

## Requirements

- PHP 8.2+

## Installation

```bash
composer require silarhi/caf-parser
```

## Quick start

```php
use Silarhi\Caf\Parser\PaymentSlipParser;

$paymentSlip = (new PaymentSlipParser())->parse(file_get_contents('/path/to/LA44ZZ.txt'));

$paymentSlip->getPaymentDate(); // DateTimeInterface
$paymentSlip->getTotalAmount(); // e.g. 600.0

foreach ($paymentSlip->getLines() as $line) {
    $line->getBeneficiaryName(); // e.g. "MME EXEMPLE ALICE"
    $line->getNetAmount();       // e.g. 312.0
}
```

`parse()` throws a `ParseException` when the content is not a LA44ZZ payment slip, or a row, an amount or a date is invalid.

## Documentation

Everything else is on **[caf-parser.silarhi.dev](https://caf-parser.silarhi.dev)**:

- [Installation](https://caf-parser.silarhi.dev/getting-started/installation/) and [quick start](https://caf-parser.silarhi.dev/getting-started/quick-start/)
- [LA44ZZ files](https://caf-parser.silarhi.dev/guides/la44zz-files/): what a slip holds and which parts are read
- [Parsing](https://caf-parser.silarhi.dev/guides/parsing/): the table, dates, amounts and metadata
- [Error handling](https://caf-parser.silarhi.dev/guides/error-handling/): when `parse()` throws, and its messages
- Reference: [`PaymentSlipParser`](https://caf-parser.silarhi.dev/reference/parser/), [parsed objects](https://caf-parser.silarhi.dev/reference/models/) (`PaymentSlip`, `PaymentSlipLine`), [exceptions](https://caf-parser.silarhi.dev/reference/exceptions/)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Release notes are in [CHANGELOG.md](CHANGELOG.md).

## License

Released under the [MIT License](LICENSE).

---

<p align="center">
    Built with care by <a href="https://github.com/silarhi">SILARHI</a>.<br>
    If CAF Parser saves you time, consider giving it a star on GitHub.
</p>
