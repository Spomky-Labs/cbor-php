<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use InvalidArgumentException;
use function ord;
use RuntimeException;

final class Decoder implements DecoderInterface
{
    /**
     * @var Tag\TagManagerInterface
     */
    private $tagObjectManager;

    /**
     * @var OtherObject\OtherObjectManagerInterface
     */
    private $otherTypeManager;

    public function __construct(?Tag\TagManagerInterface $tagObjectManager = null, ?OtherObject\OtherObjectManagerInterface $otherTypeManager = null)
    {
        $this->tagObjectManager = $tagObjectManager ?? $this->generateTagManager();
        $this->otherTypeManager = $otherTypeManager ?? $this->generateOtherObjectManager();
    }

    public static function create(?Tag\TagManagerInterface $tagObjectManager = null, ?OtherObject\OtherObjectManagerInterface $otherTypeManager = null): self
    {
        return new self($tagObjectManager, $otherTypeManager);
    }

    public function decode(Stream $stream): CBORObject
    {
        return $this->process($stream, false);
    }

    private function process(Stream $stream, bool $breakable): CBORObject
    {
        $ib = ord($stream->read(1));
        $mt = $ib >> 5;
        $ai = $ib & 0b00011111;
        $val = null;
        switch ($ai) {
            case CBORObject::LENGTH_1_BYTE: //24
            case CBORObject::LENGTH_2_BYTES: //25
            case CBORObject::LENGTH_4_BYTES: //26
            case CBORObject::LENGTH_8_BYTES: //27
                $val = $stream->read(2 ** ($ai & 0b00000111));
                break;
            case CBORObject::FUTURE_USE_1: //28
            case CBORObject::FUTURE_USE_2: //29
            case CBORObject::FUTURE_USE_3: //30
                throw new InvalidArgumentException(sprintf('Cannot parse the data. Found invalid Additional Information "%s" (%d).', str_pad(decbin($ai), 8, '0', STR_PAD_LEFT), $ai));
            case CBORObject::LENGTH_INDEFINITE: //31
                return $this->processInfinite($stream, $mt, $breakable);
        }

        return $this->processFinite($stream, $mt, $ai, $val);
    }

    private function processFinite(Stream $stream, int $mt, int $ai, ?string $val): CBORObject
    {
        switch ($mt) {
            case CBORObject::MAJOR_TYPE_UNSIGNED_INTEGER: //0
                return UnsignedIntegerObject::createObjectForValue($ai, $val);
            case CBORObject::MAJOR_TYPE_NEGATIVE_INTEGER: //1
                return NegativeIntegerObject::createObjectForValue($ai, $val);
            case CBORObject::MAJOR_TYPE_BYTE_STRING: //2
                $length = null === $val ? $ai : Utils::binToInt($val);

                return ByteStringObject::create($stream->read($length));
            case CBORObject::MAJOR_TYPE_TEXT_STRING: //3
                $length = null === $val ? $ai : Utils::binToInt($val);

                return TextStringObject::create($stream->read($length));
            case CBORObject::MAJOR_TYPE_LIST: //4
                $object = ListObject::create();
                $nbItems = null === $val ? $ai : Utils::binToInt($val);
                for ($i = 0; $i < $nbItems; ++$i) {
                    $object->add($this->process($stream, false));
                }

                return $object;
            case CBORObject::MAJOR_TYPE_MAP: //5
                $object = MapObject::create();
                $nbItems = null === $val ? $ai : Utils::binToInt($val);
                for ($i = 0; $i < $nbItems; ++$i) {
                    $object->add($this->process($stream, false), $this->process($stream, false));
                }

                return $object;
            case CBORObject::MAJOR_TYPE_TAG: //6
                return $this->tagObjectManager->createObjectForValue($ai, $val, $this->process($stream, false));
            case CBORObject::MAJOR_TYPE_OTHER_TYPE: //7
                return $this->otherTypeManager->createObjectForValue($ai, $val);
            default:
                throw new RuntimeException(sprintf('Unsupported major type "%s" (%d).', str_pad(decbin($mt), 5, '0', STR_PAD_LEFT), $mt)); // Should never append
        }
    }

    private function processInfinite(Stream $stream, int $mt, bool $breakable): CBORObject
    {
        switch ($mt) {
            case CBORObject::MAJOR_TYPE_BYTE_STRING: //2
                $object = IndefiniteLengthByteStringObject::create();
                while (!($it = $this->process($stream, true)) instanceof OtherObject\BreakObject) {
                    if (!$it instanceof ByteStringObject) {
                        throw new RuntimeException('Unable to parse the data. Infinite Byte String object can only get Byte String objects.');
                    }
                    $object->add($it);
                }

                return $object;
            case CBORObject::MAJOR_TYPE_TEXT_STRING: //3
                $object = IndefiniteLengthTextStringObject::create();
                while (!($it = $this->process($stream, true)) instanceof OtherObject\BreakObject) {
                    if (!$it instanceof TextStringObject) {
                        throw new RuntimeException('Unable to parse the data. Infinite Text String object can only get Text String objects.');
                    }
                    $object->add($it);
                }

                return $object;
            case CBORObject::MAJOR_TYPE_LIST: //4
                $object = IndefiniteLengthListObject::create();
                while (!($it = $this->process($stream, true)) instanceof OtherObject\BreakObject) {
                    $object->add($it);
                }

                return $object;
            case CBORObject::MAJOR_TYPE_MAP: //5
                $object = IndefiniteLengthMapObject::create();
                while (!($it = $this->process($stream, true)) instanceof OtherObject\BreakObject) {
                    $object->append($it, $this->process($stream, false));
                }

                return $object;
            case CBORObject::MAJOR_TYPE_OTHER_TYPE: //7
                if (!$breakable) {
                    throw new InvalidArgumentException('Cannot parse the data. No enclosing indefinite.');
                }

                return OtherObject\BreakObject::create();
            case CBORObject::MAJOR_TYPE_UNSIGNED_INTEGER: //0
            case CBORObject::MAJOR_TYPE_NEGATIVE_INTEGER: //1
            case CBORObject::MAJOR_TYPE_TAG: //6
            default:
                throw new InvalidArgumentException(sprintf('Cannot parse the data. Found infinite length for Major Type "%s" (%d).', str_pad(decbin($mt), 5, '0', STR_PAD_LEFT), $mt));
        }
    }

    private function generateTagManager(): Tag\TagManagerInterface
    {
        return Tag\TagManager::create()
            ->add(Tag\DatetimeTag::class)
            ->add(Tag\TimestampTag::class)

            ->add(Tag\UnsignedBigIntegerTag::class)
            ->add(Tag\NegativeBigIntegerTag::class)

            ->add(Tag\DecimalFractionTag::class)
            ->add(Tag\BigFloatTag::class)

            ->add(Tag\Base64UrlEncodingTag::class)
            ->add(Tag\Base64EncodingTag::class)
            ->add(Tag\Base16EncodingTag::class)
            ->add(Tag\CBOREncodingTag::class)

            ->add(Tag\UriTag::class)
            ->add(Tag\Base64UrlTag::class)
            ->add(Tag\Base64Tag::class)
            ->add(Tag\MimeTag::class)

            ->add(Tag\CBORTag::class)
        ;
    }

    private function generateOtherObjectManager(): OtherObject\OtherObjectManagerInterface
    {
        return OtherObject\OtherObjectManager::create()
            ->add(OtherObject\BreakObject::class)
            ->add(OtherObject\SimpleObject::class)
            ->add(OtherObject\FalseObject::class)
            ->add(OtherObject\TrueObject::class)
            ->add(OtherObject\NullObject::class)
            ->add(OtherObject\UndefinedObject::class)
            ->add(OtherObject\HalfPrecisionFloatObject::class)
            ->add(OtherObject\SinglePrecisionFloatObject::class)
            ->add(OtherObject\DoublePrecisionFloatObject::class)
            ;
    }
}
