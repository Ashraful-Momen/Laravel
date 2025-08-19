# Deep Copy in PHP - Simple Guide

## Definition
**Copy object completely including all referenced/nested objects**

## Simple Concept
Copy the box AND everything inside it. Each copy is completely independent.

## Real-life Analogy
Like **building a new house** with the same blueprint - you get a new house with new furniture, new appliances, everything is separate.

## Code Example

```php
<?php
class Address 
{
    public $street;
    public $city;
    
    public function __construct($street, $city) 
    {
        $this->street = $street;
        $this->city = $city;
    }
    
    public function getFullAddress() 
    {
        return $this->street . ", " . $this->city;
    }
}

class Person 
{
    public $name;
    public $age;
    public $address;    // This is an OBJECT (reference)
    public $hobbies;    // This is an ARRAY
    
    public function __construct($name, $age, Address $address, array $hobbies) 
    {
        $this->name = $name;
        $this->age = $age;
        $this->address = $address;
        $this->hobbies = $hobbies;
    }
    
    public function getInfo() 
    {
        return "{$this->name} ({$this->age}) lives at " . $this->address->getFullAddress() . 
               " and likes: " . implode(", ", $this->hobbies);
    }
    
    // Deep copy implementation
    public function __clone() 
    {
        // Clone the address object (create new Address)
        $this->address = clone $this->address;
        
        // Clone the hobbies array (create new array)
        $this->hobbies = array_merge([], $this->hobbies);
        
        // Note: If hobbies contained objects, we'd need to clone those too!
    }
    
    public function createDeepCopy() 
    {
        return clone $this; // Uses our custom __clone method
    }
}

echo "=== DEEP COPY DEMONSTRATION ===\n";

$address = new Address("789 Pine St", "Boston");
$original = new Person("Maria", 28, $address, ["painting", "dancing"]);

echo "1. Original person:\n";
echo $original->getInfo() . "\n\n";

// Create deep copy
$copy = $original->createDeepCopy();
$copy->name = "Maria Copy";
$copy->age = 35;

echo "2. After creating deep copy and changing name/age:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "✅ Name and age are independent (as expected)\n\n";

echo "3. Now let's modify the ADDRESS in the copy:\n";

// THE SOLUTION: Modifying nested object affects only the copy!
$copy->address->street = "999 Elm Street";

echo "After changing address in copy:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "✅ SUCCESS: Original person's address stays the same!\n";
echo "Because each object has its own Address object in memory\n\n";

echo "4. Let's also modify the hobbies array:\n";
$copy->hobbies[] = "cooking";

echo "After adding hobby to copy:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "✅ SUCCESS: Original person's hobbies stay the same!\n";
echo "Because each object has its own hobbies array\n\n";

echo "=== VISUAL REPRESENTATION ===\n";
echo "DEEP COPY STRUCTURE:\n";
echo "Original → [name: 'Maria'] → Address Object A → [street: '789 Pine St']\n";
echo "\n";
echo "Copy     → [name: 'Copy'] → Address Object B → [street: '789 Pine St']\n\n";

echo "AFTER changing address in copy:\n";
echo "Original → [name: 'Maria'] → Address Object A → [street: '789 Pine St'] ← UNCHANGED!\n";
echo "\n";
echo "Copy     → [name: 'Copy'] → Address Object B → [street: '999 Elm St'] ← CHANGED!\n\n";

echo "=== COMPLEX DEEP COPY EXAMPLE ===\n";

class Contact 
{
    public $email;
    public $phone;
    
    public function __construct($email, $phone) 
    {
        $this->email = $email;
        $this->phone = $phone;
    }
}

class Company 
{
    public $name;
    public $address;
    public $contacts; // Array of Contact objects
    
    public function __construct($name, Address $address, array $contacts) 
    {
        $this->name = $name;
        $this->address = $address;
        $this->contacts = $contacts;
    }
    
    // Deep copy with nested objects in array
    public function __clone() 
    {
        // Clone the address
        $this->address = clone $this->address;
        
        // Clone each contact in the array
        $clonedContacts = [];
        foreach ($this->contacts as $contact) {
            $clonedContacts[] = clone $contact;
        }
        $this->contacts = $clonedContacts;
    }
    
    public function getInfo() 
    {
        $contactInfo = [];
        foreach ($this->contacts as $contact) {
            $contactInfo[] = $contact->email;
        }
        return "{$this->name} at " . $this->address->getFullAddress() . 
               " - Contacts: " . implode(", ", $contactInfo);
    }
}

$companyAddress = new Address("100 Business St", "NYC");
$contacts = [
    new Contact("ceo@company.com", "555-0001"),
    new Contact("hr@company.com", "555-0002")
];

$originalCompany = new Company("TechCorp", $companyAddress, $contacts);
$subsidiaryCompany = clone $originalCompany;

$subsidiaryCompany->name = "TechCorp Asia";
$subsidiaryCompany->address->city = "Tokyo";
$subsidiaryCompany->contacts[0]->email = "ceo@techcorp-asia.com";

echo "Original Company:\n";
echo $originalCompany->getInfo() . "\n\n";

echo "Subsidiary Company:\n";
echo $subsidiaryCompany->getInfo() . "\n\n";

echo "✅ Each company has completely independent data!\n\n";

echo "=== MANUAL DEEP COPY (Alternative Method) ===\n";

class SimpleUser 
{
    public $name;
    public $profile;
    
    public function __construct($name, $profile) 
    {
        $this->name = $name;
        $this->profile = $profile;
    }
    
    // Manual deep copy method
    public function createDeepCopy() 
    {
        $newProfile = clone $this->profile;
        return new self($this->name, $newProfile);
    }
}

class Profile 
{
    public $bio;
    public $settings;
    
    public function __construct($bio, $settings) 
    {
        $this->bio = $bio;
        $this->settings = $settings;
    }
}

$profile = new Profile("Software Developer", ["theme" => "dark"]);
$user1 = new SimpleUser("Alice", $profile);
$user2 = $user1->createDeepCopy();

$user2->name = "Alice Clone";
$user2->profile->bio = "Senior Developer";

echo "User 1: {$user1->name} - {$user1->profile->bio}\n";
echo "User 2: {$user2->name} - {$user2->profile->bio}\n";
echo "✅ Completely independent!\n";
?>
```

## What Gets Deep Copied

| Type | Behavior | Implementation |
|---|---|---|
| **Primitive values** | ✅ Automatically copied | No extra work needed |
| **Objects** | ✅ Create new instances | `$this->object = clone $this->object` |
| **Arrays of primitives** | ✅ Create new array | `$this->array = array_merge([], $this->array)` |
| **Arrays of objects** | ✅ Clone each object | Loop and clone each element |

## When Deep Copy is Essential

✅ **User profiles** - Each user needs independent data  
✅ **Shopping carts** - Each cart must be separate  
✅ **Document editing** - Each document copy should be independent  
✅ **Game objects** - Each player should have their own items  
✅ **Configuration templates** - Each instance should be customizable  

## Implementation Strategies

### Strategy 1: Custom __clone Method
```php
public function __clone() 
{
    $this->address = clone $this->address;
    $this->hobbies = array_merge([], $this->hobbies);
}
```

### Strategy 2: Serialization (for complex objects)
```php
public function createDeepCopy() 
{
    return unserialize(serialize($this));
}
```

### Strategy 3: Manual Construction
```php
public function createDeepCopy() 
{
    $newAddress = new Address($this->address->street, $this->address->city);
    return new Person($this->name, $this->age, $newAddress, $this->hobbies);
}
```

## Memory Usage

**Disadvantage:** Deep copy uses **more memory** because everything is duplicated

```
Original: 100KB (object) + 50KB (nested objects) = 150KB
Deep Copy: 100KB (new object) + 50KB (new nested objects) = 150KB extra
Total: 300KB for both
```

## Performance

**Disadvantage:** Deep copy is **slower** because it needs to copy all nested objects

```
Deep Copy Time: ~0.005 seconds (copy everything recursively)
```

## Common Pitfalls

❌ **Forgetting to clone nested objects**
```php
public function __clone() 
{
    // WRONG: This is still shallow copy
    // $this->address stays shared
}
```

❌ **Circular references** (objects pointing to each other)
```php
// Can cause infinite loops in deep copy
$user->company->owner = $user; // Circular reference!
```

❌ **Resource objects** (file handles, database connections)
```php
// Some objects can't/shouldn't be cloned
$this->fileHandle = clone $this->fileHandle; // ERROR!
```

## Best Practices

1. **Always implement __clone** when you have nested objects
2. **Test your deep copy** by modifying nested objects
3. **Consider memory usage** for large object graphs
4. **Handle circular references** carefully
5. **Don't clone resources** (files, connections, etc.)

## Bottom Line

**Deep Copy = Slower and uses more memory, but gives you completely independent copies**

Use when you need true isolation between original and copy, especially when you plan to modify nested objects!
