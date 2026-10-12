---
title: Exceptions
description: ParseException, the only exception of the library.
sidebar:
    order: 3
---

## ParseException

`Silarhi\Caf\Exceptions\ParseException` extends PHP's `Exception` and is `final`. `PaymentSlipParser::parse()` throws
it when the content cannot be parsed:

| Message                                                                                | Cause                                                               | `getPrevious()`             |
| -------------------------------------------------------------------------------------- | ------------------------------------------------------------------- | --------------------------- |
| `Input CAF LA44 could not be parsed`                                                   | Fewer than four 121-dash borders: no payment table                  | `null`                      |
| `CAF Row n°<row> could not be parsed`                                                  | The row does not split into ten parts on `:`, or the table is empty | `null`                      |
| `CAF Row n°<row> : "<value>" date value could not be parsed, expected format is "m Y"` | Invalid or out-of-range start or end month in the row               | The date `ParseException`   |
| `"<value>" date value could not be parsed, expected format is "d m Y"`                 | Invalid or out-of-range processing or payment date                  | `null`                      |
| `CAF Row n°<row> : "<value>" amount value could not be parsed`                         | Malformed or empty gross, deduction or net amount in the row        | The amount `ParseException` |
| `"<value>" amount value could not be parsed`                                           | Malformed total                                                     | `null`                      |

`<row>` counts from 1 at the first row of the table. See [error handling](/guides/error-handling/) for examples.
