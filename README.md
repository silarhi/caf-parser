# caf-parser

![Build Status](https://github.com/silarhi/caf-parser/actions/workflows/continuous-integration.yml/badge.svg)
[![Coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fsilarhi%2Fcaf-parser%2Fbadges%2Fcoverage.json)](https://github.com/silarhi/caf-parser/actions/workflows/continuous-integration.yml)
[![Latest Stable Version](https://poser.pugx.org/silarhi/caf-parser/v/stable)](https://packagist.org/packages/silarhi/caf-parser)
[![Total Downloads](https://poser.pugx.org/silarhi/caf-parser/downloads)](https://packagist.org/packages/silarhi/caf-parser)
[![License](https://poser.pugx.org/silarhi/caf-parser/license)](https://packagist.org/packages/silarhi/caf-parser)

A PHP Parser for CAF file

Supports LA44ZZ file

## Installation

The preferred method of installation is via [Composer][]. Run the following command to install the package and add it as
a requirement to your project's
`composer.json`:

```bash
composer require silarhi/caf-parser
```

## How to use

### Parse LA44ZZ

```php
<?php

use Silarhi\Caf\Parser\PaymentSlipParser;

$parser = new PaymentSlipParser();

//Gets all statements day by day
$paymentSlip = $parser->parse('My Content');
foreach($paymentSlip->getLines() as $line) {
  assert($line instanceof \Silarhi\Caf\Model\PaymentSlipLine);
}
```

[composer]: http://getcomposer.org/
