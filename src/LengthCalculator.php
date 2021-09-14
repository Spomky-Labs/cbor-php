<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 * Copyright (c) 2018-2020 Spomky-Labs
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use Brick\Math\BigInteger;
use function chr;
use function count;
use InvalidArgumentException;

final class LengthCalculator
{
    public static function getLengthOfString(string $data): array
    {
        $length = mb_strlen($data, '8bit');

        return self::computeLength($length);
    }

    public static function getLengthOfArray(array $data): array
    {
        $length = count($data);

        return self::computeLength($length);
    }

    private static function computeLength(int $length): array
    {
        return match (true) {
            $length < 24 => [$length, null],
            $length < 0xFF => [24, chr($length)],
            $length < 0xFFFF => [25, self::hex2bin(static::fixHexLength(Utils::intToHex($length)))],
            $length < 0xFFFFFFFF => [26, self::hex2bin(static::fixHexLength(Utils::intToHex($length)))],
            BigInteger::of($length)->isLessThan(BigInteger::fromBase('FFFFFFFFFFFFFFFF', 16)) => [27, self::hex2bin(self::fixHexLength(Utils::intToHex($length)))],
            default => [31, null],
        };
    }

    private static function hex2bin(string $data): string
    {
        $result = hex2bin($data);
        if (false === $result) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }

    private static function fixHexLength(string $data): string
    {
        return str_pad($data, (int) (2 ** ceil(log(mb_strlen($data, '8bit'), 2))), '0', STR_PAD_LEFT);
    }
}
