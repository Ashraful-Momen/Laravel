<?php

/*
===============================================================================
OBSERVER PATTERN - সম্পূর্ণ নোট ও ব্যাখ্যা
===============================================================================

OBSERVER PATTERN কি?
--------------------
Observer Pattern হলো একটি Behavioral Design Pattern যেখানে:
- একটি object (Subject) তার state change এর খবর multiple objects (Observers) কে জানায়
- Subject এবং Observer এর মধ্যে loose coupling maintain করে
- One-to-Many dependency relationship তৈরি করে

কেন ব্যবহার করব?
------------------
✅ Loose Coupling - Subject এবং Observer independent থাকে
✅ Dynamic Relationships - Runtime এ observers add/remove করা যায়
✅ Open/Closed Principle - নতুন observers add করা যায় existing code modify না করে
✅ Event-Driven Architecture - Event handling এর জন্য perfect
✅ Separation of Concerns - Different observers different responsibilities handle করে

REAL-WORLD EXAMPLES:
-------------------
🔔 Newsletter Subscription - Users subscribe to get notifications
📺 YouTube Channel - Subscribers get notified of new videos  
📱 Social Media - Followers get notified of new posts
📧 Email Lists - Subscribers get email updates
🛒 E-commerce - Notify when product is back in stock
📊 Stock Market - Investors get price change notifications
*/

// ===============================================================================
// OBSERVER INTERFACE - OBSERVER এর CONTRACT
// ===============================================================================

/*
OBSERVER INTERFACE EXPLANATION:
------------------------------
এই interface সব Observer class implement করবে।
update() method এর মাধ্যমে Subject থেকে notification receive করবে।

Parameters:
- $event: কি ধরনের event ঘটেছে (string)
- $data: Event related data (mixed type)
*/
interface ObserverInterface {
    /*
    UPDATE METHOD:
    -------------
    Purpose: Subject থেকে notification receive করা
    
    Parameters:
    - string $event: Event type (e.g., 'user_created', 'email_changed')  
    - mixed $data: Event এর সাথে related data
    
    Return: void (কিছু return করে না)
    */
    public function update(string $event, $data): void;
}

// ===============================================================================
// SUBJECT INTERFACE - SUBJECT এর CONTRACT  
// ===============================================================================

/*
SUBJECT INTERFACE EXPLANATION:
------------------------------
এই interface সব Subject class implement করবে।
Observer management এর জন্য standard methods provide করে।
*/
interface SubjectInterface {
    /*
    ATTACH METHOD:
    -------------
    Purpose: নতুন Observer add করা
    Observer list এ observer কে add করে।
    */
    public function attach(ObserverInterface $observer): void;
    
    /*
    DETACH METHOD:
    -------------
    Purpose: Observer remove করা
    Observer list থেকে observer কে remove করে।
    */
    public function detach(ObserverInterface $observer): void;
    
    /*
    NOTIFY METHOD:
    -------------
    Purpose: সব registered observers কে notification পাঠানো
    Event ঘটলে এই method call করে সব observers কে inform করে।
    */
    public function notify(string $event, $data = null): void;
}

// ===============================================================================
// USER CLASS - SUBJECT IMPLEMENTATION
// ===============================================================================

/*
USER CLASS EXPLANATION:
-----------------------
Subject interface implement করে।
User এর state change হলে observers দের notify করে।
*/
class User implements SubjectInterface {
    /*
    PROPERTIES:
    ----------
    $observers: Array of registered observers
    $name: User এর নাম
    $email: User এর email (state change track করার জন্য)
    */
    private $observers = [];
    private $name;
    private $email;
    private $status;
    private $lastLogin;
    
    public function __construct(string $name, string $email) {
        $this->name = $name;
        $this->email = $email;
        $this->status = 'active';
        $this->lastLogin = null;
        
        echo "👤 User created: $name ($email)\n";
        
        // User creation event notify করা
        $this->notify('user_created', [
            'name' => $name,
            'email' => $email,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /*
    ATTACH METHOD IMPLEMENTATION:
    ----------------------------
    Observer কে observers array তে add করে।
    */
    public function attach(ObserverInterface $observer): void {
        $this->observers[] = $observer;
        $observerClass = get_class($observer);
        
        echo "🔗 Observer attached: $observerClass\n";
        echo "📊 Total observers: " . count($this->observers) . "\n";
        
        // Observer attachment event
        $this->notify('observer_attached', [
            'observer_type' => $observerClass,
            'total_observers' => count($this->observers)
        ]);
    }
    
    /*
    DETACH METHOD IMPLEMENTATION:
    ----------------------------
    Observer কে observers array থেকে remove করে।
    array_search() দিয়ে observer খুঁজে unset() করে।
    */
    public function detach(ObserverInterface $observer): void {
        $key = array_search($observer, $this->observers);
        
        if ($key !== false) {
            $observerClass = get_class($observer);
            unset($this->observers[$key]);
            
            echo "🔓 Observer detached: $observerClass\n";
            echo "📊 Remaining observers: " . count($this->observers) . "\n";
            
            // Observer detachment event  
            $this->notify('observer_detached', [
                'observer_type' => $observerClass,
                'remaining_observers' => count($this->observers)
            ]);
        } else {
            echo "⚠️ Observer not found for detachment\n";
        }
    }
    
    /*
    NOTIFY METHOD IMPLEMENTATION:
    ----------------------------
    সব registered observers এর update() method call করে।
    Loop করে প্রতিটি observer কে event এবং data পাঠায়।
    */
    public function notify(string $event, $data = null): void {
        echo "📢 Notifying " . count($this->observers) . " observers about '$event' event\n";
        
        foreach ($this->observers as $index => $observer) {
            try {
                $observer->update($event, $data);
                echo "✅ Observer " . ($index + 1) . " notified successfully\n";
            } catch (Exception $e) {
                echo "❌ Error notifying observer " . ($index + 1) . ": " . $e->getMessage() . "\n";
            }
        }
        
        echo "🎯 Notification process completed\n\n";
    }
    
    /*
    STATE CHANGE METHODS:
    --------------------
    এই methods গুলো User এর state change করে এবং observers কে notify করে।
    */
    
    // Email change method
    public function setEmail(string $email): void {
        if ($this->email === $email) {
            echo "ℹ️ Email is already set to: $email\n";
            return;
        }
        
        $oldEmail = $this->email;
        $this->email = $email;
        
        echo "📧 Email changed from '$oldEmail' to '$email'\n";
        
        // Email change event notify
        $this->notify('email_changed', [
            'user_name' => $this->name,
            'old_email' => $oldEmail,
            'new_email' => $email,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Status change method
    public function setStatus(string $status): void {
        $validStatuses = ['active', 'inactive', 'suspended', 'banned'];
        
        if (!in_array($status, $validStatuses)) {
            echo "❌ Invalid status: $status\n";
            return;
        }
        
        if ($this->status === $status) {
            echo "ℹ️ Status is already: $status\n";
            return;
        }
        
        $oldStatus = $this->status;
        $this->status = $status;
        
        echo "🔄 Status changed from '$oldStatus' to '$status'\n";
        
        // Status change event notify
        $this->notify('status_changed', [
            'user_name' => $this->name,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Login method
    public function login(): void {
        $this->lastLogin = date('Y-m-d H:i:s');
        
        echo "🔑 User '$this->name' logged in at $this->lastLogin\n";
        
        // Login event notify
        $this->notify('user_logged_in', [
            'user_name' => $this->name,
            'email' => $this->email,
            'login_time' => $this->lastLogin,
            'ip_address' => '192.168.1.1' // Mock IP
        ]);
    }
    
    // Profile update method
    public function updateProfile(array $profileData): void {
        echo "📝 Profile updated for user: $this->name\n";
        
        // Profile update event notify
        $this->notify('profile_updated', [
            'user_name' => $this->name,
            'email' => $this->email,
            'updated_fields' => $profileData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Getter methods
    public function getName(): string {
        return $this->name;
    }
    
    public function getEmail(): string {
        return $this->email;
    }
    
    public function getStatus(): string {
        return $this->status;
    }
    
    public function getLastLogin(): ?string {
        return $this->lastLogin;
    }
    
    public function getObserverCount(): int {
        return count($this->observers);
    }
}

// ===============================================================================
// OBSERVER IMPLEMENTATIONS - DIFFERENT TYPES OF OBSERVERS
// ===============================================================================

/*
EMAIL NOTIFICATION OBSERVER:
---------------------------
Email notification পাঠানোর জন্য Observer।
Specific events এর জন্য email send করে।
*/
class EmailNotificationObserver implements ObserverInterface {
    private $emailService;
    
    public function __construct(string $emailService = 'SMTP') {
        $this->emailService = $emailService;
        echo "📧 EmailNotificationObserver created with service: $emailService\n";
    }
    
    /*
    UPDATE METHOD IMPLEMENTATION:
    ----------------------------
    বিভিন্ন events এর জন্য বিভিন্ন email notification send করে।
    */
    public function update(string $event, $data): void {
        echo "📧 [EMAIL OBSERVER] Processing event: $event\n";
        
        switch ($event) {
            case 'email_changed':
                $this->sendEmailChangeNotification($data);
                break;
                
            case 'status_changed':
                $this->sendStatusChangeNotification($data);
                break;
                
            case 'user_logged_in':
                $this->sendLoginNotification($data);
                break;
                
            case 'user_created':
                $this->sendWelcomeEmail($data);
                break;
                
            default:
                echo "📧 No email template for event: $event\n";
        }
    }
    
    private function sendEmailChangeNotification($data): void {
        echo "📤 Sending email change notification...\n";
        echo "   To: {$data['new_email']}\n";
        echo "   Subject: Email Address Changed\n";
        echo "   Message: Your email has been changed from {$data['old_email']} to {$data['new_email']}\n";
    }
    
    private function sendStatusChangeNotification($data): void {
        echo "📤 Sending status change notification...\n";
        echo "   To: User {$data['user_name']}\n";
        echo "   Subject: Account Status Changed\n";
        echo "   Message: Your account status changed to: {$data['new_status']}\n";
    }
    
    private function sendLoginNotification($data): void {
        echo "📤 Sending login notification...\n";
        echo "   To: {$data['email']}\n";
        echo "   Subject: Login Detected\n";
        echo "   Message: Login detected at {$data['login_time']} from IP: {$data['ip_address']}\n";
    }
    
    private function sendWelcomeEmail($data): void {
        echo "📤 Sending welcome email...\n";
        echo "   To: {$data['email']}\n";
        echo "   Subject: Welcome to our platform!\n";
        echo "   Message: Hello {$data['name']}, welcome to our platform!\n";
    }
}

/*
LOG OBSERVER:
------------
সব events কে log file এ record করার জন্য Observer।
Debugging এবং auditing এর জন্য useful।
*/
class LogObserver implements ObserverInterface {
    private $logFile;
    private $logLevel;
    
    public function __construct(string $logFile = 'app.log', string $logLevel = 'INFO') {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
        echo "📝 LogObserver created - File: $logFile, Level: $logLevel\n";
    }
    
    public function update(string $event, $data): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$this->logLevel}] Event: $event";
        
        if ($data) {
            $logEntry .= " | Data: " . json_encode($data);
        }
        
        echo "📝 [LOG OBSERVER] $logEntry\n";
        
        // Real implementation would write to file:
        // file_put_contents($this->logFile, $logEntry . "\n", FILE_APPEND);
        
        // Log different events with different priorities
        switch ($event) {
            case 'user_created':
                echo "🟢 [INFO] New user registration logged\n";
                break;
            case 'user_logged_in':
                echo "🔵 [INFO] User login activity logged\n";
                break;
            case 'status_changed':
                echo "🟡 [WARNING] User status change logged\n";
                break;
            case 'email_changed':
                echo "🟠 [NOTICE] Email change logged\n";
                break;
            default:
                echo "⚪ [DEBUG] General event logged\n";
        }
    }
}

/*
ANALYTICS OBSERVER:
------------------
User behavior analytics track করার জন্য Observer।
Business intelligence এর জন্য data collect করে।
*/
class AnalyticsObserver implements ObserverInterface {
    private $analyticsService;
    private $metrics = [];
    
    public function __construct(string $analyticsService = 'Google Analytics') {
        $this->analyticsService = $analyticsService;
        echo "📊 AnalyticsObserver created with service: $analyticsService\n";
    }
    
    public function update(string $event, $data): void {
        echo "📊 [ANALYTICS OBSERVER] Tracking event: $event\n";
        
        // Event metrics collect করা
        if (!isset($this->metrics[$event])) {
            $this->metrics[$event] = 0;
        }
        $this->metrics[$event]++;
        
        switch ($event) {
            case 'user_created':
                $this->trackUserRegistration($data);
                break;
                
            case 'user_logged_in':
                $this->trackUserLogin($data);
                break;
                
            case 'email_changed':
                $this->trackEmailChange($data);
                break;
                
            case 'profile_updated':
                $this->trackProfileUpdate($data);
                break;
        }
        
        echo "📈 Total '$event' events tracked: {$this->metrics[$event]}\n";
    }
    
    private function trackUserRegistration($data): void {
        echo "📈 Tracking user registration...\n";
        echo "   User: {$data['name']}\n";
        echo "   Timestamp: {$data['timestamp']}\n";
        echo "   Sending to {$this->analyticsService}...\n";
    }
    
    private function trackUserLogin($data): void {
        echo "📈 Tracking user login...\n";
        echo "   User: {$data['user_name']}\n";
        echo "   Login Time: {$data['login_time']}\n";
        echo "   IP: {$data['ip_address']}\n";
    }
    
    private function trackEmailChange($data): void {
        echo "📈 Tracking email change...\n";
        echo "   User: {$data['user_name']}\n";
        echo "   Change Time: {$data['timestamp']}\n";
    }
    
    private function trackProfileUpdate($data): void {
        echo "📈 Tracking profile update...\n";
        echo "   User: {$data['user_name']}\n";
        echo "   Updated Fields: " . implode(', ', array_keys($data['updated_fields'])) . "\n";
    }
    
    public function getMetrics(): array {
        return $this->metrics;
    }
}

/*
SECURITY OBSERVER:
-----------------
Security events monitor করার জন্য Observer।
Suspicious activities detect করে security measures নেয়।
*/
class SecurityObserver implements ObserverInterface {
    private $securityService;
    private $suspiciousActivities = [];
    
    public function __construct(string $securityService = 'Security Monitor') {
        $this->securityService = $securityService;
        echo "🔒 SecurityObserver created with service: $securityService\n";
    }
    
    public function update(string $event, $data): void {
        echo "🔒 [SECURITY OBSERVER] Monitoring event: $event\n";
        
        switch ($event) {
            case 'user_logged_in':
                $this->monitorLogin($data);
                break;
                
            case 'email_changed':
                $this->monitorEmailChange($data);
                break;
                
            case 'status_changed':
                $this->monitorStatusChange($data);
                break;
                
            case 'profile_updated':
                $this->monitorProfileUpdate($data);
                break;
        }
    }
    
    private function monitorLogin($data): void {
        echo "🔒 Monitoring login activity...\n";
        
        // Mock: Check for suspicious login patterns
        $hour = (int)date('H');
        if ($hour < 6 || $hour > 22) {
            echo "⚠️ [SECURITY ALERT] Unusual login time detected: {$data['login_time']}\n";
            $this->logSuspiciousActivity('unusual_login_time', $data);
        }
        
        // Mock: Check for known malicious IPs
        if ($data['ip_address'] === '192.168.1.666') {
            echo "🚨 [SECURITY ALERT] Login from suspicious IP: {$data['ip_address']}\n";
            $this->logSuspiciousActivity('suspicious_ip', $data);
        }
        
        echo "✅ Login security check completed\n";
    }
    
    private function monitorEmailChange($data): void {
        echo "🔒 Monitoring email change...\n";
        
        // Check for rapid email changes (possible account takeover)
        echo "⚠️ [SECURITY NOTICE] Email change detected for user: {$data['user_name']}\n";
        echo "   Old: {$data['old_email']} -> New: {$data['new_email']}\n";
        
        $this->logSuspiciousActivity('email_change', $data);
    }
    
    private function monitorStatusChange($data): void {
        echo "🔒 Monitoring status change...\n";
        
        if ($data['new_status'] === 'suspended' || $data['new_status'] === 'banned') {
            echo "🚨 [SECURITY ALERT] Account restricted: {$data['user_name']} -> {$data['new_status']}\n";
            $this->logSuspiciousActivity('account_restricted', $data);
        }
    }
    
    private function monitorProfileUpdate($data): void {
        echo "🔒 Monitoring profile update...\n";
        
        $sensitiveFields = ['password', 'security_question', 'phone'];
        $updatedFields = array_keys($data['updated_fields']);
        
        foreach ($sensitiveFields as $field) {
            if (in_array($field, $updatedFields)) {
                echo "⚠️ [SECURITY NOTICE] Sensitive field updated: $field\n";
                $this->logSuspiciousActivity('sensitive_field_update', $data);
                break;
            }
        }
    }
    
    private function logSuspiciousActivity(string $type, array $data): void {
        $this->suspiciousActivities[] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo "📋 Suspicious activity logged: $type\n";
    }
    
    public function getSuspiciousActivities(): array {
        return $this->suspiciousActivities;
    }
}

/*
CACHE OBSERVER:
--------------
Cache invalidation এর জন্য Observer।
User data change হলে related cache clear করে।
*/
class CacheObserver implements ObserverInterface {
    private $cacheService;
    private $clearedCaches = [];
    
    public function __construct(string $cacheService = 'Redis') {
        $this->cacheService = $cacheService;
        echo "🗄️ CacheObserver created with service: $cacheService\n";
    }
    
    public function update(string $event, $data): void {
        echo "🗄️ [CACHE OBSERVER] Processing cache invalidation for event: $event\n";
        
        switch ($event) {
            case 'email_changed':
                $this->invalidateUserCache($data['user_name']);
                $this->invalidateEmailCache($data['old_email'], $data['new_email']);
                break;
                
            case 'status_changed':
                $this->invalidateUserCache($data['user_name']);
                $this->invalidateStatusCache($data['user_name']);
                break;
                
            case 'profile_updated':
                $this->invalidateUserCache($data['user_name']);
                $this->invalidateProfileCache($data['user_name']);
                break;
                
            case 'user_logged_in':
                $this->updateLoginCache($data['user_name']);
                break;
        }
    }
    
    private function invalidateUserCache(string $userName): void {
        $cacheKey = "user_data_{$userName}";
        echo "🗑️ Invalidating user cache: $cacheKey\n";
        $this->clearedCaches[] = $cacheKey;
        
        // Real implementation:
        // $this->cacheService->delete($cacheKey);
    }
    
    private function invalidateEmailCache(string $oldEmail, string $newEmail): void {
        $oldCacheKey = "user_by_email_{$oldEmail}";
        $newCacheKey = "user_by_email_{$newEmail}";
        
        echo "🗑️ Invalidating email caches: $oldCacheKey, $newCacheKey\n";
        $this->clearedCaches[] = $oldCacheKey;
        $this->clearedCaches[] = $newCacheKey;
    }
    
    private function invalidateStatusCache(string $userName): void {
        $cacheKey = "user_status_{$userName}";
        echo "🗑️ Invalidating status cache: $cacheKey\n";
        $this->clearedCaches[] = $cacheKey;
    }
    
    private function invalidateProfileCache(string $userName): void {
        $cacheKey = "user_profile_{$userName}";
        echo "🗑️ Invalidating profile cache: $cacheKey\n";
        $this->clearedCaches[] = $cacheKey;
    }
    
    private function updateLoginCache(string $userName): void {
        $cacheKey = "last_login_{$userName}";
        echo "♻️ Updating login cache: $cacheKey\n";
        
        // Real implementation:
        // $this->cacheService->set($cacheKey, time(), 3600); // 1 hour TTL
    }
    
    public function getClearedCaches(): array {
        return $this->clearedCaches;
    }
}

// ===============================================================================
// ADVANCED OBSERVER MANAGEMENT CLASS
// ===============================================================================

/*
OBSERVER MANAGER:
----------------
Observer Pattern এর জন্য helper class।
Multiple subjects এর observers manage করার জন্য।
*/
class ObserverManager {
    private $observerRegistry = [];
    
    public function registerObserver(string $observerType, ObserverInterface $observer): void {
        if (!isset($this->observerRegistry[$observerType])) {
            $this->observerRegistry[$observerType] = [];
        }
        
        $this->observerRegistry[$observerType][] = $observer;
        echo "📋 Observer registered: $observerType\n";
    }
    
    public function attachObserversToSubject(SubjectInterface $subject, array $observerTypes = []): void {
        if (empty($observerTypes)) {
            $observerTypes = array_keys($this->observerRegistry);
        }
        
        foreach ($observerTypes as $type) {
            if (isset($this->observerRegistry[$type])) {
                foreach ($this->observerRegistry[$type] as $observer) {
                    $subject->attach($observer);
                }
            }
        }
        
        echo "🔗 Attached observers of types: " . implode(', ', $observerTypes) . "\n";
    }
    
    public function getRegisteredObserverTypes(): array {
        return array_keys($this->observerRegistry);
    }
    
    public function getObserverCount(string $type): int {
        return count($this->observerRegistry[$type] ?? []);
    }
}

// ===============================================================================
// COMPREHENSIVE DEMONSTRATION
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "🎭 OBSERVER PATTERN COMPREHENSIVE DEMONSTRATION\n";
echo str_repeat("=", 80) . "\n\n";

// Create Observer Manager
$observerManager = new ObserverManager();

// Register different types of observers
echo "📋 REGISTERING OBSERVERS...\n";
echo str_repeat("-", 40) . "\n";

$observerManager->registerObserver('email', new EmailNotificationObserver('SendGrid'));
$observerManager->registerObserver('log', new LogObserver('user_activities.log', 'INFO'));
$observerManager->registerObserver('analytics', new AnalyticsObserver('Mixpanel'));
$observerManager->registerObserver('security', new SecurityObserver('SecurityBot'));
$observerManager->registerObserver('cache', new CacheObserver('Memcached'));

echo "\n✅ Observer registration completed\n";
echo "📊 Registered observer types: " . implode(', ', $observerManager->getRegisteredObserverTypes()) . "\n\n";

// Create user and attach observers
echo "👤 CREATING USER AND ATTACHING OBSERVERS...\n";
echo str_repeat("-", 40) . "\n";

$user = new User('আহমেদ করিম', 'ahmed.karim@example.com');

// Attach all observers
$observerManager->attachObserversToSubject($user);

echo "\n🔗 All observers attached to user\n";
echo "📊 Total observers attached: " . $user->getObserverCount() . "\n\n";

// Demonstrate various user activities
echo "🎬 DEMONSTRATING USER ACTIVITIES...\n";
echo str_repeat("-", 40) . "\n\n";

// Activity 1: Email Change
echo "🎬 SCENARIO 1: EMAIL CHANGE\n";
echo str_repeat("-", 25) . "\n";
$user->setEmail('ahmed.new@example.com');

echo "\n" . str_repeat("-", 25) . "\n\n";

// Activity 2: User Login
echo "🎬 SCENARIO 2: USER LOGIN\n";
echo str_repeat("-", 25) . "\n";
$user->login();

echo "\n" . str_repeat("-", 25) . "\n\n";

// Activity 3: Status Change
echo "🎬 SCENARIO 3: STATUS CHANGE\n";
echo str_repeat("-", 25) . "\n";
$user->setStatus('suspended');

echo "\n" . str_repeat("-", 25) . "\n\n";

// Activity 4: Profile Update
echo "🎬 SCENARIO 4: PROFILE UPDATE\n";
echo str_repeat("-", 25) . "\n";
$user->updateProfile([
    'phone' => '+8801712345678',
    'address' => 'Dhaka, Bangladesh',
    'bio' => 'Software Developer'
]);

echo "\n" . str_repeat("-", 25) . "\n\n";

// ===============================================================================
// OBSERVER DETACHMENT DEMONSTRATION
// ===============================================================================

echo "🔓 OBSERVER DETACHMENT DEMONSTRATION\n";
echo str_repeat("-", 40) . "\n";

// Create individual observer instances for detachment demo
$emailObserver = new EmailNotificationObserver();
$logObserver = new LogObserver();

// Create new user for detachment demo
$user2 = new User('রহিম উদ্দিন', 'rahim@example.com');
$user2->attach($emailObserver);
$user2->attach($logObserver);

echo "\n📧 Before detachment - changing email:\n";
$user2->setEmail('rahim.new@example.com');

// Detach email observer
echo "\n🔓 Detaching email observer...\n";
$user2->detach($emailObserver);

echo "\n📧 After email observer detachment - changing email again:\n";
$user2->setEmail('rahim.final@example.com');

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// ERROR HANDLING AND EDGE CASES
// ===============================================================================

echo "⚠️ ERROR HANDLING AND EDGE CASES\n";
echo str_repeat("-", 40) . "\n";

/*
ERROR HANDLING OBSERVER:
-----------------------
Observer যা error handle করে এবং exception throw করতে পারে।
*/
class ErrorProneObserver implements ObserverInterface {
    private $shouldThrowError;
    
    public function __construct(bool $shouldThrowError = false) {
        $this->shouldThrowError = $shouldThrowError;
        echo "💥 ErrorProneObserver created (Will throw error: " . ($shouldThrowError ? 'YES' : 'NO') . ")\n";
    }
    
    public function update(string $event, $data): void {
        if ($this->shouldThrowError) {
            throw new Exception("Observer error occurred for event: $event");
        }
        
        echo "✅ ErrorProneObserver processed event: $event\n";
    }
}

// Create user for error handling demo
$user3 = new User('Error Test User', 'error@example.com');

// Attach normal observer and error-prone observer
$normalObserver = new LogObserver();
$errorObserver = new ErrorProneObserver(true); // Will throw error

$user3->attach($normalObserver);
$user3->attach($errorObserver);

echo "\n💥 Testing error handling in notification process:\n";
$user3->setEmail('error.new@example.com');

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// PERFORMANCE TESTING
// ===============================================================================

echo "⚡ PERFORMANCE TESTING\n";
echo str_repeat("-", 40) . "\n";

/*
LIGHTWEIGHT OBSERVER:
--------------------
Performance testing এর জন্য lightweight observer।
*/
class LightweightObserver implements ObserverInterface {
    private static $instanceCount = 0;
    private $id;
    
    public function __construct() {
        self::$instanceCount++;
        $this->id = self::$instanceCount;
    }
    
    public function update(string $event, $data): void {
        // Minimal processing for performance test
        echo "⚡ LightweightObserver #{$this->id} processed: $event\n";
    }
    
    public static function getInstanceCount(): int {
        return self::$instanceCount;
    }
}

// Create user for performance testing
$perfUser = new User('Performance Test', 'perf@example.com');

// Attach multiple lightweight observers
echo "⚡ Attaching 10 lightweight observers...\n";
for ($i = 1; $i <= 10; $i++) {
    $perfUser->attach(new LightweightObserver());
}

echo "📊 Total lightweight observers created: " . LightweightObserver::getInstanceCount() . "\n";

// Measure performance
$startTime = microtime(true);

echo "\n⏱️ Performance test - triggering event with 10 observers:\n";
$perfUser->setEmail('perf.new@example.com');

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

echo "\n📊 Performance Results:\n";
echo "   Execution time: " . number_format($executionTime, 2) . " milliseconds\n";
echo "   Observers notified: " . $perfUser->getObserverCount() . "\n";

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// REAL-WORLD APPLICATIONS
// ===============================================================================

echo "🌍 REAL-WORLD APPLICATIONS\n";
echo str_repeat("-", 40) . "\n";

/*
E-COMMERCE ORDER OBSERVER:
-------------------------
E-commerce order processing এর জন্য observer।
*/
class OrderObserver implements ObserverInterface {
    public function update(string $event, $data): void {
        switch ($event) {
            case 'user_created':
                echo "🛒 [ORDER SYSTEM] New customer registered - Setting up default cart\n";
                break;
            case 'email_changed':
                echo "🛒 [ORDER SYSTEM] Customer email updated - Updating order history\n";
                break;
            case 'status_changed':
                if ($data['new_status'] === 'suspended') {
                    echo "🛒 [ORDER SYSTEM] Customer suspended - Canceling pending orders\n";
                }
                break;
        }
    }
}

/*
PAYMENT OBSERVER:
----------------
Payment system এর জন্য observer।
*/
class PaymentObserver implements ObserverInterface {
    public function update(string $event, $data): void {
        switch ($event) {
            case 'user_created':
                echo "💳 [PAYMENT SYSTEM] Setting up payment profile for new customer\n";
                break;
            case 'email_changed':
                echo "💳 [PAYMENT SYSTEM] Updating billing information\n";
                break;
            case 'status_changed':
                if ($data['new_status'] === 'banned') {
                    echo "💳 [PAYMENT SYSTEM] Customer banned - Refunding pending transactions\n";
                }
                break;
        }
    }
}

/*
LOYALTY PROGRAM OBSERVER:
------------------------
Loyalty program এর জন্য observer।
*/
class LoyaltyObserver implements ObserverInterface {
    public function update(string $event, $data): void {
        switch ($event) {
            case 'user_created':
                echo "🎁 [LOYALTY PROGRAM] Welcome bonus awarded to new member\n";
                break;
            case 'user_logged_in':
                echo "🎁 [LOYALTY PROGRAM] Login points awarded\n";
                break;
            case 'profile_updated':
                echo "🎁 [LOYALTY PROGRAM] Profile completion bonus awarded\n";
                break;
        }
    }
}

// Demonstrate real-world application
echo "🏪 E-commerce Platform Simulation:\n";

$customer = new User('অনলাইন ক্রেতা', 'customer@shop.com');

// Attach e-commerce related observers
$customer->attach(new OrderObserver());
$customer->attach(new PaymentObserver());
$customer->attach(new LoyaltyObserver());
$customer->attach(new EmailNotificationObserver('E-commerce Email Service'));

echo "\n🛒 Customer activities in e-commerce platform:\n";

// Customer login
$customer->login();
echo "\n";

// Customer updates profile
$customer->updateProfile(['phone' => '+8801987654321', 'address' => 'Chittagong']);
echo "\n";

// Customer violates terms and gets banned
$customer->setStatus('banned');

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// OBSERVER PATTERN VS OTHER PATTERNS
// ===============================================================================

echo "🔄 OBSERVER PATTERN VS OTHER PATTERNS\n";
echo str_repeat("-", 40) . "\n";

/*
MEDIATOR PATTERN COMPARISON:
---------------------------
Observer Pattern এর সাথে Mediator Pattern এর তুলনা।
*/

echo "📋 OBSERVER vs MEDIATOR Pattern:\n\n";

echo "OBSERVER PATTERN:\n";
echo "✅ One Subject -> Many Observers\n";
echo "✅ Subject doesn't know about specific observers\n";
echo "✅ Loose coupling between subject and observers\n";
echo "✅ Observers can be added/removed dynamically\n";
echo "❌ No communication between observers\n\n";

echo "MEDIATOR PATTERN:\n";
echo "✅ Central mediator handles all communication\n";
echo "✅ Components don't communicate directly\n";
echo "✅ Complex business logic can be handled\n";
echo "❌ Mediator can become complex\n";
echo "❌ Single point of failure\n\n";

/*
EVENT DISPATCHER COMPARISON:
---------------------------
Modern event-driven systems এর সাথে তুলনা।
*/

echo "📋 OBSERVER vs EVENT DISPATCHER:\n\n";

echo "TRADITIONAL OBSERVER:\n";
echo "✅ Simple implementation\n";
echo "✅ Direct method calls\n";
echo "❌ Tight coupling to specific interfaces\n";
echo "❌ Limited event filtering\n\n";

echo "EVENT DISPATCHER:\n";
echo "✅ Named events with data\n";
echo "✅ Event filtering and prioritization\n";
echo "✅ Asynchronous processing possible\n";
echo "✅ Better debugging and logging\n";

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// BEST PRACTICES AND ANTI-PATTERNS
// ===============================================================================

echo "💡 BEST PRACTICES AND ANTI-PATTERNS\n";
echo str_repeat("-", 40) . "\n";

echo "✅ BEST PRACTICES:\n\n";

echo "1. INTERFACE SEGREGATION:\n";
echo "   - Use specific interfaces for different observer types\n";
echo "   - Don't force observers to implement unused methods\n\n";

echo "2. ERROR HANDLING:\n";
echo "   - Handle observer exceptions gracefully\n";
echo "   - Don't let one observer failure stop others\n\n";

echo "3. PERFORMANCE:\n";
echo "   - Keep observer update methods lightweight\n";
echo "   - Consider asynchronous processing for heavy operations\n\n";

echo "4. MEMORY MANAGEMENT:\n";
echo "   - Properly detach observers to prevent memory leaks\n";
echo "   - Use weak references if available\n\n";

echo "❌ ANTI-PATTERNS TO AVOID:\n\n";

echo "1. TOO MANY OBSERVERS:\n";
echo "   - Don't attach hundreds of observers to one subject\n";
echo "   - Consider using event dispatcher for complex scenarios\n\n";

echo "2. CIRCULAR DEPENDENCIES:\n";
echo "   - Avoid observers that modify the subject\n";
echo "   - Can cause infinite notification loops\n\n";

echo "3. HEAVY PROCESSING IN OBSERVERS:\n";
echo "   - Don't perform database operations or API calls\n";
echo "   - Use queue systems for heavy tasks\n\n";

echo "4. FORGETTING TO DETACH:\n";
echo "   - Always detach observers when no longer needed\n";
echo "   - Can cause memory leaks and unexpected behavior\n\n";

// ===============================================================================
// LARAVEL-STYLE EVENT SYSTEM SIMULATION
// ===============================================================================

echo "🔥 LARAVEL-STYLE EVENT SYSTEM SIMULATION\n";
echo str_repeat("-", 40) . "\n";

/*
EVENT CLASS:
-----------
Laravel-style event class।
*/
class UserEmailChangedEvent {
    public $user;
    public $oldEmail;
    public $newEmail;
    public $timestamp;
    
    public function __construct($user, string $oldEmail, string $newEmail) {
        $this->user = $user;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
        $this->timestamp = date('Y-m-d H:i:s');
    }
}

/*
EVENT LISTENER:
--------------
Laravel-style event listener।
*/
class SendEmailChangeNotificationListener {
    public function handle(UserEmailChangedEvent $event): void {
        echo "🔥 [LARAVEL STYLE] Email change notification sent\n";
        echo "   User: {$event->user->getName()}\n";
        echo "   Old: {$event->oldEmail} -> New: {$event->newEmail}\n";
        echo "   Time: {$event->timestamp}\n";
    }
}

/*
SIMPLE EVENT DISPATCHER:
-----------------------
Laravel Event System এর simplified version।
*/
class EventDispatcher {
    private $listeners = [];
    
    public function listen(string $eventClass, callable $listener): void {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        $this->listeners[$eventClass][] = $listener;
    }
    
    public function dispatch($event): void {
        $eventClass = get_class($event);
        
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener($event);
            }
        }
    }
}

// Laravel-style event system demonstration
echo "🔥 Laravel-style Event System Demo:\n";

$eventDispatcher = new EventDispatcher();

// Register event listener
$eventDispatcher->listen(UserEmailChangedEvent::class, function($event) {
    $listener = new SendEmailChangeNotificationListener();
    $listener->handle($event);
});

// Create user with event dispatcher
$laravelUser = new User('Laravel User', 'laravel@example.com');

// Simulate email change with event dispatch
$oldEmail = $laravelUser->getEmail();
$newEmail = 'laravel.new@example.com';

// Dispatch event (this would be in the User class setEmail method)
$event = new UserEmailChangedEvent($laravelUser, $oldEmail, $newEmail);
$eventDispatcher->dispatch($event);

echo "\n" . str_repeat("-", 40) . "\n\n";

// ===============================================================================
// SUMMARY AND CONCLUSION
// ===============================================================================

echo "📚 SUMMARY AND CONCLUSION\n";
echo str_repeat("=", 80) . "\n";

echo "
🎯 OBSERVER PATTERN KEY LEARNINGS:
---------------------------------

1. CORE CONCEPTS:
   ✅ Subject (Observable) - Object being watched
   ✅ Observer - Object that watches and reacts
   ✅ Interface contracts for loose coupling
   ✅ Dynamic observer management (attach/detach)

2. IMPLEMENTATION DETAILS:
   ✅ ObserverInterface - update(event, data) method
   ✅ SubjectInterface - attach(), detach(), notify() methods
   ✅ Concrete implementations for specific behaviors
   ✅ Event-driven architecture support

3. REAL-WORLD APPLICATIONS:
   📧 Email notifications
   📝 Logging and auditing  
   📊 Analytics and metrics
   🔒 Security monitoring
   🗄️ Cache invalidation
   🛒 E-commerce workflows
   🎁 Loyalty programs

4. ADVANTAGES:
   ✅ Loose coupling between components
   ✅ Open/Closed Principle compliance
   ✅ Dynamic relationship management
   ✅ Easy testing with mock observers
   ✅ Separation of concerns
   ✅ Event-driven architecture foundation

5. CHALLENGES:
   ⚠️ Can cause performance issues with many observers
   ⚠️ Potential memory leaks if not managed properly
   ⚠️ Debugging complex observer chains
   ⚠️ Circular dependency risks

6. BEST PRACTICES:
   💡 Keep observer update methods lightweight
   💡 Handle errors gracefully in notification process
   💡 Use specific interfaces for different observer types
   💡 Properly detach observers to prevent memory leaks
   💡 Consider async processing for heavy operations

7. WHEN TO USE:
   ✅ When you need to notify multiple objects of state changes
   ✅ When you want loose coupling between components
   ✅ When implementing event-driven systems
   ✅ When building notification systems
   ✅ When creating audit trails or logging systems

8. WHEN NOT TO USE:
   ❌ When you have simple, direct relationships
   ❌ When performance is critical and you have many observers
   ❌ When the notification logic is complex
   ❌ When you need guaranteed delivery order

🎊 CONCLUSION:
-------------
Observer Pattern হলো একটি powerful behavioral pattern যা modern 
software architecture এর foundation। Laravel Events, JavaScript DOM Events, 
এবং অন্যান্য event-driven systems এই pattern এর উপর ভিত্তি করে তৈরি।

এই pattern master করলে আপনি clean, maintainable এবং scalable code 
লিখতে পারবেন যা real-world applications এর জন্য perfect।

🚀 NEXT STEPS:
-------------
1. Laravel Event System study করুন
2. JavaScript Event handling practice করুন  
3. Message Queue systems (RabbitMQ, Redis) explore করুন
4. Reactive Programming (RxJS, ReactiveX) শিখুন
5. Microservices Event-driven Communication implement করুন
";

echo "\n🎉 OBSERVER PATTERN TUTORIAL COMPLETE! 🎉\n";
echo str_repeat("=", 80) . "\n";

/*
===============================================================================
FINAL IMPLEMENTATION CHECKLIST:
===============================================================================

✅ Observer Interface defined with update() method
✅ Subject Interface defined with attach(), detach(), notify() methods  
✅ Concrete Subject (User) with state management
✅ Multiple Observer implementations:
   - EmailNotificationObserver
   - LogObserver  
   - AnalyticsObserver
   - SecurityObserver
   - CacheObserver
✅ Observer Manager for centralized management
✅ Error handling and edge cases covered
✅ Performance testing demonstrated
✅ Real-world applications shown (E-commerce)
✅ Comparison with other patterns
✅ Best practices and anti-patterns discussed
✅ Laravel-style event system simulation
✅ Comprehensive documentation and examples

এই implementation দিয়ে আপনি production-ready Observer Pattern 
system তৈরি করতে পারবেন।
===============================================================================
*/
