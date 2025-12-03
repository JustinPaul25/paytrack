# Brevo (Sendinblue) SMTP Setup Guide

Complete guide for setting up Brevo (formerly Sendinblue) SMTP with Laravel.

## What You Need from Brevo

1. **SMTP Username** - Your Brevo account email or SMTP username
2. **SMTP Password** - Generated in Brevo SMTP settings (NOT your account password)
3. **Verified Sender** - Email address verified in your Brevo account

## Step 1: Get Your Brevo SMTP Credentials

### In Brevo Dashboard:

1. Log in to [Brevo](https://app.brevo.com/)
2. Go to **Settings** → **SMTP & API**
3. Scroll to **"SMTP"** section
4. You'll see:
   - **SMTP Server:** `smtp-relay.brevo.com`
   - **Port:** `587` (recommended) or `465`
   - **SMTP Login:** Your SMTP username
   - **SMTP Password:** Click "Generate New Password" if you don't have one

### Generate SMTP Password:

1. Click **"Generate New Password"** in SMTP settings
2. Copy the password immediately (you won't see it again!)
3. Save it securely - this is your `MAIL_PASSWORD`

## Step 2: Verify Your Sender Email

Before sending emails, verify your sender address:

1. Go to **Settings** → **Senders & IP**
2. Click **"Add a Sender"**
3. Enter your email address (e.g., `noreply@yourdomain.com`)
4. Confirm verification email sent to that address
5. Click the verification link

**Important:** The email you use in `MAIL_FROM_ADDRESS` must be verified in Brevo!

## Step 3: Configure Laravel

### Option A: Laravel Forge (Production)

1. Go to **Forge Dashboard** → Your Server → Your Site
2. Click **"Environment"** tab
3. Add/Update these variables:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-smtp-username
MAIL_PASSWORD=your-brevo-smtp-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

4. Click **"Save"**
5. Clear config cache (see Step 4)

### Option B: Local Development (.env file)

Add to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-smtp-username
MAIL_PASSWORD=your-brevo-smtp-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

## Step 4: Clear Configuration Cache

After updating environment variables:

**In Laravel Forge:**
- Go to Site → "App" tab → "Commands"
- Run: `php artisan config:clear`

**Via SSH/Terminal:**
```bash
php artisan config:clear
php artisan cache:clear
```

## Step 5: Test the Configuration

### Test 1: Connection Test

Via SSH/Terminal:
```bash
nc -zv smtp-relay.brevo.com 587
```

Expected output:
```
Connection to smtp-relay.brevo.com 587 port [tcp/submission] succeeded!
```

### Test 2: Send Test Email

Via Laravel Tinker:
```bash
php artisan tinker
```

Then:
```php
Mail::raw('Test email from Brevo', function($message) {
    $message->to('your-email@example.com')
            ->subject('Brevo Test Email');
});
```

### Test 3: Check Logs

Monitor logs while sending:
```bash
tail -f storage/logs/laravel.log
```

## Brevo SMTP Settings Reference

### Standard Settings (Recommended)

```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### Alternative Settings

**If port 587 doesn't work, try SSL:**
```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

**Alternative port (if 587 is blocked):**
```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
```

**Note:** Brevo also supports port 2525 as an alternative to 587. Use `smtp-relay.brevo.com` for all configurations.

## Common Issues & Solutions

### Issue: "Authentication failed"

**Possible causes:**
1. Wrong SMTP username or password
2. Using account password instead of SMTP password
3. Password has special characters not escaped

**Solutions:**
- Verify you're using SMTP password (generated in Brevo), not account password
- Check username matches exactly what's shown in Brevo SMTP settings
- If password has special characters, wrap it in quotes in `.env`:
  ```env
  MAIL_PASSWORD="your-password-with-special-chars"
  ```

### Issue: "Connection timeout"

**Possible causes:**
1. Firewall blocking port
2. Wrong host or port

**Solutions:**
- Test connection: `nc -zv smtp-relay.brevo.com 587`
- Verify `MAIL_HOST` is `smtp-relay.brevo.com`
- Check firewall allows outbound connections (usually fine in Forge)

### Issue: "Sender not verified"

**Error message:** "The from email address is not verified"

**Solution:**
1. Go to Brevo → Settings → Senders & IP
2. Verify the email address you're using in `MAIL_FROM_ADDRESS`
3. Make sure it matches exactly (including case)

### Issue: Emails going to spam

**Solutions:**
1. Use a domain email (not free email like Gmail)
2. Set up SPF, DKIM, and DMARC records for your domain
3. Verify your sender domain in Brevo
4. Use a professional sender name in `MAIL_FROM_NAME`

## Brevo Account Limits

Check your Brevo plan limits:

- **Free Plan:** 300 emails/day
- **Lite Plan:** 10,000 emails/month
- **Premium Plans:** Higher limits

If you hit limits, emails will fail. Check your Brevo dashboard for usage stats.

## Best Practices

### 1. Use Transactional Email Templates

Consider using Brevo's transactional email API for better deliverability:
- Set up in `config/services.php`
- Use Brevo's SDK for advanced features

### 2. Monitor Sending Stats

- Check Brevo dashboard regularly
- Monitor bounce rates
- Review blocked emails

### 3. Handle Errors Gracefully

Add error handling in your email sending code:
```php
try {
    Mail::to($user->email)->send(new WelcomeEmail($user));
} catch (\Exception $e) {
    Log::error('Failed to send email: ' . $e->getMessage());
    // Handle error (queue for retry, notify admin, etc.)
}
```

### 4. Use Queues for Production

For better performance, queue emails:
```php
Mail::to($user->email)->queue(new WelcomeEmail($user));
```

Make sure queue worker is running:
```bash
php artisan queue:work
```

## Environment Variables Summary

```env
# Required
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-smtp-username
MAIL_PASSWORD=your-brevo-smtp-password

# Recommended
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"

# Optional
MAIL_TIMEOUT=60
```

## Quick Troubleshooting Checklist

- [ ] SMTP username is correct (from Brevo SMTP settings)
- [ ] SMTP password is generated (not account password)
- [ ] `MAIL_FROM_ADDRESS` is verified in Brevo
- [ ] Port is 587 with TLS (or 465 with SSL)
- [ ] Configuration cache is cleared
- [ ] Connection test succeeds (`nc -zv smtp-relay.brevo.com 587`)
- [ ] Firewall allows outbound connections
- [ ] Check Laravel logs for detailed errors

## Testing Commands

```bash
# Test connection
nc -zv smtp-relay.brevo.com 587

# Check mail config
php artisan tinker
>>> config('mail.mailers.smtp')

# Test email send
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'))

# View logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan config:clear
```

## Need More Help?

1. **Brevo Documentation:** https://help.brevo.com/hc/en-us
2. **Brevo SMTP Guide:** https://help.brevo.com/hc/en-us/articles/209467485
3. **Check Brevo Dashboard:** Settings → SMTP & API for your credentials
4. **Laravel Logs:** Always check `storage/logs/laravel.log` for detailed errors

