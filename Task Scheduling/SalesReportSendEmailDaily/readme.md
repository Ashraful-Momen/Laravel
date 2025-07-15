I'll help you create a simple email system that runs every night at 00:00 to send data with cat_id, pkg_id, reseller, and total_count. Based on your AccountsService, I'll create simple queries without joins for each category.I've created a complete daily email system with the following components:

## **Key Features:**

### **1. Simple Queries (No Joins)**
- Each category uses a simple query from its main table
- No complex joins to keep queries fast and simple
- Uses today's date for filtering

### **2. All Categories Covered:**
- Category 5: Travel Insurance
- Category 6: Device Insurance 
- Category 8: Life & Health
- Category 10: Motor Insurance
- Category 11: Cattle Insurance
- Category 12: HDC Card
- Category 16: Tele Medicine
- Category 20: Fire Insurance
- Category 21: Theft Insurance

### **3. Email Data Structure:**
Each category returns:
- `cat_id`: Category ID
- `pkg_id`: Package/Product ID
- `reseller`: Always "Instasure" 
- `total_count`: Count of records for today

### **4. Automated Scheduling:**
- **Command**: `php artisan email:send-daily`
- **Schedule**: Every night at 00:00
- **Timezone**: Configurable (set to Asia/Dhaka)

## **Setup Instructions:**

1. **Create the Command:**
```bash
php artisan make:command SendDailyEmailCommand
```

2. **Create the Controller:**
```bash
php artisan make:controller DailyEmailController
```

3. **Create Email View:**
```bash
mkdir -p resources/views/emails
# Then create daily-report.blade.php
```

4. **Add to Scheduler in `app/Console/Kernel.php`:**
```php
$schedule->command('email:send-daily')->dailyAt('00:00');
```

5. **Test the Email:**
```bash
# Manual test
php artisan email:send-daily

# Or visit: /test-daily-email
```

## **Email Recipients:**
Update the `$recipients` array in the controller with actual email addresses.

## **Customization:**
- Modify email styling in the Blade template
- Add more recipients in the controller
- Change timezone in the scheduler
- Adjust email subject/content as needed

The system will automatically send a nicely formatted HTML email every night with the daily insurance statistics!
