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

final class MapObject implements CBORObject, \Countable
{
    private const MAJOR_TYPE = 0b101;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var MapItem[]
     */
    private $data;

    /**
     * @var null|string
     */
    private $length;

    /**
     * CBORObject constructor.
     *
     * @param int         $additionalInformation
     * @param null|string $length
     * @param MapItem[]   $data
     */
    private function __construct(int $additionalInformation, ?string $length, array $data)
    {
        $this->additionalInformation = $additionalInformation;
        array_map(function ($item) {
            if (!$item instanceof MapItem) {
                throw new \InvalidArgumentException('The list must contain only MapItem.');
            }
        }, $data);
        $this->data = $data;
        $this->length = $length;
    }

    /**
     * @param int         $additionalInformation
     * @param null|string $length
     * @param MapItem[]   $data
     *
     * @return MapObject
     */
    public static function create(int $additionalInformation, ?string $length, array $data): self
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
     * {@inheritdoc}
     */
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return MapItem[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getNormalizedData(): array
    {
        $result = [];
        foreach ($this->data as $object) {
            $result[$object->getKey()->getNormalizedData()] = $object->getValue()->getNormalizedData();
        }

        return $result;
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
        foreach ($this->data as $object) {
            $result .= $object->getKey()->__toString();
            $result .= $object->getValue()->__toString();
        }
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
