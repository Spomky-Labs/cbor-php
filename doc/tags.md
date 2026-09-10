# CBOR Tags Reference

CBOR tags (Major Type 6) provide semantic information about data values. Tags are integers that prefix a data item to give it additional meaning according to a registry maintained by IANA.

## Table of Contents

- [Overview](#overview)
- [Built-in Tags](#built-in-tags)
  - [Date/Time Tags](#datetime-tags)
  - [Big Number Tags](#big-number-tags)
  - [Fractional Number Tags](#fractional-number-tags)
  - [Encoded Data Tags](#encoded-data-tags)
  - [Semantic Tags](#semantic-tags)
  - [Special CBOR Tags](#special-cbor-tags)
  - [COSE and CWT Tags](#cose-and-cwt-tags)
  - [Date Tags](#date-tags)
  - [Address Tags](#address-tags)
  - [Typed Array Tags](#typed-array-tags)
  - [Other Registry Tags](#other-registry-tags)
- [Using Tags](#using-tags)
- [Creating Custom Tags](#creating-custom-tags)
- [IANA Registry](#iana-registry)

## Overview

Tags in CBOR follow this structure:
```
Tag(tag_number, data_item)
```

Where:
- `tag_number` is an integer identifying the semantic meaning
- `data_item` is any CBOR object that the tag applies to

This library implements every tag of the IANA registry listed in the [summary table](#tag-summary-table) below,
and hands any other tag number to a generic handler that keeps the number and the item without interpreting them.

All of them are registered in the decoder by default. A tag whose content does not match its definition -- a URI
that is not a text string, a set that is not an array -- is rejected with an `InvalidArgumentException` rather
than decoded into something it is not. Registering the classes costs nothing until they are used: the decoder
files them by tag number and only loads the one a document actually mentions.

## Built-in Tags

### Date/Time Tags

#### Tag 0: Date/Time String (RFC 3339)

Encodes a date/time string in RFC 3339 format.

**Class:** `CBOR\Tag\DatetimeTag`
**Spec:** [RFC 8949 § 3.4.1](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.1)

```php
use CBOR\Tag\DatetimeTag;
use CBOR\TextStringObject;

// Create from RFC 3339 string
$tag = DatetimeTag::create(
    TextStringObject::create('2024-01-15T10:30:00Z')
);

// Normalize to PHP DateTimeImmutable
$dateTime = $tag->normalize();
echo $dateTime->format('Y-m-d H:i:s'); // 2024-01-15 10:30:00
```

**Accepts:** `TextStringObject` or `IndefiniteLengthTextStringObject`
**Returns:** `DateTimeImmutable` when normalized

A date-time that does not exist (`2013-02-30T12:00:00Z`, a twenty-fifth hour) is rejected with an
`InvalidArgumentException` rather than silently rolled over to the following day. A leap second is accepted and
reported as the instant that follows it, since PHP dates cannot hold one.

---

#### Tag 1: Epoch-Based Date/Time

Encodes a POSIX timestamp (seconds since Unix epoch: 1970-01-01 00:00:00 UTC).

**Class:** `CBOR\Tag\TimestampTag`
**Spec:** [RFC 8949 § 3.4.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.2)

```php
use CBOR\Tag\TimestampTag;
use CBOR\UnsignedIntegerObject;

// Create from Unix timestamp
$tag = TimestampTag::create(
    UnsignedIntegerObject::create(time())
);

// Normalize to PHP DateTimeImmutable
$dateTime = $tag->normalize();
echo $dateTime->format('c'); // ISO 8601 format
```

**Accepts:** `UnsignedIntegerObject`, `NegativeIntegerObject`, or float objects
**Returns:** `DateTimeImmutable` when normalized

A float timestamp is converted to the nearest microsecond. `NAN`, `INF` and magnitudes no date object can hold are
rejected with an `InvalidArgumentException`.

---

### Big Number Tags

#### Tag 2: Unsigned Bignum

Encodes an arbitrarily large positive integer as a byte string.

**Class:** `CBOR\Tag\UnsignedBigIntegerTag`
**Spec:** [RFC 8949 § 3.4.3](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.3)

```php
use CBOR\Tag\UnsignedBigIntegerTag;
use CBOR\ByteStringObject;

// The byte string contains the big-endian representation
$tag = UnsignedBigIntegerTag::create(
    ByteStringObject::create(hex2bin('0123456789ABCDEF'))
);

// Normalize to string representation
$value = $tag->normalize(); // "81985529216486895"
```

**Accepts:** `ByteStringObject` or `IndefiniteLengthByteStringObject`
**Returns:** Numeric string when normalized

An empty byte string is the preferred serialization of zero (RFC 8949 § 3.4.3): tag 2 normalizes it to `"0"`
and tag 3 to `"-1"`.

The byte string shall not exceed `MAX_BYTE_LENGTH` (256 bytes, i.e. a 2048 bit integer); a longer one is rejected
with an `InvalidArgumentException`. Converting it to the decimal string `normalize()` returns is a base conversion,
which `brick/math` performs in time quadratic in the length on every calculator but GMP. Install `ext-gmp` when the
input is untrusted.

---

#### Tag 3: Negative Bignum

Encodes an arbitrarily large negative integer. The value is -1 minus the unsigned integer encoded in the byte string.

**Class:** `CBOR\Tag\NegativeBigIntegerTag`
**Spec:** [RFC 8949 § 3.4.3](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.3)

```php
use CBOR\Tag\NegativeBigIntegerTag;
use CBOR\ByteStringObject;

// Represents: -1 - unsigned_value
$tag = NegativeBigIntegerTag::create(
    ByteStringObject::create(hex2bin('FF'))
);

$value = $tag->normalize(); // "-256"
```

**Accepts:** `ByteStringObject` or `IndefiniteLengthByteStringObject`
**Returns:** Numeric string when normalized

An empty byte string is the preferred serialization of zero (RFC 8949 § 3.4.3): tag 2 normalizes it to `"0"`
and tag 3 to `"-1"`.

The byte string shall not exceed `MAX_BYTE_LENGTH` (256 bytes, i.e. a 2048 bit integer); a longer one is rejected
with an `InvalidArgumentException`. Converting it to the decimal string `normalize()` returns is a base conversion,
which `brick/math` performs in time quadratic in the length on every calculator but GMP. Install `ext-gmp` when the
input is untrusted.

---

### Fractional Number Tags

#### Tag 4: Decimal Fraction

Encodes a decimal fraction as: **mantissa × 10^exponent**

**Class:** `CBOR\Tag\DecimalFractionTag`
**Spec:** [RFC 8949 § 3.4.4](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.4)
**Requires:** `ext-bcmath`

**Bound:** the absolute value of the exponent shall not exceed `MAX_ABSOLUTE_EXPONENT` (1024); beyond it the tag is
rejected with an `InvalidArgumentException`. `10^e` needs about `e` digits to write down, so an unbounded exponent
lets a six byte item expand into kilobytes.

```php
use CBOR\Tag\DecimalFractionTag;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;

// Method 1: From exponent and mantissa
// Represents: 1234 × 10^(-2) = 12.34
$tag = DecimalFractionTag::createFromExponentAndMantissa(
    NegativeIntegerObject::create(-2),  // exponent
    UnsignedIntegerObject::create(1234) // mantissa
);

echo $tag->normalize(); // "12.34"

// Method 2: From PHP float
$tag = DecimalFractionTag::createFromFloat(3.14159, precision: 5);
echo $tag->normalize(); // "3.14159"

// Method 3: From list
use CBOR\ListObject;

$tag = DecimalFractionTag::create(
    ListObject::create([
        NegativeIntegerObject::create(-2),
        UnsignedIntegerObject::create(1234)
    ])
);
```

**Accepts:** `ListObject` or `IndefiniteLengthListObject` with exactly 2 elements [exponent, mantissa]
**Returns:** Exact string representation of the decimal value when normalized

---

#### Tag 5: Bigfloat

Encodes a binary floating-point value as: **mantissa × 2^exponent**

**Class:** `CBOR\Tag\BigFloatTag`
**Spec:** [RFC 8949 § 3.4.4](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.4)
**Requires:** `ext-bcmath`

**Bound:** the absolute value of the exponent shall not exceed `MAX_ABSOLUTE_EXPONENT` (1024); beyond it the tag is
rejected with an `InvalidArgumentException`. `2^-e` needs exactly `e` digits to write down, so an unbounded exponent
lets a six byte item expand into kilobytes.

```php
use CBOR\Tag\BigFloatTag;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;

// Represents: 5 × 2^(-2) = 1.25
$tag = BigFloatTag::createFromExponentAndMantissa(
    NegativeIntegerObject::create(-2),  // exponent (base 2)
    UnsignedIntegerObject::create(5)    // mantissa
);

echo $tag->normalize(); // "1.25"

// From PHP float
$tag = BigFloatTag::createFromFloat(3.14159);
```

**Accepts:** `ListObject` with exactly 2 elements [exponent, mantissa]
**Returns:** String representation of the value when normalized

---

### Encoded Data Tags

#### Tag 21: Base64url Encoding (Expected Conversion)

Indicates that the byte string should be encoded as base64url when converted to text.

**Class:** `CBOR\Tag\Base64UrlEncodingTag`
**Spec:** [RFC 8949 § 3.4.5.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.2)

```php
use CBOR\Tag\Base64UrlEncodingTag;
use CBOR\ByteStringObject;

$tag = Base64UrlEncodingTag::create(
    ByteStringObject::create('Hello World!')
);

// The tag is only a hint: the byte string is stored as-is and the conversion is left to the application.
$bytes = $tag->getValue()->normalize(); // "Hello World!"
$encoded = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); // "SGVsbG8gV29ybGQh"
```

**Accepts:** `ByteStringObject` or `IndefiniteLengthByteStringObject`
**Returns:** the tagged object through `getValue()`; the tag does not implement `Normalizable`

---

#### Tag 22: Base64 Encoding (Expected Conversion)

Indicates that the byte string should be encoded as base64 when converted to text.

**Class:** `CBOR\Tag\Base64EncodingTag`
**Spec:** [RFC 8949 § 3.4.5.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.2)

```php
use CBOR\Tag\Base64EncodingTag;
use CBOR\ByteStringObject;

$tag = Base64EncodingTag::create(
    ByteStringObject::create('Hello World!')
);

$bytes = $tag->getValue()->normalize(); // "Hello World!"
$encoded = base64_encode($bytes); // "SGVsbG8gV29ybGQh"
```

**Accepts:** `ByteStringObject` or `IndefiniteLengthByteStringObject`
**Returns:** the tagged object through `getValue()`; the tag does not implement `Normalizable`

---

#### Tag 23: Base16 Encoding (Expected Conversion)

Indicates that the byte string should be encoded as hexadecimal when converted to text.

**Class:** `CBOR\Tag\Base16EncodingTag`
**Spec:** [RFC 8949 § 3.4.5.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.2)

```php
use CBOR\Tag\Base16EncodingTag;
use CBOR\ByteStringObject;

$tag = Base16EncodingTag::create(
    ByteStringObject::create('Hello')
);

$bytes = $tag->getValue()->normalize(); // "Hello"
$encoded = bin2hex($bytes); // "48656c6c6f"
```

**Accepts:** `ByteStringObject` or `IndefiniteLengthByteStringObject`
**Returns:** the tagged object through `getValue()`; the tag does not implement `Normalizable`

---

### Semantic Tags

#### Tag 32: URI

Indicates that the text string contains a URI as defined by RFC 3986.

**Class:** `CBOR\Tag\UriTag`
**Spec:** [RFC 8949 § 3.4.5.3](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.3)

```php
use CBOR\Tag\UriTag;
use CBOR\TextStringObject;

$tag = UriTag::create(
    TextStringObject::create('https://example.com/path?query=value')
);

$uri = $tag->normalize(); // "https://example.com/path?query=value"
```

**Accepts:** `TextStringObject` or `IndefiniteLengthTextStringObject`
**Returns:** URI string when normalized

---

#### Tag 33: Base64url Encoded Text

The text string contains data that is already base64url encoded.

**Class:** `CBOR\Tag\Base64UrlTag`
**Spec:** [RFC 8949 § 3.4.5.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.2)

```php
use CBOR\Tag\Base64UrlTag;
use CBOR\TextStringObject;

$tag = Base64UrlTag::create(
    TextStringObject::create('SGVsbG8gV29ybGQh')
);
```

**Accepts:** `TextStringObject` or `IndefiniteLengthTextStringObject`

---

#### Tag 34: Base64 Encoded Text

The text string contains data that is already base64 encoded.

**Class:** `CBOR\Tag\Base64Tag`
**Spec:** [RFC 8949 § 3.4.5.2](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.2)

```php
use CBOR\Tag\Base64Tag;
use CBOR\TextStringObject;

$tag = Base64Tag::create(
    TextStringObject::create('SGVsbG8gV29ybGQh')
);
```

**Accepts:** `TextStringObject` or `IndefiniteLengthTextStringObject`

---

#### Tag 36: MIME Message

Indicates that the byte string or text string contains a MIME message (including email).

**Class:** `CBOR\Tag\MimeTag`
**Spec:** [RFC 8949 § 3.4.5.3](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.3)

```php
use CBOR\Tag\MimeTag;
use CBOR\TextStringObject;

$mimeMessage = "Content-Type: text/plain\r\n\r\nHello World!";

$tag = MimeTag::create(
    TextStringObject::create($mimeMessage)
);
```

**Accepts:** `TextStringObject`, `IndefiniteLengthTextStringObject`, `ByteStringObject`, or `IndefiniteLengthByteStringObject`

---

### Special CBOR Tags

#### Tag 24: Encoded CBOR Data Item

Indicates that the byte string contains a CBOR-encoded data item.

**Class:** `CBOR\Tag\CBOREncodingTag`
**Spec:** [RFC 8949 § 3.4.5.1](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.5.1)

```php
use CBOR\Tag\CBOREncodingTag;
use CBOR\ByteStringObject;
use CBOR\UnsignedIntegerObject;

// Encode a CBOR object
$innerObject = UnsignedIntegerObject::create(42);
$encoded = (string) $innerObject;

// Wrap it in Tag 24
$tag = CBOREncodingTag::create(
    ByteStringObject::create($encoded)
);

// Can be decoded later
$decoder = Decoder::create();
$decoded = $decoder->decode(StringStream::create($tag->getValue()->getValue()));
```

**Accepts:** `ByteStringObject`

---

#### Tag 55799: Self-Described CBOR

A "magic number" that marks the beginning of a CBOR data stream. This helps decoders quickly identify CBOR-encoded data.

**Class:** `CBOR\Tag\CBORTag`
**Spec:** [RFC 8949 § 3.4.6](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.6)
**Tag Number:** `55799` (0xd9d9f7 in hex)

```php
use CBOR\Tag\CBORTag;
use CBOR\MapObject;
use CBOR\TextStringObject;

// Wrap any CBOR object with self-describe tag
$data = MapObject::create()
    ->add(TextStringObject::create('key'), TextStringObject::create('value'));

$selfDescribed = CBORTag::create($data);

// When encoded, this will start with the bytes: d9 d9 f7
$encoded = (string) $selfDescribed;

// Retrieve the wrapped object
$innerObject = $selfDescribed->getValue();

// Or normalize straight through to the wrapped value
$value = $selfDescribed->normalize();
```

**Accepts:** Any `CBORObject`
**Purpose:** Allows decoders to rapidly identify CBOR data without parsing

> **Deprecated:** `CBOR\Tag\SelfDescribeCBORTag` also declares tag 55799 and was documented here until
> 3.3.5. A tag manager registers one class per tag number, and `CBORTag` is the one the default decoder
> uses, so decoding a self-described document always yields a `CBORTag`. Use `CBORTag`;
> `SelfDescribeCBORTag` will be removed in 4.0.0. Its `getCBORObject()` is a duplicate of the inherited
> `getValue()`.

---

### COSE and CWT Tags

The six COSE structures of [RFC 9052](https://datatracker.ietf.org/doc/html/rfc9052) and the CBOR Web Token tag
of [RFC 8392](https://datatracker.ietf.org/doc/html/rfc8392) that wraps them. Each one describes the shape of the
structure and gives access to its parts; **verifying a signature or a MAC is not done here** and needs a COSE
implementation such as [web-auth/cose-lib](https://github.com/web-auth/cose-lib).

| Tag | Structure | Class | Content |
|-----|-----------|-------|---------|
| 16 | COSE_Encrypt0 | `CoseEncrypt0Tag` | [protected, unprotected, ciphertext] |
| 17 | COSE_Mac0 | `CoseMac0Tag` | [protected, unprotected, payload, tag] |
| 18 | COSE_Sign1 | `CoseSign1Tag` | [protected, unprotected, payload, signature] |
| 96 | COSE_Encrypt | `CoseEncryptTag` | [protected, unprotected, ciphertext, recipients] |
| 97 | COSE_Mac | `CoseMacTag` | [protected, unprotected, payload, tag, recipients] |
| 98 | COSE_Sign | `CoseSignTag` | [protected, unprotected, payload, signatures] |
| 61 | CWT | `CwtTag` | a COSE structure, tagged or not, or a bare claims set |

```php
use CBOR\Decoder;
use CBOR\StringStream;
use CBOR\Tag\CoseSign1Tag;

$object = Decoder::create()->decode(StringStream::create($encoded));

if ($object instanceof CoseSign1Tag) {
    // The protected header is signed as it was written, so it travels wrapped in a byte string.
    $algorithm = $object->getProtectedHeaderAsMap()->normalize()[1] ?? null;
    $payload = $object->getPayload();     // ByteStringObject, or NullObject when detached
    $signature = $object->getSignature();
}
```

Two shapes that a stricter reading would turn away are accepted on purpose:

- **a detached payload**, which COSE writes as `null`: the content travels out of band and only the signature
  comes with the structure;
- **an absent protected header**, which COSE writes as an empty byte string: `getProtectedHeaderAsMap()` answers
  an empty `MapObject` rather than failing to parse zero bytes.

To build one, `createFromComponents()` takes the parts and serializes the protected header for you:

```php
$tag = CoseSign1Tag::createFromComponents(
    $protectedHeader,    // MapObject; encoded into the byte string COSE signs
    $unprotectedHeader,  // MapObject
    ByteStringObject::create($payload),
    ByteStringObject::create($signature)
);
```

---

### Date Tags

A calendar day is not an instant: a birthday or an expiry date is the same day everywhere, and pinning it to a
moment moves it across a day boundary depending on where it is read. [RFC 8943](https://datatracker.ietf.org/doc/html/rfc8943)
gives it two tags of its own.

| Tag | Description | Class | Accepts |
|-----|-------------|-------|---------|
| 100 | Days since 1970-01-01 | `DateTag` | `UnsignedIntegerObject`, `NegativeIntegerObject` |
| 1004 | RFC 3339 full-date (`YYYY-MM-DD`) | `DateStringTag` | `TextStringObject` |

```php
use CBOR\Tag\DateTag;
use CBOR\UnsignedIntegerObject;

$tag = DateTag::create(UnsignedIntegerObject::create(19737));
echo $tag->normalize()->format('Y-m-d'); // 2024-01-15
```

PHP has no date-only type, so both normalize to **midnight UTC** -- the reading that keeps the day intact -- and
neither is affected by the ambient time zone. A day that does not exist (`2013-02-30`) is rejected rather than
rolled over into the next month.

---

### Address Tags

| Tag | Description | Class | Accepts |
|-----|-------------|-------|---------|
| 52 | IPv4 address, prefix or zoned address | `Ipv4Tag` | `ByteStringObject`, or a 2 item `ListObject` |
| 54 | IPv6 address, prefix or zoned address | `Ipv6Tag` | `ByteStringObject`, or a 2 item `ListObject` |
| 260 | Network address (IPv4, IPv6 or MAC) | `NetworkAddressTag` | a 4, 6 or 16 byte `ByteStringObject` |
| 261 | Network address prefix | `NetworkAddressPrefixTag` | a single entry `MapObject` |

Tags 52 and 54 come from [RFC 9164](https://datatracker.ietf.org/doc/html/rfc9164) and carry three shapes under
one number, told apart by their first item: a byte string is an address, `[length, bytes]` is a prefix whose
trailing zero bytes may be left out, and `[bytes, zone]` is an address with the zone that scopes it.

```php
use CBOR\Tag\Ipv6Tag;

echo Ipv6Tag::createFromAddress('2001:db8::1')->normalize(); // 2001:db8::1
// A prefix normalizes with its length, a zoned address with its zone:
// "2001:db8::/32", "fe80::1%eth0"
```

Tags 260 and 261 are the earlier form, which tells the family from the length of the byte string. RFC 9164
supersedes them; prefer 52 and 54 for anything new.

---

### Typed Array Tags

[RFC 8746](https://datatracker.ietf.org/doc/html/rfc8746) packs an array of numbers of one fixed type into a
single byte string: a megabyte of samples travels as a megabyte instead of as three. The tag number gives the
element type, so the only thing the content has to satisfy is being a whole number of elements long.

| Tags | Element | Classes (namespace `CBOR\Tag\TypedArray`) |
|------|---------|------------------------------------------|
| 64, 68 | uint8, uint8 clamped | `Uint8ArrayTag`, `Uint8ClampedArrayTag` |
| 65, 69 | uint16 big / little endian | `Uint16BigEndianArrayTag`, `Uint16LittleEndianArrayTag` |
| 66, 70 | uint32 big / little endian | `Uint32BigEndianArrayTag`, `Uint32LittleEndianArrayTag` |
| 67, 71 | uint64 big / little endian | `Uint64BigEndianArrayTag`, `Uint64LittleEndianArrayTag` |
| 72 | sint8 | `Sint8ArrayTag` |
| 73, 77 | sint16 big / little endian | `Sint16BigEndianArrayTag`, `Sint16LittleEndianArrayTag` |
| 74, 78 | sint32 big / little endian | `Sint32BigEndianArrayTag`, `Sint32LittleEndianArrayTag` |
| 75, 79 | sint64 big / little endian | `Sint64BigEndianArrayTag`, `Sint64LittleEndianArrayTag` |
| 80, 84 | binary16 big / little endian | `Float16BigEndianArrayTag`, `Float16LittleEndianArrayTag` |
| 81, 85 | binary32 big / little endian | `Float32BigEndianArrayTag`, `Float32LittleEndianArrayTag` |
| 82, 86 | binary64 big / little endian | `Float64BigEndianArrayTag`, `Float64LittleEndianArrayTag` |
| 83, 87 | binary128 big / little endian | `Float128BigEndianArrayTag`, `Float128LittleEndianArrayTag` |

```php
use CBOR\Tag\TypedArray\Uint16BigEndianArrayTag;
use CBOR\ByteStringObject;

$tag = Uint16BigEndianArrayTag::create(ByteStringObject::create("\x00\x01\x00\x02"));

count($tag);          // 2
$tag->normalize();    // [1, 2]
```

**Returns:** a list of `int` or `float` when normalized. A uint64 above `PHP_INT_MAX` comes back as a decimal
string rather than as a negative integer.

**The binary128 pair is the exception:** PHP has no quadruple precision float, and returning a binary64 would
quietly drop fifteen digits, so tags 83 and 87 do not implement `Normalizable`. Use `getChunks()`, which hands
back the 16 bytes of each element.

#### Tags 40 and 1040: Multi-Dimensional Arrays

The same RFC folds a flat array -- a plain one or a typed array -- into a shape, as `[dimensions, values]`.

| Tag | Order | Class |
|-----|-------|-------|
| 40 | Row-major (last index varies fastest) | `RowMajorMultiDimensionalArrayTag` |
| 1040 | Column-major (first index varies fastest) | `ColumnMajorMultiDimensionalArrayTag` |

```php
// 40([[2, 3], [1, 2, 3, 4, 5, 6]])
$tag->getDimensions();  // [2, 3]
$tag->normalize();      // [[1, 2, 3], [4, 5, 6]]
// The same values under tag 1040 fold the other way: [[1, 3, 5], [2, 4, 6]]
```

`normalize()` rejects a document whose dimensions do not account for exactly the number of values it carries.

Tag 41 (`HomogeneousArrayTag`) belongs to the same RFC. It is a hint that every item of an array has the same
type; the array normalizes as it would untagged, and homogeneity is not checked, since what counts as "the same
type" is the application's data model rather than CBOR's major types.

---

### Other Registry Tags

| Tag | Description | Class | Accepts |
|-----|-------------|-------|---------|
| 25 | String reference | `StringReferenceTag` | `UnsignedIntegerObject` |
| 26 | Serialised Perl object | `PerlObjectTag` | `ListObject` opening with a text string |
| 27 | Serialised language-independent object | `LanguageIndependentObjectTag` | `ListObject` opening with a text string |
| 28 | Shareable value | `ShareableTag` | any object |
| 29 | Shared value reference | `SharedReferenceTag` | `UnsignedIntegerObject` |
| 30 | Rational number | `RationalNumberTag` | 2 item `ListObject` [numerator, denominator] |
| 35 | Regular expression | `RegexpTag` | `TextStringObject` |
| 37 | Binary UUID | `UuidTag` | a 16 byte `ByteStringObject` |
| 38 | Language-tagged string | `LanguageTaggedStringTag` | 2 item `ListObject` [language, text] |
| 39 | Identifier | `IdentifierTag` | any object |
| 42 | IPLD content identifier (CID) | `IpldContentIdentifierTag` | `ByteStringObject` |
| 63 | Encoded CBOR sequence | `CBORSequenceTag` | `ByteStringObject` |
| 256 | String reference namespace | `StringReferenceNamespaceTag` | any object |
| 257 | Binary MIME message | `BinaryMimeTag` | `ByteStringObject` |
| 258 | Mathematical finite set | `SetTag` | `ListObject` |
| 259 | Explicit map | `ExplicitMapTag` | `MapObject` |
| 1001 | Extended time | `ExtendedTimeTag` | `MapObject` |
| 1002 | Duration | `DurationTag` | `MapObject` |
| 1003 | Period | `PeriodTag` | `ListObject` |

A few of them are worth a word.

**Tag 37 (UUID)** normalizes to the canonical `8-4-4-4-12` form and can be built from it:

```php
use CBOR\Tag\UuidTag;

$tag = UuidTag::createFromUuidString('01234567-89ab-cdef-0123-456789abcdef');
echo $tag->normalize(); // 01234567-89ab-cdef-0123-456789abcdef
```

**Tag 30 (rational number)** normalizes to lowest terms, as `"numerator/denominator"` -- or as the numerator
alone when the denominator reduces to 1. Either part may be a bignum (tags 2 and 3), which is the point of the
tag: a ratio such as 1/3 has no exact decimal or binary floating point form.

**Tag 63 (CBOR sequence)** is to a sequence what tag 24 is to a single item. `getSequence()` decodes the byte
string into the items it holds:

```php
$items = $tag->getSequence(); // CBORObject[]
```

**Tag 258 (set)** returns its items in the order they were written. Nothing in CBOR keeps a duplicate out of an
array, so a repeated item is left for the application to notice -- and, most often, to reject the document over,
since a set that carries one is not what it claims to be.

**Tags 25, 28, 29 and 256 (string references and value sharing)** are recorded rather than resolved. The table a
reference points into is built while the whole document is read, which is outside the scope of any single tag,
so the index is returned as it stands and the marked value normalizes as it would untagged.

**Tags 1001, 1002 and 1003 ([RFC 9581](https://datatracker.ietf.org/doc/html/rfc9581))** are returned as the map
or array they are. The components they allow -- a leap second, a time scale that is not UTC, a resolution below
the microsecond -- do not all fit `DateTimeImmutable` or `DateInterval`, and flattening them into one would drop
whichever does not.

---

## Using Tags

### Encoding with Tags

```php
use CBOR\Tag\TimestampTag;
use CBOR\UnsignedIntegerObject;

// Create the data
$timestamp = UnsignedIntegerObject::create(time());

// Wrap with a tag
$tagged = TimestampTag::create($timestamp);

// Encode to CBOR binary
$encoded = (string) $tagged;
```

### Decoding Tagged Data

```php
use CBOR\Decoder;
use CBOR\StringStream;

$decoder = Decoder::create();
$decoded = $decoder->decode(StringStream::create($encoded));

// The decoder automatically recognizes registered tags
if ($decoded instanceof TimestampTag) {
    $dateTime = $decoded->normalize();
    echo $dateTime->format('Y-m-d H:i:s');
}
```

### Accessing Tagged Values

```php
// Get the wrapped object
$wrappedObject = $tagged->getValue();

// Get additional data (if any)
$data = $tagged->getData();

// Get the tag number
$tagId = $tagged::getTagId();
```

---

## Creating Custom Tags

See [Creating Custom Tags](custom-tags.md) for a comprehensive guide on implementing your own tag types.

---

## IANA Registry

The CBOR Tags registry is maintained by IANA. This library implements the most commonly used tags from the registry.

**Full Registry:** [IANA CBOR Tags](https://www.iana.org/assignments/cbor-tags/cbor-tags.xhtml)

### Tag Summary Table

| Tag | Description | Class | Spec |
|-----|-------------|-------|------|
| 0 | Date/Time (RFC 3339) | `DatetimeTag` | RFC 8949 § 3.4.1 |
| 1 | Epoch Timestamp | `TimestampTag` | RFC 8949 § 3.4.2 |
| 2 | Unsigned Bignum | `UnsignedBigIntegerTag` | RFC 8949 § 3.4.3 |
| 3 | Negative Bignum | `NegativeBigIntegerTag` | RFC 8949 § 3.4.3 |
| 4 | Decimal Fraction | `DecimalFractionTag` | RFC 8949 § 3.4.4 |
| 5 | Bigfloat | `BigFloatTag` | RFC 8949 § 3.4.4 |
| 16 | COSE_Encrypt0 | `CoseEncrypt0Tag` | RFC 9052 |
| 17 | COSE_Mac0 | `CoseMac0Tag` | RFC 9052 |
| 18 | COSE_Sign1 | `CoseSign1Tag` | RFC 9052 |
| 21 | Base64url (expected) | `Base64UrlEncodingTag` | RFC 8949 § 3.4.5.2 |
| 22 | Base64 (expected) | `Base64EncodingTag` | RFC 8949 § 3.4.5.2 |
| 23 | Base16 (expected) | `Base16EncodingTag` | RFC 8949 § 3.4.5.2 |
| 24 | Encoded CBOR | `CBOREncodingTag` | RFC 8949 § 3.4.5.1 |
| 25 | String reference | `StringReferenceTag` | stringref |
| 26 | Serialised Perl object | `PerlObjectTag` | IANA registry |
| 27 | Serialised language-independent object | `LanguageIndependentObjectTag` | IANA registry |
| 28 | Shareable value | `ShareableTag` | value sharing |
| 29 | Shared value reference | `SharedReferenceTag` | value sharing |
| 30 | Rational number | `RationalNumberTag` | IANA registry |
| 32 | URI | `UriTag` | RFC 8949 § 3.4.5.3 |
| 33 | Base64url | `Base64UrlTag` | RFC 8949 § 3.4.5.2 |
| 34 | Base64 | `Base64Tag` | RFC 8949 § 3.4.5.2 |
| 35 | Regular expression | `RegexpTag` | RFC 7049 |
| 36 | MIME Message | `MimeTag` | RFC 8949 § 3.4.5.3 |
| 37 | Binary UUID | `UuidTag` | RFC 4122 |
| 38 | Language-tagged string | `LanguageTaggedStringTag` | IANA registry |
| 39 | Identifier | `IdentifierTag` | IANA registry |
| 40 | Multi-dimensional array, row-major | `RowMajorMultiDimensionalArrayTag` | RFC 8746 |
| 41 | Homogeneous array | `HomogeneousArrayTag` | RFC 8746 |
| 42 | IPLD content identifier | `IpldContentIdentifierTag` | IANA registry |
| 52 | IPv4 address or prefix | `Ipv4Tag` | RFC 9164 |
| 54 | IPv6 address or prefix | `Ipv6Tag` | RFC 9164 |
| 61 | CBOR Web Token | `CwtTag` | RFC 8392 |
| 63 | Encoded CBOR Sequence | `CBORSequenceTag` | RFC 8742 |
| 64-87 | Typed arrays | `CBOR\Tag\TypedArray\*` | RFC 8746 |
| 96 | COSE_Encrypt | `CoseEncryptTag` | RFC 9052 |
| 97 | COSE_Mac | `CoseMacTag` | RFC 9052 |
| 98 | COSE_Sign | `CoseSignTag` | RFC 9052 |
| 100 | Date (days since epoch) | `DateTag` | RFC 8943 |
| 256 | String reference namespace | `StringReferenceNamespaceTag` | stringref |
| 257 | Binary MIME message | `BinaryMimeTag` | IANA registry |
| 258 | Mathematical finite set | `SetTag` | IANA registry |
| 259 | Explicit map | `ExplicitMapTag` | IANA registry |
| 260 | Network address (legacy) | `NetworkAddressTag` | RFC 9164 App. A |
| 261 | Network address prefix (legacy) | `NetworkAddressPrefixTag` | RFC 9164 App. A |
| 1001 | Extended time | `ExtendedTimeTag` | RFC 9581 |
| 1002 | Duration | `DurationTag` | RFC 9581 |
| 1003 | Period | `PeriodTag` | RFC 9581 |
| 1004 | Date string | `DateStringTag` | RFC 8943 |
| 1040 | Multi-dimensional array, column-major | `ColumnMajorMultiDimensionalArrayTag` | RFC 8746 |
| 55799 | Self-Describe CBOR | `CBORTag` | RFC 8949 § 3.4.6 |

### Tags Not Implemented

The registry is open ended and most of what is not in the table above is specific to one application. Any tag
number missing from it decodes to `GenericTag`, which keeps the number and the item without claiming to
understand either -- nothing is lost, only the semantics are left to the caller.

```php
use CBOR\Tag\GenericTag;

$object = $decoder->decode($stream);

if ($object instanceof GenericTag) {
    $item = $object->getValue();  // the tagged item, decoded as usual
}
```

The tag number is where CBOR puts it: in the additional information for a number up to 23, and in the bytes of
`getData()` beyond that.

To give one of them a class of its own, see [Creating Custom Tags](custom-tags.md), then register it:

```php
use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagManager;

$decoder = Decoder::create(
    TagManager::create()->add(MyTag::class),
    OtherObjectManager::create()
);
```

Note that a manager built this way replaces the default one rather than adding to it: it holds `MyTag` and
nothing else. Add back the built-in classes you still need, or register the tag on a manager of your own that
mirrors the default set.

---

## Best Practices

1. **Choose the Right Tag**: Use standard IANA-registered tags when available for interoperability
2. **Validate Data Types**: Ensure the wrapped object matches the expected type for the tag
3. **Handle Unknown Tags**: Be prepared to handle `GenericTag` for unrecognized tag numbers
4. **Check Extensions**: Some tags require specific PHP extensions (e.g., `bcmath` for decimal fractions)
5. **Normalize Appropriately**: Use `normalize()` to convert tagged values to native PHP types

---

[← Back to Documentation Index](index.md) | [Creating Custom Tags →](custom-tags.md)
