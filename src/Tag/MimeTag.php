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

use CBOR\CBORObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\Tag;
use CBOR\TextStringObject;
use InvalidArgumentException;

final class MimeTag extends Tag
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (!$object instanceof TextStringObject && !$object instanceof IndefiniteLengthTextStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_MIME;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Tag
    {
        return new self(self::TAG_MIME, null, $object);
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        return $this->object->getNormalizedData($ignoreTags);
    }
}