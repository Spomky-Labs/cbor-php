<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\TagObject as Base;
use DateTimeImmutable;

/**
 * @final
 */
class DatetimeTag extends Base
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

        $result = DateTimeImmutable::createFromFormat(DATE_RFC3339, $this->object->getNormalizedData($ignoreTags));
        if (false !== $result) {
            return $result;
        }

        return DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $this->object->getNormalizedData($ignoreTags));
    }
}
