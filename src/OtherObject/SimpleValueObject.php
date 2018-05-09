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
     * {@inheritdoc}
     */
    public static function supportedAdditionalInformation(): array
    {
        return [24];
    }

    /**
     * {@inheritdoc}
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
    public static function createFromInteger(int $value): self
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
    public static function create(string $value): self
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
    public function getNormalizedData(bool $ignoreTags = false)
    {
        if (null === $this->getData()) {
            return $this->getAdditionalInformation();
        }

        return gmp_intval(gmp_init(bin2hex($this->getData()), 16));
    }
}
