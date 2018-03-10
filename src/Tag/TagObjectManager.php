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

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\TagObject;

class TagObjectManager
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
        return array_key_exists($value, $this->classes) ? $this->classes[$value] : TagObject::class;
    }

    /**
     * @param int         $additionalInformation
     * @param null|string $data
     * @param CBORObject  $object
     *
     * @return TagObject
     */
    public function createObjectForValue(int $additionalInformation, ?string $data, CBORObject $object): TagObject
    {
        $value = $additionalInformation;
        if ($additionalInformation >= 24) {
            $value = gmp_intval(gmp_init(bin2hex($data), 16));
        }
        $class = $this->getClassForValue($value);

        return $class::create($additionalInformation, $data, $object);
    }
}
