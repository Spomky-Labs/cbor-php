<?php

declare(strict_types=1);

namespace CBOR;

abstract class OtherObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_OTHER_TYPE;

    public function __construct(
        int $additionalInformation,
        protected ?string $data
    ) {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if ($this->data !== null) {
            $result .= $this->data;
        }

        return $result;
    }

    public function getContent(): ?string
    {
        return $this->data;
    }

    /**
     * @return int[]
     */
    abstract public static function supportedAdditionalInformation(): array;

    abstract public static function createFromLoadedData(int $additionalInformation, ?string $data): self;
}
