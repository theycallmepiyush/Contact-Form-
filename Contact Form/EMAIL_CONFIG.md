# 📧 Email Configuration Guide

## Quick Setup

The contact form now supports **configurable email settings** with both **SMTP** and **mail()** methods.

### 1. Edit Configuration File

Open `config.php` and update your email settings:

```php
'email' => [
    'enabled' => true,
    'method' => 'smtp',  // Options: 'smtp' or 'mail'
    
    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'username'   => 'your-email@gmail.com',
        'password'   => 'your-app-password',  // NOT your regular password!
        'encryption' => 'tls',
    ],
    
    'from' => [
        'email' => 'noreply@yourdomain.com',
        'name'  => 'SupportDesk',
    ],
    
    'support_email' => 'support@yourdomain.com',
    'company_name' => 'SupportDesk',
],
```

---

## Email Methods

### Option 1: Using mail() Function (Default - Localhost)

**Best for:** Local development and cheap hosting

1. Set `'method' => 'mail'` in `config.php`
2. That's it! Uses PHP's built-in mail() function
3. **Limitations:** Won't work reliably on localhost without additional setup

### Option 2: SMTP (Recommended - Production)

**Best for:** Reliable email delivery

#### Gmail Setup (Free)

1. Enable "2-Step Verification" on your Google Account
2. Generate "App Password":
   - Go to [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
   - Select "Mail" and "Windows Computer"
   - Copy the generated 16-character password

3. Update `config.php`:
```php
'smtp' => [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'username'   => 'your-email@gmail.com',
    'password'   => 'xxxx xxxx xxxx xxxx',  // 16-char password from Google
    'encryption' => 'tls',
],
```

#### Other SMTP Providers

| Provider | Host | Port | Encryption |
|----------|------|------|-----------|
| Gmail | smtp.gmail.com | 587 | TLS |
| Outlook | smtp-mail.outlook.com | 587 | TLS |
| SendGrid | smtp.sendgrid.net | 587 | TLS |
| Mailtrap | smtp.mailtrap.io | 2525 | TLS |
| AWS SES | email-smtp.region.amazonaws.com | 587 | TLS |

---

## Test Your Configuration

### Via Web Interface

1. Open `http://localhost:8080/test-email.html` in your browser
2. Fill in your email settings
3. Click "Send Test Email"
4. Check your inbox for the test message

### Manual Test

Create a test file (`test.php`):

```php
<?php
require_once 'EmailHelper.php';
$config = require 'config.php';
$mailer = new EmailHelper($config);

$success = $mailer->send(
    'test@example.com',
    'Test Subject',
    'This is a test message',
    'from@example.com',
    'From Name'
);

echo $success ? 'Email sent!' : 'Failed: ' . $mailer->getLastError();
?>
```

---

## Debugging

If emails aren't sending:

1. **Check `config.php`:**
   - Is `'enabled' => true`?
   - Are SMTP credentials correct?

2. **Check browser console (F12):**
   - Look for error messages in Network tab
   - Check the response from submit.php

3. **Check logs:**
   - Debug mode shows errors if `'debug' => true` in config.php

4. **Test directly:**
   - Use `test-email.html` to verify settings
   - Try sending from a PHP script directly

---

## File Structure

```
Contact Form/
├── index.html          ← Contact form
├── script.js           ← Form validation & submission
├── submit.php          ← Backend (processes form)
├── config.php          ← ⭐ EMAIL CONFIGURATION HERE
├── EmailHelper.php     ← Email sending helper
├── test-email.html     ← Email configuration tester
├── test-email.php      ← Backend for tester
├── style.css           ← Form styling
└── tickets.log         ← Submitted tickets (logged)
```

---

## Features

✅ **SMTP Support** - Professional email delivery
✅ **TLS/SSL Encryption** - Secure connections
✅ **Configuration File** - Easy to manage settings
✅ **Email Testing Tool** - Verify configuration before deploying
✅ **Fallback Logging** - Always saves to file if DB isn't set up
✅ **Debug Mode** - Shows real errors on localhost
✅ **Confirmation Emails** - Auto-sends to users
✅ **Team Notifications** - Auto-sends to support team

---

## Common Issues

### "Connection refused" or "Could not connect to SMTP"
- Check SMTP host and port are correct
- Verify firewall allows outbound connections to SMTP port
- Try a different SMTP provider

### "Authentication failed"
- Gmail: Make sure you're using 16-char App Password, not your regular password
- Other providers: Verify username/password are correct

### "Email sent" but not received
- Check spam/junk folder
- Verify the "to" email address is correct
- Try sending to a different email address

### Works on localhost but not on live server
- Hosting may block mail() function
- Use SMTP method instead
- Contact your hosting provider about mail restrictions

---

## Production Checklist

Before going live:

- [ ] Set `'debug' => false` in config.php
- [ ] Update `'from_email'` to a verified email
- [ ] Update `'support_email'` to your support team email
- [ ] Update `'company_name'` to your company name
- [ ] Test email sending with test-email.html
- [ ] Verify confirmation emails are being received
- [ ] Check that support team emails are being received
- [ ] Review email templates in submit.php if needed

