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

namespace Silarhi\Caf\Parser;

use function count;
use DateTime;
use DateTimeInterface;
use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Model\PaymentSlip;
use Silarhi\Caf\Model\PaymentSlipLine;

/**
 * Parser for LA44ZZ file
 */
class PaymentSlipParser
{
    private const TABLE_CONTENT_REGEX = '/-{121}[\S\s]*-{121}\s*?([\s|\S]*)\s*-{121}[\S\s]*-{121}/U';

    public const DATE_FORMAT = 'm Y';

    /**
     * @throws ParseException
     */
    public function parse(string $content): PaymentSlip
    {
        if (!preg_match_all(self::TABLE_CONTENT_REGEX, $content, $matches, \PREG_SET_ORDER)) {
            throw new ParseException('Input CAF LA44 could not be parsed');
        }

        //Normalize Carriage return before splitting
        $tableContent = str_replace("\r\n", "\n", $matches[0][1]);

        $paymentSlip = new PaymentSlip();
        $lines = explode("\n", $tableContent);
        foreach ($lines as $i => $line) {
            $values = explode(':', $line);
            if (10 !== count($values)) {
                throw new ParseException(sprintf('CAF Row n°%d could not be parsed', $i + 1));
            }

            $values = array_map('trim', $values);

            $paymentSlipLine = new PaymentSlipLine();
            $paymentSlipLine
                ->setReference($values[1])
                ->setBeneficiaryReference($values[2])
                ->setBeneficiaryName($values[3]);

            try {
                $paymentSlipLine->setStartDate($this->getDateValue($values[4]));
                $paymentSlipLine->setEndDate($this->getDateValue($values[5]));
            } catch (ParseException $e) {
                throw new ParseException(sprintf('CAF Row n°%d : %s', $i + 1, $e->getMessage()), $e->getCode(), $e);
            }

            $paymentSlipLine
                ->setGrossAmount($this->getAmountValue($values[6]))
                ->setDeduction($this->getAmountValue($values[7]))
                ->setNetAmount($this->getAmountValue($values[8]));

            $paymentSlip->addLine($paymentSlipLine);
        }

        return $paymentSlip;
    }

    private function getAmountValue(string $input): float
    {
        $input = str_replace(',', '.', $input);

        return (float) $input;
    }

    private function getDateValue(string $input): DateTimeInterface
    {
        $result = DateTime::createFromFormat(self::DATE_FORMAT, $input);
        if (!$result) {
            throw new ParseException(sprintf('"%s" date value could not be parsed, expected format is "%s"', $input, self::DATE_FORMAT));
        }

        $result->setDate((int) $result->format('Y'), (int) $result->format('m'), 1);
        $result->setTime(0, 0);

        return $result;
    }
}
