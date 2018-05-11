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

final class MapObject implements CBORObject, \Countable, \IteratorAggregate
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
     * @param MapItem[] $data
     */
    private function __construct(array $data)
    {
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfArray($data);
        array_map(function ($item) {
            if (!$item instanceof MapItem) {
                throw new \InvalidArgumentException('The list must contain only MapItem.');
            }
        }, $data);
        $this->data = $data;
    }

    /**
     * @param MapItem[] $data
     *
     * @return MapObject
     */
    public static function create(array $data): self
    {
        return new self($data);
    }

    public function add(CBORObject $key, CBORObject $value): void
    {
        $this->data[] = MapItem::create($key, $value);
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfArray($this->data);
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
    public function count()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
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
    public function getNormalizedData(bool $ignoreTags = false): array
    {
        $result = [];
        foreach ($this->data as $object) {
            $result[$object->getKey()->getNormalizedData($ignoreTags)] = $object->getValue()->getNormalizedData($ignoreTags);
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
