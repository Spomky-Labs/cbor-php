<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class BreakObject extends Base
{
    public function __construct()
    {
        parent::__construct(0b00011111, null);
    }

    public static function supportedAdditionalInformation(): array
    {
        return [0b00011111];
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
