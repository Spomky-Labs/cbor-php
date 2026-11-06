<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\Tag;

/**
 * Tag 19: COSE_Countersignature, a signature over another COSE structure (RFC 9338 section 3.1).
 *
 * Content: [protected header, unprotected header, signature]. It is a COSE_Signature (RFC 9052 section 4.1),
 * that is a COSE_Sign1 without the payload: what is signed is the message the countersignature is attached to,
 * and it travels in that message's header under label 11.
 *
 * The tag is optional on the wire: RFC 9338 lets a countersignature "be encoded as either tagged or untagged,
 * depending on the context". An untagged one is a plain three-item array and decodes to a ListObject; only the
 * tagged form reaches this class.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc9338#section-3.1
 */
final class CoseCountersignatureTag extends AbstractCoseTag
{
    private const STRUCTURE_NAME = 'CoseCountersignature';

    private const ITEM_COUNT = 3;

    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        $structure = self::assertStructure($object, self::ITEM_COUNT, self::STRUCTURE_NAME);
        self::assertByteStringAt($structure, 2, self::STRUCTURE_NAME);

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_COSE_COUNTERSIGNATURE;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): self
    {
        [$ai, $data] = self::determineComponents(self::TAG_COSE_COUNTERSIGNATURE);

        return new self($ai, $data, $object);
    }

    /**
     * Builds the structure from its parts, serializing the protected header into the byte string COSE signs.
     */
    public static function createFromComponents(
        MapObject $protectedHeader,
        MapObject $unprotectedHeader,
        ByteStringObject $signature
    ): self {
        return self::create(ListObject::create([
            ByteStringObject::create((string) $protectedHeader),
            $unprotectedHeader,
            $signature,
        ]));
    }

    public function getSignature(): ByteStringObject|IndefiniteLengthByteStringObject
    {
        /** @var ByteStringObject|IndefiniteLengthByteStringObject $item */
        $item = $this->itemAt(2);

        return $item;
    }
}
