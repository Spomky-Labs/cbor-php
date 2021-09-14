<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use Brick\Math\BigInteger;
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

    public function getNormalizedData(bool $ignoreTags = false): mixed
    {
        $exp = $this->getExponent();
        $mant = $this->getMantissa();
        $sign = $this->getSign();

        if (0 === $exp) {
            $val = $mant * 2 ** (-24);
        } elseif (0b11111 !== $exp) {
            $val = ($mant + (1 << 10)) * 2 ** ($exp - 25);
        } else {
            $val = 0 === $mant ? INF : NAN;
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
