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

class ListObject implements CBORObject, \Countable, \IteratorAggregate
{
    private const MAJOR_TYPE = 0b100;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var CBORObject[]
     */
    private $data;

    /**
     * @var null|string
     */
    private $length;

    /**
     * CBORObject constructor.
     *
     * @param CBORObject[] $data
     */
    private function __construct(array $data)
    {
        list($this->additionalInformation, $this->length) = LengthCalculator::getLengthOfArray($data);
        array_map(function ($item) {
            if (!$item instanceof CBORObject) {
                throw new \InvalidArgumentException('The list must contain only CBORObjects.');
            }
        }, $data);
        $this->data = $data;
    }

    /**
     * @param CBORObject[] $data
     *
     * @return ListObject
     */
    public static function create(array $data): self
    {
        return new self($data);
    }

    /**
     * {@inheritdoc}
     */
    public function add(CBORObject $object): void
    {
        $this->data[] = $object;
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
     * @param int $index
     *
     * @return CBORObject
     */
    public function get(int $index): CBORObject
    {
        if (!array_key_exists($index, $this->data)) {
            throw new \InvalidArgumentException('Index not found.');
        }

        return $this->data[$index];
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
        return array_map(function (CBORObject $item) use ($ignoreTags) {
            return $item->getNormalizedData($ignoreTags);
        }, $this->data);
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
    public function __toString(): string
    {
        $result = chr(self::MAJOR_TYPE << 5 | $this->additionalInformation);
        if (null !== $this->length) {
            $result .= $this->length;
        }
        foreach ($this->data as $object) {
            $result .= $object->__toString();
        }
        if (0b00011111 === $this->additionalInformation) {
            $result .= hex2bin('FF');
        }

        return $result;
    }
}
