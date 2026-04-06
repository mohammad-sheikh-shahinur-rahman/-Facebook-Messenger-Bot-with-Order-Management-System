# Facebook Messenger Bot with Order Management System

Complete PHP-based solution for automating customer service and order management through Facebook Messenger.

---

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Facebook Setup](#facebook-setup)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)
- [Developer](#developer)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## ✨ Features

### Bot Features
- ✅ Auto-reply to keyword-based messages
- ✅ Welcome message on first contact
- ✅ Persistent menu with 3 main options
- ✅ Quick reply buttons for user interactions
- ✅ Product catalog display
- ✅ Multi-step order collection

### Order System
- ✅ Step-by-step order flow via Messenger
- ✅ Collect customer: Name, Phone, Address, Product, Quantity
- ✅ Auto-confirmation message
- ✅ Real-time order updates to customer

### Admin Dashboard
- ✅ Secure login system
- ✅ View all orders with filters
- ✅ Order status management (Pending → Confirmed → Delivered)
- ✅ Edit/Delete orders
- ✅ Real-time analytics
- ✅ CSV export functionality
- ✅ Daily/Total order statistics
- ✅ Top products overview

### Security
- ✅ CSRF protection
- ✅ Input validation & sanitization
- ✅ Prepared statements (SQL Injection prevention)
- ✅ Password hashing with bcrypt
- ✅ Session management

---

## 🛠️ Requirements

### Server Requirements
- **PHP:** 7.4+ (8.0+ recommended)
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Web Server:** Apache or Nginx
- **SSL/HTTPS:** Required by Facebook
- **cPanel:** Compatible

### PHP Extensions
- MySQLi (enabled by default)
- JSON (enabled by default)
- cURL (for API requests)

### Software
- Git (optional, for cloning)
- cPanel/File Manager access
- Facebook Business Account

---

## 📦 Installation

### Step 1: Download and Upload Files

```bash
# Option A: Using cPanel File Manager
1. Create folder /public_html/fb-bot/
2. Upload all files

# Option B: Using FTP
ftp> cd public_html
ftp> mkdir fb-bot
ftp> put all files
```

### Step 2: Create Database

**Via cPanel:**
1. Go to phpMyAdmin
2. Create new database: `fb_messenger_bot`
3. Import `database.sql` file
4. Execute all SQL statements

**Via SSH:**
```bash
mysql -u root < database.sql
```

**Via Command Line:**
```bash
mysql> SOURCE /path/to/database.sql;
```

### Step 3: Configure Application

Edit `config.php` and update:

```php
// Database
define('DB_HOST', 'localhost');      // Usually localhost
define('DB_USER', 'root');           // Your MySQL user
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'fb_messenger_bot');

// Facebook (Get these from Facebook Developers)
define('FB_PAGE_ID', 'YOUR_PAGE_ID');
define('FB_PAGE_ACCESS_TOKEN', 'YOUR_ACCESS_TOKEN');
define('FB_VERIFY_TOKEN', 'CREATE_ANY_RANDOM_STRING');

// Your Domain
define('APP_URL', 'https://yourdomain.com');
```

### Step 4: Set File Permissions

```bash
chmod 755 ./
chmod 755 ./admin/
chmod 644 ./config.php
chmod 644 ./functions.php
chmod 644 ./webhook.php
mkdir -p logs
chmod 777 logs
```

### Step 5: Test Installation

Visit: `https://yourdomain.com/admin/`

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **CHANGE THIS IMMEDIATELY AFTER LOGIN!**

---

## ⚙️ Configuration

### 1. Facebook Setup

**Get Your Credentials:**

1. Go to [Facebook Developers](https://developers.facebook.com)
2. Create an app or use existing one
3. Set App Type: "Business"
4. Add "Messenger" product

**Find Your Credentials:**

- **Page ID:** Settings → Page Settings → Page ID
- **Access Token:** Messenger → Settings → Generate Token
- **Verify Token:** Create any random string (e.g., "my_secret_token_123")

### 2. Webhook Configuration

**In Facebook Developers Console:**

1. Go to Messenger → Settings
2. Add Webhook:
   - **Callback URL:** `https://yourdomain.com/webhook.php`
   - **Verify Token:** (Use what you set in config.php)
3. Click Verify and Save
4. Subscribe to events: `messages`, `messaging_postbacks`

**Test Webhook:**
```bash
curl -X GET "https://yourdomain.com/webhook.php?hub.mode=subscribe&hub.verify_token=YOUR_VERIFY_TOKEN&hub.challenge=test_challenge_123"
```

Expected response: `test_challenge_123`

### 3. Set Persistent Menu

After webhook is verified, run:
```bash
curl -X POST "https://yourdomain.com/webhook.php?action=setup_menu"
```

Or add this to your installation script.

### 4. Verify HTTPS

Facebook requires HTTPS. If using self-signed certificate:

```php
// In config.php during testing only:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

⚠️ Do NOT disable SSL verification in production!

---

## 🚀 Usage

### For Customer (via Messenger)

1. **Find Your Page** on Facebook
2. **Send Messages:**
   - "hi" → Get welcome message
   - "products" → See product list
   - "order" → Start ordering
   - "help" → Get help

3. **Place Order:**
   - Click "Place Order" button
   - Fill: Name → Phone → Address → Product → Quantity
   - Get instant confirmation with Order ID

### For Admin (Dashboard)

1. **Login:** https://yourdomain.com/admin/
2. **View Dashboard:** See stats and recent orders
3. **Manage Orders:**
   - View all orders with filters
   - Change order status
   - Edit order details
   - Delete orders
4. **Analytics:** Track top products, daily trends
5. **Export:** Export orders to CSV

---

## 📁 Project Structure

```
/fb-bot/
├── config.php                 # Configuration file (EDIT THIS!)
├── functions.php              # Helper functions
├── webhook.php                # Facebook webhook handler
├── database.sql               # Database schema
├── README.md                  # This file
├── SETUP.md                   # Detailed setup guide
│
├── /admin/                    # Admin dashboard
│   ├── login.php              # Admin login
│   ├── logout.php             # Admin logout
│   ├── header.php             # Navigation header
│   ├── footer.php             # Page footer
│   ├── dashboard.php          # Main dashboard
│   ├── orders.php             # Order list
│   ├── order-add.php          # Add new order
│   ├── order-edit.php         # Edit order
│   ├── order-delete.php       # Delete order
│   ├── analytics.php          # Analytics & reports
│   ├── settings.php           # Settings page
│   └── export-csv.php         # Export to CSV
│
├── /logs/                     # Log files (auto-created)
│   ├── bot_YYYY-MM-DD.log     # Daily bot logs
│   └── error.log              # PHP errors
│
└── /assets/                   # CSS/JS (optional)
    ├── style.css
    └── script.js
```

---

## 🔐 Security Configuration

### 1. Change Admin Password

After first login:
1. Go to Settings
2. Click "Change Password"
3. Enter current and new password

### 2. Database Security

```sql
-- Create limited database user
CREATE USER 'fb_bot'@'localhost' IDENTIFIED BY strong_password_here';
GRANT ALL PRIVILEGES ON fb_messenger_bot.* TO 'fb_bot'@'localhost';
FLUSH PRIVILEGES;

-- Update config.php
define('DB_USER', 'fb_bot');
define('DB_PASS', 'strong_password_here');
```

### 3. Admin Login Security

- Use HTTPS only
- Keep login URL private
- Enable CSRF protection (enabled by default)
- Use strong admin password

### 4. Facebook Configuration

- Never commit `config.php` to public repos
- Rotate access tokens regularly
- Use App Roles for team access
- Monitor webhook errors

---

## 🐛 Troubleshooting

### Webhook Not Receiving Messages

**Check:**
1. Verify webhook URL is HTTPS
2. Verify token matches config.php
3. Check SSL certificate validity
4. Review `logs/bot_*.log` for errors
5. Test webhook endpoint manually

**Fix:**
```bash
# Check webhook health
curl -v -X POST https://yourdomain.com/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"object":"page","entry":[{"messaging":[]}]}'
```

### Database Connection Error

**Error:** "Database Connection Failed"

**Fix:**
1. Verify MySQL is running
2. Check DB_HOST, DB_USER, DB_PASS in config.php
3. Ensure database exists
4. Check PHP MySQLi extension is loaded

```php
// Test in new file
<?php
echo extension_loaded('mysqli') ? 'MySQLi OK' : 'MySQLi Missing';
?>
```

### Admin Login Not Working

**Error:** "Invalid username or password"

**Fix:**
1. Verify admin user exists in database
2. Check admin active status
3. Try default credentials: admin / admin123
4. Check session_start() is called

```sql
-- Check admin record
SELECT * FROM admins WHERE username = 'admin';
```

### Orders Not Saving

**Error:** "Failed to create order"

**Fix:**
1. Check customer_sessions table exists
2. Verify orders table exists
3. Check database permissions
4. Review error.log for details

### Slow Admin Dashboard

**Optimize:**
1. Add database indexes (included in schema)
2. Limit results: `LIMIT 50`
3. Enable query caching
4. Use CDN for Bootstrap/jQuery

---

## 📊 Database Management

### Daily Backup

```bash
#!/bin/bash
mysqldump -u root fb_messenger_bot > backup_$(date +%Y%m%d).sql
```

### Monitor Database Size

```sql
SELECT 
  table_name,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.tables 
WHERE table_schema = 'fb_messenger_bot';
```

### Clean Old Sessions

```sql
-- Run weekly via cron
DELETE FROM customer_sessions WHERE expires_at < NOW();
DELETE FROM messages_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 🎯 Next Steps

1. ✅ Complete installation
2. ✅ Configure Facebook app
3. ✅ Test webhook
4. ✅ Send first message to bot
5. ✅ Change admin password
6. ✅ Customize product list
7. ✅ Set up backup routine

---

## �‍💻 Developer

### Mohammad Sheikh Shahinur Rahman
- **Website:** https://shahinurrahman.com/
- **Email:** contact@shahinurrahman.com
- **GitHub:** [@shahinurrahman](https://github.com/shahinurrahman)
- **LinkedIn:** [Mohammad Sheikh Shahinur Rahman](https://linkedin.com/in/shahinurrahman)

**About:** Full-stack PHP & JavaScript developer with expertise in Facebook Messenger bots, order management systems, and custom web solutions.

---

## 🤝 Contributing

We welcome contributions from the community! Whether it's bug reports, feature requests, or code improvements, your help makes this project better.

### How to Contribute

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/your-feature`)
3. **Commit** your changes (`git commit -am 'Add your feature'`)
4. **Push** to the branch (`git push origin feature/your-feature`)
5. **Submit** a Pull Request

### Contribution Types
- 🐛 **Bug Reports:** Found an issue? Report it on GitHub Issues
- ✨ **Features:** Have an idea? Suggest it in GitHub Discussions
- 📝 **Documentation:** Help improve guides and README
- 🔧 **Code:** Submit pull requests with improvements
- 🎨 **UI/UX:** Design improvements and usability enhancements

### Code Guidelines
- Follow PSR-12 PHP coding standards
- Write clean, commented code
- Test your changes thoroughly
- Update documentation for new features
- Never commit `config.php` with real credentials

For detailed contribution guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md)

---

## 📝 License

This project is licensed under the **MIT License** - see [LICENSE](LICENSE) file for details.

### Key Points:
- ✅ **Free to use** for commercial and personal projects
- ✅ **Open source** - modify and distribute
- ✅ **No warranty** - use at your own risk
- ✅ **Attribution** - please credit the original developer

### License Conditions:
- Include original license in any distributions
- No liability from the original author
- Modifications must be clearly marked

---

## 📞 Support & Documentation

- **Facebook Developers:** https://developers.facebook.com/docs/messenger-platform
- **Graph API:** https://developers.facebook.com/docs/graph-api
- **Webhook Events:** https://developers.facebook.com/docs/messenger-platform/webhooks
- **Developer Website:** https://shahinurrahman.com/
- **GitHub Issues:** Report bugs and request features
- **Discussions:** Ask questions and share ideas

---

## 🔄 Version History

- **v1.0.0** (2024) - Initial release
  - Complete Messenger bot
  - Order management system
  - Admin dashboard
  - Analytics

---

## 📊 Project Stats

![GitHub Stars](https://img.shields.io/github/stars/shahinurrahman/fb-messenger-bot?style=flat-square)
![GitHub Forks](https://img.shields.io/github/forks/shahinurrahman/fb-messenger-bot?style=flat-square)
![GitHub Issues](https://img.shields.io/github/issues/shahinurrahman/fb-messenger-bot?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)

---

**Made with ❤️ by [Mohammad Sheikh Shahinur Rahman](https://shahinurrahman.com/)**

If you find this project helpful, please consider:
- ⭐ Giving it a star on GitHub
- 🐛 Reporting issues and bugs
- ✨ Contributing improvements
- 📢 Sharing with others

**For commercial use, support, or custom development:** [Contact the developer](https://shahinurrahman.com/)

For updates and support, refer to official Facebook Messenger Platform documentation.
