#php artisan tinker (debugs): 
----------------------------
# In your tinker session or run these commands:
> file_exists('/var/www/html/public/instasure')
> is_dir('/var/www/html/public/instasure')
> is_writable('/var/www/html/public/instasure')

> Storage::disk('fallback')->put('test.txt', 'Hello World');
// Should return true now

> file_exists('/var/www/html/public/instasure/test.txt');
// Should return true

> file_get_contents('/var/www/html/public/instasure/test.txt');
// Should show "Hello World"


# Exit tinker first (type exit)
> exit

# Now in the container shell:
mkdir -p /var/www/html/public/instasure
chown -R www-data:www-data /var/www/html/public/instasure
chmod -R 755 /var/www/html/public/instasure

# Verify the permissions
ls -la /var/www/html/public/ | grep instasure


# On the host machine (not in container)
cd ~/gitlab_project/instasure-dockerized
sudo chown -R 33:33 public/instasure  # 33 is usually www-data's UID/GID
sudo chmod -R 775 public/instasure

docker exec -it --user root $(docker ps -qf "name=app") bash -c "chown -R www-data:www-data /var/www/html/public/instasure && chmod -R 775 /var/www/html/public/instasure"


> clearstatcache();  // Clear PHP's file stat cache
> is_writable('/var/www/html/public/instasure')
// Should return true now

> fileperms('/var/www/html/public/instasure')
// Check the actual permissions

> posix_getpwuid(fileowner('/var/www/html/public/instasure'))
// Check who owns the directory


> get_current_user()
// Shows which user PHP is running as

> exec('whoami')
// Shows the actual system user

> exec('ls -la /var/www/html/public/ | grep instasure')
// See the actual permissions


> Storage::disk('fallback')->put('test.txt', 'Test content');
// Should return true if writable

> file_put_contents('/var/www/html/public/instasure/test2.txt', 'Direct test');
// Try direct file write to confirm


www@1abb875aec3e:/var/www/html$ php artisan tinker
Psy Shell v0.12.10 (PHP 8.3.25 — cli) by Justin Hileman
> is_writable('var/www/html/public/instrasure')
= false

> clearstatcache(); 
= null

> is_writable('/var/www/html/public/instasure')
= false

> fileperms('/var/www/html/public/instasure')fileperms('/var/www/html/public/instasure')fileperms('/var/www/html/public/instasure')

   PARSE ERROR  PHP Parse error: Syntax error, unexpected T_STRING in vendor/psy/psysh/src/Exception/ParseErrorException.php on line 44.

> fileperms('/var/www/html/public/instasure')
= 17917

> posix_getpwuid(fileowner('/var/www/html/public/instasure'))
= [
    "name" => "www-data",
    "passwd" => "x",
    "uid" => 33,
    "gid" => 33,
    "gecos" => "www-data",
    "dir" => "/var/www",
    "shell" => "/usr/sbin/nologin",
  ]

> get_current_user()
= "www-data"

> exec('whoami')
= "www"

> exec('ls -la /var/www/html/public/ | grep instasure')
= "drwxrwsr-x+ 13 www-data www-data   4096 Sep 18 09:31 instasure"

> 
