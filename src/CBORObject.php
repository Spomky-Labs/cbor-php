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

namespace CBOR;

interface CBORObject
{
    public const MAJOR_TYPE_UNSIGNED_INTEGER = 0b000;
    public const MAJOR_TYPE_NEGATIVE_INTEGER = 0b001;
    public const MAJOR_TYPE_BYTE_STRING = 0b010;
    public const MAJOR_TYPE_TEXT_STRING = 0b011;
    public const MAJOR_TYPE_LIST = 0b100;
    public const MAJOR_TYPE_MAP = 0b101;
    public const MAJOR_TYPE_TAG = 0b110;
    public const MAJOR_TYPE_OTHER_TYPE = 0b111;

    public const LENGTH_1_BYTE = 0b00011000;
    public const LENGTH_2_BYTES = 0b00011001;
    public const LENGTH_4_BYTES = 0b00011010;
    public const LENGTH_8_BYTES = 0b00011011;
    public const LENGTH_INDEFINITE = 0b00011111;

    public const FUTURE_USE_1 = 0b00011100;
    public const FUTURE_USE_2 = 0b00011101;
    public const FUTURE_USE_3 = 0b00011110;

    public function __toString(): string;

    public function getMajorType(): int;

    public function getAdditionalInformation(): int;

    /**
     * @return mixed|null
     */
    public function getNormalizedData(bool $ignoreTags = false);
}
