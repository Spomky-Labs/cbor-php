<?php

declare(strict_types=1);

namespace CBOR\Tag;

<<<<<<< HEAD
use CBOR\CBORObject;
use CBOR\TagObject as Base;
use DateTimeImmutable;
use JetBrains\PhpStorm\Pure;

final class EpochTag extends Base
{
    public static function getTagId(): int
    {
        return 0;
    }

     public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
     {
         return new self($additionalInformation, $data, $object);
     }

    public static function create(CBORObject $object): Base
    {
        return new self(0, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return DateTimeImmutable::createFromFormat(DATE_RFC3339, $this->object->getNormalizedData($ignoreTags));
    }
=======
/**
 * @deprecated The class EpochTag is deprecated and will be removed in v3.0. Please use DatetimeTag instead
 */
final class EpochTag extends DatetimeTag
{
>>>>>>> v2.0
}
