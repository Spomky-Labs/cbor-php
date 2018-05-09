<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\TagObject as Base;
use CBOR\UnsignedIntegerObject;

final class TimestampTag extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function getTagId(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof UnsignedIntegerObject && !$object instanceof HalfPrecisionFloatObject && !$object instanceof SinglePrecisionFloatObject && !$object instanceof DoublePrecisionFloatObject) {
            throw new \InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        return new self(1, null, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->getData()->getNormalizedData($ignoreTags);
        }
        switch (true) {
            case $this->getData() instanceof UnsignedIntegerObject:
                return \DateTimeImmutable::createFromFormat('U', strval($this->getData()->getNormalizedData($ignoreTags)));
            case $this->getData() instanceof HalfPrecisionFloatObject:
            case $this->getData() instanceof SinglePrecisionFloatObject:
            case $this->getData() instanceof DoublePrecisionFloatObject:
                return \DateTimeImmutable::createFromFormat('U.u', strval($this->getData()->getNormalizedData($ignoreTags)));
            default:
                return $this->getData()->getNormalizedData($ignoreTags);
        }
    }
}
