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
     * @var null|string
     */
    private $length;

    /**
     * @var string
     */
    private $data;

    /**
     * ByteStringObject constructor.
     *
     * @param int         $additionalInformation
     * @param string|null $length
     * @param string      $data
     */
    private function __construct(int $additionalInformation, ?string $length, string $data)
    {
        $this->additionalInformation = $additionalInformation;
        $this->length = $length;
        $this->data = $data;
    }

    /**
     * @param int         $additionalInformation
     * @param string|null $length
     * @param string      $data
     *
     * @return ByteStringObject
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $length, string $data): self
    {
        return new self($additionalInformation, $length, $data);
    }

    /**
     * @param string $data
     *
     * @return ByteStringObject
     */
    public static function create(string $data): self
    {
        list($additionalInformation, $length) = LengthCalculator::getLengthOfString($data);

        return new self($additionalInformation, $length, $data);
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

    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): string
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
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
