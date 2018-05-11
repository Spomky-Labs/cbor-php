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

final class InfiniteMapObject implements CBORObject, \Countable, \IteratorAggregate
{
    private const MAJOR_TYPE = 0b101;
    private const ADDITIONAL_INFORMATION = 0b00011111;

    /**
     * @var MapItem[]
     */
    private $data = [];

    /**
     * CBORObject constructor.
     *
     * @param MapItem[] $data
     */
    private function __construct(array $data)
    {
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
     * @return InfiniteMapObject
     */
    public static function create(array $data = []): self
    {
        return new self($data);
    }

    /**
     * @param CBORObject $key
     * @param CBORObject $value
     */
    public function append(CBORObject $key, CBORObject $value)
    {
        $this->data[] = MapItem::create($key, $value);
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
        return self::ADDITIONAL_INFORMATION;
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
        $result = chr(self::MAJOR_TYPE << 5 | self::ADDITIONAL_INFORMATION);
        foreach ($this->data as $object) {
            $result .= $object->getKey()->__toString();
            $result .= $object->getValue()->__toString();
        }
        $result .= hex2bin('FF');

        return $result;
    }
}
