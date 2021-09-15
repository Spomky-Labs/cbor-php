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

use CBOR\OtherObject as Base;
use CBOR\Utils;
use function chr;
use InvalidArgumentException;
use function ord;

final class SimpleObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return range(0, 19);
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        if (null !== $data && ord($data) < 32) {
            throw new InvalidArgumentException('Invalid simple value. Content data should not be present.');
        }

        return new self($additionalInformation, $data);
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        if (null === $this->data) {
            return $this->getAdditionalInformation();
        }

        return Utils::binToInt($this->data);
    }

    /**
     * @return SimpleObject
     */
    public static function create(int $value): self
    {
        switch (true) {
            case $value < 32:
                return new self($value, null);
            case $value < 256:
                return new self(24, chr($value));
            default:
                throw new InvalidArgumentException('The value is not a valid simple value');
        }
    }
}
