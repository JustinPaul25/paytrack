# How to Ensure SMTP Ports Are Allowed

This guide shows you how to check and configure firewall rules to allow SMTP ports (25, 587, 465) for outbound connections.

## Quick Test First

Before configuring anything, test if ports are already working:

```bash
# Replace with your SMTP host and port
nc -zv smtp.gmail.com 587
```

**If you see:** `Connection to smtp.gmail.com 587 port [tcp/submission] succeeded!`
→ Ports are already allowed! ✅

**If you see:** `Connection timed out` or `Connection refused`
→ Continue reading to configure firewall ❌

---

## Laravel Forge

### Good News: Usually Already Allowed! 🎉

Laravel Forge **allows outbound SMTP connections by default**. You typically don't need to configure anything.

### How to Verify in Forge:

1. **Go to your Server** (not site) in Forge Dashboard
2. Click on **"Firewall"** tab
3. You'll see firewall rules listed
4. Outbound connections are typically allowed by default

### If You Need to Add Rules (Rare):

1. In the **"Firewall"** tab, click **"Add Rule"**
2. Configure:
   - **Port:** `587` (or `465`, `25`)
   - **Protocol:** `TCP`
   - **Direction:** `Outbound` ⚠️ Important!
   - **Action:** `Allow`
3. Click **"Add Rule"**

**Note:** Forge uses UFW (Uncomplicated Firewall) under the hood, but you shouldn't need to modify it manually.

---

## Linux Servers (SSH Access)

### Method 1: UFW (Ubuntu/Debian - Most Common)

#### Check Current Status:

```bash
# Check if firewall is running
sudo ufw status

# Check detailed rules
sudo ufw status verbose

# Check numbered rules (easier to delete later)
sudo ufw status numbered
```

#### Allow SMTP Ports Outbound:

```bash
# Allow port 587 (most common)
sudo ufw allow out 587/tcp

# Allow port 465 (SSL)
sudo ufw allow out 465/tcp

# Allow port 25 (if needed, often blocked by providers)
sudo ufw allow out 25/tcp

# Verify the rules were added
sudo ufw status numbered
```

#### If Firewall is Inactive:

```bash
# Enable firewall
sudo ufw enable

# Then add SMTP rules (above commands)
```

### Method 2: firewalld (CentOS/RHEL)

#### Check Current Status:

```bash
# Check if firewall is running
sudo firewall-cmd --state

# List all rules
sudo firewall-cmd --list-all

# List only outbound rules
sudo firewall-cmd --list-all --zone=public
```

#### Allow SMTP Ports Outbound:

```bash
# Allow port 587
sudo firewall-cmd --permanent --add-port=587/tcp

# Allow port 465
sudo firewall-cmd --permanent --add-port=465/tcp

# Allow port 25
sudo firewall-cmd --permanent --add-port=25/tcp

# Reload firewall to apply changes
sudo firewall-cmd --reload

# Verify
sudo firewall-cmd --list-ports
```

### Method 3: iptables (Advanced)

```bash
# Allow port 587 outbound
sudo iptables -A OUTPUT -p tcp --dport 587 -j ACCEPT

# Allow port 465 outbound
sudo iptables -A OUTPUT -p tcp --dport 465 -j ACCEPT

# Allow port 25 outbound
sudo iptables -A OUTPUT -p tcp --dport 25 -j ACCEPT

# Save rules (Ubuntu/Debian)
sudo iptables-save | sudo tee /etc/iptables/rules.v4

# Or save rules (CentOS/RHEL)
sudo service iptables save
```

---

## Cloud Providers

### AWS EC2

1. Go to **EC2 Console** → **Security Groups**
2. Select your instance's security group
3. Click **"Edit Outbound Rules"**
4. Click **"Add Rule"**
5. Configure:
   - **Type:** Custom TCP
   - **Port Range:** `587` (or `465`, `25`)
   - **Destination:** `0.0.0.0/0` (all IPs)
   - **Description:** SMTP Outbound
6. Click **"Save Rules"**

**Important Notes:**
- AWS **blocks port 25 by default** on many instances
- Use port 587 or 465 instead
- You may need to request port 25 unblocking via AWS support

### DigitalOcean

1. Go to **Networking** → **Cloud Firewalls**
2. Select your firewall or create a new one
3. Click **"Outbound Rules"** tab
4. Click **"Add Outbound Rule"**
5. Configure:
   - **Type:** Custom
   - **Protocol:** TCP
   - **Port Range:** `587` (or `465`, `25`)
   - **Destination:** All IPv4, All IPv6
6. Click **"Add Rule"**

### Vultr

1. Go to **Networking** → **Firewall Groups**
2. Select your firewall group
3. Click **"Add Rule"**
4. Configure:
   - **Protocol:** TCP
   - **Port:** `587` (or `465`, `25`)
   - **Source/Destination:** Leave default (outbound)
5. Click **"Add Rule"**

### Linode

1. Go to **Firewalls**
2. Select your firewall
3. Click **"Outbound"** tab
4. Click **"Add a Rule"**
5. Configure:
   - **Label:** SMTP Outbound
   - **Protocol:** TCP
   - **Ports:** `587` (or `465`, `25`)
   - **Action:** Accept
6. Click **"Create"**

---

## Testing After Configuration

### Test 1: Quick Connection Test

```bash
# Test port 587
nc -zv smtp.gmail.com 587

# Test port 465
nc -zv smtp.gmail.com 465

# Test port 25 (may be blocked by provider)
nc -zv smtp.gmail.com 25
```

**Expected Output (Success):**
```
Connection to smtp.gmail.com 587 port [tcp/submission] succeeded!
```

### Test 2: Using Telnet

```bash
telnet smtp.gmail.com 587
```

**Expected Output (Success):**
```
Trying 74.125.24.108...
Connected to smtp.gmail.com.
Escape character is '^]'.
220 smtp.gmail.com ESMTP ...
```

Press `Ctrl + ]` then type `quit` to exit.

### Test 3: Using PHP Script

Upload and run the `test-smtp-connection.php` script:

```bash
php test-smtp-connection.php
```

### Test 4: Bash Built-in Test

```bash
# Test port 587
timeout 5 bash -c 'cat < /dev/null > /dev/tcp/smtp.gmail.com/587' && echo "✓ Port open" || echo "✗ Port blocked"
```

---

## Common Issues & Solutions

### Issue: "Connection timed out"

**Possible causes:**
- Firewall is blocking outbound connections
- Your hosting provider blocks SMTP ports at network level
- Port is wrong for your SMTP provider

**Solutions:**
1. Check firewall rules (see above)
2. Try different ports (587, 465, 25)
3. Contact your hosting provider

### Issue: "Connection refused"

**Possible causes:**
- SMTP server is down
- Wrong hostname or port
- SMTP server doesn't accept connections from your IP

**Solutions:**
1. Verify SMTP host and port
2. Check if SMTP provider requires IP whitelisting
3. Try different SMTP provider for testing

### Issue: Port 25 is blocked

**Common on:**
- AWS EC2 (by default)
- Many shared hosting providers
- Some VPS providers

**Solution:**
- Use port 587 (TLS) or 465 (SSL) instead
- These ports are rarely blocked

---

## Important Notes

### Outbound vs Inbound

⚠️ **Critical:** You need to allow **OUTBOUND** connections, not inbound!

- **Outbound:** Your server → SMTP server (what you need)
- **Inbound:** External → Your server (not needed for email)

Make sure firewall rules are configured for **outbound** direction.

### Port Recommendations

| Port | Encryption | Recommended? |
|------|-----------|--------------|
| 587  | TLS       | ✅ **Yes - Most reliable** |
| 465  | SSL       | ✅ Yes - Good alternative |
| 25   | None      | ⚠️ Often blocked |

**Best Practice:** Use port 587 with TLS encryption.

---

## Verification Checklist

After configuring firewall, verify:

- [ ] Firewall rule added for outbound connections
- [ ] Test connection with `nc` command succeeds
- [ ] Test email sending via Laravel Tinker works
- [ ] Check Laravel logs for email errors
- [ ] Verified correct port (587, 465, or 25)
- [ ] Encryption matches port (tls for 587, ssl for 465)

---

## Quick Reference Commands

```bash
# Test connection (replace with your SMTP host)
nc -zv smtp.gmail.com 587

# Check UFW status
sudo ufw status

# Allow SMTP ports (UFW)
sudo ufw allow out 587/tcp
sudo ufw allow out 465/tcp

# Check firewalld status
sudo firewall-cmd --list-all

# Allow SMTP ports (firewalld)
sudo firewall-cmd --permanent --add-port=587/tcp
sudo firewall-cmd --reload

# Test with PHP script
php test-smtp-connection.php

# View email logs
tail -f storage/logs/laravel.log
```

