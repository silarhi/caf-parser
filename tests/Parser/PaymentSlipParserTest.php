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

namespace Silarhi\Caf\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Silarhi\Caf\Exceptions\ParseException;
use Silarhi\Caf\Parser\PaymentSlipParser;

class PaymentSlipParserTest extends TestCase
{
    public function testEmptyInput()
    {
        $this->expectException(ParseException::class);

        $parser = new PaymentSlipParser();
        $parser->parse('');
    }

    public function testUnexpectedInput()
    {
        $this->expectException(ParseException::class);

        $parser = new PaymentSlipParser();
        $parser->parse('Lorem ipsum dolor sit amet');
    }

    public function testUnparseable2ndCafRow()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessageMatches('/^CAF Row n°2 could not be parsed$/');

        $parser = new PaymentSlipParser();
        $parser->parse(file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_unparseable_2nd_row.txt'));
    }

    public function testParsing()
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44.txt'));
        $this->assertNotNull($result);

        //@TODO test parsed rows & line values
    }

    public function testParsing2()
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_2.txt'));
        $this->assertNotNull($result);

        //@TODO test parsed rows & line values
    }

    public function testParsing3()
    {
        $parser = new PaymentSlipParser();
        $result = $parser->parse(file_get_contents(__DIR__ . '/../fixtures/LA44ZZ/caf_LA44_3.txt'));
        $this->assertNotNull($result);

        //@TODO test parsed rows & line values
    }
}
