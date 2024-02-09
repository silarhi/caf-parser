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

class PaymentSlipParserTest extends TestCase
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
        $this->assertNotNull($result);
    }

    public function testParsing2(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotNull($result);
    }

    public function testParsing3(): void
    {
        $parser = new PaymentSlipParser();
        $content = file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_3.txt');
        $this->assertNotFalse($content);
        $result = $parser->parse($content);
        $this->assertNotNull($result);
    }
}
