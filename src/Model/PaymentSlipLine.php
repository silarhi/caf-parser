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

use DateTimeInterface;

class PaymentSlipLine
{
    /** @var string|null */
    private $reference;

    /** @var string */
    private $beneficiaryReference;

    /** @var string */
    private $beneficiaryName;

    /** @var DateTimeInterface */
    private $startDate;

    /** @var DateTimeInterface */
    private $endDate;

    /** @var float */
    private $grossAmount;

    /** @var float */
    private $deduction;

    /** @var float */
    private $netAmount;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getBeneficiaryReference(): string
    {
        return $this->beneficiaryReference;
    }

    public function setBeneficiaryReference(string $beneficiaryReference): self
    {
        $this->beneficiaryReference = $beneficiaryReference;

        return $this;
    }

    public function getBeneficiaryName(): string
    {
        return $this->beneficiaryName;
    }

    public function setBeneficiaryName(string $beneficiaryName): self
    {
        $this->beneficiaryName = $beneficiaryName;

        return $this;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getGrossAmount(): float
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(float $grossAmount): self
    {
        $this->grossAmount = $grossAmount;

        return $this;
    }

    public function getDeduction(): float
    {
        return $this->deduction;
    }

    public function setDeduction(float $deduction): self
    {
        $this->deduction = $deduction;

        return $this;
    }

    public function getNetAmount(): float
    {
        return $this->netAmount;
    }

    public function setNetAmount(float $netAmount): self
    {
        $this->netAmount = $netAmount;

        return $this;
    }
}
