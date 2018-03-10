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

final class TextStringWithChunkObject implements CBORObject
{
    private const MAJOR_TYPE = 0b011;
    private const ADDITIONAL_INFORMATION = 0b00011111;

    /**
     * @var TextStringObject[]
     */
    private $data;

    /**
     * CBORObject constructor.
     *
     * @param TextStringObject[] $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param TextStringObject[] $data
     *
     * @return TextStringWithChunkObject
     */
    public static function create(array $data): self
    {
        return new self($data);
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
        return self::ADDITIONAL_INFORMATION;
    }

    public function getLength(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getNormalizedData(): string
    {
        $result = '';
        foreach ($this->data as $object) {
            $result .= $object->getNormalizedData();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(self::MAJOR_TYPE << 5 | self::ADDITIONAL_INFORMATION);
        foreach ($this->data as $object) {
            $result .= $object->__toString();
        }
        $result .= hex2bin('FF');

        return $result;
    }
}
