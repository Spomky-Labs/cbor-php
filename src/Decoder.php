<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use CBOR\OtherObject\BreakObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;

final class Decoder
{
    /**
     * @var TagObjectManager
     */
    private $tagObjectManager;

    /**
     * @var OtherObjectManager
     */
    private $otherTypeManager;

    /**
     * Decoder constructor.
     *
     * @param TagObjectManager   $tagObjectManager
     * @param OtherObjectManager $otherTypeManager
     */
    public function __construct(TagObjectManager $tagObjectManager, OtherObjectManager $otherTypeManager)
    {
        $this->tagObjectManager = $tagObjectManager;
        $this->otherTypeManager = $otherTypeManager;
    }

    /**
     * @param Stream $stream
     *
     * @return CBORObject
     */
    public function decode(Stream $stream): CBORObject
    {
        return $this->process($stream);
    }

    /**
     * @param Stream $stream
     * @param bool   $breakable
     *
     * @return CBORObject
     */
    private function process(Stream $stream, bool $breakable = false): CBORObject
    {
        $ib = ord($stream->read(1));
        $mt = $ib >> 5;
        $ai = $ib & 0b00011111;
        $val = null;
        switch ($ai) {
            case 0b00011000: //24
            case 0b00011001: //25
            case 0b00011010: //26
            case 0b00011011: //27
                $val = $stream->read(pow(2, $ai & 0b00000111));
                break;
            case 0b00011100: //28
            case 0b00011101: //29
            case 0b00011110: //30
                throw new \InvalidArgumentException(sprintf('Cannot parse the data. Found invalid Additional Information "%s" (%d).', str_pad(decbin($ai), 5, '0', STR_PAD_LEFT), $ai));
            case 0b00011111: //31
                return $this->processInfinite($stream, $mt, $breakable);
        }

        return $this->processFinite($stream, $mt, $ai, $val);
    }

    /**
     * @param Stream      $stream
     * @param int         $mt
     * @param int         $ai
     * @param null|string $val
     *
     * @return CBORObject
     */
    private function processFinite(Stream $stream, int $mt, int $ai, ?string $val): CBORObject
    {
        switch ($mt) {
            case 0b000: //0
                return UnsignedIntegerObject::createObjectForValue($ai, $val);
            case 0b001: //1
                return SignedIntegerObject::createObjectForValue($ai, $val);
            case 0b010: //2
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return ByteStringObject::create($stream->read($length));
            case 0b011: //3
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return TextStringObject::create($stream->read($length));
            case 0b100: //4
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = $this->process($stream);
                }

                return ListObject::create($list);
            case 0b101: //5
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = MapItem::create($this->process($stream), $this->process($stream));
                }

                return MapObject::create($list);
            case 0b110: //6
                return $this->tagObjectManager->createObjectForValue($ai, $val, $this->process($stream));
            case 0b111: //7
                return $this->otherTypeManager->createObjectForValue($ai, $val);
            default:
                throw new \RuntimeException(sprintf('Unsupported major type "%s" (%d).', str_pad(decbin($mt), 5, '0', STR_PAD_LEFT), $mt)); // Should never append
        }
    }

    /**
     * @param Stream $stream
     * @param int    $mt
     * @param bool   $breakable
     *
     * @return CBORObject
     */
    private function processInfinite(Stream $stream, int $mt, bool $breakable): CBORObject
    {
        switch ($mt) {
            case 0b010: //2
                $object = ByteStringWithChunkObject::create();
                while (!($it = $this->process($stream, true)) instanceof BreakObject) {
                    if (!$it instanceof ByteStringObject) {
                        throw new \RuntimeException('Unable to parse the data. Infinite Byte String object can only get Byte String objects.');
                    }
                    $object->addChunk($it);
                }

                return $object;
            case 0b011: //3
                $object = TextStringWithChunkObject::create();
                while (!($it = $this->process($stream, true)) instanceof BreakObject) {
                    if (!$it instanceof TextStringObject) {
                        throw new \RuntimeException('Unable to parse the data. Infinite Text String object can only get Text String objects.');
                    }
                    $object->addChunk($it);
                }

                return $object;
            case 0b100: //4
                $object = InfiniteListObject::create();
                while (!($it = $this->process($stream, true)) instanceof BreakObject) {
                    $object->append($it);
                }

                return $object;
            case 0b101: //5
                $object = InfiniteMapObject::create();
                while (!($it = $this->process($stream, true)) instanceof BreakObject) {
                    $object->append($it, $this->process($stream));
                }

                return $object;
            case 0b111: //7
                if (!$breakable) {
                    throw new \InvalidArgumentException('Cannot parse the data. No enclosing indefinite.');
                }

                return BreakObject::create();
            case 0b000: //0
            case 0b001: //1
            case 0b110: //6
            default:
                throw new \InvalidArgumentException(sprintf('Cannot parse the data. Found infinite length for Major Type "%s" (%d).', str_pad(decbin($mt), 5, '0', STR_PAD_LEFT), $mt));
        }
    }
}
