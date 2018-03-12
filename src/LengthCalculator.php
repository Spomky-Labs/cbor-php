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

namespace CBOR;

final class LengthCalculator
{
    /**
     * @param string $data
     *
     * @return array
     */
    public static function getLengthOfString(string $data): array
    {
        $length = mb_strlen($data, '8bit');

        return self::computeLength($length);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getLengthOfArray(array $data): array
    {
        $length = count($data);

        return self::computeLength($length);
    }

    /**
     * @param int $length
     *
     * @return array
     */
    private static function computeLength(int $length): array
    {
        switch (true) {
            case $length < 24:
                return [$length, null];
            case $length < 0xFF:
                return [24, chr($length)];
            case $length < 0xFFFF:
                return [25, hex2bin(gmp_strval(gmp_init($length)))];
            case $length < 0xFFFFFFFF:
                return [26, hex2bin(gmp_strval(gmp_init($length)))];
            case -1 == gmp_cmp(gmp_init($length), gmp_init('FFFFFFFFFFFFFFFF', 16)):
                return [27, hex2bin(gmp_strval(gmp_init($length)))];
            default:
                return [31, null];
        }
    }
}
