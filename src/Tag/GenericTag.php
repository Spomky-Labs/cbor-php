<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\TagObject as Base;

final class GenericTag extends Base
{
    public static function getTagId(): int
    {
        return -1;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false): CBORObject
    {
        return $this->object;
    }
}
