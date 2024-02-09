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

namespace Silarhi\Caf\Model;

class PaymentSlip
{
    /** @var PaymentSlipLine[] */
    private array $lines = [];

    public function addLine(PaymentSlipLine $line): self
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * @return PaymentSlipLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param PaymentSlipLine[] $lines
     */
    public function setLines(array $lines): self
    {
        $this->lines = $lines;

        return $this;
    }
}
