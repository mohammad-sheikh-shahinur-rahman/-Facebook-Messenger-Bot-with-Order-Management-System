# Complete Setup Guide - Facebook Messenger Bot

Step-by-step guide to get your bot running on cPanel.

---

## 🟢 Phase 1: Facebook Developer App Setup

### Step 1.1: Create Facebook App

1. Go to **[Facebook Developers](https://developers.facebook.com)**
2. Click **"My Apps"** → **"Create App"**
3. Select **"Business"** as app type
4. Fill form:
   - **App Name:** "FB Messenger Bot" (or your choice)
   - **App Purpose:** "Business"
   - **App Contact Email:** Your email
5. Click **"Create App"**

### Step 1.2: Add Messenger Product

1. In app dashboard, find **"Add Product"**
2. Search for **"Messenger"**
3. Click **"Set Up"**
4. You'll be redirected to Messenger settings

### Step 1.3: Get Your Credentials

**In Messenger Settings:**

1. **Get Page ID:**
   - Go to Settings → Basic
   - Find "Page IDs" section at top
   - Copy your Page ID (e.g., `123456789`)

2. **Generate Access Token:**
   - Still in Messenger settings
   - Find "Access Tokens" section
   - Click "Generate Token"
   - Select your Page from dropdown
   - Copy the long token string
   - This is your `FB_PAGE_ACCESS_TOKEN`

3. **Create Verify Token:**
   - This can be any random string you create
   - Examples:
     - `my_secret_verify_token_12345`
     - `fb_bot_verify_xyz789`
   - Use whatever you want (just remember it!)

**Save these three values somewhere safe:**
```
Page ID:          123456789
Access Token:     EAAB...xyz789...
Verify Token:     my_secret_verify_token_12345
```

---

## 🟢 Phase 2: cPanel Installation

### Step 2.1: Upload Files to cPanel

**Method A: Using cPanel File Manager (Easiest)**

1. Open cPanel → **File Manager**
2. Navigate to **`public_html`**
3. Create new folder: **`fb-bot`**
4. Upload these files inside:
   - `config.php`
   - `functions.php`
   - `webhook.php`
   - `database.sql`
   - `admin/` folder
   - `README.md`

**Method B: Using FTP**

```bash
ftp ftp.yourdomain.com
Username: your_cpanel_username
Password: your_cpanel_password

ftp> cd public_html
ftp> mkdir fb-bot
ftp> cd fb-bot
ftp> mput *  # Upload all files
ftp> bye
```

### Step 2.2: Create MySQL Database

**In cPanel:**

1. Go to **MySQL Databases**
2. Create new database:
   - **Database Name:** `fb_messenger_bot`
   - Click **"Create Database"**

3. Create new MySQL user:
   - **Username:** `fb_bot`
   - **Password:** (Create strong password)
   - Click **"Create User"**

4. Add user to database:
   - Find your user and database
   - Add ALL PRIVILEGES
   - Click **"Make Changes"**

### Step 2.3: Import Database Schema

**Method A: Using cPanel phpMyAdmin**

1. Open cPanel → **phpMyAdmin**
2. Select your database: `fb_messenger_bot`
3. Go to **"Import"** tab
4. Upload `database.sql` file
5. Click **"Import"**
6. You should see "Successful" message

**Method B: Using SSH**

```bash
# SSH into your cPanel
ssh user@yourdomain.com

# Import database
mysql -u fb_bot -p fb_messenger_bot < /home/user/public_html/fb-bot/database.sql

# Enter password when prompted
```

### Step 2.4: Update config.php

Open cPanel → File Manager → `fb-bot/config.php`

Update these lines:

```php
// DATABASE CONFIGURATION
define('DB_HOST', 'localhost');        // Keep as localhost
define('DB_USER', 'fb_bot');           // Username you just created
define('DB_PASS', 'your_password');    // Password you just set
define('DB_NAME', 'fb_messenger_bot'); // Database name

// FACEBOOK CONFIGURATION
define('FB_PAGE_ID', '123456789');                    // Your Page ID
define('FB_PAGE_ACCESS_TOKEN', 'EAAB...your_token'); // Your Access Token
define('FB_VERIFY_TOKEN', 'my_secret_verify_token_12345'); // Your Verify Token

// APPLICATION CONFIGURATION
define('APP_URL', 'https://yourdomain.com');  // Your website URL
```

**Save the file.**

### Step 2.5: Set File Permissions

**In cPanel Terminal (if available) or SSH:**

```bash
# Navigate to your app
cd /home/user/public_html/fb-bot

# Create logs directory
mkdir -p logs

# Set permissions
chmod 755 .
chmod 755 admin
chmod 644 config.php
chmod 644 functions.php
chmod 644 webhook.php
chmod 644 database.sql
chmod 777 logs

# Verify
ls -la
```

### Step 2.6: Test Admin Access

1. Visit: **`https://yourdomain.com/fb-bot/admin/login.php`**
2. Login with:
   - Username: `admin`
   - Password: `admin123`

**If successful:** You should see the Dashboard

**If login fails:** Check config.php database settings

---

## 🟢 Phase 3: Facebook Webhook Setup

### Step 3.1: Configure Webhook in Facebook

1. Go to **Facebook Developers**
2. Select your app
3. Go to **Messenger → Settings**
4. Find **"Webhooks"** section
5. Click **"Add Callback URL"**

6. Fill the form:
   - **Callback URL:** `https://yourdomain.com/fb-bot/webhook.php`
   - **Verify Token:** (Use the same token from config.php)
   - Example: `my_secret_verify_token_12345`

7. Click **"Verify and Save"**

**What should happen:**
- Your server receives a GET request with the verify token
- webhook.php checks if token matches
- Facebook receives a challenge response
- Status shows "✓ Verified"

**If verification fails:**
- Check webhook.php is accessible
- Check HTTPS is working
- Check token matches exactly
- Check logs for errors

### Step 3.2: Subscribe to Events

1. Still in Messenger Settings
2. Find **"Webhook Fields"** section
3. Check these boxes:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `messaging_quick_replies`

4. Find **"Select a Page"** → Select your page
5. Click **"Subscribe"**

### Step 3.3: Test Webhook

**From command line:**

```bash
# Test verification
curl -X GET "https://yourdomain.com/fb-bot/webhook.php?hub.mode=subscribe&hub.verify_token=my_secret_verify_token_12345&hub.challenge=test_challenge_123"

# Should return: test_challenge_123
```

**Check logs:**

1. Go to cPanel → File Manager
2. Navigate to `fb-bot/logs/`
3. Open latest `bot_YYYY-MM-DD.log` file
4. Should see: "Webhook Request"

---

## 🟢 Phase 4: Test the Bot

### Step 4.1: Send First Message

1. Go to your **Facebook Page**
2. Look for **"Send Message"** button
3. Click it to open Messenger chat
4. Type: `hello`
5. You should get: **Welcome message**

**If no response:**
- Check webhook is verified in Phase 3
- Check logs for errors
- Check all Facebook credentials are correct

### Step 4.2: Test Order Flow

1. In Messenger, type: `order`
2. Bot should ask for your name
3. Follow the steps:
   - Name
   - Phone
   - Address
   - Product selection
   - Quantity

4. After completing, you should see:
   - Order confirmation
   - Order ID

5. Check admin dashboard:
   - Login to dashboard
   - New order should appear

### Step 4.3: Test Admin Dashboard

1. Login: `https://yourdomain.com/fb-bot/admin/`
2. Go to **Orders**
3. Your order should be listed
4. Click edit to change status
5. Try exporting to CSV

---

## 🟢 Phase 5: Customization

### Customize Welcome Message

**In `functions.php`**, find `get_welcome_message()`:

```php
function get_welcome_message() {
    return "👋 Welcome to MY STORE!\n\n" .
           "I'm your assistant. I can help you:\n" .
           "• View our products\n" .
           "• Place an order\n\n" .
           "Type 'menu' to see all options!";
}
```

Change the text as needed.

### Add/Edit Products

**In `webhook.php`**, find `show_products()` function:

```php
$products = [
    [
        'title' => '🍔 Burger Combo',
        'subtitle' => 'Includes burger, fries & drink',
        'price' => '$12.99'
    ],
    // Add more products here
];
```

### Customize Reply Keywords

**In `webhook.php`**, find `get_keyword_response()` function:

```php
switch ($text) {
    case 'hi':
    case 'hello':
        // Add your own cases here
        return ['type' => 'text', 'data' => ['text' => 'Hello!']];
}
```

---

## 🔒 Phase 6: Security Hardening

### Step 6.1: Change Admin Password

1. Login to admin dashboard
2. Go to **Settings**
3. Click **"Change Password"**
4. Use strong password (12+ chars, mix of letters/numbers/symbols)

### Step 6.2: Backup Database

**Weekly backup via cPanel:**

1. Go to **Backup**
2. Download full backup
3. Save safely

**Or via SSH:**

```bash
mysqldump -u fb_bot -p fb_messenger_bot > backup_$(date +%Y%m%d).sql
```

### Step 6.3: SSL Certificate

- Your hosting should have free SSL (Let's Encrypt)
- Make sure HTTPS is enabled
- Facebook requires HTTPS for webhooks

**Test SSL:**

```bash
curl -I https://yourdomain.com/fb-bot/webhook.php

# Should show "HTTP/2 200" or "HTTP/1.1 200"
```

### Step 6.4: Close Admin to Specific IPs (Optional)

**In `.htaccess` (if not already in public_html):**

```apache
<Directory /home/user/public_html/fb-bot/admin>
    <IfModule mod_authz_core.c>
        Require ip 1.2.3.4  # Your IP
        # Add more IPs as needed
    </IfModule>
</Directory>
```

---

## 🐛 Troubleshooting

### Problem: "Database Connection Failed"

**Solution:**
1. Check MySQL is running in cPanel
2. Verify username and password in config.php
3. Check database exists: `phpMyAdmin → Databases`
4. Run this in phpMyAdmin SQL:
   ```sql
   SELECT * FROM admins;
   ```
   Should show admin record

### Problem: Webhook Not Receiving Messages

**Solution:**
1. Verify webhook URL is correct in Facebook settings
2. Verify token matches exactly
3. Check if HTTPS is working
4. Test: `curl -X GET "https://yourdomain.com/fb-bot/webhook.php?..."`
5. Check logs: `cPanel → File Manager → fb-bot/logs/`

### Problem: Admin Login Fails

**Solution:**
1. Try default: admin / admin123
2. Check database:
   ```sql
   SELECT * FROM admins WHERE username = 'admin';
   ```
3. Reset password with new hash:
   ```sql
   UPDATE admins SET password = '$2y$12$6l2EZ3p6/s8X0o1.XvKOLu3c9/L9Zi.m1m2G0gG9QX.5X5G5Q0Epa' WHERE id = 1;
   -- Password becomes: admin123
   ```

### Problem: Orders Not Saving

**Solution:**
1. Check `customer_sessions` table exists in phpMyAdmin
2. Check `orders` table exists
3. Review logs for SQL errors
4. Try adding order manually from admin dashboard

### Problem: Bot Not Responding

**Solution:**
1. Verify webhook verification in Phase 3
2. Try different keywords: hi, hello, order, products
3. Check logs for incoming messages
4. Verify Page ID and Access Token are correct

---

## 📞 Support Resources

- **Facebook Messenger Platform:** https://developers.facebook.com/docs/messenger-platform
- **Webhook Events:** https://developers.facebook.com/docs/messenger-platform/webhooks/incoming-messages-events
- **cPanel Documentation:** https://documentation.cpanel.net

---

## ✅ Checklist

- [ ] Facebook app created
- [ ] Credentials copied (Page ID, Token, Verify Token)
- [ ] Files uploaded to cPanel
- [ ] Database created and imported
- [ ] config.php updated with credentials
- [ ] File permissions set
- [ ] Admin dashboard accessible
- [ ] Webhook verified in Facebook
- [ ] Events subscribed (messages, quick_replies, postbacks)
- [ ] First message received and logged in logs
- [ ] Order created and saved in database
- [ ] Admin can view and edit orders
- [ ] Database backed up
- [ ] Admin password changed

---

**Your bot is now ready to use!** 🚀

Visit your admin dashboard: `https://yourdomain.com/fb-bot/admin/`
