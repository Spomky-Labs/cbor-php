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

final class SimpleValueObject extends Base
{
    /**
     * @param int         $additionalInformation
     * @param null|string $data
     *
     * @return SimpleValueObject
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * @param int $value
     *
     * @return SimpleValueObject
     */
    public static function createFromInteger(int $value): SimpleValueObject
    {
        if ($value > 255) {
            throw new \InvalidArgumentException('The value is not a valid simple value');
        }

        return self::create(chr($value));
    }

    /**
     * @param string $value
     *
     * @return SimpleValueObject
     */
    public static function create(string $value): SimpleValueObject
    {
        if (mb_strlen($value, '8bit') !== 1) {
            throw new \InvalidArgumentException('The value is not a valid simple value');
        }

        $ai = ord($value);
        if ($ai > 23) {
            $ai = 24;
            $data = $value;
        } else {
            $data = null;
        }

        return new self($ai, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        if (null === $this->getData()) {
            return $this->getAdditionalInformation();
        }
        return gmp_intval(gmp_init(bin2hex($this->getData()), 16));
    }
}
