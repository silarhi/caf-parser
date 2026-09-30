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
        $this->assertNotFalse($content);
        $parser->parse($content);
    }

    public function testParsing(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotCount(0, $result->getLines());

        // Test metadata
        $this->assertNotNull($result->getProcessingDate());
        $this->assertSame('2021-11-27', $result->getProcessingDate()->format('Y-m-d'));

        $this->assertNotNull($result->getPaymentDate());
        $this->assertSame('2021-11-25', $result->getPaymentDate()->format('Y-m-d'));

        $this->assertSame('CAISSE D\'ALLOCATIONS FAMILIALES DE HAUTE GARONNE', $result->getCafName());
        $this->assertSame('24 RUE PIERRE PAUL RIQUET, 31046 TOULOUSE CEDEX 9', $result->getCafAddress());

        $this->assertSame('SCI SCITEST', $result->getRecipientName());
        $this->assertSame('34 RUE DES ALOUETTES, 81100 CASTRES', $result->getRecipientAddress());

        $this->assertSame('0111111 0002', $result->getReference());

        $this->assertSame('CMCIFR2A', $result->getBic());
        $this->assertSame('FR7600000000111122223333444', $result->getIban());

        $this->assertSame(1298.00, $result->getTotalAmount());
    }

    public function testParsingLines(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44.txt');
        $this->assertNotFalse($content);
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

        $this->assertSame($expected, $actual);

        $netTotal = array_sum(array_map(static fn (PaymentSlipLine $line): float => $line->getNetAmount(), $result->getLines()));
        $this->assertSame($result->getTotalAmount(), $netTotal);
    }

    public function testParsing2(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotCount(0, $result->getLines());

        $this->assertNotNull($result->getProcessingDate());
        $this->assertSame('2021-02-10 00:00:00', $result->getProcessingDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($result->getPaymentDate());
        $this->assertSame('2021-02-09 00:00:00', $result->getPaymentDate()->format('Y-m-d H:i:s'));
        $this->assertSame('FR7610278022040055555555555', $result->getIban());
        $this->assertSame(33.00, $result->getTotalAmount());

        $this->assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        $this->assertSame('', $line->getReference());
        $this->assertSame('1111111 J', $line->getBeneficiaryReference());
        $this->assertSame('MME JESUS', $line->getBeneficiaryName());
        $this->assertSame('2021-01-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        $this->assertSame('2021-01-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        $this->assertSame(33.00, $line->getGrossAmount());
        $this->assertSame(0.00, $line->getDeduction());
        $this->assertSame(33.00, $line->getNetAmount());
    }

    public function testParsing3(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_3.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotCount(0, $result->getLines());
    }

    public function testParsingWindowsLineEndings(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        $this->assertNotFalse($content);

        $expected = $parser->parse($content);
        $result = $parser->parse(str_replace("\n", "\r\n", $content));

        $this->assertEquals($expected, $result);
        $this->assertCount(1, $result->getLines());
        $this->assertSame('MME JESUS', $result->getLines()[0]->getBeneficiaryName());
    }

    public function testParsingLineColumns(): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            ' : 1234567890123        : 1234567 A : MME TEST ALPHA           : 12 2021 : 01 2022 :      300,00:      25,50:     274,50 :',
            ' :                      : 7654321 B : MR TEST BETA             : 03 2022 : 03 2022 :       10,00:       0,00:      10,00 :',
        ));

        $this->assertCount(2, $result->getLines());

        $line = $result->getLines()[0];
        $this->assertSame('1234567890123', $line->getReference());
        $this->assertSame('1234567 A', $line->getBeneficiaryReference());
        $this->assertSame('MME TEST ALPHA', $line->getBeneficiaryName());
        $this->assertSame('2021-12-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        $this->assertSame('2022-01-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        $this->assertSame(300.00, $line->getGrossAmount());
        $this->assertSame(25.50, $line->getDeduction());
        $this->assertSame(274.50, $line->getNetAmount());

        $line = $result->getLines()[1];
        $this->assertSame('', $line->getReference());
        $this->assertSame('7654321 B', $line->getBeneficiaryReference());
        $this->assertSame('MR TEST BETA', $line->getBeneficiaryName());
        $this->assertSame('2022-03-01 00:00:00', $line->getStartDate()->format('Y-m-d H:i:s'));
        $this->assertSame('2022-03-01 00:00:00', $line->getEndDate()->format('Y-m-d H:i:s'));
        $this->assertSame(10.00, $line->getGrossAmount());
        $this->assertSame(0.00, $line->getDeduction());
        $this->assertSame(10.00, $line->getNetAmount());
    }

    public function testParsingWithoutMetadata(): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            ' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :       42,00:       0,00:      42,00 :',
        ));

        $this->assertCount(1, $result->getLines());
        $this->assertSame(42.00, $result->getLines()[0]->getNetAmount());

        $this->assertNull($result->getProcessingDate());
        $this->assertNull($result->getPaymentDate());
        $this->assertNull($result->getCafName());
        $this->assertNull($result->getCafAddress());
        $this->assertNull($result->getRecipientName());
        $this->assertNull($result->getRecipientAddress());
        $this->assertNull($result->getReference());
        $this->assertNull($result->getBic());
        $this->assertNull($result->getIban());
        $this->assertNull($result->getTotalAmount());
    }

    #[DataProvider('provideAmounts')]
    public function testParsingLineAmounts(string $amount, float $expected): void
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(self::buildContent(
            sprintf(' :                      : 1234567 A : MME TEST ALPHA           : 01 2022 : 01 2022 :%s:%s:%s:', $amount, $amount, $amount),
        ));

        $this->assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        $this->assertSame($expected, $line->getGrossAmount());
        $this->assertSame($expected, $line->getDeduction());
        $this->assertSame($expected, $line->getNetAmount());
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

        $this->assertSame($expected, $result->getTotalAmount());
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

        $this->assertCount(1, $result->getLines());
        $line = $result->getLines()[0];
        $this->assertEquals(new DateTimeImmutable($expectedDate), $line->getStartDate());
        $this->assertEquals(new DateTimeImmutable($expectedDate), $line->getEndDate());
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

    public function testInvalidDateKeepsPreviousException(): void
    {
        $parser = new PaymentSlipParser();

        try {
            $parser->parse(self::buildContent(
                ' :                      : 1234567 A : MME TEST ALPHA           : XX XXXX : 01 2022 :       42,00:       0,00:      42,00 :',
            ));
            $this->fail('A ParseException should have been thrown');
        } catch (ParseException $e) {
            $this->assertSame('CAF Row n°1 : "XX XXXX" date value could not be parsed, expected format is "m Y"', $e->getMessage());
            $this->assertInstanceOf(ParseException::class, $e->getPrevious());
            $this->assertSame('"XX XXXX" date value could not be parsed, expected format is "m Y"', $e->getPrevious()->getMessage());
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
