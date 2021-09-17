<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use CBOR\Decoder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BaseTestCase extends TestCase
{
    /**
     * @var Decoder|null
     */
    private $decoder;

    protected function getDecoder(): Decoder
    {
        if (null === $this->decoder) {
            $this->decoder = Decoder::create();
        }

        return $this->decoder;
    }
}
