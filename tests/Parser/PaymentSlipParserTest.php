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

use PHPUnit\Framework\TestCase;
use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Parser\PaymentSlipParser;

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

    public function testParsing2(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotCount(0, $result->getLines());
    }

    public function testParsing3(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_3.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotCount(0, $result->getLines());
    }
}
