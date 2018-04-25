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
                throw new \InvalidArgumentException('Cannot stream the data');
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
            // case 0, 1, 7 do not have content; just use val
            case 0b000: //0
                return UnsignedIntegerObject::createObjectForValue($ai, $val);
            case 0b001: //1
                return SignedIntegerObject::createObjectForValue($ai, $val);
            case 0b010: //2
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return ByteStringObject::createFromLoadedData($ai, $val, $stream->read($length));
            case 0b011: //3
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return TextStringObject::createFromLoadedData($ai, $val, $stream->read($length));
            case 0b100: //4
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = $this->process($stream);
                }

                return ListObject::createObjectForValue($ai, $val, $list);
            case 0b101: //5
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = MapItem::create($this->process($stream), $this->process($stream));
                }

                return MapObject::createObjectForValue($ai, $val, $list);
            case 0b110: //6
                return $this->tagObjectManager->createObjectForValue($ai, $val, $this->process($stream));
            case 0b111: //7
                return $this->otherTypeManager->createObjectForValue($ai, $val);
            default:
                throw new \RuntimeException('Unsupported major type'); // Should never append
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
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ByteStringWithChunkObject::createFromLoadedData($val);
            case 0b011: //3
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return TextStringWithChunkObject::createFromLoadedData($val);
            case 0b100: //4
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ListObject::createObjectForValue(0b00011111, null, $val);
            case 0b101: //5
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = MapItem::create($it, $this->process($stream));
                }

                return MapObject::createObjectForValue(0b00011111, null, $val);
            case 0b111: //7
                if ($breakable) {
                    return $this->otherTypeManager->createObjectForValue(0b00011111, null);
                } else {
                    throw new \InvalidArgumentException('Cannot stream the data');
                }
            default:
                throw new \InvalidArgumentException('Cannot stream the data');
        }
    }

    /**
     * @param CBORObject $object
     *
     * @return bool
     */
    private function isBreak(CBORObject $object): bool
    {
        return $object->getMajorType() === 0b111 && $object->getAdditionalInformation() === 0b00011111;
    }
}
