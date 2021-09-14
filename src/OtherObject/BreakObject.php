<?php

declare(strict_types=1);

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;
use JetBrains\PhpStorm\Pure;

final class BreakObject extends Base
{
    #[Pure]
    public function __construct()
    {
        parent::__construct(0b00011111, null);
    }

    #[Pure]
    public static function supportedAdditionalInformation(): array
    {
        return [0b00011111];
    }

    #[Pure]
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self();
    }

    #[Pure]
    public function getNormalizedData(bool $ignoreTags = false): bool
    {
        return false;
    }
}
