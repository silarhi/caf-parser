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

namespace Silarhi\Caf\Tests\Parser;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Model\PaymentSlipLine;
use Silarhi\Caf\Parser\PaymentSlipParser;

use function sprintf;

final class PaymentSlipParserTest extends TestCase
{
    public function testEmptyInput(): void
    {
        $this->expectException(ParseException::class);

        $parser = new PaymentSlipParser();
        $parser->parse('');
    }

    public function testUnexpectedInput(): void
    {
        $this->expectException(ParseException::class);

        $parser = new PaymentSlipParser();
        $parser->parse('Lorem ipsum dolor sit amet');
    }

    public function testUnparseable2ndCafRow(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessageMatches('/^CAF Row n°2 could not be parsed$/');

        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_unparseable_2nd_row.txt');
        self::assertNotFalse($content);
        $parser->parse($content);
    }

    public function testParsing(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44.txt');
        self::assertNotFalse($content);
        $result = $parser->parse($content);
        self::assertNotCount(0, $result->getLines());

        // Test metadata
        self::assertNotNull($result->getProcessingDate());
        self::assertSame('2021-11-27', $result->getProcessingDate()->format('Y-m-d'));

        self::assertNotNull($result->getPaymentDate());
        self::assertSame('2021-11-25', $result->getPaymentDate()->format('Y-m-d'));

        self::assertSame('CAISSE D\'ALLOCATIONS FAMILIALES DE HAUTE GARONNE', $result->getCafName());
        self::assertSame('24 RUE PIERRE PAUL RIQUET, 31046 TOULOUSE CEDEX 9', $result->getCafAddress());

        self::assertSame('SCI SCITEST', $result->getRecipientName());
        self::assertSame('34 RUE DES ALOUETTES, 81100 CASTRES', $result->getRecipientAddress());

        self::assertSame('0111111 0002', $result->getReference());

        self::assertSame('CMCIFR2A', $result->getBic());
        self::assertSame('FR7600000000111122223333444', $result->getIban());

        self::assertSame(1298.00, $result->getTotalAmount());
    }

    public function testParsingLines(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44.txt');
        self::assertNotFalse($content);
        $result = $parser->parse($content);

        $expected = [
            ['', '1111111 S', 'MR ABABABA JOHNNY', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 272.00, 0.00, 272.00],
            ['', '2222222 L', 'MR ADADADA PHILIPPE', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 50.00, 0.00, 50.00],
            ['', '3333333 H', 'MR AFAFAFAFA MATHIEU', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 272.00, 0.00, 272.00],
            ['0000000000000', '4444444 S', 'MME AGAGA MARIE', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 175.00, 0.00, 175.00],
            ['', '5555555 E', 'MR ANANA JEAN PHILIPPE', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 175.00, 0.00, 175.00],
            ['', '6666666 M', 'MR AMAMA MARTIN', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 133.00, 0.00, 133.00],
            ['', '7777777 H', 'MR AYAYAYA LEO', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 133.00, 0.00, 133.00],
            ['0000000000000', '8888888 U', 'MR AZAZAZ MARC', '2021-11-01 00:00:00', '2021-11-01 00:00:00', 88.00, 0.00, 88.00],
        ];

        $actual = array_map(static fn (PaymentSlipLine $line): array => [
            $line->getReference(),
            $line->getBeneficiaryReference(),
            $line->getBeneficiaryName(),
            $line->getStartDate()->format('Y-m-d H:i:s'),
            $line->getEndDate()->format('Y-m-d H:i:s'),
            $line->getGrossAmount(),
            $line->getDeduction(),
            $line->getNetAmount(),
        ], $result->getLines());

        self::assertSame($expected, $actual);

        $netTotal = array_sum(array_map(static fn (PaymentSlipLine $line): float => $line->getNetAmount(), $result->getLines()));
        self::assertSame($result->getTotalAmount(), $netTotal);
    }

    public function testParsing2(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        self::assertNotFalse($content);
        $result = $parser->parse($content);
        self::assertNotCount(0, $result->getLines());

        self::assertNotNull($result->getProcessingDate());
        self::assertSame('2021-02-10 00:00:00', $result->getProcessingDate()->format('Y-m-d H:i:s'));
        self::assertNotNull($result->getPaymentDate());
        self::assertSame('2021-02-09 00:00:00', $result->getPaymentDate()->format('Y-m-d H:i:s'));
        self::assertSame('FR7610278022040055555555555', $result->getIban());
        self::assertSame(33.00, $result->getTotalAmount());

        self::assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        self::assertSame('', $line->getReference());
        self::assertSame('1111111 J', $line->getBeneficiaryReference());
        self::assertSame('MME JESUS', $line->getBeneficiaryName());
        self::assertSame('2021-01-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        self::assertSame('2021-01-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        self::assertSame(33.00, $line->getGrossAmount());
        self::assertSame(0.00, $line->getDeduction());
        self::assertSame(33.00, $line->getNetAmount());
    }

    public function testParsing3(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_3.txt');
        self::assertNotFalse($content);
        $result = $parser->parse($content);
        self::assertNotCount(0, $result->getLines());
    }

    public function testParsingWindowsLineEndings(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        self::assertNotFalse($content);

        $expected = $parser->parse($content);
        $result = $parser->parse(str_replace("\n", "\r\n", $content));

        self::assertEquals($expected, $result);
        self::assertCount(1, $result->getLines());
        self::assertSame('MME JESUS', $result->getLines()[0]->getBeneficiaryName());
    }

    public function testParsingLineColumns(): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            ' : 1234567890123        : 1234567 A : MME TEST ALPHA           : 12 2021 : 01 2022 :      300,00:      25,50:     274,50 :',
            ' :                      : 7654321 B : MR TEST BETA             : 03 2022 : 03 2022 :       10,00:       0,00:      10,00 :',
        ));

        self::assertCount(2, $result->getLines());

        $line = $result->getLines()[0];
        self::assertSame('1234567890123', $line->getReference());
        self::assertSame('1234567 A', $line->getBeneficiaryReference());
        self::assertSame('MME TEST ALPHA', $line->getBeneficiaryName());
        self::assertSame('2021-12-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        self::assertSame('2022-01-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        self::assertSame(300.00, $line->getGrossAmount());
        self::assertSame(25.50, $line->getDeduction());
        self::assertSame(274.50, $line->getNetAmount());

        $line = $result->getLines()[1];
        self::assertSame('', $line->getReference());
        self::assertSame('7654321 B', $line->getBeneficiaryReference());
        self::assertSame('MR TEST BETA', $line->getBeneficiaryName());
        self::assertSame('2022-03-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        self::assertSame('2022-03-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        self::assertSame(10.00, $line->getGrossAmount());
        self::assertSame(0.00, $line->getDeduction());
        self::assertSame(10.00, $line->getNetAmount());
    }

    public function testParsingWithoutMetadata(): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            ' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :       42,00:       0,00:      42,00 :',
        ));

        self::assertCount(1, $result->getLines());
        self::assertSame(42.00, $result->getLines()[0]->getNetAmount());

        self::assertNull($result->getProcessingDate());
        self::assertNull($result->getPaymentDate());
        self::assertNull($result->getCafName());
        self::assertNull($result->getCafAddress());
        self::assertNull($result->getRecipientName());
        self::assertNull($result->getRecipientAddress());
        self::assertNull($result->getReference());
        self::assertNull($result->getBic());
        self::assertNull($result->getIban());
        self::assertNull($result->getTotalAmount());
    }

    #[DataProvider('provideAmounts')]
    public function testParsingLineAmounts(string $amount, float $expected): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            sprintf(' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :%s:%s:%s:', $amount, $amount, $amount),
        ));

        self::assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        self::assertSame($expected, $line->getGrossAmount());
        self::assertSame($expected, $line->getDeduction());
        self::assertSame($expected, $line->getNetAmount());
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function provideAmounts(): iterable
    {
        yield 'without thousands separator' => ['     1298,00 ', 1298.00];
        yield 'zero' => ['        0,00 ', 0.00];
        yield 'without decimals' => ['         272 ', 272.00];
        yield 'space thousands separator' => ['    1 298,50 ', 1298.50];
        yield 'dot thousands separator' => ['    1.298,50 ', 1298.50];
        yield 'no-break space thousands separator' => ["    1\u{00A0}298,50 ", 1298.50];
        yield 'narrow no-break space thousands separator' => ["    1\u{202F}298,50 ", 1298.50];
        yield 'several thousands separators' => [' 12 345 678,90 ', 12345678.90];
        yield 'several dot thousands separators' => [' 12.345.678,90 ', 12345678.90];
        yield 'dot decimal separator' => ['     1298.50 ', 1298.50];
        yield 'dot decimal separator with space thousands separator' => ['    1 298.50 ', 1298.50];
        yield 'dot decimal separator with no-break space thousands separator' => ["    1\u{00A0}298.50 ", 1298.50];
        yield 'dot decimal separator with narrow no-break space thousands separator' => ["    1\u{202F}298.50 ", 1298.50];
    }

    /**
     * TOTAL_REGEX only accepts digits, commas, dots and ASCII whitespaces in the total amount.
     */
    #[DataProvider('provideTotalAmounts')]
    public function testParsingTotalAmount(string $total, float $expected): void
    {
        $parser = new PaymentSlipParser();
        $content = self::buildContent(
            ' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :       42,00:       0,00:      42,00 :',
        );
        $result = $parser->parse(str_replace('TOTAL :', sprintf('TOTAL : %s :', $total), $content));

        self::assertSame($expected, $result->getTotalAmount());
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function provideTotalAmounts(): iterable
    {
        yield 'without thousands separator' => ['        1298,00', 1298.00];
        yield 'space thousands separator' => ['       1 298,00', 1298.00];
        yield 'dot thousands separator' => ['       1.298,00', 1298.00];
        yield 'several thousands separators' => ['  12 345 678,90', 12345678.90];
        yield 'several dot thousands separators' => ['  12.345.678,90', 12345678.90];
        yield 'dot decimal separator' => ['        1298.00', 1298.00];
        yield 'dot decimal separator with space thousands separator' => ['       1 298.00', 1298.00];
        yield 'without decimals' => ['           1298', 1298.00];
    }

    /**
     * Every month is covered so that the test fails on any 29th, 30th or 31st of a month
     * if the parsed date inherits the current day of month (e.g. "02 2021" overflowing to March).
     */
    #[DataProvider('provideMonthDates')]
    public function testMonthDatesDoNotDependOnCurrentDay(string $month, string $expectedDate): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            sprintf(' :                      : 1234567 A : MME TEST ALPHA           : %s : %s :       42,00:       0,00:      42,00 :', $month, $month),
        ));

        self::assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        self::assertEquals(new DateTimeImmutable($expectedDate), $line->getStartDate());
        self::assertEquals(new DateTimeImmutable($expectedDate), $line->getEndDate());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMonthDates(): iterable
    {
        for ($month = 1; $month <= 12; ++$month) {
            yield sprintf('month %02d', $month) => [sprintf('%02d 2021', $month), sprintf('2021-%02d-01 00:00:00', $month)];
        }

        yield 'leap year february' => ['02 2024', '2024-02-01 00:00:00'];
    }

    #[DataProvider('provideInvalidDateRows')]
    public function testInvalidDate(string $row, string $expectedMessage): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);

        $parser = new PaymentSlipParser();
        $parser->parse(self::buildContent(
            ' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :       42,00:       0,00:      42,00 :',
            $row,
        ));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidDateRows(): iterable
    {
        yield 'invalid start date' => [
            ' :                      : 7654321 B : MR TEST BETA             : AB 2022 : 01 2022 :       10,00:       0,00:      10,00 :',
            'CAF Row n°2 : "AB 2022" date value could not be parsed, expected format is "m Y"',
        ];

        yield 'invalid end date' => [
            ' :                      : 7654321 B : MR TEST BETA             : 01 2022 : 2022-01 :       10,00:       0,00:      10,00 :',
            'CAF Row n°2 : "2022-01" date value could not be parsed, expected format is "m Y"',
        ];

        yield 'empty start date' => [
            ' :                      : 7654321 B : MR TEST BETA             :         : 01 2022 :       10,00:       0,00:      10,00 :',
            'CAF Row n°2 : "" date value could not be parsed, expected format is "m Y"',
        ];

        yield 'out of range start month' => [
            ' :                      : 7654321 B : MR TEST BETA             : 13 2021 : 01 2022 :       10,00:       0,00:      10,00 :',
            'CAF Row n°2 : "13 2021" date value could not be parsed, expected format is "m Y"',
        ];

        yield 'zero end month' => [
            ' :                      : 7654321 B : MR TEST BETA             : 01 2022 : 00 2022 :       10,00:       0,00:      10,00 :',
            'CAF Row n°2 : "00 2022" date value could not be parsed, expected format is "m Y"',
        ];
    }

    public function testParsingMetadataDates(): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(implode("\n", [
            ' DATE DE TRAITEMENT : 29  02  2024',
            ' : BORDEREAU DE PAIEMENT A.L.  DU     31 01 2024 :',
            self::buildContent(
                ' :                      : 1234567 A : MME TEST ALPHA           : 01 2024 : 01 2024 :       42,00:       0,00:      42,00 :',
            ),
        ]));

        self::assertEquals(new DateTimeImmutable('2024-02-29 00:00:00'), $result->getProcessingDate());
        self::assertEquals(new DateTimeImmutable('2024-01-31 00:00:00'), $result->getPaymentDate());
    }

    #[DataProvider('provideInvalidMetadataDates')]
    public function testInvalidMetadataDate(string $header, string $expectedMessage): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);

        $parser = new PaymentSlipParser();
        $parser->parse(implode("\n", [
            $header,
            self::buildContent(
                ' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :       42,00:       0,00:      42,00 :',
            ),
        ]));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidMetadataDates(): iterable
    {
        yield 'invalid processing day' => [
            ' DATE DE TRAITEMENT : 31 02 2021',
            '"31 02 2021" date value could not be parsed, expected format is "d m Y"',
        ];

        yield 'invalid processing month' => [
            ' DATE DE TRAITEMENT : 15 13 2021',
            '"15 13 2021" date value could not be parsed, expected format is "d m Y"',
        ];

        yield 'invalid payment day' => [
            ' : BORDEREAU DE PAIEMENT A.L.  DU     00 11 2021 :',
            '"00 11 2021" date value could not be parsed, expected format is "d m Y"',
        ];

        yield 'invalid payment month' => [
            ' : BORDEREAU DE PAIEMENT A.L.  DU     15 00 2021 :',
            '"15 00 2021" date value could not be parsed, expected format is "d m Y"',
        ];

        yield 'non leap year february 29th' => [
            ' : BORDEREAU DE PAIEMENT A.L.  DU     29 02 2021 :',
            '"29 02 2021" date value could not be parsed, expected format is "d m Y"',
        ];
    }

    public function testInvalidDateKeepsPreviousException(): void
    {
        $parser = new PaymentSlipParser();

        try {
            $parser->parse(self::buildContent(
                ' :                      : 1234567 A : MME TEST ALPHA           : XX XXXX : 01 2022 :       42,00:       0,00:      42,00 :',
            ));
            self::fail('A ParseException should have been thrown');
        } catch (ParseException $e) {
            self::assertSame('CAF Row n°1 : "XX XXXX" date value could not be parsed, expected format is "m Y"', $e->getMessage());
            self::assertInstanceOf(ParseException::class, $e->getPrevious());
            self::assertSame('"XX XXXX" date value could not be parsed, expected format is "m Y"', $e->getPrevious()->getMessage());
        }
    }

    /**
     * Builds a minimal LA44ZZ document (no header metadata) around the given table rows.
     */
    private static function buildContent(string ...$rows): string
    {
        $separator = ' ' . str_repeat('-', 121);

        return implode("\n", [
            $separator,
            ' :      REFERENCES      :   NUMERO  :       NOM DESTINATAIRE   : DATE    :  DATE   :  MONTANT   : RETENUE   :  MONTANT   :',
            $separator,
            ...$rows,
            $separator,
            ' :                                                                                               TOTAL :',
            $separator,
        ]) . "\n";
    }
}
