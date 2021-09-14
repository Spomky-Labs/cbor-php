<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class GenericObject extends Base
{
    public static function supportedAdditionalInformation(): array
    {
        return [];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public function getNormalizedData(bool $ignoreTags = false): ?string
    {
        return $this->data;
    }
}
