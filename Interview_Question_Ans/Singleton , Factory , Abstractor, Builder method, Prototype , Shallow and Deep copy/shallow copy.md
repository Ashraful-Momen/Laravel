# Shallow Copy in PHP - Simple Guide

## Definition
**Copy an object but properties that use references/pointers still point to the same memory location**

## Simple Concept
Copy the box, but inside objects are still shared between original and copy.

## Real-life Analogy
Like **photocopying a house blueprint** - you get a new blueprint, but both blueprints point to the same actual furniture in the house.

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
    
    // Shallow copy method
    public function createShallowCopy() 
    {
        return clone $this; // PHP default clone = shallow copy
    }
}

echo "=== SHALLOW COPY DEMONSTRATION ===\n";

$address = new Address("123 Main St", "New York");
$original = new Person("John", 25, $address, ["reading", "coding"]);

echo "1. Original person:\n";
echo $original->getInfo() . "\n\n";

// Create shallow copy
$copy = $original->createShallowCopy();
$copy->name = "John Copy";
$copy->age = 30;

echo "2. After creating shallow copy and changing name/age:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "✅ Name and age are independent (primitive values)\n\n";

echo "3. Now let's modify the ADDRESS in the copy:\n";

// THE PROBLEM: Modifying nested object affects both!
$copy->address->street = "456 Oak Avenue";

echo "After changing address in copy:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "🚨 PROBLEM: Original person's address ALSO changed!\n";
echo "Because both objects share the same Address object in memory\n\n";

echo "4. Let's also modify the hobbies array:\n";
$copy->hobbies[] = "swimming";

echo "After adding hobby to copy:\n";
echo "Original: " . $original->getInfo() . "\n";
echo "Copy: " . $copy->getInfo() . "\n\n";

echo "🚨 PROBLEM: Original person's hobbies ALSO changed!\n";
echo "Because both objects share the same array in memory\n\n";

echo "=== VISUAL REPRESENTATION ===\n";
echo "BEFORE:\n";
echo "Original → [name: 'John'] → Address Object → [street: '123 Main St']\n";
echo "                              ↗\n";
echo "Copy     → [name: 'Copy'] → ↗ (points to same address)\n\n";

echo "AFTER changing address in copy:\n";
echo "Original → [name: 'John'] → Address Object → [street: '456 Oak Avenue'] ← CHANGED!\n";
echo "                              ↗\n";
echo "Copy     → [name: 'Copy'] → ↗ (same address object)\n\n";

echo "=== SIMPLE DEMONSTRATION ===\n";

class SharedData 
{
    public $value;
    
    public function __construct($value) 
    {
        $this->value = $value;
    }
}

class Container 
{
    public $id;
    public $data;
    
    public function __construct($id, SharedData $data) 
    {
        $this->id = $id;
        $this->data = $data;
    }
}

$sharedData = new SharedData("important info");
$container1 = new Container(1, $sharedData);
$container2 = clone $container1; // Shallow copy
$container2->id = 2;

echo "Before changing shared data:\n";
echo "Container 1: ID={$container1->id}, Data='{$container1->data->value}'\n";
echo "Container 2: ID={$container2->id}, Data='{$container2->data->value}'\n\n";

// Change data in container2
$container2->data->value = "MODIFIED!";

echo "After changing data in container2:\n";
echo "Container 1: ID={$container1->id}, Data='{$container1->data->value}'\n"; // ALSO CHANGED!
echo "Container 2: ID={$container2->id}, Data='{$container2->data->value}'\n";

echo "\n📝 Notice: Both containers now show 'MODIFIED!' because they share the same data object\n";
?>
```

## What Gets Copied vs Shared

| Type | Behavior | Example |
|---|---|---|
| **Primitive values** | ✅ Copied independently | `$name`, `$age`, `$price` |
| **Objects** | ⚠️ Reference shared | `$address`, `$user` |
| **Arrays** | ⚠️ Reference shared | `$hobbies`, `$items` |

## When Shallow Copy is Good

✅ **Configuration objects** - All users should share same config  
✅ **Database connections** - Share connection pool  
✅ **Cache objects** - Share same cache instance  
✅ **Read-only data** - Data that never changes  
✅ **Performance critical** - When speed matters more than safety  

## When Shallow Copy is Dangerous

❌ **User profiles** - Each user should have independent data  
❌ **Shopping carts** - Each cart should be separate  
❌ **Form data** - Each form should be independent  
❌ **Temporary objects** - When you plan to modify them  

## Common Mistakes

```php
// MISTAKE: Expecting independent copies
$user1 = new User("John", $address);
$user2 = clone $user1;
$user2->address->street = "New Street"; // This changes BOTH users!

// MISTAKE: Modifying shared arrays
$product1 = new Product("Phone", $features);
$product2 = clone $product1;
$product2->features[] = "New feature"; // This affects BOTH products!
```

## Memory Usage

**Advantage:** Shallow copy uses **less memory** because objects are shared

```
Original: 100KB (object) + 50KB (nested objects) = 150KB
Shallow Copy: 100KB (new object) + 0KB (shared nested) = 100KB extra
Total: 250KB for both
```

## Performance

**Advantage:** Shallow copy is **faster** because it doesn't need to copy nested objects

```
Shallow Copy Time: ~0.001 seconds (just copy references)
```

## Bottom Line

**Shallow Copy = Fast and memory-efficient, but changes to nested objects affect both copies**

Use when you want to share certain data between copies, but be careful about unintended modifications!
