<?php

declare(strict_types=1);

namespace CBOR;

use Brick\Math\BigInteger;
use function chr;
use function count;
use InvalidArgumentException;
use const STR_PAD_LEFT;

final class LengthCalculator
{
    /**
     * @return array{int, null|string}
     */
    public static function getLengthOfString(string $data): array
    {
        $length = mb_strlen($data, '8bit');

        return self::computeLength($length);
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array{int, null|string}
     */
    public static function getLengthOfArray(array $data): array
    {
        $length = count($data);

        return self::computeLength($length);
    }

    /**
     * @return array{int, null|string}
     */
    private static function computeLength(int $length): array
    {
        switch (true) {
            case $length <= 23:
                return [$length, null];
            case $length <= 0xFF:
                return [24, chr($length)];
            case $length <= 0xFFFF:
                return [25, self::hex2bin(dechex($length))];
            case $length <= 0xFFFFFFFF:
                return [26, self::hex2bin(dechex($length))];
            case BigInteger::of($length)->isLessThanOrEqualTo(BigInteger::fromBase('FFFFFFFFFFFFFFFF', 16)):
                return [27, self::hex2bin(dechex($length))];
            default:
                return [31, null];
        }
    }

    private static function hex2bin(string $data): string
    {
        $data = str_pad($data, (int) (2 ** ceil(log(mb_strlen($data, '8bit'), 2))), '0', STR_PAD_LEFT);
        $result = hex2bin($data);
        if ($result === false) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
