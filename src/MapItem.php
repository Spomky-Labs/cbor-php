<?php

declare(strict_types=1);

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

    public function __construct(CBORObject $key, CBORObject $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): CBORObject
    {
        return $this->key;
    }

    public function getValue(): CBORObject
    {
        return $this->value;
    }
}
