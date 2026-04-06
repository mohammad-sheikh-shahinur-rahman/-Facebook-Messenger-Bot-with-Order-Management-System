# Contributing to Facebook Messenger Bot

Thank you for your interest in contributing! This document provides guidelines for contributing to this project.

## Code of Conduct

- Be respectful and professional
- Report security vulnerabilities privately
- Focus on constructive feedback

## How to Contribute

### 1. Fork & Clone
```bash
git clone https://github.com/YOUR_USERNAME/fb-messenger-bot.git
cd fb-messenger-bot
```

### 2. Create Feature Branch
```bash
git checkout -b feature/your-feature-name
```

Use clear branch names:
- `feature/add-analytics` - for new features
- `bugfix/webhook-timeout` - for bug fixes
- `docs/update-readme` - for documentation

### 3. Setup Local Environment

```bash
# Copy configuration
cp config.php.example config.php

# Update config.php with test credentials
nano config.php

# Import database
mysql -u root < database.sql
```

### 4. Make Your Changes

- Write clean, readable code
- Follow PHP PSR-12 coding standards
- Add comments for complex logic
- Test thoroughly

### 5. Commit & Push

```bash
git add .
git commit -m "Clear description of what changed"
git push origin feature/your-feature-name
```

### 6. Create Pull Request

1. Go to GitHub repository
2. Click "Compare & pull request"
3. Describe what you changed and why
4. Wait for review

## Code Standards

### PHP Style Guide
- Use PSR-12 coding standards
- 4 spaces for indentation (not tabs)
- Line length max 120 characters

### Example:
```php
<?php
namespace App;

class OrderManager
{
    public function createOrder($customerId, $products)
    {
        // Clear, descriptive variable names
        $totalAmount = $this->calculateTotal($products);
        
        // Add comments for non-obvious logic
        $result = $this->saveToDatabase($customerId, $products, $totalAmount);
        
        return $result;
    }
}
?>
```

### Naming Conventions
- Functions: `camelCase` - `createOrder()`
- Classes: `PascalCase` - `OrderManager`
- Constants: `UPPER_SNAKE_CASE` - `DB_HOST`
- Private methods: `_camelCase` - `_validateInput()`

## Testing

### Before Submitting PR:
1. Test the feature manually
2. Check error logs for any issues
3. Verify database migrations work
4. Test on different browsers if UI

```bash
# Check PHP syntax
php -l webhook.php
php -l functions.php

# Test database connection
php -r "require 'config.php'; MySQLi..."
```

## Documentation

- Update README.md for user-facing changes
- Update API_REFERENCE.md for API changes
- Add comments to complex code
- Include examples for new features

## Commit Message Guidelines

```
[Type] Brief description (50 chars max)

Detailed explanation if needed (72 chars per line)

- Bullet point 1
- Bullet point 2

Fixes #123 (if fixing an issue)
```

### Types:
- `[Feature]` - New feature
- `[Bugfix]` - Bug fix
- `[Docs]` - Documentation
- `[Style]` - Code style
- `[Refactor]` - Code refactoring
- `[Test]` - Adding tests

## Issue Guidelines

### Reporting Bugs
Include:
- PHP version
- Steps to reproduce
- Expected vs actual behavior
- Error message/log
- Screenshots if UI-related

### Feature Requests
- Clear title
- Description of use case
- Examples if applicable
- Mock-ups if UI-related

## PR Review Process

1. Automated checks (if any)
2. Code review by maintainer
3. Discuss changes if needed
4. Approval and merge

## File Structure

- `/admin/` - Dashboard pages
- `/logs/` - Application logs (not committed)
- `/uploads/` - User uploads (not committed)
- `*.php` - Core application files
- `*.md` - Documentation
- `.github/` - GitHub configurations

## Security

⚠️ **IMPORTANT:**
- Never commit `config.php` with real credentials
- Never hardcode API keys or passwords
- Sanitize all user input
- Use prepared statements for SQL
- Report security issues privately

## Performance Tips

- Use database indexes for frequently queried fields
- Cache static content
- Minimize API calls
- Optimize images before uploading

## Questions?

- Check existing issues and PRs
- Review API_REFERENCE.md
- Look at DEPLOYMENT.md for setup questions
- Read code comments for implementation details

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing! 🎉**
