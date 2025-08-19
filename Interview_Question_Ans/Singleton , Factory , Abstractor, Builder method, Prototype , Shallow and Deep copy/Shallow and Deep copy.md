# Deep Copy vs Shallow Copy in PHP - Simple Guide

## Definition

**Shallow Copy:** Copy an object but properties that use references/pointers still point to the same memory location

**Deep Copy:** Copy object completely including all referenced/nested objects

## Simple Concept

- **Shallow Copy** = Copy the box, but inside objects are still shared
- **Deep Copy** = Copy the box AND everything inside it

## Real-life Analogy
Imagine copying a **house blueprint**:
- **Shallow Copy** = Copy the blueprint, but both blueprints point to the same actual furniture
- **Deep Copy** = Copy the blueprint AND make new furniture for each house

## Code Example

```php
<?php
// Helper class to understand the concept
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
}

// Shallow Copy Class (default PHP behavior)
class PersonShallow extends Person 
{
    public function createShallowCopy() 
    {
        return clone $this; // PHP default clone = shallow copy
    }
    
    public function __clone() 
    {
        // Do nothing - this creates shallow copy
    }
}

// Deep Copy Class
class PersonDeep extends Person 
{
    public function createDeepCopy() 
    {
        return clone $this; // Will use our custom __clone method
    }
    
    public function __clone() 
    {
        // Deep copy: clone the address object
        $this->address = clone $this->address;
        
        // Deep copy: create new array (if array had objects, clone those too)
        $this->hobbies = array_merge([], $this->hobbies);
    }
}

echo "=== SHALLOW COPY DEMONSTRATION ===\n";

$address = new Address("123 Main St", "New York");
$originalPerson = new PersonShallow("John", 25, $address, ["reading", "coding"]);

echo "Original: " . $originalPerson->getInfo() . "\n";

// Create shallow copy
$shallowCopy = $originalPerson->createShallowCopy();
$shallowCopy->name = "John Copy";
$shallowCopy->age = 30;

echo "After creating shallow copy:\n";
echo "Original: " . $originalPerson->getInfo() . "\n";
echo "Shallow Copy: " . $shallowCopy->getInfo() . "\n";

echo "\n--- Now let's modify the ADDRESS in shallow copy ---\n";

// PROBLEM: Modifying address in copy affects original!
$shallowCopy->address->street = "456 Oak Avenue";
$shallowCopy->hobbies[] = "swimming";

echo "After modifying address in shallow copy:\n";
echo "Original: " . $originalPerson->getInfo() . "\n";        // ADDRESS CHANGED!
echo "Shallow Copy: " . $shallowCopy->getInfo() . "\n";

echo "\n🚨 PROBLEM: Original person's address changed too!\n";
echo "Because both objects point to the same Address object in memory\n\n";

echo "=== DEEP COPY DEMONSTRATION ===\n";

$address2 = new Address("789 Pine St", "Boston");
$originalPerson2 = new PersonDeep("Maria", 28, $address2, ["painting", "dancing"]);

echo "Original: " . $originalPerson2->getInfo() . "\n";

// Create deep copy
$deepCopy = $originalPerson2->createDeepCopy();
$deepCopy->name = "Maria Copy";
$deepCopy->age = 35;

echo "After creating deep copy:\n";
echo "Original: " . $originalPerson2->getInfo() . "\n";
echo "Deep Copy: " . $deepCopy->getInfo() . "\n";

echo "\n--- Now let's modify the ADDRESS in deep copy ---\n";

// SOLUTION: Modifying address in copy does NOT affect original!
$deepCopy->address->street = "999 Elm Street";
$deepCopy->hobbies[] = "cooking";

echo "After modifying address in deep copy:\n";
echo "Original: " . $originalPerson2->getInfo() . "\n";      // ADDRESS UNCHANGED!
echo "Deep Copy: " . $deepCopy->getInfo() . "\n";

echo "\n✅ SUCCESS: Original person's address stays the same!\n";
echo "Because each object has its own Address object in memory\n\n";

echo "=== SIMPLE VISUAL EXPLANATION ===\n";

class SimpleExample 
{
    public $number;
    public $object;
    
    public function __construct($number, $object) 
    {
        $this->number = $number;
        $this->object = $object;
    }
}

class SharedObject 
{
    public $value;
    
    public function __construct($value) 
    {
        $this->value = $value;
    }
}

// Create original
$sharedObj = new SharedObject("shared data");
$original = new SimpleExample(10, $sharedObj);

// Shallow copy (default)
$shallow = clone $original;

// Deep copy (manual)
$deep = clone $original;
$deep->object = clone $original->object; // Clone the nested object too

echo "Original object value: " . $original->object->value . "\n";
echo "Shallow copy object value: " . $shallow->object->value . "\n";
echo "Deep copy object value: " . $deep->object->value . "\n";

echo "\n--- Changing original object value ---\n";
$original->object->value = "CHANGED!";

echo "Original object value: " . $original->object->value . "\n";
echo "Shallow copy object value: " . $shallow->object->value . "\n";    // ALSO CHANGED!
echo "Deep copy object value: " . $deep->object->value . "\n";          // UNCHANGED!

echo "\n--- Summary ---\n";
echo "Shallow copy shares the inner object (both changed)\n";
echo "Deep copy has its own inner object (only original changed)\n";
?>
```

## Key Differences

| Shallow Copy | Deep Copy |
|---|---|
| **Fast** - just copies references | **Slower** - creates new objects |
| **Memory efficient** - shares objects | **More memory** - duplicates everything |
| **Dangerous** - changes affect both copies | **Safe** - independent copies |
| **Default PHP behavior** | **Requires custom `__clone` method** |

## When to Use Each

### Use Shallow Copy When:
✅ Inner objects should be shared  
✅ Memory is limited  
✅ Objects are immutable (never change)  
✅ Performance is critical  

### Use Deep Copy When:
✅ You need completely independent copies  
✅ You'll modify nested objects  
✅ Safety is more important than performance  
✅ Working with user data/configurations  

## Common Scenarios

**Shallow Copy Example:**
```php
// Configuration objects that should be shared
$config = new DatabaseConfig("localhost", "mydb");
$user1 = new User("John", $config);  // All users share same config
$user2 = clone $user1;               // Should share config
```

**Deep Copy Example:**
```php
// User profiles that should be independent
$address = new Address("123 Main St", "NYC");
$user1 = new User("John", $address);
$user2 = clone $user1;               // Should have own address
```

## Memory Visualization

```
SHALLOW COPY:
Original  → [name: "John"] → Address Object → [street: "Main St"]
                              ↗
Copy      → [name: "Copy"] → ↗ (points to same address)

DEEP COPY:
Original → [name: "John"] → Address Object A → [street: "Main St"]

Copy     → [name: "Copy"] → Address Object B → [street: "Main St"]
```

## Bottom Line

- **Shallow Copy** = Copy the container, share the contents
- **Deep Copy** = Copy the container AND the contents
- Choose based on whether you want changes to affect both copies or not!
