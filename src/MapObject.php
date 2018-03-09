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

final class MapObject implements CBORObject
{
    private const MAJOR_TYPE = 0b101;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var MapItem[]
     */
    private $objects;

    /**
     * @var null|string
     */
    private $value;

    /**
     * CBORObject constructor.
     *
     * @param int         $additionalInformation
     * @param null|string $value
     * @param MapItem[]   $objects
     */
    private function __construct(int $additionalInformation, ?string $value, array $objects)
    {
        $this->additionalInformation = $additionalInformation;
        array_map(function ($item) {
            if (!$item instanceof MapItem) {
                throw new \InvalidArgumentException('The list must contain only MapItem.');
            }
        }, $objects);
        $this->objects = $objects;
        $this->value = $value;
    }

    /**
     * @param int         $additionalInformation
     * @param null|string $value
     * @param MapItem[]   $objects
     *
     * @return MapObject
     */
    public static function create(int $additionalInformation, ?string $value, array $objects): self
    {
        return new self($additionalInformation, $value, $objects);
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
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return MapItem[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * @return array
     */
    public function getNormalizedValue(): array
    {
        $result = [];
        foreach ($this->objects as $object) {
            $result[$object->getKey()->getNormalizedValue()] = $object->getValue()->getNormalizedValue();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(0b10100000 | $this->additionalInformation);
        foreach ($this->objects as $object) {
            $result .= $object->getKey()->__toString();
            $result .= $object->getValue()->__toString();
        }
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
