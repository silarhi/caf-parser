---
title: Parsed objects
description: PaymentSlip and PaymentSlipLine, the objects parse() returns, and their getters.
sidebar:
    order: 2
---

Both classes are `final`, in the `Silarhi\Caf\Model` namespace.

## PaymentSlip

The slip: its metadata and its lines. Metadata getters return `null` when the information is missing from the file.

| Getter                  | Type                 | Notes                                                                                          |
| ----------------------- | -------------------- | ---------------------------------------------------------------------------------------------- |
| `getProcessingDate()`   | `?DateTimeInterface` | `DATE DE TRAITEMENT`, full date (`d m Y`) at midnight                                          |
| `getPaymentDate()`      | `?DateTimeInterface` | Date of the `BORDEREAU DE PAIEMENT`, full date (`d m Y`) at midnight                           |
| `getCafName()`          | `?string`            | e.g. `CAISSE D'ALLOCATIONS FAMILIALES DE HAUTE GARONNE`                                        |
| `getCafAddress()`       | `?string`            | Street and city, e.g. `1 RUE DE L EXEMPLE, 99999 DOCVILLE CEDEX`; `null` unless both are found |
| `getRecipientName()`    | `?string`            | The landlord receiving the payment, e.g. `SCI EXEMPLE`                                         |
| `getRecipientAddress()` | `?string`            | Street and city, e.g. `10 RUE DES ESSAIS, 99999 DOCVILLE`                                      |
| `getReference()`        | `?string`            | `NM REFERENCE` of the slip, e.g. `0000000 0001`                                                |
| `getBic()`              | `?string`            | BIC of the recipient's bank account                                                            |
| `getIban()`             | `?string`            | IBAN without spaces, e.g. `FR7600000000000000000000000`                                        |
| `getTotalAmount()`      | `?float`             | The `TOTAL` row of the table                                                                   |
| `getLines()`            | `PaymentSlipLine[]`  | The rows of the table, in the order of the file                                                |

## PaymentSlipLine

One row of the table. Every getter returns a value on a parsed line.

| Getter                      | Type                | Notes                                                               |
| --------------------------- | ------------------- | ------------------------------------------------------------------- |
| `getReference()`            | `?string`           | Housing or loan reference; an empty string when the column is blank |
| `getBeneficiaryReference()` | `string`            | Beneficiary number, e.g. `1234567 A`                                |
| `getBeneficiaryName()`      | `string`            | e.g. `MME EXEMPLE ALICE`                                            |
| `getStartDate()`            | `DateTimeInterface` | First month of the period: the 1st of the month at midnight         |
| `getEndDate()`              | `DateTimeInterface` | Last month of the period: the 1st of the month at midnight          |
| `getGrossAmount()`          | `float`             | `MONTANT BRUT`                                                      |
| `getDeduction()`            | `float`             | `RETENUE`                                                           |
| `getNetAmount()`            | `float`             | `MONTANT NET`                                                       |

The dates are `DateTimeImmutable` instances, one instance per line and per date: changing one never changes another.

## Setters

The parser fills both objects through setters, which you can use to build them yourself, in tests for example. Each
getter above has a setter taking the same type (`setCafName(?string)`, `setStartDate(DateTimeInterface)`…) and
returning the object, so calls chain:

```php
use Silarhi\Caf\Model\PaymentSlip;
use Silarhi\Caf\Model\PaymentSlipLine;

$line = (new PaymentSlipLine())
    ->setReference('')
    ->setBeneficiaryReference('1234567 A')
    ->setBeneficiaryName('MME EXEMPLE ALICE')
    ->setStartDate(new DateTimeImmutable('2026-01-01'))
    ->setEndDate(new DateTimeImmutable('2026-01-01'))
    ->setGrossAmount(312.0)
    ->setDeduction(0.0)
    ->setNetAmount(312.0);

$paymentSlip = (new PaymentSlip())
    ->setTotalAmount(312.0)
    ->addLine($line);
```

`PaymentSlip::addLine()` appends a line; `setLines()` replaces them all. The properties of a new `PaymentSlipLine` have
no default value, except the reference (`null`): calling a getter before its setter throws an `Error`.
