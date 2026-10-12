<?php

declare(strict_types=1);

/*
 * This file is part of the CAF Parser package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Model\PaymentSlip;
use Silarhi\Caf\Parser\PaymentSlipParser;

// Parses the example files of the documentation with the parser itself, so the output the pages show is what the
// parser really returns.
//
//     php docs/scripts/render-examples.php            # every example
//     php docs/scripts/render-examples.php --check    # fails when a committed output is missing or out of date (CI)
//
// Reads docs/examples/<example>.txt, writes docs/src/data/examples/<example>.txt.

require \dirname(__DIR__, 2) . '/vendor/autoload.php';

$examples = \dirname(__DIR__) . '/examples';
$output = \dirname(__DIR__) . '/src/data/examples';
$check = \in_array('--check', \array_slice($argv ?? [], 1), true);

/**
 * One getter call per line, its value as a comment, aligned.
 *
 * @param array<string, mixed> $calls
 */
function renderCalls(array $calls): string
{
    $width = max(0, ...array_map(strlen(...), array_keys($calls)));
    $lines = [];
    foreach ($calls as $call => $value) {
        $lines[] = str_pad($call, $width) . ' // ' . renderValue($value);
    }

    return implode("\n", $lines);
}

function renderValue(mixed $value): string
{
    return match (true) {
        $value instanceof DateTimeInterface => $value::class . " '" . $value->format('Y-m-d H:i:s') . "'",
        null === $value => 'null',
        // Double quotes, unless the string holds some (exception messages)
        \is_string($value) => str_contains($value, '"') ? "'{$value}'" : "\"{$value}\"",
        default => var_export($value, true),
    };
}

function renderPaymentSlip(PaymentSlip $paymentSlip): string
{
    $blocks = [renderCalls([
        '$paymentSlip->getProcessingDate();' => $paymentSlip->getProcessingDate(),
        '$paymentSlip->getPaymentDate();' => $paymentSlip->getPaymentDate(),
        '$paymentSlip->getCafName();' => $paymentSlip->getCafName(),
        '$paymentSlip->getCafAddress();' => $paymentSlip->getCafAddress(),
        '$paymentSlip->getRecipientName();' => $paymentSlip->getRecipientName(),
        '$paymentSlip->getRecipientAddress();' => $paymentSlip->getRecipientAddress(),
        '$paymentSlip->getReference();' => $paymentSlip->getReference(),
        '$paymentSlip->getBic();' => $paymentSlip->getBic(),
        '$paymentSlip->getIban();' => $paymentSlip->getIban(),
        '$paymentSlip->getTotalAmount();' => $paymentSlip->getTotalAmount(),
        'count($paymentSlip->getLines());' => \count($paymentSlip->getLines()),
    ])];

    foreach ($paymentSlip->getLines() as $i => $line) {
        $blocks[] = "\$line = \$paymentSlip->getLines()[{$i}];\n" . renderCalls([
            '$line->getReference();' => $line->getReference(),
            '$line->getBeneficiaryReference();' => $line->getBeneficiaryReference(),
            '$line->getBeneficiaryName();' => $line->getBeneficiaryName(),
            '$line->getStartDate();' => $line->getStartDate(),
            '$line->getEndDate();' => $line->getEndDate(),
            '$line->getGrossAmount();' => $line->getGrossAmount(),
            '$line->getDeduction();' => $line->getDeduction(),
            '$line->getNetAmount();' => $line->getNetAmount(),
        ]);
    }

    return implode("\n\n", $blocks);
}

function renderException(ParseException $exception): string
{
    $lines = ['// ' . $exception::class, '$e->getMessage(); // ' . renderValue($exception->getMessage())];
    $previous = $exception->getPrevious();
    if (null !== $previous) {
        $lines[] = '$e->getPrevious()->getMessage(); // ' . renderValue($previous->getMessage());
    }

    return implode("\n", $lines);
}

$parser = new PaymentSlipParser();

/** @var array<string, string> $renders example file name => parser output */
$renders = [];
$files = glob("{$examples}/*.txt");
foreach (false === $files ? [] : $files as $file) {
    try {
        $render = renderPaymentSlip($parser->parse((string) file_get_contents($file)));
    } catch (ParseException $e) {
        $render = renderException($e);
    }
    $renders[basename($file)] = $render . "\n";
}
ksort($renders);

// Outputs without an example any more
$outputs = glob("{$output}/*.txt");
$stale = array_diff(array_map(basename(...), false === $outputs ? [] : $outputs), array_keys($renders));

if ($check) {
    $errors = [];
    foreach ($renders as $name => $render) {
        $committed = @file_get_contents("{$output}/{$name}");
        if (false === $committed) {
            $errors[] = "missing   {$name}";
        } elseif ($committed !== $render) {
            $errors[] = "outdated  {$name}";
        }
    }
    foreach ($stale as $name) {
        $errors[] = "stale     {$name}";
    }

    if ([] !== $errors) {
        fwrite(\STDERR, implode("\n", $errors) . "\n\nThe documentation examples do not match the parser: run php docs/scripts/render-examples.php and commit docs/src/data/examples/.\n");
        exit(1);
    }

    echo \count($renders) . " examples up to date\n";
    exit(0);
}

@mkdir($output, 0o755, true);
foreach ($renders as $name => $render) {
    file_put_contents("{$output}/{$name}", $render);
}
foreach ($stale as $name) {
    unlink("{$output}/{$name}");
}

echo implode("\n", array_keys($renders)) . "\n";
