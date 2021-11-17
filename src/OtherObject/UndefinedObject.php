<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class UndefinedObject extends Base
{
    public function __construct()
    {
        parent::__construct(self::OBJECT_UNDEFINED, null);
    }

    public static function create(): self
    {
        return new self();
    }

    public static function supportedAdditionalInformation(): array
    {
        return [self::OBJECT_UNDEFINED];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    /**
     * @deprecated The method will be removed on v3.0. Please use CBOR\Normalizable interface
     *
     * @return string
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        return 'undefined';
    }
}
