<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;
use JetBrains\PhpStorm\Pure;

final class GenericObject extends Base
{
    #[Pure]
    public static function supportedAdditionalInformation(): array
    {
        return [];
    }

    #[Pure]
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    public function getNormalizedData(bool $ignoreTags = false): ?string
    {
        return $this->data;
    }
}
