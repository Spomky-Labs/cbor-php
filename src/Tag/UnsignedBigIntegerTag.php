<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\TagObject as Base;
use CBOR\Utils;
use InvalidArgumentException;

/**
 * @final
 */
class UnsignedBigIntegerTag extends Base
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (!$object instanceof ByteStringObject && !$object instanceof IndefiniteLengthByteStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_UNSIGNED_BIG_NUM;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Base
    {
        return new self(self::TAG_UNSIGNED_BIG_NUM, null, $object);
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function getNormalizedData(bool $ignoreTags = false)
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
