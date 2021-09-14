<?php

declare(strict_types=1);

namespace CBOR;

use JetBrains\PhpStorm\Pure;

abstract class TagObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b110;

    protected ?string $data;
    protected CBORObject $object;

    #[Pure]
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
        $this->object = $object;
    }

    #[Pure]
    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->data) {
            $result .= $this->data;
        }
        $result .= (string) $this->object;

        return $result;
    }

    #[Pure]
    abstract public static function getTagId(): int;

    #[Pure]
    abstract public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): self;

    #[Pure]
    public function getValue(): CBORObject
    {
        return $this->object;
    }
}
