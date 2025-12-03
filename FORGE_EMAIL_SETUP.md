# Laravel Forge Email Setup - Quick Guide

## Quick Setup Steps

### 1. Add Environment Variables in Forge

1. Go to **Forge Dashboard** → Your Server → Your Site
2. Click **"Environment"** tab
3. Add/Update these variables:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

4. Click **"Save"**

### 2. Clear Cache (Important!)

After saving environment variables, clear the config cache:

**Option A: Via Forge Dashboard**
- Go to Site → "App" tab → Scroll to "Commands"
- Run: `php artisan config:clear`

**Option B: Via SSH Terminal**
```bash
php artisan config:clear
php artisan cache:clear
```

### 3. Test Connection

**Via Forge SSH Terminal:**

1. Click "SSH Terminal" button in your Site
2. Test SMTP connection:
```bash
nc -zv smtp.gmail.com 587
```

If successful, you'll see:
```
Connection to smtp.gmail.com 587 port [tcp/submission] succeeded!
```

### 4. Test Email Sending

Via SSH Terminal:
```bash
php artisan tinker
```

Then in Tinker:
```php
Mail::raw('Test email', function($message) {
    $message->to('your-email@example.com')->subject('Test');
});
```

## Common SMTP Settings

### Gmail
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```
⚠️ **Note:** Requires App Password (not regular password)

### SendGrid
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
```

### Mailgun
```env
MAIL_MAILER=mailgun
```
Then configure in `config/services.php`:
```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    'scheme' => 'https',
],
```

### Postmark
```env
MAIL_MAILER=postmark
```
Add to `.env`:
```env
POSTMARK_TOKEN=your-postmark-token
```

### Brevo (formerly Sendinblue)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-brevo-smtp-username
MAIL_PASSWORD=your-brevo-smtp-password
MAIL_FROM_ADDRESS=your-verified-sender@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```
ℹ️ **Note:** 
- Username is your Brevo SMTP username (usually your full Brevo email)
- Password is your SMTP password (generate in Brevo SMTP settings)
- `MAIL_FROM_ADDRESS` must be a verified sender in your Brevo account

## Firewall in Forge

**Good News:** Forge allows outbound SMTP connections by default!

You typically **don't need** to configure firewall rules for email.

If you want to verify:
1. Go to **Server** (not site) → "Firewall" tab
2. SMTP ports should already be allowed outbound

## Troubleshooting

### Emails not sending?

1. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verify environment variables loaded:**
   ```bash
   php artisan tinker
   >>> config('mail.mailers.smtp')
   ```

3. **Test connection:**
   ```bash
   nc -zv your-smtp-host.com 587
   ```

4. **Clear all caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Common Errors

**"Connection timeout"**
- Check `MAIL_HOST` is correct
- Verify port number matches encryption type
- Test connection with `nc` command

**"Authentication failed"**
- Double-check `MAIL_USERNAME` and `MAIL_PASSWORD`
- For Gmail: Use App Password, not regular password

**"Could not connect"**
- Verify firewall allows outbound (usually already allowed in Forge)
- Check if SMTP provider requires IP whitelisting

## Port & Encryption Guide

| Port | Encryption | MAIL_ENCRYPTION |
|------|-----------|----------------|
| 587  | TLS       | `tls`          |
| 465  | SSL       | `ssl`          |
| 25   | None      | `null`         |

**Recommendation:** Use port 587 with TLS (most reliable)

## Quick Commands Reference

```bash
# Clear config cache
php artisan config:clear

# Test SMTP connection
nc -zv smtp-host.com 587

# Check mail config
php artisan tinker
>>> config('mail')

# View logs
tail -f storage/logs/laravel.log

# Test email
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'))
```

## Need Help?

1. Check `storage/logs/laravel.log` for detailed error messages
2. Test connection using `nc` command
3. Verify all environment variables are set correctly
4. Make sure you cleared config cache after updating `.env`

