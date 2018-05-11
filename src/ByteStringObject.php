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
     * @var string
     */
    private $data;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|string
     */
    private $value;
    /**
     * ByteStringObject constructor.
     *
     * @param string      $data
     */
    private function __construct(string $data)
    {
        list($this->additionalInformation, $this->value) = LengthCalculator::getLengthOfString($data);
        $this->data = $data;
    }

    /**
     * @param string $data
     *
     * @return ByteStringObject
     */
    public static function create(string $data): self
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
        return $this->additionalInformation;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData(bool $ignoreTags = false): string
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(self::MAJOR_TYPE << 5 | $this->additionalInformation);
        if (null !== $this->value) {
            $result .= $this->value;
        }
        $result .= $this->data;

        return $result;
    }
}
