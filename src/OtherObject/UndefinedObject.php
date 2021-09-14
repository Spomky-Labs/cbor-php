<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class UndefinedObject extends Base
{
    public function __construct()
    {
        parent::__construct(23, null);
    }

    public static function supportedAdditionalInformation(): array
    {
        return [23];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        return 'undefined';
    }
}
