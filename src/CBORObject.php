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

interface CBORObject
{
    /**
     * @return int
     */
    public function getMajorType(): int;

    /**
     * @return int
     */
    public function getAdditionalInformation(): int;

    /**
     * @return null|string
     */
    public function getLength(): ?string;

    /**
     * @return mixed|null
     */
    public function getData();

    /**
     * @return mixed|null
     */
    public function getNormalizedData();

    /**
     * @return string
     */
    public function __toString(): string;
}
