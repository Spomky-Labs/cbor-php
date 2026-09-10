# CBOR PHP Library - Documentation

Complete documentation for the CBOR (Concise Binary Object Representation) PHP library.

## Quick Links

- [Installation & Quick Start](../README.md#installation)
- [Tags Reference](tags.md) - Complete guide to all supported CBOR tags
- [Creating Custom Tags](custom-tags.md) - How to implement your own tags
- [API Reference](#api-reference)
- [Examples](#examples)

## Table of Contents

1. [Introduction](#introduction)
2. [Core Concepts](#core-concepts)
3. [API Reference](#api-reference)
4. [Tags Documentation](#tags-documentation)
5. [Advanced Usage](#advanced-usage)
6. [Integration Examples](#integration-examples)

---

## Introduction

CBOR (Concise Binary Object Representation) is a data format defined in [RFC 8949](https://datatracker.ietf.org/doc/html/rfc8949) that provides a compact binary encoding for structured data. This PHP library provides a complete implementation for encoding and decoding CBOR data.

### Why CBOR?

- **Compact**: Smaller than JSON for most data
- **Fast**: Efficient binary encoding and decoding
- **Extensible**: Support for tags provides semantic annotations
- **Standardized**: Well-defined specification with IANA registry
- **Versatile**: Supports all major data types including binary data

### Use Cases

- **IoT & Embedded Systems**: Efficient data exchange with constrained devices
- **WebAuthn/FIDO2**: Authentication data encoding
- **COSE**: Cryptographic object signing and encryption
- **CWT**: CBOR Web Tokens (JWT alternative)
- **CoAP**: Constrained Application Protocol payloads
- **API Communication**: Binary alternative to JSON

---

## Core Concepts

### Major Types

CBOR defines 8 major types (0-7):

| Type | Description | PHP Classes |
|------|-------------|-------------|
| 0 | Unsigned Integer | `UnsignedIntegerObject` |
| 1 | Negative Integer | `NegativeIntegerObject` |
| 2 | Byte String | `ByteStringObject`, `IndefiniteLengthByteStringObject` |
| 3 | Text String | `TextStringObject`, `IndefiniteLengthTextStringObject` |
| 4 | Array | `ListObject`, `IndefiniteLengthListObject` |
| 5 | Map | `MapObject`, `IndefiniteLengthMapObject` |
| 6 | Tag | `Tag` subclasses (see [Tags Documentation](tags.md)) |
| 7 | Simple/Float | `TrueObject`, `FalseObject`, `NullObject`, float objects |

### Object Hierarchy

```
CBORObject (interface)
├── AbstractCBORObject (abstract)
│   ├── UnsignedIntegerObject
│   ├── NegativeIntegerObject
│   ├── ByteStringObject
│   ├── IndefiniteLengthByteStringObject
│   ├── TextStringObject
│   ├── IndefiniteLengthTextStringObject
│   ├── ListObject
│   ├── IndefiniteLengthListObject
│   ├── MapObject
│   ├── IndefiniteLengthMapObject
│   ├── Tag (abstract)
│   │   ├── DatetimeTag
│   │   ├── TimestampTag
│   │   ├── DecimalFractionTag
│   │   ├── BigFloatTag
│   │   ├── ... (see Tags Reference)
│   │   └── GenericTag
│   └── OtherObject (abstract)
│       ├── TrueObject
│       ├── FalseObject
│       ├── NullObject
│       ├── UndefinedObject
│       ├── DoublePrecisionFloatObject
│       └── ...
```

### Normalization

Many CBOR objects implement the `Normalizable` interface, which provides a `normalize()` method to convert to native PHP types:

```php
$text = TextStringObject::create('Hello');
$text->normalize(); // string: "Hello"

$int = UnsignedIntegerObject::create(42);
$int->normalize(); // string: "42"

$map = MapObject::create()
    ->add(TextStringObject::create('key'), UnsignedIntegerObject::create(100));
$map->normalize(); // array: ['key' => '100']
```

---

## API Reference

### Encoding API

#### Creating Objects

All CBOR objects provide a static `create()` method:

```php
// Integers
$uint = UnsignedIntegerObject::create(42);
$nint = NegativeIntegerObject::create(-42);

// Strings
$text = TextStringObject::create('Hello World');
$bytes = ByteStringObject::create('binary data');

// Collections
$list = ListObject::create([
    UnsignedIntegerObject::create(1),
    UnsignedIntegerObject::create(2),
]);

$map = MapObject::create()
    ->add(TextStringObject::create('name'), TextStringObject::create('Alice'))
    ->add(TextStringObject::create('age'), UnsignedIntegerObject::create(30));

// Tags
$timestamp = TimestampTag::create(UnsignedIntegerObject::create(time()));
```

#### Encoding to Binary

All objects implement `__toString()`:

```php
$object = TextStringObject::create('Hello');
$encoded = (string) $object;
// or
$encoded = $object->__toString();

echo bin2hex($encoded); // "6548656c6c6f"
```

### Decoding API

#### Creating a Decoder

```php
use CBOR\Decoder;

// Default decoder with all standard tags
$decoder = Decoder::create();

// Custom decoder with specific tags
use CBOR\Tag\TagManager;
use CBOR\OtherObject\OtherObjectManager;

$tagManager = TagManager::create()
    ->add(TimestampTag::class)
    ->add(DatetimeTag::class);

$otherObjectManager = OtherObjectManager::create()
    ->add(TrueObject::class)
    ->add(FalseObject::class)
    ->add(NullObject::class);

$decoder = Decoder::create($tagManager, $otherObjectManager);
```

#### Limiting the Nesting Depth

Decoding is a recursive operation: a deeply nested data item (e.g. thousands of nested arrays, maps or tags) can
exhaust the call stack. The decoder therefore rejects data nested deeper than `Decoder::DEFAULT_MAX_DEPTH` (1000
levels) with an `InvalidArgumentException`.

When the data comes from an untrusted source, a much lower limit is recommended:

```php
// Accept at most 32 levels of nested arrays, maps and tags
$decoder = Decoder::create(null, null, 32);
```

#### Decoding Binary Data

```php
use CBOR\StringStream;

$binaryData = hex2bin('6548656c6c6f');
$stream = StringStream::create($binaryData);

$object = $decoder->decode($stream);

// Check type and extract value
if ($object instanceof TextStringObject) {
    echo $object->getValue(); // "Hello"
    echo $object->normalize(); // "Hello"
}
```

### Working with Collections

#### Lists (Arrays)

```php
$list = ListObject::create();

// Add items
$list->add(UnsignedIntegerObject::create(1));
$list->add(TextStringObject::create('two'));

// Access items
$first = $list->get(0);
$count = $list->count();

// Check existence
if ($list->has(1)) {
    // ...
}

// Remove items
$list->remove(0);

// Array access
$list[0] = UnsignedIntegerObject::create(42);
$value = $list[0];

// Iteration
foreach ($list as $item) {
    // ...
}

// Normalize to PHP array
$phpArray = $list->normalize();
```

#### Maps (Dictionaries)

```php
$map = MapObject::create();

// Add key-value pairs
$map->add(
    TextStringObject::create('name'),
    TextStringObject::create('Alice')
);

// Access values
$name = $map->get('name');

// Check existence
if ($map->has('name')) {
    // ...
}

// Remove entries
$map->remove('name');

// Count entries
$count = $map->count();

// Iteration
foreach ($map as $item) {
    $key = $item->getKey();
    $value = $item->getValue();
}

// Normalize to PHP array
$phpArray = $map->normalize();
```

### Working with Tags

See the comprehensive [Tags Reference](tags.md) for detailed information.

```php
// Create tagged values
$timestamp = TimestampTag::create(UnsignedIntegerObject::create(time()));
$decimal = DecimalFractionTag::createFromFloat(3.14159);

// Access wrapped value
$wrappedObject = $timestamp->getValue();

// Normalize to PHP types
$dateTime = $timestamp->normalize(); // DateTimeImmutable
$number = $decimal->normalize(); // "3.14159"
```

---

## Tags Documentation

### Complete Tags Reference

See [**Tags Reference**](tags.md) for comprehensive documentation on all supported tags, including:

- Date/Time tags (Tags 0, 1)
- Big number tags (Tags 2, 3)
- Fractional number tags (Tags 4, 5)
- Encoding tags (Tags 21, 22, 23, 33, 34)
- Semantic tags (Tags 32, 36)
- Special CBOR tags (Tags 24, 55799)

### Creating Custom Tags

See [**Creating Custom Tags**](custom-tags.md) for a complete guide on implementing custom tag types for:

- Domain-specific data types
- Integration with other specifications (COSE, CWT, etc.)
- Custom validation and transformation logic

---

## Advanced Usage

### Indefinite-Length Objects

CBOR supports streaming with indefinite-length objects:

```php
// Indefinite-length text string
$text = IndefiniteLengthTextStringObject::create()
    ->append('Hello')
    ->append(' ')
    ->append('World');

echo $text->getValue(); // "Hello World"

// Indefinite-length byte string
$bytes = IndefiniteLengthByteStringObject::create()
    ->append('part1')
    ->append('part2');

// Indefinite-length list
$list = IndefiniteLengthListObject::create()
    ->add(UnsignedIntegerObject::create(1))
    ->add(UnsignedIntegerObject::create(2));

// Indefinite-length map
$map = IndefiniteLengthMapObject::create()
    ->add(TextStringObject::create('key1'), UnsignedIntegerObject::create(1))
    ->add(TextStringObject::create('key2'), UnsignedIntegerObject::create(2));
```

### Custom Streams

Implement the `Stream` interface for custom data sources:

```php
use CBOR\Stream;

class FileStream implements Stream
{
    private $handle;
    private int $position = 0;

    public function __construct(string $filepath)
    {
        $this->handle = fopen($filepath, 'rb');
    }

    public function read(int $length): string
    {
        $data = fread($this->handle, $length);
        $this->position += strlen($data);
        return $data;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    // ... implement remaining methods
}

// Use with decoder
$stream = new FileStream('/path/to/cbor/file.cbor');
$object = $decoder->decode($stream);
```

### Type Detection

```php
$object = $decoder->decode($stream);

// Check major type
if ($object instanceof UnsignedIntegerObject) {
    echo "Unsigned integer: " . $object->getValue();
}

// Check for tags
if ($object instanceof TimestampTag) {
    echo "Timestamp: " . $object->normalize()->format('Y-m-d');
}

// Check for normalizable
if ($object instanceof Normalizable) {
    $phpValue = $object->normalize();
}

// Pattern matching (PHP 8.0+)
$value = match (true) {
    $object instanceof TextStringObject => $object->getValue(),
    $object instanceof UnsignedIntegerObject => (int) $object->getValue(),
    $object instanceof ListObject => $object->normalize(),
    default => null,
};
```

---

## Integration Examples

### WebAuthn/FIDO2

CBOR is used extensively in WebAuthn for credential data:

```php
// Decode authenticator data
$authenticatorData = $decoder->decode(StringStream::create($binaryData));

if ($authenticatorData instanceof MapObject) {
    $credentialId = $authenticatorData->get('credentialId');
    $publicKey = $authenticatorData->get('publicKey');

    // Process WebAuthn credential
}
```

### COSE (Object Signing)

```php
// Decode a COSE_Sign1 structure
$coseSign1 = $decoder->decode($stream);

if ($coseSign1 instanceof COSESign1Tag) {
    $protected = $coseSign1->getProtectedHeaders();
    $payload = $coseSign1->getPayload();
    $signature = $coseSign1->getSignature();

    // Verify signature
}
```

### IoT Sensor Data

```php
// Encode sensor readings efficiently
$sensorData = MapObject::create()
    ->add(
        TextStringObject::create('temperature'),
        DecimalFractionTag::createFromFloat(23.5)
    )
    ->add(
        TextStringObject::create('humidity'),
        UnsignedIntegerObject::create(65)
    )
    ->add(
        TextStringObject::create('timestamp'),
        TimestampTag::create(UnsignedIntegerObject::create(time()))
    );

$encoded = (string) $sensorData;
// Send over network (much smaller than JSON)
```

### API Response Encoding

```php
// Convert PHP data to CBOR
function encodeToCBOR(array $data): string
{
    $map = MapObject::create();

    foreach ($data as $key => $value) {
        $keyObj = TextStringObject::create($key);
        $valueObj = match (gettype($value)) {
            'string' => TextStringObject::create($value),
            'integer' => UnsignedIntegerObject::create($value),
            'array' => convertArrayToList($value),
            default => throw new Exception('Unsupported type'),
        };

        $map->add($keyObj, $valueObj);
    }

    return (string) $map;
}

// Usage
$response = encodeToCBOR([
    'status' => 'success',
    'code' => 200,
    'data' => ['item1', 'item2']
]);

header('Content-Type: application/cbor');
echo $response;
```

---

## Performance Tips

1. **Reuse Decoders**: Create decoder instances once and reuse
2. **Use Appropriate Types**: Choose the smallest integer type that fits
3. **Batch Operations**: Build collections incrementally rather than recreating
4. **Stream Large Data**: Use indefinite-length objects for streaming
5. **Enable Extensions**: Install `ext-gmp` or `ext-bcmath` for better performance

---

## Error Handling

```php
use InvalidArgumentException;
use RuntimeException;

try {
    $decoder = Decoder::create();
    $object = $decoder->decode($stream);
} catch (InvalidArgumentException $e) {
    // Invalid CBOR structure or data
    error_log('CBOR decoding error: ' . $e->getMessage());
} catch (RuntimeException $e) {
    // Missing required extension
    error_log('Runtime error: ' . $e->getMessage());
}
```

---

## Debugging

### Inspect CBOR Objects

```php
// Get human-readable representation
var_dump($object);

// For maps and lists
foreach ($map as $item) {
    echo sprintf(
        "Key: %s, Value: %s\n",
        $item->getKey()->normalize(),
        $item->getValue()->normalize()
    );
}

// Check encoded binary
$encoded = (string) $object;
echo 'Hex: ' . bin2hex($encoded) . "\n";
echo 'Length: ' . strlen($encoded) . " bytes\n";
```

### Online Tools

- [CBOR Playground](http://cbor.me/) - Visualize CBOR data
- [CBOR.io](https://cbor.io/) - Online encoder/decoder

---

## Additional Resources

### Specifications

- [RFC 8949: CBOR](https://datatracker.ietf.org/doc/html/rfc8949)
- [IANA CBOR Tags Registry](https://www.iana.org/assignments/cbor-tags/)
- [RFC 8152: COSE](https://datatracker.ietf.org/doc/html/rfc8152)
- [RFC 8392: CWT](https://datatracker.ietf.org/doc/html/rfc8392)

### Related Libraries

- [WebAuthn Framework](https://github.com/web-auth/webauthn-framework) - Uses CBOR for credentials
- [PHP JOSE](https://github.com/web-token/jwt-framework) - Related JWT/JWE library

### Community

- [GitHub Repository](https://github.com/Spomky-Labs/cbor-php)
- [Issue Tracker](https://github.com/Spomky-Labs/cbor-php/issues)
- [Discussions](https://github.com/Spomky-Labs/cbor-php/discussions)

---

## Quick Reference Card

```php
// Encoding
$text = TextStringObject::create('hello');
$int = UnsignedIntegerObject::create(42);
$list = ListObject::create([$text, $int]);
$map = MapObject::create()->add($text, $int);
$tagged = TimestampTag::create($int);
$encoded = (string) $map;

// Decoding
$decoder = Decoder::create();
$stream = StringStream::create($encoded);
$object = $decoder->decode($stream);
$phpValue = $object->normalize();

// Tags
$timestamp = TimestampTag::create(UnsignedIntegerObject::create(time()));
$decimal = DecimalFractionTag::createFromFloat(3.14);
$datetime = DatetimeTag::create(TextStringObject::create('2024-01-15T10:30:00Z'));
```

---

[Tags Reference →](tags.md) | [Creating Custom Tags →](custom-tags.md)
