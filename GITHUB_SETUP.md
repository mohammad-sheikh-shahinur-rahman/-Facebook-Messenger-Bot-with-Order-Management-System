# 🚀 Push to GitHub - Step by Step Guide

## Prerequisites
- Git installed on your computer
- GitHub account created
- Command line/Terminal access

---

## Step 1: Initialize Git Repository (First Time Only)

```bash
cd "i:\FB BOT"
git init
git config user.name "Your Name"
git config user.email "your-email@example.com"
```

---

## Step 2: Create GitHub Repository

1. Go to [GitHub.com](https://github.com)
2. Click **"New"** (top-left dropdown)
3. **Repository name:** `fb-messenger-bot` (or your choice)
4. **Description:** Facebook Messenger Bot with Order Management
5. **Visibility:** 
   - `Public` - if you want others to see it
   - `Private` - if you want only you to access it
6. **Do NOT initialize with README, gitignore, or license** (we already have them)
7. Click **"Create repository"**

---

## Step 3: Add Remote & Push to GitHub

Copy the SSH or HTTPS URL from your new GitHub repo, then run:

### Using HTTPS (Easier for beginners):
```bash
git remote add origin https://github.com/YOUR_USERNAME/fb-messenger-bot.git
git branch -M main
git add .
git commit -m "Initial commit: FB Messenger Bot with Order Management"
git push -u origin main
```

### Using SSH (More secure):
```bash
git remote add origin git@github.com:YOUR_USERNAME/fb-messenger-bot.git
git branch -M main
git add .
git commit -m "Initial commit: FB Messenger Bot with Order Management"
git push -u origin main
```

**Replace `YOUR_USERNAME` with your actual GitHub username**

---

## Step 4: Create config.php Template (For Developers)

After pushing, create a template file so others know what to configure:

```bash
cp config.php config.php.example
git add config.php.example
git commit -m "Add config.php example template"
git push
```

---

## Step 5: Future Updates

After making changes locally:

```bash
git add .
git commit -m "Your commit message here"
git push
```

### Useful Git Commands:
```bash
git status              # See what changed
git log                 # See commit history
git diff                # See changes before committing
git pull                # Get latest changes
```

---

## ⚠️ Security Reminder

✅ **Already excluded** (in .gitignore):
- `config.php` - Never push this with real credentials
- `logs/` - No sensitive logs uploaded
- `database.sql` - Raw backups not in version control

🔒 **Best Practice for Credentials:**
1. Create `config.php.example` with dummy values
2. Developers copy it to their local `config.php`
3. Update with their own credentials locally
4. `config.php` stays in `.gitignore` (not tracked)

Example `config.php.example`:
```php
<?php
// Copy this file to config.php and update with YOUR credentials

define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'fb_messenger_bot');

define('FB_PAGE_ID', 'YOUR_PAGE_ID');
define('FB_PAGE_ACCESS_TOKEN', 'YOUR_ACCESS_TOKEN');
define('FB_VERIFY_TOKEN', 'YOUR_VERIFY_TOKEN');

define('APP_URL', 'https://yourdomain.com');
?>
```

---

## GitHub Actions (Optional - For CI/CD)

Create `.github/workflows/php-linter.yml` for automatic code checking:

```yaml
name: PHP Linter

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/composer-action@v6
      - run: php -l webhook.php
      - run: php -l functions.php
```

---

## Troubleshooting

### Error: "fatal: not a git repository"
```bash
git init
git remote add origin https://github.com/YOUR_USERNAME/fb-messenger-bot.git
```

### Error: "Repository already exists"
```bash
# Remove and re-add remote
git remote remove origin
git remote add origin https://github.com/YOUR_USERNAME/fb-messenger-bot.git
```

### Error: "Permission denied (publickey)"
- Switch to HTTPS instead of SSH
- Or set up SSH keys: https://github.com/settings/keys

### Error: "src refspec main does not match any"
```bash
git branch -M main
git push -u origin main
```

---

## 📚 Documentation to Update on GitHub

Add to your GitHub repo description:
- **Topic tags:** `facebook-bot`, `messenger`, `php`, `order-management`
- **Link to README:** Automatically displayed
- **Link to deployment guide:** Create `DEPLOYMENT.md` in repo

---

## 🎯 What Gets Uploaded

✅ **Will be pushed to GitHub:**
- All PHP files (`webhook.php`, `functions.php`, etc.)
- All admin pages
- `README.md`, `SETUP.md`, etc.
- This `GITHUB_SETUP.md` file
- `.gitignore` and `.gitattributes`

❌ **Will NOT be pushed (excluded):**
- `config.php` (sensitive credentials)
- `logs/` directory
- `.vscode/` and `.idea/` settings
- `database-backup.sql` files
- Any system files

---

## ✨ After First Push

1. **Go to your GitHub repo** → Settings → General
2. Keep it organized:
   - Add topic tags
   - Write a good short description
   - Link deployment guide

3. **Optional: Add collaborators**
   - Settings → Collaborators
   - Invite team members

4. **Track updates:**
   - Use GitHub Issues for bugs/features
   - Use Pull Requests for code reviews

---

## 📞 Quick Reference

```bash
# Clone your repo elsewhere (to test)
git clone https://github.com/YOUR_USERNAME/fb-messenger-bot.git

# Check remote URL
git remote -v

# See all commits
git log

# Undo last commit (before push)
git reset --soft HEAD~1

# Check what will be pushed
git diff --cached
```

---

**Happy coding! 🚀**

For more info: https://github.com/docs
