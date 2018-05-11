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

final class TextStringObject implements CBORObject
{
    private const MAJOR_TYPE = 0b011;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|string
     */
    private $length;

    /**
     * @var null|mixed
     */
    private $data;

    /**
     * CBORObject constructor.
     *
     * @param string $data
     */
    private function __construct(string $data)
    {
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfString($data);

        $this->data = $data;
    }

    /**
     * @param string $data
     *
     * @return TextStringObject
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
        if (null !== $this->length) {
            $result .= $this->length;
        }
        $result .= $this->data;

        return $result;
    }
}
