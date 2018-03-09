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

final class ByteStringWithChunkObject implements CBORObject
{
    private const MAJOR_TYPE = 0b010;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var ByteStringObject[]
     */
    private $value;

    /**
     * CBORObject constructor.
     *
     * @param int                $additionalInformation
     * @param ByteStringObject[] $value
     */
    private function __construct(int $additionalInformation, array $value)
    {
        $this->additionalInformation = $additionalInformation;
        $this->value = $value;
    }

    /**
     * @param int                $additionalInformation
     * @param ByteStringObject[] $value
     *
     * @return ByteStringWithChunkObject
     */
    public static function create(int $additionalInformation, array $value): self
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
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getNormalizedValue(): string
    {
        $result = '';
        foreach ($this->value as $object) {
            $result .= $object->getNormalizedValue();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(0b01000000 | $this->additionalInformation);
        foreach ($this->value as $object) {
            $result .= $object->__toString();
        }
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
