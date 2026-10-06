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

namespace Silarhi\Caf\Tests\Model;

use PHPUnit\Framework\TestCase;
use Silarhi\Caf\Model\PaymentSlip;
use Silarhi\Caf\Model\PaymentSlipLine;

final class PaymentSlipTest extends TestCase
{
    public function testAddLineAppendsLines(): void
    {
        $first = new PaymentSlipLine();
        $second = new PaymentSlipLine();

        $paymentSlip = (new PaymentSlip())
            ->addLine($first)
            ->addLine($second);

        self::assertSame([$first, $second], $paymentSlip->getLines());
    }

    public function testSetLinesReplacesExistingLines(): void
    {
        $existing = new PaymentSlipLine();
        $replacement = new PaymentSlipLine();

        $paymentSlip = (new PaymentSlip())->addLine($existing);

        self::assertSame($paymentSlip, $paymentSlip->setLines([$replacement]));
        self::assertSame([$replacement], $paymentSlip->getLines());

        self::assertSame([], $paymentSlip->setLines([])->getLines());
    }
}
