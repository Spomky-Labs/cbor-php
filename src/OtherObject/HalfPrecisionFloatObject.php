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

use Brick\Math\BigInteger;
use CBOR\Normalizable;
use CBOR\OtherObject as Base;
use CBOR\Utils;
use InvalidArgumentException;

final class HalfPrecisionFloatObject extends Base implements Normalizable
{
    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_HALF_PRECISION_FLOAT];
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
        if (2 !== mb_strlen($value, '8bit')) {
            throw new InvalidArgumentException('The value is not a valid half precision floating point');
        }

        return new self(self::OBJECT_HALF_PRECISION_FLOAT, $value);
    }

    /**
     * @deprecated The method will be removed on v3.0. Please use CBOR\Normalizable interface
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        return $this->normalize();
    }

    /**
     * @return float|int
     */
    public function normalize()
    {
        $exponent = $this->getExponent();
        $mantissa = $this->getMantissa();
        $sign = $this->getSign();

        if (0 === $exponent) {
            $val = $mantissa * 2 ** (-24);
        } elseif (0b11111 !== $exponent) {
            $val = ($mantissa + (1 << 10)) * 2 ** ($exponent - 25);
        } else {
            $val = 0 === $mantissa ? INF : NAN;
        }

        return $sign * $val;
    }

    public function getExponent(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->shiftedRight(10)->and(Utils::hexToBigInteger('1f'))->toInt();
    }

    public function getMantissa(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');

        return Utils::binToBigInteger($data)->and(Utils::hexToBigInteger('3ff'))->toInt();
    }

    public function getSign(): int
    {
        $data = $this->data;
        Utils::assertString($data, 'Invalid data');
        $sign = Utils::binToBigInteger($data)->shiftedRight(15);

        return $sign->isEqualTo(BigInteger::one()) ? -1 : 1;
    }
}
