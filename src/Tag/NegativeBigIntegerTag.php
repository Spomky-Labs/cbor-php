<?php

declare(strict_types=1);

namespace CBOR\Tag;

use Brick\Math\BigInteger;
use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\TagObject as Base;
use InvalidArgumentException;

final class NegativeBigIntegerTag extends Base
{
    public static function getTagId(): int
    {
        return 3;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof ByteStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        return new self(3, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        if (!$this->object instanceof ByteStringObject) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        $integer = BigInteger::fromBase(bin2hex($this->object->getValue()), 16);
        $minusOne = BigInteger::of(-1);

        return $minusOne->minus($integer)->toBase(10);
    }
}
