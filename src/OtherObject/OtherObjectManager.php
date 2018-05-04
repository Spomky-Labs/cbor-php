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
     * @param string $class
     */
    public function add(string $class)
    {
        foreach ($class::supportedAdditionalInformation() as $ai) {
            $this->classes[$ai] = $class;
        }
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function getClassForValue(int $value): string
    {
        return array_key_exists($value, $this->classes) ? $this->classes[$value] : GenericObject::class;
    }

    /**
     * @param int         $value
     * @param null|string $data
     *
     * @return OtherObject
     */
    public function createObjectForValue(int $value, ?string $data): OtherObject
    {
        /** @var OtherObject $class */
        $class = $this->getClassForValue($value);

        return $class::createFromLoadedData($value, $data);
    }
}
