# CBOR Tags Reference

CBOR tags (Major Type 6) provide semantic information about data values. Tags are integers that prefix a data item to give it additional meaning according to a registry maintained by IANA.

## Table of Contents

- [Overview](#overview)
- [Built-in Tags](#built-in-tags)
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

This library provides implementations for commonly used tags and a generic tag handler for unsupported tags.

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

---

### Fractional Number Tags

#### Tag 4: Decimal Fraction

Encodes a decimal fraction as: **mantissa × 10^exponent**

**Class:** `CBOR\Tag\DecimalFractionTag`
**Spec:** [RFC 8949 § 3.4.4](https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.4)
**Requires:** `ext-bcmath`

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
| 21 | Base64url (expected) | `Base64UrlEncodingTag` | RFC 8949 § 3.4.5.2 |
| 22 | Base64 (expected) | `Base64EncodingTag` | RFC 8949 § 3.4.5.2 |
| 23 | Base16 (expected) | `Base16EncodingTag` | RFC 8949 § 3.4.5.2 |
| 24 | Encoded CBOR | `CBOREncodingTag` | RFC 8949 § 3.4.5.1 |
| 32 | URI | `UriTag` | RFC 8949 § 3.4.5.3 |
| 33 | Base64url | `Base64UrlTag` | RFC 8949 § 3.4.5.2 |
| 34 | Base64 | `Base64Tag` | RFC 8949 § 3.4.5.2 |
| 36 | MIME Message | `MimeTag` | RFC 8949 § 3.4.5.3 |
| 55799 | Self-Describe CBOR | `CBORTag` | RFC 8949 § 3.4.6 |

### Unsupported Tags

For tags not implemented by this library, use `GenericTag`:

```php
use CBOR\Tag\GenericTag;
use CBOR\TextStringObject;

// Create a tag with any tag number
$tag = GenericTag::createFromLoadedData(
    $additionalInformation,
    $data,
    TextStringObject::create('custom data')
);
```

---

## Best Practices

1. **Choose the Right Tag**: Use standard IANA-registered tags when available for interoperability
2. **Validate Data Types**: Ensure the wrapped object matches the expected type for the tag
3. **Handle Unknown Tags**: Be prepared to handle `GenericTag` for unrecognized tag numbers
4. **Check Extensions**: Some tags require specific PHP extensions (e.g., `bcmath` for decimal fractions)
5. **Normalize Appropriately**: Use `normalize()` to convert tagged values to native PHP types

---

[← Back to Documentation Index](index.md) | [Creating Custom Tags →](custom-tags.md)
