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

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class DoublePrecisionFloatObject extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function supportedAdditionalInformation(): array
    {
        return [27];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * @param string $value
     *
     * @return DoublePrecisionFloatObject
     */
    public static function create(string $value): self
    {
        if (mb_strlen($value, '8bit') !== 8) {
            throw new \InvalidArgumentException('The value is not a valid double precision floating point');
        }

        return new self(27, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        $single = gmp_init(bin2hex($this->data), 16);
        $exp = gmp_intval($this->bitwiseAnd($this->rightShift($single, 52), gmp_init('7ff', 16)));
        $mant = gmp_intval($this->bitwiseAnd($single, gmp_init('fffffffffffff', 16)));
        $sign = gmp_intval($this->rightShift($single, 63));

        if ($exp === 0) {
            $val = $mant * pow(2, -(1022 + 52));
        } elseif ($exp !== 0b11111111111) {
            $val = ($mant + (1 << 52)) * pow(2, $exp - (1023 + 52));
        } else {
            $val = $mant === 0 ? INF : NAN;
        }

        return $sign ? -$val : $val;
    }

    /**
     * @return int
     */
    public function getExponent(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return ($single >> 52) & 0x7ff;
    }

    /**
     * @return int
     */
    public function getMantissa(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return $single & 0x7fffff;
    }

    /**
     * @return int
     */
    public function getSign(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return $single >> 63 ? -1 : 1;
    }

    /**
     * @param \GMP $number
     * @param int  $positions
     *
     * @return \GMP
     */
    private function rightShift(\GMP $number, int $positions): \GMP
    {
        return gmp_div($number, gmp_pow(gmp_init(2, 10), $positions));
    }

    /**
     * @param \GMP $first
     * @param \GMP $other
     *
     * @return \GMP
     */
    private function bitwiseAnd(\GMP $first, \GMP $other): \GMP
    {
        return gmp_and($first, $other);
    }
}
