<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\TagObject as Base;
use JetBrains\PhpStorm\Pure;

final class GenericTag extends Base
{
    #[Pure]
 public static function getTagId(): int
 {
     return -1;
 }

    #[Pure]
    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    #[Pure]
    public function getNormalizedData(bool $ignoreTags = false): CBORObject
    {
        return $this->object;
    }
}
