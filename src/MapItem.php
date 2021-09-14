<?php

declare(strict_types=1);

namespace CBOR;

use JetBrains\PhpStorm\Pure;

class MapItem
{
    private CBORObject $key;
    private CBORObject $value;

    #[Pure]
    public function __construct(CBORObject $key, CBORObject $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    #[Pure]
    public function getKey(): CBORObject
    {
        return $this->key;
    }

    #[Pure]
    public function getValue(): CBORObject
    {
        return $this->value;
    }
}
