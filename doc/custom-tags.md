# Creating Custom CBOR Tags

This guide explains how to create custom tag implementations for CBOR data that extends beyond the standard tags defined in RFC 8949.

## Table of Contents

- [Understanding Tags](#understanding-tags)
- [When to Create Custom Tags](#when-to-create-custom-tags)
- [Tag Anatomy](#tag-anatomy)
- [Implementation Steps](#implementation-steps)
- [Complete Examples](#complete-examples)
- [Advanced Topics](#advanced-topics)
- [Integration with Other Specifications](#integration-with-other-specifications)

## Understanding Tags

CBOR tags (Major Type 6) are semantic annotations that give additional meaning to CBOR data items. A tag consists of:

1. **Tag Number**: An integer identifier (registered with IANA or private-use)
2. **Tagged Value**: The CBOR data item the tag applies to

```
Tag(42, "Hello") → Tag number 42 applied to text string "Hello"
```

## When to Create Custom Tags

Create custom tags when you need to:

- Implement CBOR tags from other specifications (COSE, CWT, etc.)
- Add domain-specific semantics to your data
- Encode complex data structures with specific validation rules
- Ensure interoperability with other systems using registered tags
- Optimize serialization for your specific use case

## Tag Anatomy

All tags in this library extend the abstract `CBOR\Tag` class. Here's the basic structure:

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\Tag;

class MyCustomTag extends Tag
{
    // Required: Return the IANA tag number
    public static function getTagId(): int
    {
        return 1234; // Your tag number
    }

    // Required: Create tag from decoded data
    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): Tag {
        return new self($additionalInformation, $data, $object);
    }

    // Optional: Convenience constructor
    public static function create(CBORObject $object): self
    {
        [$ai, $data] = self::determineComponents(self::getTagId());
        return new self($ai, $data, $object);
    }
}
```

### Key Methods

#### Required Methods

- **`getTagId()`**: Returns the IANA-registered tag number
- **`createFromLoadedData()`**: Factory method used by the decoder

#### Optional Methods

- **`create()`**: Convenience method for creating tagged objects
- **`normalize()`**: Convert to native PHP types (implement `Normalizable` interface)
- **Constructor validation**: Add custom validation logic

## Implementation Steps

### Step 1: Choose a Tag Number

**For Private Use**: Use tag numbers in the range 256-55799 (unregistered)
**For Public Use**: Register with [IANA CBOR Tags Registry](https://www.iana.org/assignments/cbor-tags/)

```php
// Private use example
private const TAG_MY_CUSTOM = 1234;
```

### Step 2: Extend the Tag Class

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\Tag;

final class MyCustomTag extends Tag
{
    private const TAG_NUMBER = 1234;

    public static function getTagId(): int
    {
        return self::TAG_NUMBER;
    }

    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): Tag {
        return new self($additionalInformation, $data, $object);
    }
}
```

### Step 3: Add Validation (Optional)

Validate the wrapped object in the constructor:

```php
use InvalidArgumentException;
use CBOR\TextStringObject;

public function __construct(
    int $additionalInformation,
    ?string $data,
    CBORObject $object
) {
    // Validate that the object is the expected type
    if (!$object instanceof TextStringObject) {
        throw new InvalidArgumentException(
            'MyCustomTag only accepts TextStringObject'
        );
    }

    // Add custom validation
    $value = $object->getValue();
    if (strlen($value) < 5) {
        throw new InvalidArgumentException(
            'Value must be at least 5 characters'
        );
    }

    parent::__construct($additionalInformation, $data, $object);
}
```

### Step 4: Add Normalization (Optional)

Implement the `Normalizable` interface to convert to PHP types:

```php
use CBOR\Normalizable;

final class MyCustomTag extends Tag implements Normalizable
{
    public function normalize(): mixed
    {
        /** @var TextStringObject $object */
        $object = $this->object;

        // Custom transformation logic
        return strtoupper($object->getValue());
    }
}
```

### Step 5: Add Convenience Methods (Optional)

```php
public static function create(CBORObject $object): self
{
    [$ai, $data] = self::determineComponents(self::TAG_NUMBER);
    return new self($ai, $data, $object);
}

public static function createFromString(string $value): self
{
    return self::create(TextStringObject::create($value));
}
```

## Complete Examples

### Example 1: Simple Email Tag

A tag that marks text strings as email addresses.

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\Normalizable;
use CBOR\Tag;
use CBOR\TextStringObject;
use InvalidArgumentException;

/**
 * Tag 260: Email Address
 * Marks a text string as an RFC 5322 email address
 */
final class EmailTag extends Tag implements Normalizable
{
    private const TAG_EMAIL = 260;

    public function __construct(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ) {
        if (!$object instanceof TextStringObject
            && !$object instanceof IndefiniteLengthTextStringObject) {
            throw new InvalidArgumentException(
                'EmailTag only accepts text strings'
            );
        }

        // Validate email format
        $email = $object->getValue();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Invalid email address format'
            );
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_EMAIL;
    }

    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): Tag {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): self
    {
        [$ai, $data] = self::determineComponents(self::TAG_EMAIL);
        return new self($ai, $data, $object);
    }

    public static function createFromEmail(string $email): self
    {
        return self::create(TextStringObject::create($email));
    }

    public function normalize(): string
    {
        /** @var TextStringObject|IndefiniteLengthTextStringObject $object */
        $object = $this->object;
        return $object->normalize();
    }

    public function getEmail(): string
    {
        return $this->normalize();
    }
}
```

**Usage:**

```php
use CBOR\Tag\EmailTag;

// Create
$email = EmailTag::createFromEmail('user@example.com');
$encoded = (string) $email;

// Decode
$decoder = Decoder::create();
$decoded = $decoder->decode(StringStream::create($encoded));

if ($decoded instanceof EmailTag) {
    echo $decoded->getEmail(); // user@example.com
}
```

---

### Example 2: Geographic Coordinates

A tag for WGS84 coordinates (latitude, longitude, altitude).

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\ListObject;
use CBOR\Normalizable;
use CBOR\Tag;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use InvalidArgumentException;

/**
 * Tag 103: Geographic Coordinates (WGS-84)
 * Array of [latitude, longitude, altitude (optional)]
 */
final class GeoCoordinatesTag extends Tag implements Normalizable
{
    private const TAG_GEO_COORDINATES = 103;

    public function __construct(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ) {
        if (!$object instanceof ListObject) {
            throw new InvalidArgumentException(
                'GeoCoordinatesTag requires a ListObject'
            );
        }

        $count = count($object);
        if ($count < 2 || $count > 3) {
            throw new InvalidArgumentException(
                'GeoCoordinates must have 2 or 3 elements [lat, lon, alt?]'
            );
        }

        // Validate latitude
        $lat = $object->get(0);
        if (!$this->isNumeric($lat)) {
            throw new InvalidArgumentException('Latitude must be numeric');
        }

        // Validate longitude
        $lon = $object->get(1);
        if (!$this->isNumeric($lon)) {
            throw new InvalidArgumentException('Longitude must be numeric');
        }

        // Validate altitude if present
        if ($count === 3) {
            $alt = $object->get(2);
            if (!$this->isNumeric($alt)) {
                throw new InvalidArgumentException('Altitude must be numeric');
            }
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    private function isNumeric(CBORObject $object): bool
    {
        return $object instanceof UnsignedIntegerObject
            || $object instanceof NegativeIntegerObject
            || $object instanceof DoublePrecisionFloatObject
            || $object instanceof SinglePrecisionFloatObject;
    }

    public static function getTagId(): int
    {
        return self::TAG_GEO_COORDINATES;
    }

    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): Tag {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): self
    {
        [$ai, $data] = self::determineComponents(self::TAG_GEO_COORDINATES);
        return new self($ai, $data, $object);
    }

    public static function createFromCoordinates(
        float $latitude,
        float $longitude,
        ?float $altitude = null
    ): self {
        $list = ListObject::create([
            DoublePrecisionFloatObject::create($latitude),
            DoublePrecisionFloatObject::create($longitude),
        ]);

        if ($altitude !== null) {
            $list->add(DoublePrecisionFloatObject::create($altitude));
        }

        return self::create($list);
    }

    /**
     * @return array{latitude: float, longitude: float, altitude: float|null}
     */
    public function normalize(): array
    {
        /** @var ListObject $list */
        $list = $this->object;

        $result = [
            'latitude' => (float) $list->get(0)->normalize(),
            'longitude' => (float) $list->get(1)->normalize(),
            'altitude' => null,
        ];

        if (count($list) === 3) {
            $result['altitude'] = (float) $list->get(2)->normalize();
        }

        return $result;
    }

    public function getLatitude(): float
    {
        /** @var ListObject $list */
        $list = $this->object;
        return (float) $list->get(0)->normalize();
    }

    public function getLongitude(): float
    {
        /** @var ListObject $list */
        $list = $this->object;
        return (float) $list->get(1)->normalize();
    }

    public function getAltitude(): ?float
    {
        /** @var ListObject $list */
        $list = $this->object;

        if (count($list) === 3) {
            return (float) $list->get(2)->normalize();
        }

        return null;
    }
}
```

**Usage:**

```php
use CBOR\Tag\GeoCoordinatesTag;

// Create coordinates for Paris, France
$coords = GeoCoordinatesTag::createFromCoordinates(
    48.8566,  // latitude
    2.3522,   // longitude
    35.0      // altitude (meters)
);

$normalized = $coords->normalize();
/*
[
    'latitude' => 48.8566,
    'longitude' => 2.3522,
    'altitude' => 35.0
]
*/

echo $coords->getLatitude();  // 48.8566
echo $coords->getLongitude(); // 2.3522
echo $coords->getAltitude();  // 35.0
```

---

### Example 3: Complex Validated Tag

A tag with multiple validation rules and transformation logic.

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\MapObject;
use CBOR\Normalizable;
use CBOR\Tag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;

/**
 * Tag 1000: User Profile
 * Structured user profile with validation
 */
final class UserProfileTag extends Tag implements Normalizable
{
    private const TAG_USER_PROFILE = 1000;

    private const REQUIRED_KEYS = ['username', 'email', 'age'];

    public function __construct(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ) {
        if (!$object instanceof MapObject) {
            throw new InvalidArgumentException(
                'UserProfileTag requires a MapObject'
            );
        }

        // Validate required keys
        foreach (self::REQUIRED_KEYS as $key) {
            if (!$object->has($key)) {
                throw new InvalidArgumentException(
                    "Missing required key: {$key}"
                );
            }
        }

        // Validate username
        $username = $object->get('username');
        if (!$username instanceof TextStringObject) {
            throw new InvalidArgumentException('Username must be a text string');
        }
        if (strlen($username->getValue()) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters');
        }

        // Validate email
        $email = $object->get('email');
        if (!$email instanceof TextStringObject) {
            throw new InvalidArgumentException('Email must be a text string');
        }
        if (!filter_var($email->getValue(), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Validate age
        $age = $object->get('age');
        if (!$age instanceof UnsignedIntegerObject) {
            throw new InvalidArgumentException('Age must be an unsigned integer');
        }
        $ageValue = (int) $age->getValue();
        if ($ageValue < 0 || $ageValue > 150) {
            throw new InvalidArgumentException('Age must be between 0 and 150');
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_USER_PROFILE;
    }

    public static function createFromLoadedData(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ): Tag {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): self
    {
        [$ai, $data] = self::determineComponents(self::TAG_USER_PROFILE);
        return new self($ai, $data, $object);
    }

    public static function createFromArray(array $profile): self
    {
        $map = MapObject::create()
            ->add(
                TextStringObject::create('username'),
                TextStringObject::create($profile['username'])
            )
            ->add(
                TextStringObject::create('email'),
                TextStringObject::create($profile['email'])
            )
            ->add(
                TextStringObject::create('age'),
                UnsignedIntegerObject::create($profile['age'])
            );

        return self::create($map);
    }

    public function normalize(): array
    {
        /** @var MapObject $map */
        $map = $this->object;

        return [
            'username' => $map->get('username')->normalize(),
            'email' => $map->get('email')->normalize(),
            'age' => (int) $map->get('age')->normalize(),
        ];
    }
}
```

**Usage:**

```php
use CBOR\Tag\UserProfileTag;

// Create from array
$profile = UserProfileTag::createFromArray([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Validation happens automatically
try {
    $invalid = UserProfileTag::createFromArray([
        'username' => 'jo', // Too short!
        'email' => 'invalid-email',
        'age' => 30,
    ]);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Username must be at least 3 characters"
}
```

---

## Advanced Topics

### Handling Nested Tags

Tags can wrap other tagged values:

```php
// Timestamp wrapped in a custom "audit log" tag
$timestamp = TimestampTag::create(UnsignedIntegerObject::create(time()));
$auditLog = AuditLogTag::create($timestamp);
```

### Performance Considerations

- **Lazy Validation**: Validate only when necessary
- **Caching**: Cache normalized values if normalization is expensive
- **Type Checking**: Use `instanceof` checks sparingly

### Error Handling

```php
public function __construct(
    int $additionalInformation,
    ?string $data,
    CBORObject $object
) {
    try {
        // Validation logic
        $this->validate($object);
    } catch (\Exception $e) {
        throw new InvalidArgumentException(
            sprintf('Invalid %s: %s', static::class, $e->getMessage()),
            0,
            $e
        );
    }

    parent::__construct($additionalInformation, $data, $object);
}
```

---

## Integration with Other Specifications

### COSE (RFC 8152) - CBOR Object Signing and Encryption

COSE uses several CBOR tags:

- **Tag 98**: COSE Single Recipient Encrypted
- **Tag 96**: COSE Encrypted
- **Tag 97**: COSE MAC'd
- **Tag 98**: COSE Single Signer
- **Tag 18**: COSE Sign

Example implementation outline:

```php
namespace CBOR\Tag\COSE;

use CBOR\CBORObject;
use CBOR\Tag;
use CBOR\ListObject;

/**
 * Tag 98: COSE_Sign1 - Single Signer
 * @see https://datatracker.ietf.org/doc/html/rfc8152#section-4.2
 */
final class COSESign1Tag extends Tag
{
    private const TAG_COSE_SIGN1 = 98;

    public function __construct(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ) {
        if (!$object instanceof ListObject || count($object) !== 4) {
            throw new InvalidArgumentException(
                'COSE_Sign1 must be an array of 4 elements'
            );
        }

        // Validate structure: [protected, unprotected, payload, signature]
        // ... validation logic

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_COSE_SIGN1;
    }

    // ... implementation
}
```

### CWT (RFC 8392) - CBOR Web Token

CWT uses Tag 61 for the entire token:

```php
namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\Tag;
use CBOR\Tag\COSE\COSESign1Tag;

/**
 * Tag 61: CBOR Web Token (CWT)
 * @see https://datatracker.ietf.org/doc/html/rfc8392
 */
final class CWTTag extends Tag
{
    private const TAG_CWT = 61;

    public function __construct(
        int $additionalInformation,
        ?string $data,
        CBORObject $object
    ) {
        // CWT is typically a COSE_Sign1 or COSE_Mac0
        if (!$object instanceof COSESign1Tag
            && !$object instanceof COSEMac0Tag) {
            throw new InvalidArgumentException(
                'CWT must wrap a COSE structure'
            );
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    // ... implementation
}
```

### CoAP (RFC 7252) - Constrained Application Protocol

Custom tags for CoAP-specific data types.

### SenML (RFC 8428) - Sensor Markup Language

SenML uses CBOR extensively with custom semantics.

---

## Registering Custom Tags

### Step 1: Register with TagManager

```php
use CBOR\Decoder;
use CBOR\Tag\TagManager;

$tagManager = TagManager::create()
    ->add(EmailTag::class)
    ->add(GeoCoordinatesTag::class)
    ->add(UserProfileTag::class);

$decoder = Decoder::create($tagManager);
```

### Step 2: Use in Your Application

```php
// Encoding
$email = EmailTag::createFromEmail('user@example.com');
$encoded = (string) $email;

// Decoding
$decoded = $decoder->decode(StringStream::create($encoded));

if ($decoded instanceof EmailTag) {
    echo $decoded->getEmail();
}
```

---

## Testing Custom Tags

```php
use PHPUnit\Framework\TestCase;

class EmailTagTest extends TestCase
{
    public function testCreateFromValidEmail(): void
    {
        $tag = EmailTag::createFromEmail('test@example.com');

        $this->assertInstanceOf(EmailTag::class, $tag);
        $this->assertSame('test@example.com', $tag->getEmail());
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EmailTag::createFromEmail('not-an-email');
    }

    public function testEncodingAndDecoding(): void
    {
        $original = EmailTag::createFromEmail('test@example.com');
        $encoded = (string) $original;

        $tagManager = TagManager::create()->add(EmailTag::class);
        $decoder = Decoder::create($tagManager);

        $decoded = $decoder->decode(StringStream::create($encoded));

        $this->assertInstanceOf(EmailTag::class, $decoded);
        $this->assertSame('test@example.com', $decoded->getEmail());
    }
}
```

---

## Best Practices

1. **Use Appropriate Tag Numbers**: Check IANA registry to avoid conflicts
2. **Validate Early**: Validate in the constructor to fail fast
3. **Document Thoroughly**: Include RFC references and usage examples
4. **Implement Normalizable**: Make tags easy to work with
5. **Type-Safe**: Use strict types and type hints
6. **Test Extensively**: Test validation, encoding, and decoding
7. **Handle Edge Cases**: Consider null values, empty collections, etc.
8. **Follow Specifications**: When implementing standard tags, follow specs exactly

---

[← Back to Tags Reference](tags.md) | [Documentation Index](index.md)
