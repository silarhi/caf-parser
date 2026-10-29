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

    // Metadata patterns stay on one line ("[ \t]" rather than "\s", which matches "\n") and never scan a run of spaces
    // twice (possessive quantifiers, lookbehinds), so that their cost stays linear whatever the content
    private const PROCESSING_DATE_REGEX = '/DATE DE TRAITEMENT\s*:\s*(\d{2}\s+\d{2}\s+\d{4})/';

    // The first "BORDEREAU DE PAIEMENT" of the line, then its last "DU <date>"
    private const PAYMENT_DATE_REGEX = '/^(?>[^\n]*?BORDEREAU DE PAIEMENT)[^\n]*DU\s+(\d{2}\s+\d{2}\s+\d{4})/m';

    // 2nd line of the CAF name, e.g. "DE HAUTE GARONNE", "DE L'AIN" or "DE SEINE-SAINT-DENIS": words separated by
    // single spaces, up to 2 spaces or the end of the line
    private const CAF_NAME_REGEX = '/^[ \t]*+(CAISSE D\'ALLOCATIONS FAMILIALES)[^\n]*+\n[ \t]*+([A-ZÀ-Ü\'\-]++(?: [A-ZÀ-Ü\'\-]++)*+)(?:[ \t]{2,}|[ \t]*$)/m';

    // The CAF street and city are what precedes their label on the same line
    private const CAF_STREET_REGEX = '/^[ \t]*+(\S.*?)(?<![ \t])[ \t]++NUMERO DE PRODUIT/m';

    private const CAF_CITY_REGEX = '/^[ \t]*+(\d{5}[ \t]++\S.*?)(?<![ \t])[ \t]++TYPE DE TRAITEMENT/m';

    // The whole line, trailing spaces included (they are trimmed)
    private const RECIPIENT_NAME_REGEX = '/^[ \t]{20,}+([A-Za-zÀ-Ü][A-Za-zÀ-Ü0-9 \t\-\.\'&]++)$/m';

    // The recipient street ends the reference line, the next line starts with the postal code (both are trimmed)
    private const RECIPIENT_ADDRESS_REGEX = '/NM REFERENCE\s*:\s*\d+\s+\d+[ \t]{2,}+(\S[^\n]*+)\n[ \t]{20,}+(\d{5}[ \t]++\S[^\n]*+)/';

    private const REFERENCE_REGEX = '/NM REFERENCE\s*:\s*(\d+\s+\d+)/';

    private const BANK_REGEX = '/^[ \t]*+VB\s+([A-Z0-9]+)\s+(FR\d{2}[ \t0-9]+)/m';

    // The row below the table, whatever its value: " :     TOTAL :  1298,00 :"
    private const TOTAL_REGEX = '/^[ \t]*:[ \t]*TOTAL[ \t]*:([^:\n]*):/m';

    // Space thousands separators of amounts: whitespaces, including UTF-8 no-break and narrow no-break spaces
    private const AMOUNT_SPACES_REGEX = '/\s|\xC2\xA0|\xE2\x80\xAF/';

    // Once spaces are removed, at most 12 digits before the decimals, which are 2 digits as in LA44ZZ files: either
    // digits with optionally a dot or comma as decimal separator ("272", "1298,50", "1298.50"), or digits grouped by 3
    // with dots or commas, then the other one as decimal separator ("1.298,50", "1,298.50") or at least 2 groups
    // ("12.345.678"). A single separator followed by 3 digits ("1,298" or "1.298") is ambiguous and rejected.
    private const AMOUNT_REGEX = '/^-?(?:\d{1,12}(?:[.,]\d{2})?|\d{1,3}(?<thousands>[.,])\d{3}(?:(?:\k<thousands>\d{3}){0,2}(?!\k<thousands>)[.,]\d{2}|(?:\k<thousands>\d{3}){1,2}))$/';

    public const DATE_FORMAT = 'm Y';

    public const FULL_DATE_FORMAT = 'd m Y';

    /** @var array<string, DateTimeImmutable> */
    private array $dateCache = [];

    /**
     * @throws ParseException
     */
    public function parse(string $content): PaymentSlip
    {
        // Normalize Carriage return before splitting the rows and matching the metadata, whose patterns stay on one line
        $content = str_replace("\r\n", "\n", $content);

        $tableContent = $this->extractTableContent($content);
        if (null === $tableContent) {
            throw new ParseException('Input CAF LA44 could not be parsed');
        }

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
                $paymentSlipLine
                    ->setStartDate($this->getDateValue(trim($values[4])))
                    ->setEndDate($this->getDateValue(trim($values[5])))
                    ->setGrossAmount($this->getAmountValue($values[6]))
                    ->setDeduction($this->getAmountValue($values[7]))
                    ->setNetAmount($this->getAmountValue($values[8]));
            } catch (ParseException $e) {
                throw new ParseException(sprintf('CAF Row n°%d : %s', $i + 1, $e->getMessage()), $e->getCode(), $e);
            }

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
        $amount = preg_replace(self::AMOUNT_SPACES_REGEX, '', $input) ?? $input;

        // Anything else ("", "1.298.50", "12,00-") would be silently truncated by the float cast
        if (1 !== preg_match(self::AMOUNT_REGEX, $amount, $matches)) {
            throw new ParseException(sprintf('"%s" amount value could not be parsed', trim($input)));
        }

        if ('' !== ($matches['thousands'] ?? '')) {
            $amount = str_replace($matches['thousands'], '', $amount);
        }

        $value = (float) str_replace(',', '.', $amount);

        // Unreachable with 12 digits at most, kept so that INF or NAN can never be returned as an amount
        if (!is_finite($value)) {
            throw new ParseException(sprintf('"%s" amount value could not be parsed', trim($input)));
        }

        return $value;
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

    /**
     * A failed match (e.g. pcre.backtrack_limit reached) throws instead of being taken for a missing field.
     *
     * @return array<int|string, string>|null
     *
     * @throws ParseException
     */
    private function matchMetadata(string $pattern, string $content): ?array
    {
        $result = preg_match($pattern, $content, $matches);
        if (false === $result) {
            throw new ParseException(sprintf('Input CAF LA44 metadata could not be parsed: %s', preg_last_error_msg()));
        }

        return 1 === $result ? $matches : null;
    }

    /**
     * @throws ParseException
     */
    private function parseMetadata(string $content, PaymentSlip $paymentSlip): void
    {
        // Processing date
        if (null !== ($matches = $this->matchMetadata(self::PROCESSING_DATE_REGEX, $content))) {
            $paymentSlip->setProcessingDate($this->getFullDateValue($matches[1]));
        }

        // Payment date
        if (null !== ($matches = $this->matchMetadata(self::PAYMENT_DATE_REGEX, $content))) {
            $paymentSlip->setPaymentDate($this->getFullDateValue($matches[1]));
        }

        // CAF name
        if (null !== ($matches = $this->matchMetadata(self::CAF_NAME_REGEX, $content))) {
            $cafName = trim($matches[1] . ' ' . trim($matches[2]));
            $paymentSlip->setCafName($cafName);
        }

        // CAF address
        $cafStreet = null;
        $cafCity = null;
        if (null !== ($matches = $this->matchMetadata(self::CAF_STREET_REGEX, $content))) {
            $cafStreet = trim($matches[1]);
        }
        if (null !== ($matches = $this->matchMetadata(self::CAF_CITY_REGEX, $content))) {
            $cafCity = trim($matches[1]);
        }
        if (null !== $cafStreet && null !== $cafCity) {
            $paymentSlip->setCafAddress($cafStreet . ', ' . $cafCity);
        }

        // Recipient name
        if (null !== ($matches = $this->matchMetadata(self::RECIPIENT_NAME_REGEX, $content))) {
            $paymentSlip->setRecipientName(trim($matches[1]));
        }

        // Recipient address
        if (null !== ($matches = $this->matchMetadata(self::RECIPIENT_ADDRESS_REGEX, $content))) {
            $recipientAddress = trim($matches[1]) . ', ' . trim($matches[2]);
            $paymentSlip->setRecipientAddress($recipientAddress);
        }

        // Reference
        if (null !== ($matches = $this->matchMetadata(self::REFERENCE_REGEX, $content))) {
            $paymentSlip->setReference(trim($matches[1]));
        }

        // Bank references (BIC and IBAN)
        if (null !== ($matches = $this->matchMetadata(self::BANK_REGEX, $content))) {
            $paymentSlip->setBic(trim($matches[1]));
            $iban = preg_replace('/\s+/', '', $matches[2]);
            $paymentSlip->setIban($iban);
        }

        // Total amount: a blank one is ignored like a missing one, any other value must be a valid amount
        if (null !== ($matches = $this->matchMetadata(self::TOTAL_REGEX, $content))
            && '' !== preg_replace(self::AMOUNT_SPACES_REGEX, '', $matches[1])) {
            $paymentSlip->setTotalAmount($this->getAmountValue($matches[1]));
        }
    }
}
