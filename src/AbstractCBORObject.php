<?php

declare(strict_types=1);

namespace CBOR;

use function chr;
use JetBrains\PhpStorm\Pure;

abstract class AbstractCBORObject implements CBORObject
{
    protected int $additionalInformation;
    private int $majorType;

    #[Pure]
    public function __construct(int $majorType, int $additionalInformation)
    {
        $this->majorType = $majorType;
        $this->additionalInformation = $additionalInformation;
    }

    #[Pure]
    public function __toString(): string
    {
        return chr($this->majorType << 5 | $this->additionalInformation);
    }

    #[Pure]
    public function getMajorType(): int
    {
        return $this->majorType;
    }

    #[Pure]
    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }
}
