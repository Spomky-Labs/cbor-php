<?php

declare(strict_types=1);

namespace CBOR;

use Stringable;

interface CBORObject extends Stringable
{
    public function __toString(): string;

    public function getMajorType(): int;

    public function getAdditionalInformation(): int;

    public function getNormalizedData(bool $ignoreTags = false);
}
