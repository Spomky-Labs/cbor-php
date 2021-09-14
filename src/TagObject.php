<?php

declare(strict_types=1);

namespace CBOR;

abstract class TagObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b110;

    /**
     * @var string|null
     */
    protected $data;

    /**
     * @var CBORObject
     */
    protected $object;

    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
        $this->object = $object;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->data) {
            $result .= $this->data;
        }
        $result .= (string) $this->object;

        return $result;
    }

    abstract public static function getTagId(): int;

    abstract public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): self;

    public function getValue(): CBORObject
    {
        return $this->object;
    }
}
