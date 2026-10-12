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

use DateTimeImmutable;
use DateTimeInterface;
use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Model\PaymentSlip;
use Silarhi\Caf\Model\PaymentSlipLine;

use function sprintf;
use function strlen;

/**
 * Parser for LA44ZZ file
 */
final class PaymentSlipParser
{
    // Table borders: a line of 121 dashes
    private const TABLE_BORDER = '-------------------------------------------------------------------------------------------------------------------------';

    // Characters matched by PCRE "\s" (without the "u" modifier)
    private const WHITESPACES = " \t\n\v\f\r";

    private const PROCESSING_DATE_REGEX = '/DATE DE TRAITEMENT\s*:\s*(\d{2}\s+\d{2}\s+\d{4})/';

    private const PAYMENT_DATE_REGEX = '/BORDEREAU DE PAIEMENT.*DU\s+(\d{2}\s+\d{2}\s+\d{4})/';

    private const CAF_NAME_REGEX = '/^\s*(CAISSE D\'ALLOCATIONS FAMILIALES)\s*.*\n\s*([A-ZÀ-Ü\s]+?)\s{2,}/m';

    private const CAF_STREET_REGEX = '/(\d+\s+RUE[^N]+)NUMERO DE PRODUIT/';

    private const CAF_CITY_REGEX = '/(\d{5}\s+\w[^T]+)TYPE DE TRAITEMENT/';

    private const RECIPIENT_NAME_REGEX = '/^\s{20,}([A-ZÀ-Ü][A-ZÀ-Ü0-9\s\-\.]+?)\s*$/m';

    private const RECIPIENT_ADDRESS_REGEX = '/NM REFERENCE.*?\s{2,}(\d+\s+[A-ZÀ-Ü\s\']+?)\s*\n\s{20,}(\d{5}\s+[A-ZÀ-Ü\s]+?)\s*$/m';

    private const REFERENCE_REGEX = '/NM REFERENCE\s*:\s*(\d+\s+\d+)/';

    private const BANK_REGEX = '/^\s*VB\s+([A-Z0-9]+)\s+(FR\d{2}[\s0-9]+)/m';

    private const TOTAL_REGEX = '/TOTAL\s*:\s*([\d\s,.]+)\s*:/';

    // Space thousands separators of amounts: whitespaces, including UTF-8 no-break and narrow no-break spaces
    private const AMOUNT_SPACES_REGEX = '/\s|\xC2\xA0|\xE2\x80\xAF/';

    public const DATE_FORMAT = 'm Y';

    public const FULL_DATE_FORMAT = 'd m Y';

    /** @var array<string, DateTimeImmutable> */
    private array $dateCache = [];

    /**
     * @throws ParseException
     */
    public function parse(string $content): PaymentSlip
    {
        $tableContent = $this->extractTableContent($content);
        if (null === $tableContent) {
            throw new ParseException('Input CAF LA44 could not be parsed');
        }

        // Normalize Carriage return before splitting
        $tableContent = str_replace("\r\n", "\n", $tableContent);
        $this->dateCache = [];

        $paymentSlip = new PaymentSlip();

        // Extract metadata
        $this->parseMetadata($content, $paymentSlip);

        $lines = explode("\n", $tableContent);
        foreach ($lines as $i => $line) {
            $values = explode(':', $line);
            if (10 !== count($values)) {
                throw new ParseException(sprintf('CAF Row n°%d could not be parsed', $i + 1));
            }

            $paymentSlipLine = new PaymentSlipLine();
            $paymentSlipLine
                ->setReference(trim($values[1]))
                ->setBeneficiaryReference(trim($values[2]))
                ->setBeneficiaryName(trim($values[3]));

            try {
                $paymentSlipLine->setStartDate($this->getDateValue(trim($values[4])));
                $paymentSlipLine->setEndDate($this->getDateValue(trim($values[5])));
            } catch (ParseException $e) {
                throw new ParseException(sprintf('CAF Row n°%d : %s', $i + 1, $e->getMessage()), $e->getCode(), $e);
            }

            $paymentSlipLine
                ->setGrossAmount($this->getAmountValue(trim($values[6])))
                ->setDeduction($this->getAmountValue(trim($values[7])))
                ->setNetAmount($this->getAmountValue(trim($values[8])));

            $paymentSlip->addLine($paymentSlipLine);
        }

        $this->dateCache = [];

        return $paymentSlip;
    }

    /**
     * Returns the content between the 2nd and the 3rd table borders, without its surrounding whitespaces,
     * provided that a 4th border follows. Same result as the former regex
     * '/-{121}[\S\s]*-{121}\s*?([\s|\S]*)\s*-{121}[\S\s]*-{121}/U', whose lazy capture used one backtracking step
     * per character and hit pcre.backtrack_limit on large files.
     */
    private function extractTableContent(string $content): ?string
    {
        $length = strlen(self::TABLE_BORDER);

        $headerStart = strpos($content, self::TABLE_BORDER);
        if (false === $headerStart) {
            return null;
        }

        $headerEnd = strpos($content, self::TABLE_BORDER, $headerStart + $length);
        if (false === $headerEnd) {
            return null;
        }

        $start = $headerEnd + $length;
        $start += strspn($content, self::WHITESPACES, $start);

        $end = strpos($content, self::TABLE_BORDER, $start);
        if (false === $end || false === strpos($content, self::TABLE_BORDER, $end + $length)) {
            return null;
        }

        while ($end > $start && str_contains(self::WHITESPACES, $content[$end - 1])) {
            --$end;
        }

        return substr($content, $start, $end - $start);
    }

    private function getAmountValue(string $input): float
    {
        $input = preg_replace(self::AMOUNT_SPACES_REGEX, '', $input) ?? $input;

        // French formatting ("1.298,00"): the comma is the decimal separator and dots are thousands separators.
        // Without a comma, a dot is the decimal separator ("1298.00").
        if (str_contains($input, ',')) {
            $input = str_replace(['.', ','], ['', '.'], $input);
        }

        return (float) $input;
    }

    private function getDateValue(string $input): DateTimeInterface
    {
        // Rows mostly share the same few months: parse each value once, return a copy so that lines do not share instances
        return clone ($this->dateCache[$input] ??= $this->createDateFromFormat(self::DATE_FORMAT, $input));
    }

    private function getFullDateValue(string $input): DateTimeInterface
    {
        $input = preg_replace('/\s+/', ' ', trim($input)) ?? $input;

        return $this->createDateFromFormat(self::FULL_DATE_FORMAT, $input);
    }

    private function createDateFromFormat(string $format, string $input): DateTimeImmutable
    {
        // "!" resets the fields missing from the format (day and time) instead of taking them from the current date,
        // so the result does not depend on the day the parser runs on (e.g. "m Y" is always the 1st of the month at midnight)
        $result = DateTimeImmutable::createFromFormat('!' . $format, $input);

        // Out of range values (e.g. "13 2021" or "31 02 2021") are parsed with a warning and silently rolled over
        $errors = DateTimeImmutable::getLastErrors();
        if (false === $result || (false !== $errors && $errors['warning_count'] > 0)) {
            throw new ParseException(sprintf('"%s" date value could not be parsed, expected format is "%s"', $input, $format));
        }

        return $result;
    }

    private function parseMetadata(string $content, PaymentSlip $paymentSlip): void
    {
        // Processing date
        if (1 === preg_match(self::PROCESSING_DATE_REGEX, $content, $matches)) {
            $paymentSlip->setProcessingDate($this->getFullDateValue($matches[1]));
        }

        // Payment date
        if (1 === preg_match(self::PAYMENT_DATE_REGEX, $content, $matches)) {
            $paymentSlip->setPaymentDate($this->getFullDateValue($matches[1]));
        }

        // CAF name
        if (1 === preg_match(self::CAF_NAME_REGEX, $content, $matches)) {
            $cafName = trim($matches[1] . ' ' . trim($matches[2]));
            $paymentSlip->setCafName($cafName);
        }

        // CAF address
        $cafStreet = null;
        $cafCity = null;
        if (1 === preg_match(self::CAF_STREET_REGEX, $content, $matches)) {
            $cafStreet = trim($matches[1]);
        }
        if (1 === preg_match(self::CAF_CITY_REGEX, $content, $matches)) {
            $cafCity = trim($matches[1]);
        }
        if (null !== $cafStreet && null !== $cafCity) {
            $paymentSlip->setCafAddress($cafStreet . ', ' . $cafCity);
        }

        // Recipient name
        if (1 === preg_match(self::RECIPIENT_NAME_REGEX, $content, $matches)) {
            $paymentSlip->setRecipientName(trim($matches[1]));
        }

        // Recipient address
        if (1 === preg_match(self::RECIPIENT_ADDRESS_REGEX, $content, $matches)) {
            $recipientAddress = trim($matches[1]) . ', ' . trim($matches[2]);
            $paymentSlip->setRecipientAddress($recipientAddress);
        }

        // Reference
        if (1 === preg_match(self::REFERENCE_REGEX, $content, $matches)) {
            $paymentSlip->setReference(trim($matches[1]));
        }

        // Bank references (BIC and IBAN)
        if (1 === preg_match(self::BANK_REGEX, $content, $matches)) {
            $paymentSlip->setBic(trim($matches[1]));
            $iban = preg_replace('/\s+/', '', $matches[2]);
            $paymentSlip->setIban($iban);
        }

        // Total amount
        if (1 === preg_match(self::TOTAL_REGEX, $content, $matches)) {
            $paymentSlip->setTotalAmount($this->getAmountValue($matches[1]));
        }
    }
}
