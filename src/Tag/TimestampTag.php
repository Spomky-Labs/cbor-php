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
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\Tag;
use CBOR\UnsignedIntegerObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class TimestampTag extends Tag
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (!$object instanceof UnsignedIntegerObject && !$object instanceof NegativeIntegerObject && !$object instanceof HalfPrecisionFloatObject && !$object instanceof SinglePrecisionFloatObject && !$object instanceof DoublePrecisionFloatObject) {
            throw new InvalidArgumentException('This tag only accepts integer-based or float-based objects.');
        }
        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_EPOCH_DATETIME;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Tag
    {
        [$ai, $data] = self::determineComponents(self::TAG_EPOCH_DATETIME);

        return new self($ai, $data, $object);
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        switch (true) {
            case $this->object instanceof UnsignedIntegerObject:
            case $this->object instanceof NegativeIntegerObject:
                return DateTimeImmutable::createFromFormat('U', (string) $this->object->getNormalizedData($ignoreTags));
            case $this->object instanceof HalfPrecisionFloatObject:
            case $this->object instanceof SinglePrecisionFloatObject:
            case $this->object instanceof DoublePrecisionFloatObject:
                return DateTimeImmutable::createFromFormat('U.u', (string) $this->object->getNormalizedData($ignoreTags));
            default:
                return $this->object->getNormalizedData($ignoreTags);
        }
    }
}
