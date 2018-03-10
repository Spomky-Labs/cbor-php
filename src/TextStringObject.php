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
     * @return TextStringObject
     */
    public static function create(int $additionalInformation, ?string $length, string $data): self
    {
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

    /**
     * @return null|string
     */
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
     * @return string
     */
    public function getNormalizedData(): string
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
