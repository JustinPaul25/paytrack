# Mail Configuration Troubleshooting Guide

## Common Issues & Solutions

### Issue: Emails not sending in production/live

#### 1. Check Environment Variables

Make sure these are set correctly in your `.env` file on the live server:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

#### 2. Clear Configuration Cache

After updating environment variables, clear the config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

#### 3. Common SMTP Port & Encryption Combinations

| Port | Encryption | MAIL_ENCRYPTION Value |
|------|-----------|----------------------|
| 587  | TLS       | `tls`                |
| 465  | SSL       | `ssl`                |
| 25   | None      | `null` or leave empty |

#### 4. Test Email Sending

You can test if emails work by:

1. **Using Laravel Tinker:**
   ```bash
   php artisan tinker
   ```
   Then:
   ```php
   Mail::raw('Test email', function($message) {
       $message->to('your-email@example.com')
               ->subject('Test Email');
   });
   ```

2. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

#### 5. Check Firewall Settings

If emails are timing out or failing to connect, the firewall might be blocking SMTP ports. Here's how to check:

##### On Linux Servers (SSH Access):

1. **Check if firewall is running:**
   ```bash
   # For UFW (Ubuntu/Debian)
   sudo ufw status
   
   # For firewalld (CentOS/RHEL)
   sudo firewall-cmd --state
   sudo firewall-cmd --list-all
   
   # For iptables
   sudo iptables -L -n
   ```

2. **Test SMTP port connectivity:**
   ```bash
   # Test if you can reach the SMTP server (replace with your MAIL_HOST)
   telnet smtp.gmail.com 587
   # Or
   nc -zv smtp.gmail.com 587
   # Or
   timeout 5 bash -c 'cat < /dev/null > /dev/tcp/smtp.gmail.com/587' && echo "Port is open" || echo "Port is blocked"
   ```

3. **Check if port is allowed outbound:**
   ```bash
   # Check UFW rules for outbound connections
   sudo ufw status numbered
   
   # Allow outbound SMTP (if blocked)
   sudo ufw allow out 587/tcp
   sudo ufw allow out 465/tcp
   sudo ufw allow out 25/tcp
   ```

##### On Cloud Providers:

**AWS EC2:**
- Check Security Groups → Outbound Rules
- Ensure ports 25, 587, or 465 are allowed outbound
- Note: AWS blocks port 25 by default on many instances

**DigitalOcean:**
- Check Cloud Firewalls → Outbound Rules
- Ensure SMTP ports are allowed

**Forge/Laravel Forge:**
- Check Server Settings → Firewall Rules
- SMTP ports should be allowed outbound by default
- See detailed Laravel Forge setup guide below
- **Important:** Forge allows outbound connections by default - you typically don't need to configure anything

##### Test Connection from Server:

```bash
# Test SMTP connection directly (replace with your settings)
telnet your-smtp-host.com 587

# If telnet isn't available, use nc (netcat)
nc -zv your-smtp-host.com 587

# Or use curl for HTTPS endpoints
curl -v telnet://your-smtp-host.com:587
```

**Expected output if working:**
```
Connected to your-smtp-host.com
220 ... ESMTP server ready
```

**If blocked/failed:**
```
Connection timed out
Network is unreachable
Connection refused
```

##### Using PHP to Test:

Create a test file `test-smtp.php`:
```php
<?php
$host = 'your-smtp-host.com';
$port = 587;

$connection = @fsockopen($host, $port, $errno, $errstr, 10);

if ($connection) {
    echo "✓ Connection successful to {$host}:{$port}\n";
    fclose($connection);
} else {
    echo "✗ Connection failed: {$errstr} ({$errno})\n";
    echo "This may indicate a firewall block.\n";
}
```

Run it:
```bash
php test-smtp.php
```

##### Common Firewall Issues:

- **Port 25 blocked**: Many providers block port 25. Use 587 or 465 instead.
- **Outbound rules**: Make sure firewall allows outbound connections, not just inbound.
- **Cloud provider restrictions**: Check your hosting provider's documentation for SMTP restrictions.

#### 6. Common Error Messages

- **"Connection timeout"**: Check firewall, port, and host settings
- **"Authentication failed"**: Verify username and password
- **"SSL certificate problem"**: May need to set `MAIL_ENCRYPTION=null` temporarily for testing
- **"Could not connect to host"**: Check `MAIL_HOST` is correct or firewall is blocking

#### 7. Provider-Specific Settings

**Gmail:**
- Port: 587
- Encryption: tls
- Note: You'll need an "App Password" not your regular password

**Outlook/Hotmail:**
- Port: 587
- Encryption: tls

**SendGrid:**
- Port: 587
- Encryption: tls

**Brevo (formerly Sendinblue):**
- Host: `smtp-relay.brevo.com`
- Port: 587
- Encryption: tls
- Username: Your Brevo SMTP username
- Password: SMTP password (generate in Brevo dashboard, NOT account password)
- Note: Sender email must be verified in Brevo account

**Mailgun:**
- Use `MAIL_MAILER=mailgun` instead of smtp
- Requires API key configuration

**Postmark:**
- Use `MAIL_MAILER=postmark` instead of smtp
- Requires API token in `config/services.php`

#### 8. Verify Queue Workers Are Running

Emails are queued for better performance. Make sure queue workers are running:

- **Production (Forge):** Check Site → "Daemons" tab → Ensure queue worker is running
- **Local/Development:** Run `php artisan queue:work`
- **Or** set `QUEUE_CONNECTION=sync` in `.env` for synchronous processing (not recommended for production)

#### 9. Check PHP Extensions

Ensure these PHP extensions are installed:
- `openssl` (for TLS/SSL)
- `mbstring`

Check with:
```bash
php -m | grep -i openssl
php -m | grep -i mbstring
```

## Laravel Forge Setup Guide

### Setting Up Email in Laravel Forge

Laravel Forge makes it easy to configure email, but there are a few important steps:

#### Step 1: Configure Environment Variables in Forge

1. **Go to your Site in Forge Dashboard**
   - Navigate to your server → Select your site

2. **Click on "Environment" tab**
   - This shows your site's `.env` file

3. **Add/Update Mail Configuration:**
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

4. **Save the Environment**
   - Click "Save" at the bottom
   - Forge will automatically deploy these changes

#### Step 2: Check Firewall Settings

**Option A: Via Forge Dashboard (Recommended)**

1. Go to your **Server** (not site) in Forge
2. Click on **"Firewall"** tab
3. SMTP ports (25, 587, 465) should be allowed **outbound** by default
4. If not, you can add them:
   - Port: `587`
   - Protocol: `TCP`
   - Direction: `Outbound`
   - Action: `Allow`

**Option B: Via SSH**

If you need to manually check, SSH into your server:
```bash
# Check UFW status
ufw status

# If you need to allow ports (usually not needed in Forge)
sudo ufw allow out 587/tcp
sudo ufw allow out 465/tcp
```

**Note:** In Laravel Forge, outbound connections are typically allowed by default. You usually don't need to modify firewall rules for SMTP.

#### Step 3: Test Email Connection

**Method 1: Using Forge's SSH Terminal**

1. Go to your Site in Forge
2. Click **"SSH Terminal"** button
3. Run the test script:
   ```bash
   php test-smtp-connection.php
   ```

**Method 2: Using Laravel Tinker (via SSH)**

1. SSH into your server:
   ```bash
   cd /home/forge/your-site.com
   php artisan tinker
   ```

2. Test sending an email:
   ```php
   Mail::raw('Test email from Forge', function($message) {
       $message->to('your-email@example.com')
               ->subject('Test Email');
   });
   ```

3. Check for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

**Method 3: Quick Connection Test**

From Forge SSH terminal:
```bash
# Test SMTP connection (replace with your host)
nc -zv smtp.gmail.com 587

# Or with timeout
timeout 5 bash -c 'cat < /dev/null > /dev/tcp/smtp.gmail.com/587' && echo "✓ Port open" || echo "✗ Port blocked"
```

#### Step 4: Clear Configuration Cache

After updating environment variables in Forge:

1. **Via Forge Dashboard:**
   - Go to your Site → "App" tab
   - Scroll down to "Commands"
   - Run: `php artisan config:clear`
   - Run: `php artisan cache:clear`

2. **Via SSH:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

#### Step 5: Verify Queue Workers (If Using Queues)

If you're using queues for other emails:

1. Go to your Site → "Daemons" tab
2. Ensure queue worker is running:
   ```
   Command: php artisan queue:work --sleep=3 --tries=3
   Directory: /home/forge/your-site.com/current
   User: forge
   ```
3. If not running, click "Start Daemon"

**Or** set queue to sync mode in `.env`:
```env
QUEUE_CONNECTION=sync
```

#### Common Forge-Specific Issues

**Issue: Environment variables not loading**
- **Solution:** After saving `.env` in Forge, manually clear config cache
- Run: `php artisan config:clear` via SSH or Forge commands

**Issue: Port 25 blocked**
- **Solution:** Forge servers typically allow port 25, but many SMTP providers prefer 587 or 465
- Use port 587 with TLS encryption instead

**Issue: SSL certificate errors**
- **Solution:** Check your `MAIL_ENCRYPTION` setting
- For port 587: Use `tls`
- For port 465: Use `ssl`

**Issue: Connection timeout**
- **Solution:** 
  1. Verify firewall allows outbound connections (usually enabled by default)
  2. Test connection using `nc` or `telnet` commands
  3. Check if your SMTP provider requires IP whitelisting

#### Quick Checklist for Forge Setup

- [ ] Environment variables added in Forge dashboard
- [ ] `MAIL_MAILER=smtp` is set
- [ ] `MAIL_ENCRYPTION` matches your port (tls for 587, ssl for 465)
- [ ] Configuration cache cleared
- [ ] Tested connection using `nc` command
- [ ] Tested email sending via Tinker
- [ ] Checked Laravel logs for errors

#### Accessing Logs in Forge

1. **Via Forge Dashboard:**
   - Go to Site → "Logs" tab
   - Select "Laravel Log" from dropdown

2. **Via SSH:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Real-time monitoring:**
   ```bash
   php artisan pail
   ```

## Quick Diagnostic Commands

```bash
# Check current mail configuration
php artisan tinker
>>> config('mail')

# Test email sending
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('your@email.com')->subject('Test'))

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

