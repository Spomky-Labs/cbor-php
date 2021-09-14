<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\ByteStringObject;
use CBOR\ByteStringWithChunkObject;
use CBOR\CBORObject;
use CBOR\TagObject as Base;
use CBOR\TextStringObject;
use CBOR\TextStringWithChunkObject;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

final class Base16EncodingTag extends Base
{
    #[Pure]
    public static function getTagId(): int
    {
        return 23;
    }

    #[Pure]
    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof ByteStringObject && !$object instanceof ByteStringWithChunkObject && !$object instanceof TextStringObject && !$object instanceof TextStringWithChunkObject) {
            throw new InvalidArgumentException('This tag only accepts Byte String, Infinite Byte String, Text String or Infinite Text String objects.');
        }

        return new self(23, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false): mixed
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        if (!$this->object instanceof ByteStringObject && !$this->object instanceof ByteStringWithChunkObject && !$this->object instanceof TextStringObject && !$this->object instanceof TextStringWithChunkObject) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return bin2hex($this->object->getNormalizedData($ignoreTags));
    }
}
