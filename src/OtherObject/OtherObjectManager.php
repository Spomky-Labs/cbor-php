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

namespace CBOR\OtherObject;

use CBOR\OtherObject;

class OtherObjectManager
{
    /**
     * @var string[]
     */
    private $classes = [];

    /**
     * @param int    $value
     * @param string $class
     */
    public function add(int $value, string $class)
    {
        $this->classes[$value] = $class;
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function getClassForValue(int $value): string
    {
        return array_key_exists($value, $this->classes) ? $this->classes[$value] : OtherObject::class;
    }

    /**
     * @param int         $value
     * @param null|string $data
     *
     * @return OtherObject
     */
    public function createObjectForValue(int $value, ?string $data): OtherObject
    {
        $class = $this->getClassForValue($value);

        return $class::create($value, $data);
    }
}
