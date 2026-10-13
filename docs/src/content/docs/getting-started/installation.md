---
title: Installation
description: Install the CAF Parser with Composer.
sidebar:
    order: 1
---

## Requirements

| Dependency | Version |
| ---------- | ------- |
| PHP        | 8.2+    |

The library has no other dependency.

## Installation

```bash
composer require silarhi/caf-parser
```

The classes live under the `Silarhi\Caf` namespace:

| Class                                   | Role                                                                                   |
| --------------------------------------- | -------------------------------------------------------------------------------------- |
| `Silarhi\Caf\Parser\PaymentSlipParser`  | Reads the content of a LA44ZZ file                                                     |
| `Silarhi\Caf\Model\PaymentSlip`         | The parsed slip: its metadata and its lines                                            |
| `Silarhi\Caf\Model\PaymentSlipLine`     | One payment line of the table                                                          |
| `Silarhi\Caf\Exceptions\ParseException` | Thrown when the content is not a LA44ZZ slip, or a row, an amount or a date is invalid |

Next: [quick start](/getting-started/quick-start/).
