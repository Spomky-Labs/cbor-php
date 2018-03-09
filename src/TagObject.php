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

class TagObject implements CBORObject
{
    private const MAJOR_TYPE = 0b110;

    /**
     * @var int
     */
    private $additionalInformation;

    /**
     * @var null|string
     */
    private $value;

    /**
     * @var CBORObject
     */
    private $object;

    /**
     * CBORObject constructor.
     *
     * @param int         $additionalInformation
     * @param null|string $value
     * @param CBORObject  $object
     */
    private function __construct(int $additionalInformation, ?string $value, CBORObject $object)
    {
        $this->additionalInformation = $additionalInformation;
        $this->value = $value;
        $this->object = $object;
    }

    /**
     * @param int         $additionalInformation
     * @param null|string $value
     * @param CBORObject  $object
     *
     * @return TagObject
     */
    public static function create(int $additionalInformation, ?string $value, CBORObject $object): self
    {
        return new self($additionalInformation, $value, $object);
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
    public function getValue(): CBORObject
    {
        return $this->object;
    }

    /**
     * @return CBORObject
     */
    public function getObject(): CBORObject
    {
        return $this->object;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedValue()
    {
        return $this->object->getNormalizedValue();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $result = chr(0b11000000 | $this->additionalInformation);
        if (null !== $this->value) {
            $result .= $this->value;
        }
        $result .= $this->object->__toString();

        return $result;
    }
}
