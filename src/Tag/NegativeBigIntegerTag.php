<?php

declare(strict_types=1);

namespace CBOR\Tag;

use Brick\Math\BigInteger;
use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\Normalizable;
use CBOR\Tag;
use CBOR\Utils;
use InvalidArgumentException;

final class NegativeBigIntegerTag extends Tag implements Normalizable
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (! $object instanceof ByteStringObject && ! $object instanceof IndefiniteLengthByteStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_NEGATIVE_BIG_NUM;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Tag
    {
        [$ai, $data] = self::determineComponents(self::TAG_NEGATIVE_BIG_NUM);

        return new self($ai, $data, $object);
    }

    public function normalize(): string
    {
        /** @var ByteStringObject|IndefiniteLengthByteStringObject $object */
        $object = $this->object;
        $value = $object->getValue();
        // RFC 8949 section 3.4.3: the empty byte string is the preferred serialization of zero, so tag 3 wrapping
        // it is -1 - 0.
        $integer = $value === '' ? BigInteger::zero() : Utils::hexToBigInteger(bin2hex($value));
        $minusOne = BigInteger::of(-1);

        return $minusOne->minus($integer)
            ->toBase(10)
        ;
    }
}
