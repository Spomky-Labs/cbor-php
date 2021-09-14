<?php

declare(strict_types=1);

namespace CBOR;

use function chr;

abstract class AbstractCBORObject implements CBORObject
{
    protected int $additionalInformation;
    private int $majorType;

    public function __construct(int $majorType, int $additionalInformation)
    {
        $this->majorType = $majorType;
        $this->additionalInformation = $additionalInformation;
    }

    public function __toString(): string
    {
        return chr($this->majorType << 5 | $this->additionalInformation);
    }

    public function getMajorType(): int
    {
        return $this->majorType;
    }

    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }
}
