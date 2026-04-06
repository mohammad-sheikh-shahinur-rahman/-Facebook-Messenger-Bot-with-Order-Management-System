# Deployment & Troubleshooting Guide

Production-ready deployment instructions and common issues.

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] All credentials in config.php are correct
- [ ] Database is backed up
- [ ] Webhook is verified on Facebook
- [ ] HTTPS certificate is valid and working
- [ ] Admin password is changed from default
- [ ] Log directory has proper permissions (777)
- [ ] Database user has minimum required permissions

### Deployment Steps

1. **Upload all files to production server**
   ```bash
   scp -r fb-bot/ user@yourdomain.com:public_html/
   ```

2. **Create production database**
   - Create new database for production
   - Import database.sql schema
   - Create dedicated MySQL user

3. **Update config.php with production credentials**
   - Production DB credentials
   - Production Facebook credentials
   - Production domain URL
   - Change error_reporting to E_ALL but log errors (not display)

4. **Set proper file permissions**
   ```bash
   chmod 755 /home/user/public_html/fb-bot
   chmod 777 /home/user/public_html/fb-bot/logs
   chmod 600 /home/user/public_html/fb-bot/config.php
   ```

5. **Enable SSL/HTTPS**
   - Install Let's Encrypt certificate (free with cPanel)
   - Force HTTPS redirect
   - Update APP_URL in config.php to https://

6. **Update Facebook webhook**
   - Change webhook URL to production URL
   - Use production verify token
   - Test webhook verification

7. **Test bot functionality**
   - Send test message
   - Verify order creation
   - Check admin dashboard
   - Review logs

### Post-Deployment

- [ ] Monitor logs daily for errors
- [ ] Set up automated database backups
- [ ] Set up log rotation (delete old logs after 90 days)
- [ ] Test on multiple Facebook profiles
- [ ] Monitor page analytics

---

## 🔧 Common Issues & Solutions

### Issue 1: "Webhook Verification Failed"

**Symptoms:**
- Red X next to webhook in Facebook Settings
- Bot not receiving messages
- Error: "Webhook validation request failed"

**Causes:**
1. Webhook URL is incorrect
2. Verify token doesn't match
3. HTTPS not working
4. PHP not processing webhook.php
5. Firewall blocking requests

**Solutions:**

```bash
# Test 1: Check URL is accessible
curl -I https://yourdomain.com/fb-bot/webhook.php
# Should return HTTP 200

# Test 2: Check token verification works
curl -X GET "https://yourdomain.com/fb-bot/webhook.php?hub.mode=subscribe&hub.verify_token=YOUR_VERIFY_TOKEN&hub.challenge=test123"
# Should return: test123

# Test 3: Check if PHP executable
php -l webhook.php
# Should show: No syntax errors

# Test 4: Check logs
tail -f logs/bot_$(date +%Y-%m-%d).log
```

**Fix:**

1. Copy exact webhook URL from browser address bar
2. Ensure verify token matches config.php exactly (case-sensitive)
3. Test with curl first before Facebook
4. Check SSL certificate: `echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443`

---

### Issue 2: "Database Connection Failed"

**Symptoms:**
- Admin dashboard shows blank or error
- Can't create orders
- Error in logs: "Database Connection Failed"

**Causes:**
1. Wrong DB_HOST, DB_USER, or DB_PASS
2. MySQL not running
3. Database doesn't exist
4. User doesn't have permissions

**Solutions:**

```bash
# Test MySQL connectivity in SSH
mysql -h localhost -u fb_bot -p fb_messenger_bot -e "SELECT 1;"
# Enter password when prompted

# In phpMyAdmin, run:
SELECT USER();
SELECT DATABASE();
SHOW TABLES;
```

**Fix:**

1. Verify credentials match exactly in config.php
2. Check cPanel → MySQL Databases → Verify user is assigned to database
3. Check user has ALL PRIVILEGES
4. Test connection string:
   ```php
   $mysqli = new mysqli("localhost", "fb_bot", "password", "fb_messenger_bot");
   echo $mysqli->connect_error ? $mysqli->connect_error : "Connected";
   ```

---

### Issue 3: Bot Not Receiving Messages

**Symptoms:**
- No log entries when message is sent
- Webhook appears verified in Facebook
- No error messages

**Causes:**
1. Webhook not actually verified (false positive)
2. Events not subscribed (messages event not checked)
3. Bot blocked by Facebook
4. Webhook returning error status

**Solutions:**

```bash
# Check webhook logs
cat logs/bot_$(date +%Y-%m-%d).log

# Manual webhook test (POST request)
curl -X POST https://yourdomain.com/fb-bot/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "object": "page",
    "entry": [{
      "messaging": [{
        "sender": {"id": "123"},
        "recipient": {"id": "456"},
        "message": {"text": "hello"}
      }]
    }]
  }'

# Should log the message
```

**Fix:**

1. In Facebook Settings → Messenger → Webhooks
   - Click "Test Subscriber" button
   - Should show "OK"

2. Check Events subscribed:
   - [ ] messages
   - [ ] messaging_postbacks
   - [ ] messaging_quick_replies (optional)

3. Verify Page is selected in "Select a Page" dropdown

4. Check bot isn't rate-limited - restart webhook monitoring

---

### Issue 4: Orders Not Saving to Database

**Symptoms:**
- Bot asks for all order info
- Order confirmation message sends
- But order doesn't appear in database or admin panel
- Error: "Failed to create order"

**Causes:**
1. Database table `orders` doesn't exist
2. `customer_sessions` table missing
3. Database INSERT permission denied
4. Character encoding issue

**Solutions:**

```bash
# Check if tables exist in phpMyAdmin or via SSH:
mysql -u fb_bot -p fb_messenger_bot -e "SHOW TABLES;"

# Check table structure:
mysql -u fb_bot -p fb_messenger_bot -e "DESCRIBE orders;"

# Check user permissions:
mysql -u root -e "SHOW GRANTS FOR 'fb_bot'@'localhost';"
```

**Fix:**

1. If tables missing, import database.sql again:
   ```bash
   mysql -u fb_bot -p fb_messenger_bot < database.sql
   ```

2. Ensure user has INSERT privilege:
   ```sql
   GRANT INSERT, UPDATE, SELECT ON fb_messenger_bot.* TO 'fb_bot'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Check for encoding errors in webhook.php logs

---

### Issue 5: Admin Login Not Working

**Symptoms:**
- Login page appears
- "Invalid username or password" error
- Can't access any admin pages
- Session issues

**Causes:**
1. Default credentials changed or missing
2. Admin user deleted from database
3. Session not starting
4. CSRF token mismatch

**Solutions:**

```php
// Create quick reset script (DELETE AFTER USE!)
<?php
session_start();
require 'config.php';
require 'functions.php';

$hashed_password = hash_password('admin123');
$sql = "UPDATE admins SET password = ? WHERE id = 1";
$stmt = db_query($sql, 's', [&$hashed_password]);

echo $stmt ? "Password reset to admin123" : "Error: " . $GLOBALS['mysqli']->error;
mysqli_close($GLOBALS['mysqli']);
?>
```

**Fix:**

1. Check admin record exists:
   ```sql
   SELECT * FROM admins WHERE username = 'admin';
   ```

2. If missing, create:
   ```sql
   INSERT INTO admins (username, email, password, status)
   VALUES ('admin', 'admin@example.com', 
   '$2y$12$6l2EZ3p6/s8X0o1.XvKOLu3c9/L9Zi.m1m2G0gG9QX.5X5G5Q0Epa', 'active');
   ```
   Password becomes: admin123

3. Check session_start() is first line in login.php

4. Enable cookies in browser

---

### Issue 6: Slow Admin Dashboard

**Symptoms:**
- Dashboard takes 5+ seconds to load
- Lots of page freezing
- High server CPU usage

**Causes:**
1. Large number of orders (no pagination)
2. Missing database indexes
3. Unoptimized queries
4. Too many AJAX requests

**Solutions:**

```bash
# Check database query performance
mysql -u fb_bot -p fb_messenger_bot -e "\G SELECT * FROM orders LIMIT 10;" 

# Check if indexes exist
mysql -u fb_bot -p fb_messenger_bot -e "SHOW INDEXES FROM orders;"

# Optimize tables
mysql -u fb_bot -p fb_messenger_bot -e "OPTIMIZE TABLE orders; OPTIMIZE TABLE customer_sessions;"
```

**Fix:**

1. Ensure all indexes are created (included in database.sql)
2. Limit queries: `LIMIT 50` instead of unlimited
3. Add compound indexes:
   ```sql
   ALTER TABLE orders ADD INDEX idx_status_date (status, created_at);
   ALTER TABLE orders ADD INDEX idx_user_date (facebook_user_id, created_at);
   ```

4. Archive old orders (>1 year) to separate table

---

### Issue 7: CSRF Token Errors

**Symptoms:**
- "Security token invalid" error when submitting forms
- Randomly happens after session timeout
- More common on mobile

**Causes:**
1. Session expired
2. Browser doesn't accept cookies
3. Multiple tabs opened
4. Token not generated

**Solutions:**

In admin pages, verify CSRF before form submission:

```php
// In any admin form
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
</form>

// In processing
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $error = 'Security token invalid';
}
```

**Fix:**

1. Ensure browser cookies are enabled
2. Check session timeout not too short in config.php
3. Clear old sessions:
   ```sql
   DELETE FROM customer_sessions WHERE expires_at < NOW();
   ```

---

## 📊 Performance Optimization

### Database Optimization

```sql
-- Run monthly
OPTIMIZE TABLE orders;
OPTIMIZE TABLE customer_sessions;
OPTIMIZE TABLE messages_log;

-- Check slow queries
SHOW PROCESSLIST;

-- Analyze table statistics
ANALYZE TABLE orders;
```

### Log Rotation

```bash
# Delete logs older than 90 days
find /home/user/public_html/fb-bot/logs -name "*.log" -mtime +90 -delete

# Compress old logs
find /home/user/public_html/fb-bot/logs -name "*.log" -mtime +30 -exec gzip {} \;
```

Add to crontab:
```bash
0 2 * * * find /home/user/public_html/fb-bot/logs -name "*.log" -mtime +90 -delete
0 3 * * 0 mysqldump -u fb_bot -p*** fb_messenger_bot > /backups/db_$(date +\%Y\%m\%d).sql
```

### Query Optimization

```php
// Instead of:
$result = db_query("SELECT * FROM orders");
while ($row = $result->fetch_assoc()) { }

// Use:
$result = db_query("SELECT id, customer_name, status FROM orders LIMIT 50");
```

---

## 🔍 Monitoring

### Set Up Error Alerts

```php
// In config.php, add email alerts for critical errors
if (error_reporting() & E_ERROR) {
    mail('admin@example.com', 'Bot Error', error_get_last()['message']);
}
```

### Monitor Webhook Health

```php
// Add to webhook.php
$webhook_log = __DIR__ . '/logs/webhook_health.log';
$health = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'ok',
    'messages_received' => get_order_count()
];
file_put_contents($webhook_log, json_encode($health) . "\n", FILE_APPEND);
```

### Database Health Check

```sql
-- Run daily
SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as orders_24h,
    MAX(updated_at) as last_update
FROM orders;
```

---

## 🔐 Security Hardening

### Rate Limiting (Optional)

Add to webhook.php:
```php
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = sys_get_temp_dir() . "/webhook_rate_" . md5($ip);

if (file_exists($rate_limit_file) && time() - filemtime($rate_limit_file) < 1) {
    http_response_code(429);
    exit;
}

touch($rate_limit_file);
```

### Log Rotation for Security

Keep only last 30 days for security:
```bash
find logs/ -name "*.log" -mtime +30 -delete
```

### Database Security

Never commit config.php to git:
```bash
echo "config.php" > .gitignore
```

Use environment variables (upgrade option):
```php
// In config.php
define('DB_USER', getenv('DB_USER') ?: 'default_user');
```

---

## 📞 When to Contact Support

Contact your hosting provider if:
- MySQL won't start
- Disk space full
- SSL certificate issues
- cPanel functionality broken

Contact Facebook if:
- Webhook repeatedly fails verification
- Messages not delivered
- Access tokens expiring
- Page permissions issues

---

**Need Help?** Check logs first: `/logs/bot_*.log`
