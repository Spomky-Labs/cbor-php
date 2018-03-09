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

final class ByteStringObject implements CBORObject
{
    private const MAJOR_TYPE = 0b010;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|mixed
     */
    private $value;

    /**
     * CBORObject constructor.
     *
     * @param int    $additionalInformation
     * @param string $value
     */
    private function __construct(int $additionalInformation, string $value)
    {
        $this->additionalInformation = $additionalInformation;
        $this->value = $value;
    }

    /**
     * @param int    $additionalInformation
     * @param string $value
     *
     * @return ByteStringObject
     */
    public static function create(int $additionalInformation, string $value): self
    {
        return new self($additionalInformation, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getMajorType(): int
    {
        return self::MAJOR_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getNormalizedValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(0b01000000 | $this->additionalInformation);
        $result .= $this->value;
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
