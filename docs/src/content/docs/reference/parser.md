---
title: PaymentSlipParser
description: The parser class, its parse() method and its date format constants.
sidebar:
    order: 1
---

`Silarhi\Caf\Parser\PaymentSlipParser` is `final`. It has no constructor argument.

## parse()

```php
public function parse(string $content): PaymentSlip
```

Parses the content of a LA44ZZ file and returns a [`PaymentSlip`](/reference/models/#paymentslip) with one
[`PaymentSlipLine`](/reference/models/#paymentslipline) per row of the table, in the order of the file.

Throws a [`ParseException`](/reference/exceptions/) when the content has no payment table, a row or an amount is
malformed, or a date is invalid. See [parsing](/guides/parsing/) for the rules.

## Constants

| Constant           | Value   | Used for                                     |
| ------------------ | ------- | -------------------------------------------- |
| `DATE_FORMAT`      | `m Y`   | The start and end months of the rows         |
| `FULL_DATE_FORMAT` | `d m Y` | The processing and payment dates of the slip |

Both are formats of `DateTimeImmutable::createFromFormat()`, and appear in the date error messages.
