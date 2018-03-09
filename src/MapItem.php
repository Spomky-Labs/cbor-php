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

class MapItem
{
    /**
     * @var CBORObject
     */
    private $key;

    /**
     * @var CBORObject
     */
    private $value;

    /**
     * MapItem constructor.
     *
     * @param CBORObject $key
     * @param CBORObject $value
     */
    private function __construct(CBORObject $key, CBORObject $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @param CBORObject $key
     * @param CBORObject $value
     *
     * @return MapItem
     */
    public static function create(CBORObject $key, CBORObject $value): self
    {
        return new self($key, $value);
    }

    /**
     * @return CBORObject
     */
    public function getKey(): CBORObject
    {
        return $this->key;
    }

    /**
     * @return CBORObject
     */
    public function getValue(): CBORObject
    {
        return $this->value;
    }
}
