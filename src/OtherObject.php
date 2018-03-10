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

class OtherObject implements CBORObject
{
    private const MAJOR_TYPE = 0b111;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|string
     */
    private $data;

    /**
     * CBORObject constructor.
     *
     * @param int         $additionalInformation
     * @param null|string $data
     */
    protected function __construct(int $additionalInformation, ?string $data)
    {
        $this->additionalInformation = $additionalInformation;
        $this->data = $data;
    }

    /**
     * @param int         $additionalInformation
     * @param null|string $data
     *
     * @return OtherObject
     */
    public static function create(int $additionalInformation, ?string $data): self
    {
        return new self($additionalInformation, $data);
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
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(self::MAJOR_TYPE << 5 | $this->additionalInformation);
        if (null !== $this->data) {
            $result .= $this->data;
        }

        return $result;
    }
}
