<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class FalseObject extends Base
{
    public function __construct()
    {
        parent::__construct(20, null);
    }

    public static function supportedAdditionalInformation(): array
    {
        return [20];
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    public function getNormalizedData(bool $ignoreTags = false): bool
    {
        return false;
    }
}
