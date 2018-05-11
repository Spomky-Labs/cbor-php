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
    private $data = [];

    /**
     * CBORObject constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return TextStringWithChunkObject
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param TextStringObject $chunk
     */
    public function addChunk(TextStringObject $chunk)
    {
        $this->data[] = $chunk;
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

    /**
     * @return string
     */
    public function getValue(): string
    {
        $result = '';
        foreach ($this->data as $object) {
            $result .= $object->getValue();
        }

        return $result;
    }

    public function getLength(): int
    {
        $length = 0;
        foreach ($this->data as $object) {
            $length += $object->getLength();
        }

        return $length;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData(bool $ignoreTags = false): string
    {
        $result = '';
        foreach ($this->data as $object) {
            $result .= $object->getNormalizedData($ignoreTags);
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
