<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\CBORObject;
use CBOR\Tag;

/**
 * Every shipped tag hard-codes its own number, so the head computation can only be exercised through a tag that
 * takes the number as an argument -- the very thing doc/custom-tags.md tells implementors to write.
 *
 * @internal
 */
final class ArbitraryNumberTag extends Tag
{
    public static function getTagId(): int
    {
        return -1;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function createWithTagNumber(int $tag, CBORObject $object): self
    {
        [$additionalInformation, $data] = self::determineComponents($tag);

        return new self($additionalInformation, $data, $object);
    }
}
