<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\TagObject as Base;
use DateTimeImmutable;
use JetBrains\PhpStorm\Pure;

final class EpochTag extends Base
{
    #[Pure]
    public static function getTagId(): int
    {
        return 0;
    }

    #[Pure]
     public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
     {
         return new self($additionalInformation, $data, $object);
     }

    #[Pure]
    public static function create(CBORObject $object): Base
    {
        return new self(0, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false): mixed
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return DateTimeImmutable::createFromFormat(DATE_RFC3339, $this->object->getNormalizedData($ignoreTags));
    }
}
