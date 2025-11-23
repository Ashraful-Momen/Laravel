#install the php & composer and check those version. 



┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed]
└──╼ $ composer global requir laravel/installer


┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed]
└──╼ $composer global config bin-dir --absolute
Changed current directory to /home/shuvo/.config/composer
/home/shuvo/.config/composer/vendor/bin

┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed]
└──╼ $composer create-project laravel/laravel project_name



## 🎯 **NEXT STEPS: PostgreSQL Configuration**

### Step 1: Install PostgreSQL PHP Extension

# Install pdo_pgsql extension
sudo apt update
sudo apt install php8.2-pgsql

# Or using your package manager
sudo apt install php-pgsql

# Verify installation
php -m | grep pgsql

================================= install the postgresql ===================================

# Navigate to your SAFFRON project directory
cd ~/Projects/SAFFRON-BAKERY-DAIRY-ENTERPRISE

# Create a dedicated docker-compose for database services
cat > docker-compose.db.yml << 'EOF'
version: '3.8'
services:
  postgres:
    image: postgres:15
    container_name: saffron-postgres
    environment:
      POSTGRES_DB: saffron_dev
      POSTGRES_USER: saffron_user
      POSTGRES_PASSWORD: saffron_password_2024
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/init:/docker-entrypoint-initdb.d
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U saffron_user -d saffron_dev"]
      interval: 30s
      timeout: 10s
      retries: 3

  pgadmin:
    image: dpage/pgadmin4
    container_name: saffron-pgadmin
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@saffronbd.com
      PGADMIN_DEFAULT_PASSWORD: admin123
    ports:
      - "5050:80"
    restart: unless-stopped
    depends_on:
      postgres:
        condition: service_healthy

volumes:
  postgres_data:

networks:
  default:
    name: saffron-network
EOF

# Create initialization directory
mkdir -p database/init

# Start the database services
docker-compose -f docker-compose.db.yml up -d

# Check if services are running
docker ps



================================ after install the postgresql ===============================


#update laravel .env file : 
------------------------------
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saffron_dev
DB_USERNAME=saffron_user
DB_PASSWORD=secure_password_here


Test DB connection : 
-------------------
# Run migrations to test PostgreSQL connection
php artisan migrate

# Or test connection directly
php artisan tinker
>>> DB::connection()->getPdo()

======================================== set the redis serviec =======================

*** if the redis is runner then stop it and re dun the docker-compose. 


in the docker-compose file add the redis part : 
----------------------------------------------
redis:
  image: redis:7.2-alpine
  container_name: saffron-redis
  ports:
    - "6380:6379"  # External: 6380, Internal: 6379
  volumes:
    - redis_data:/data
  restart: unless-stopped
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 30s
    timeout: 10s
    retries: 3

--------------------------------------------

# Stop host Redis service
sudo systemctl stop redis-server

# Check if port 6379 is free now
sudo netstat -tulpn | grep 6379

# Start your Docker services
docker-compose up -d

# Verify all containers are running
docker ps

=================================== After install the redis : add those in laravel .env ==================

# PostgreSQL
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saffron_dev
DB_USERNAME=saffron_user
DB_PASSWORD=saffron_password_2024

# pgAdmin
URL: http://localhost:5050
Email: admin@saffronbd.com
Password: admin123

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379


=============================== Verify redis and postgresql with docker =============================

# Check all containers status
docker ps

# Check health status of all services
docker-compose ps

# Test PostgreSQL connection from container
docker exec saffron-postgres pg_isready -U saffron_user -d saffron_dev

# Test Redis connection from container
docker exec saffron-redis redis-cli ping


============================================================================================

Excellent! ✅ PostgreSQL is running and accepting connections. Now let's test Redis and configure Laravel.

## 🔍 **Test Redis Connection**

```bash
# Test Redis connection from container
docker exec saffron-redis redis-cli ping

# Test Redis from host system
redis-cli ping
```

## 🔧 **Configure Laravel .env File**

```bash
# Navigate to Laravel backend directory
cd ~/Code/Laravel\ 12\ Installed/backend

# Edit the .env file
nano .env
```

**Update these sections in your .env file:**

```env
# Database Configuration - PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saffron_dev
DB_USERNAME=saffron_user
DB_PASSWORD=saffron_password_2024

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache and Session using Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Application Configuration
APP_NAME="SAFFRON BAKERY & DAIRY ENTERPRISE"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## 🧪 **Test Laravel Database Connection**

```bash
# Test if Laravel can connect to PostgreSQL
php artisan migrate:status

# If successful, you should see output like:
# +------+------------------------------------------------+-------+
# | Ran? | Migration                                      | Batch |
# +------+------------------------------------------------+-------+
# | No   | 2014_10_12_000000_create_users_table           |       |
# | No   | 2014_10_12_100000_create_password_reset_tokens_table |       |
# | No   | 2019_08_19_000000_create_failed_jobs_table     |       |
# | No   | 2019_12_14_000001_create_personal_access_tokens_table |       |
# +------+------------------------------------------------+-------+
```

## 🚀 **Run Database Migrations**

```bash
# Run Laravel migrations to create database tables
php artisan migrate

# You should see output like:
# 2014_10_12_000000_create_users_table ................................................................................................. 13ms DONE
# 2014_10_12_100000_create_password_reset_tokens_table ............................................................................... 10ms DONE
# 2019_08_19_000000_create_failed_jobs_table ......................................................................................... 10ms DONE
# 2019_12_14_000001_create_personal_access_tokens_table ............................................................................. 12ms DONE
```

## 🧪 **Test Redis with Laravel**

```bash
# Test Redis connection through Laravel
php artisan tinker

# In the tinker shell, run:
>>> Cache::put('saffron_test', 'Redis and PostgreSQL are working!', 10)
>>> Cache::get('saffron_test')
>>> exit
```

## 🌐 **Access pgAdmin to Verify Database**

1. **Open browser:** `http://localhost:5050`
2. **Login:**
   - Email: `admin@saffronbd.com`
   - Password: `admin123`

3. **Connect to PostgreSQL server:**
   - Expand "Servers" → "Saffron PostgreSQL" → "Databases" → "saffron_dev" → "Schemas" → "public" → "Tables"
   - You should see Laravel tables: `users`, `migrations`, `password_reset_tokens`, etc.

## 📋 **Complete Test Sequence**

Run these commands in order:

```bash
# 1. Test Redis
docker exec saffron-redis redis-cli ping

# 2. Configure Laravel .env (if not done)
cd ~/Code/Laravel\ 12\ Installed/backend
nano .env  # Update with PostgreSQL and Redis config

# 3. Test database connection
php artisan migrate:status

# 4. Run migrations
php artisan migrate

# 5. Test Redis through Laravel
php artisan tinker --execute="echo Cache::put('test', 'success', 10) ? 'Redis OK' : 'Redis FAIL';"

# 6. Start Laravel development server
php artisan serve
```

## 🎯 **Expected Results**

After completing these steps, you should have:

- ✅ **PostgreSQL**: Connected and migrations run successfully
- ✅ **Redis**: Responding to Laravel cache commands
- ✅ **Laravel**: Connected to both database and cache
- ✅ **pgAdmin**: Able to view database tables

**Please run the Redis test first, then update your Laravel .env file and test the database connection.**

What's the output of `docker exec saffron-redis redis-cli ping`?


================================= access the bash of the postgresql conatiner and check the passwd of pgsql ============

┌─[✗]─[shuvo@parrot]─[~/Code/Laravel 12 Installed/backend]
└──╼ $docker exec -it saffron-postgres bash
root@29c44ce1aae1:/# psql -U saffron_user -d saffron_dev
psql (15.15 (Debian 15.15-1.pgdg13+1))
Type "help" for help.



# allow DB to accept any requst from out side : 

┌─[✗]─[shuvo@parrot]─[~/Code/Laravel 12 Installed/backend]
└──╼ $docker exec saffron-postgres bash -c "echo 'host all all all md5' >> /var/lib/postgresql/data/pg_hba.conf"

#pgsql listen from anywhere: 

┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed/backend]
└──╼ $docker exec saffron-postgres bash -c "echo \"listen_addresses = '*'\" >> /var/lib/postgresql/data/postgresql.conf"


┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed/backend]
└──╼ $docker restart saffron-postgres
saffron-postgres


#login to DB with passwd: 

┌─[shuvo@parrot]─[~/Code/Laravel 12 Installed/backend]
└──╼ $psql -h 127.0.0.1 -U saffron_user -d saffron_dev -c "SELECT version();"
Password for user saffron_user: 
                                                       version                                                        
----------------------------------------------------------------------------------------------------------------------
 PostgreSQL 15.15 (Debian 15.15-1.pgdg13+1) on x86_64-pc-linux-gnu, compiled by gcc (Debian 14.2.0-19) 14.2.0, 64-bit
(1 row)


=================================== Fix the redis class not found from laravel class ============================

# 1. Install PHP Redis extension
sudo apt install php8.2-redis

# 2. Verify
php -m | grep redis

# 3. Clean up .env file (remove duplicates)
cd ~/Code/Laravel\ 12\ Installed/backend
nano .env  # Remove duplicate DB_* lines

use this for the .env => REDIS_CLIENT=predis #preredis for the client site and docker-compose run the redis server 

# 4. Clear cache and test
php artisan config:clear
php artisan cache:clear
php artisan serve
