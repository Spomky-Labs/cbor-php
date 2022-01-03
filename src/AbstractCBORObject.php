<?php

declare(strict_types=1);

namespace CBOR;

use function chr;
use Stringable;

abstract class AbstractCBORObject implements CBORObject, Stringable
{

    /** @var int $majorType */
    private $majorType;

    /** @var int $additionalInformation; */
    protected $additionalInformation;

    public function __construct(
        int $majorType,
        int $additionalInformation
    ) {
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
