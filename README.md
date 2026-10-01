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

---

## Features

- **LA44ZZ payment slips** — parses every payment line of the table (beneficiary, period, gross, deduction and net amounts)
- **Slip metadata** — processing and payment dates, CAF name and address, recipient name and address, reference, BIC, IBAN and total
- **Typed results** — dates as `DateTimeInterface`, amounts as `float`
- **French amount formats** — a comma, when present, is the decimal separator; spaces, no-break spaces and dots are
  thousands separators (`1 298,50`, `1.298,50` and `1298.50` all give `1298.5`)
- **Strict validation** — malformed rows and invalid or out-of-range dates throw a `ParseException`
- **No dependencies** — plain PHP, works with Windows (`\r\n`) and Unix line endings

## Requirements

| Dependency | Version |
| ---------- | ------- |
| PHP        | 8.2+    |

## Installation

```bash
composer require silarhi/caf-parser
```

## Usage

### Parse a LA44ZZ file

```php
use Silarhi\Caf\Parser\PaymentSlipParser;

$parser = new PaymentSlipParser();
$paymentSlip = $parser->parse(file_get_contents('/path/to/LA44ZZ.txt'));

foreach ($paymentSlip->getLines() as $line) {
    $line->getBeneficiaryReference(); // e.g. "1111111 J"
    $line->getBeneficiaryName();      // e.g. "MME JESUS"
    $line->getStartDate();            // DateTimeInterface, 1st of the month at midnight
    $line->getEndDate();              // DateTimeInterface, 1st of the month at midnight
    $line->getNetAmount();            // e.g. 33.0
}
```

### What you get

`PaymentSlip` holds the slip metadata and its lines. Metadata getters return `null` when the information is missing from
the file:

| Getter                                        | Type                 | Notes                                                   |
| --------------------------------------------- | -------------------- | ------------------------------------------------------- |
| `getProcessingDate()`, `getPaymentDate()`     | `?DateTimeInterface` | Full dates (`d m Y`), at midnight                       |
| `getCafName()`, `getCafAddress()`             | `?string`            | e.g. `CAISSE D'ALLOCATIONS FAMILIALES DE HAUTE GARONNE` |
| `getRecipientName()`, `getRecipientAddress()` | `?string`            | The landlord receiving the payment                      |
| `getReference()`                              | `?string`            | `NM REFERENCE` of the slip                              |
| `getBic()`, `getIban()`                       | `?string`            | IBAN without spaces                                     |
| `getTotalAmount()`                            | `?float`             |                                                         |
| `getLines()`                                  | `PaymentSlipLine[]`  |                                                         |

Each `PaymentSlipLine` exposes `getReference()` (housing/loan reference, may be empty), `getBeneficiaryReference()`,
`getBeneficiaryName()`, `getStartDate()` and `getEndDate()` (the month of the period, as the 1st of the month at
midnight), and `getGrossAmount()`, `getDeduction()` and `getNetAmount()` as floats.

### Error handling

`parse()` throws a `ParseException` when the content has no LA44ZZ payment table, when a row does not have the expected
columns, or when a date (row period or header date) is invalid or out of range: `13 2021` or `31 02 2021` are rejected
instead of being rolled over. Row errors carry the row number, e.g. `CAF Row n°2 could not be parsed`.

```php
use Silarhi\Caf\Exceptions\ParseException;

try {
    $paymentSlip = $parser->parse($content);
} catch (ParseException $e) {
    // Not a LA44ZZ payment slip, malformed row or invalid date
}
```

## Testing & Quality

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Static analysis (level: max)
vendor/bin/phpstan analyse

# Code style check
vendor/bin/php-cs-fixer fix --dry-run --diff

# Code style fix
vendor/bin/php-cs-fixer fix

# Code modernization check
vendor/bin/rector process --dry-run
```

## Contributing

Contributions are welcome! Please make sure your changes pass all quality checks before submitting a pull request:

```bash
vendor/bin/phpunit && vendor/bin/phpstan analyse && vendor/bin/php-cs-fixer fix --dry-run --diff
```

## License

MIT License. See [LICENSE](LICENSE) for details.

---

<p align="center">
    Built with care by <a href="https://github.com/silarhi">SILARHI</a>.<br>
    If CAF Parser saves you time, consider giving it a star on GitHub.
</p>
