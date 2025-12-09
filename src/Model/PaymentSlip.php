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

final class PaymentSlip
{
    /** @var PaymentSlipLine[] */
    private array $lines = [];

    private ?DateTimeInterface $paymentDate = null;

    private ?DateTimeInterface $processingDate = null;

    private ?string $recipientName = null;

    private ?string $recipientAddress = null;

    private ?string $iban = null;

    private ?string $bic = null;

    private ?float $totalAmount = null;

    private ?string $cafName = null;

    private ?string $cafAddress = null;

    private ?string $reference = null;

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

    public function getPaymentDate(): ?DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getProcessingDate(): ?DateTimeInterface
    {
        return $this->processingDate;
    }

    public function setProcessingDate(?DateTimeInterface $processingDate): self
    {
        $this->processingDate = $processingDate;

        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getRecipientAddress(): ?string
    {
        return $this->recipientAddress;
    }

    public function setRecipientAddress(?string $recipientAddress): self
    {
        $this->recipientAddress = $recipientAddress;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): self
    {
        $this->bic = $bic;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getCafName(): ?string
    {
        return $this->cafName;
    }

    public function setCafName(?string $cafName): self
    {
        $this->cafName = $cafName;

        return $this;
    }

    public function getCafAddress(): ?string
    {
        return $this->cafAddress;
    }

    public function setCafAddress(?string $cafAddress): self
    {
        $this->cafAddress = $cafAddress;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }
}
