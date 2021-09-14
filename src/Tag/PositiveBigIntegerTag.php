<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\TagObject as Base;
use CBOR\Utils;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

final class PositiveBigIntegerTag extends Base
{
    #[Pure]
     public static function getTagId(): int
     {
         return 2;
     }

    #[Pure]
     public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
     {
         return new self($additionalInformation, $data, $object);
     }

    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof ByteStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        return new self(2, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false): mixed
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        if (!$this->object instanceof ByteStringObject) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return Utils::hexToString($this->object->getValue());
    }
}
