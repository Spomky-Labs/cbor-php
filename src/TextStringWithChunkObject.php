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
        array_map(function ($obj) {
            if (!$obj instanceof TextStringObject) {
                throw new \InvalidArgumentException('The data must be an array of TextStringObject objects.');
            }
        }, $data);
        $this->data = $data;
    }

    /**
     * @param string $data
     *
     * @return TextStringWithChunkObject
     */
    public static function create(string $data = ''): self
    {
        $chunks = [];
        if (!empty($data)) {
            $chunks[] = TextStringObject::create($data);
        }

        return new self($chunks);
    }

    /**
     * @param string $chunk
     */
    public function append(string $chunk)
    {
        $this->addChunk(TextStringObject::create($chunk));
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
