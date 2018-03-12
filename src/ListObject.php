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

final class ListObject implements CBORObject, \Countable
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
     * @param int          $additionalInformation
     * @param null|string  $length
     * @param CBORObject[] $data
     */
    private function __construct(int $additionalInformation, ?string $length, array $data)
    {
        $this->additionalInformation = $additionalInformation;
        array_map(function ($item) {
            if (!$item instanceof CBORObject) {
                throw new \InvalidArgumentException('The list must contain only CBORObjects.');
            }
        }, $data);
        $this->data = $data;
        $this->length = $length;
    }

    /**
     * @param int          $additionalInformation
     * @param null|string  $length
     * @param CBORObject[] $data
     *
     * @return ListObject
     */
    public static function createObjectForValue(int $additionalInformation, ?string $length, array $data): self
    {
        return new self($additionalInformation, $length, $data);
    }

    /**
     * @param CBORObject[] $data
     *
     * @return ListObject
     */
    public static function create(array $data): self
    {
        list($additionalInformation, $length) = LengthCalculator::getLengthOfArray($data);

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
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * @return CBORObject[]
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
        return array_map(function (CBORObject $item) {
            return $item->getNormalizedData();
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
