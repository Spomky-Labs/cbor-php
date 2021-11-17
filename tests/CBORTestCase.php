<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Decoder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
abstract class CBORTestCase extends TestCase
{
    private ?Decoder $decoder = null;

    protected function getDecoder(): Decoder
    {
        if ($this->decoder === null) {
            $this->decoder = Decoder::create();
        }

        return $this->decoder;
    }
}
