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

abstract class TagObject implements CBORObject
{
    private const MAJOR_TYPE = 0b110;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|string
     */
    private $data;

    /**
     * @var CBORObject
     */
    private $object;

    /**
     * CBORObject constructor.
     *
     * @param int         $additionalInformation
     * @param null|string $data
     * @param CBORObject  $object
     */
    protected function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        $this->additionalInformation = $additionalInformation;
        $this->data = $data;
        $this->object = $object;
    }

    /**
     * @return int
     */
    abstract public static function getTagId(): int;

    /**
     * @param int         $additionalInformation
     * @param null|string $data
     * @param CBORObject  $object
     *
     * @return TagObject
     */
    abstract public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): self;

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
    public function getData(): CBORObject
    {
        return $this->object;
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
        return $this->object->getNormalizedData();
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
        $result .= $this->object->__toString();

        return $result;
    }
}
