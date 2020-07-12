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

namespace CBOR\OtherObject;

use Assert\Assertion;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;

final class HalfPrecisionFloatObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [25];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * @return HalfPrecisionFloatObject
     */
    public static function create(string $value): self
    {
        if (4 !== mb_strlen($value, '8bit')) {
            throw new InvalidArgumentException('The value is not a valid half precision floating point');
        }

        return new self(25, $value);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        $data = $this->data;
        Assertion::string($data, 'Invalid data');
        $half = Utils::hexToInt($data);
        $exp = ($half >> 10) & 0x1f;
        $mant = $half & 0x3ff;

        if (0 === $exp) {
            $val = $mant * 2 ** (-24);
        } elseif (0b11111 !== $exp) {
            $val = ($mant + (1 << 10)) * 2 ** ($exp - 25);
        } else {
            $val = 0 === $mant ? INF : NAN;
        }

        return 1 === ($half >> 15) ? -$val : $val;
    }

    public function getExponent(): int
    {
        $data = $this->data;
        Assertion::string($data, 'Invalid data');
        $half = Utils::hexToInt($data);

        return ($half >> 10) & 0x1f;
    }

    public function getMantissa(): int
    {
        $data = $this->data;
        Assertion::string($data, 'Invalid data');
        $half = Utils::hexToInt($data);

        return $half & 0x3ff;
    }

    public function getSign(): int
    {
        $data = $this->data;
        Assertion::string($data, 'Invalid data');
        $half = Utils::hexToInt($data);

        return 1 === ($half >> 15) ? -1 : 1;
    }
}
