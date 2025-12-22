# GOOGLE WORKSPACE EMAIL SECURITY CONFIGURATION
## Complete Setup Guide for PAUSATF.org

**Last Updated:** 2025-12-20
**Domain:** pausatf.org
**Email Provider:** Google Workspace

---

## TABLE OF CONTENTS

1. [Current Status](#current-status)
2. [Required Google Workspace DKIM Setup](#required-google-workspace-dkim-setup)
3. [DMARC Policy Upgrade](#dmarc-policy-upgrade)
4. [Verification Steps](#verification-steps)
5. [Troubleshooting](#troubleshooting)

---

## CURRENT STATUS

### ✅ Properly Configured

**MX Records (Mail Exchange):**
```
Priority  Mail Server
1         aspmx.l.google.com
5         alt1.aspmx.l.google.com
5         alt2.aspmx.l.google.com
10        alt3.aspmx.l.google.com
10        alt4.aspmx.l.google.com
```
- ✅ All 5 Google MX records configured correctly
- ✅ Proper priority order (1, 5, 5, 10, 10)
- ✅ Email routing to Google Workspace working

**SPF Record:**
```
v=spf1 include:_spf.google.com include:sendgrid.net ~all
```
- ✅ Authorizes Google Workspace to send email
- ✅ Authorizes SendGrid for marketing/transactional email
- ✅ Softfail (~all) for unauthorized senders

**DKIM Records (Active):**
1. **SendGrid DKIM:**
   - s1._domainkey.pausatf.org → SendGrid
   - s2._domainkey.pausatf.org → SendGrid
   - ✅ Active for SendGrid email signing

2. **Cloudflare DKIM:**
   - cf2024-1._domainkey.pausatf.org → Cloudflare key
   - ✅ Active for Cloudflare email features

---

### ⚠️ MISSING: Google Workspace DKIM

**Status:** ❌ NOT CONFIGURED

**Why This Matters:**
Google Workspace generates a unique DKIM key for your domain that cryptographically signs all outgoing emails from Google Workspace. Without this, emails sent from Google Workspace:
- May be flagged as spam by receiving servers
- Won't have Google's cryptographic signature
- Reduce email deliverability and trust

**Impact:** Medium Priority - Affects email deliverability

---

## REQUIRED GOOGLE WORKSPACE DKIM SETUP

### Step 1: Generate DKIM Key in Google Workspace Admin Console

**Access Admin Console:**
1. Go to https://admin.google.com/
2. Login with admin account for pausatf.org
3. Navigate to: **Apps → Google Workspace → Gmail → Authenticate email**

**Generate DKIM Key:**
1. Click **"Generate new record"**
2. DKIM key length: **2048 bits** (recommended)
3. Prefix selector: Use **"google"** or leave default
4. Click **"Generate"**

**Google will show you a TXT record like this:**
```
Hostname: google._domainkey.pausatf.org
TXT record value: v=DKIM1; k=rsa; p=MIIBIjANBgkq....[long string]
```

### Step 2: Add DKIM Record to Cloudflare DNS

**Via Cloudflare Dashboard:**
1. Login to https://dash.cloudflare.com/
2. Select domain: **pausatf.org**
3. Go to: **DNS → Records**
4. Click: **Add record**
5. Configure:
   ```
   Type: TXT
   Name: google._domainkey
   Content: [paste value from Google - including v=DKIM1; k=rsa; p=...]
   TTL: Auto
   Proxy status: DNS only (gray cloud)
   ```
6. Click **Save**

**Via Cloudflare API:**
```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/your-cloudflare-zone-id/dns_records" \
  -H "Authorization: Bearer your-cloudflare-api-token" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "TXT",
    "name": "google._domainkey",
    "content": "v=DKIM1; k=rsa; p=[YOUR_KEY_FROM_GOOGLE]",
    "ttl": 1,
    "comment": "Google Workspace DKIM key"
  }'
```

### Step 3: Verify and Enable in Google Workspace

**Back in Google Admin Console:**
1. Wait 5-10 minutes for DNS propagation
2. Click **"Start authentication"** next to your DKIM record
3. Google will verify the DNS record
4. Status should change to: **"Authenticating email"**

**Verification:**
```bash
# Check if DKIM record exists in DNS
dig google._domainkey.pausatf.org TXT +short

# Expected output:
# "v=DKIM1; k=rsa; p=MIIBIjANBgkq..."
```

---

## DMARC POLICY UPGRADE

### Current DMARC Configuration

**Current Record:**
```
_dmarc.pausatf.org
TXT: v=DMARC1; p=none;
```

**Current Behavior:**
- `p=none` = Monitoring mode only
- No action taken on failed emails
- No reports generated
- Minimal protection

### Recommended DMARC Configuration for Google Workspace

**Recommended Record:**
```
v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@pausatf.org; ruf=mailto:dmarc-forensics@pausatf.org; pct=100; adkim=r; aspf=r; fo=1
```

**Explanation of Each Parameter:**
- `v=DMARC1` - DMARC version 1
- `p=quarantine` - **Recommended policy** (suspicious emails go to spam)
  - Alternative: `p=reject` (strongest - blocks suspicious emails entirely)
  - Current: `p=none` (monitoring only)
- `rua=mailto:dmarc-reports@pausatf.org` - Aggregate reports email
- `ruf=mailto:dmarc-forensics@pausatf.org` - Forensic reports email
- `pct=100` - Apply policy to 100% of failing emails
- `adkim=r` - Relaxed DKIM alignment (allows subdomains)
- `aspf=r` - Relaxed SPF alignment (allows subdomains)
- `fo=1` - Generate forensic reports for any failure

### Migration Path (Recommended)

**Phase 1: Monitoring (Current - 30 days)**
```
v=DMARC1; p=none; rua=mailto:dmarc-reports@pausatf.org; pct=100
```
- Monitor email patterns
- Review reports to identify legitimate vs suspicious email
- No impact on email delivery

**Phase 2: Quarantine (After 30 days)**
```
v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@pausatf.org; pct=100; adkim=r; aspf=r
```
- Suspicious emails go to spam folders
- Legitimate emails continue to inbox
- Moderate protection

**Phase 3: Reject (After 60 days, optional)**
```
v=DMARC1; p=reject; rua=mailto:dmarc-reports@pausatf.org; pct=100; adkim=r; aspf=r
```
- **Strongest protection** - blocks suspicious emails entirely
- Only enable after confirming legitimate email isn't affected
- Maximum protection against spoofing

### How to Update DMARC Record

**Via Cloudflare API:**
```bash
# First, get the current DMARC record ID
RECORD_ID=$(curl -s "https://api.cloudflare.com/client/v4/zones/your-cloudflare-zone-id/dns_records?name=_dmarc.pausatf.org" \
  -H "Authorization: Bearer your-cloudflare-api-token" | jq -r '.result[0].id')

# Update to Phase 1 (monitoring with reports)
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/your-cloudflare-zone-id/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer your-cloudflare-api-token" \
  -H "Content-Type: application/json" \
  --data '{
    "content": "v=DMARC1; p=none; rua=mailto:dmarc-reports@pausatf.org; pct=100"
  }'
```

---

## VERIFICATION STEPS

### 1. Test Email Authentication

**Send Test Email:**
1. Send email from your Google Workspace account (e.g., name@pausatf.org)
2. Send to: check-auth@verifier.port25.com
3. You'll receive an automatic reply showing authentication results

**Expected Results:**
```
DKIM: PASS (after Google DKIM is added)
SPF: PASS
DMARC: PASS (after DMARC upgrade)
```

### 2. Use Google Workspace Toolbox

**Check MX Records:**
```
https://toolbox.googleapps.com/apps/checkmx/
Domain: pausatf.org
```

**Expected:** ✅ All 5 MX records found

**Check SPF:**
```
https://toolbox.googleapps.com/apps/dig/
Query: pausatf.org
Type: TXT
```

**Expected:** Shows SPF record with include:_spf.google.com

### 3. Third-Party Email Authentication Checker

**MXToolbox:**
```
https://mxtoolbox.com/SuperTool.aspx
Domain: pausatf.org
```

**Tests to run:**
- MX Lookup (should show 5 Google servers)
- SPF Record Lookup (should pass)
- DMARC Lookup (should show policy)
- DKIM Lookup (use selector: google._domainkey.pausatf.org)

**Expected Results:**
- MX: ✅ PASS (5 records)
- SPF: ✅ PASS (includes Google)
- DMARC: ⚠️ WARNING (policy too lenient) - until upgraded
- DKIM: ❌ FAIL - until Google DKIM is added

---

## TROUBLESHOOTING

### DKIM Not Verifying in Google Workspace

**Problem:** Google Admin Console shows "Verification failed"

**Solutions:**
1. **Wait for DNS propagation:**
   ```bash
   # Check if record is visible
   dig google._domainkey.pausatf.org TXT +short
   ```
   - If empty: wait 5-10 more minutes
   - If shows record: proceed to step 2

2. **Verify exact TXT record value:**
   - Copy ENTIRE value from Google (including v=DKIM1; k=rsa; p=...)
   - Don't add extra quotes
   - Don't add extra spaces

3. **Check DNS record type:**
   - Must be TXT record (not CNAME)
   - Must be on exact hostname (google._domainkey.pausatf.org)

### Emails Going to Spam After DMARC Upgrade

**Problem:** Legitimate emails flagged as spam after changing p=quarantine

**Solutions:**
1. **Check DMARC alignment:**
   - DKIM must pass
   - SPF must pass
   - Domain in "From" address must match

2. **Review DMARC reports:**
   - Check rua= email for aggregate reports
   - Identify which emails are failing
   - Adjust SPF/DKIM if needed

3. **Temporarily revert to p=none:**
   ```bash
   # Revert DMARC to monitoring only
   # [Use update command from above with p=none]
   ```

### SendGrid Emails Affected

**Problem:** Marketing emails from SendGrid failing DMARC

**Solutions:**
1. **Verify SendGrid domain authentication:**
   - Login to SendGrid
   - Check domain authentication status
   - Ensure s1 and s2 DKIM records are active

2. **Check SPF includes SendGrid:**
   ```
   v=spf1 include:_spf.google.com include:sendgrid.net ~all
   ```

3. **Ensure "From" domain matches:**
   - SendGrid emails should use @pausatf.org sender
   - Or configure SendGrid custom domain

---

## RECOMMENDED ACTIONS

### Immediate (High Priority)

**1. Add Google Workspace DKIM Record**
- **Why:** Improves email deliverability for all Google Workspace email
- **Impact:** High - affects all outgoing email
- **Time:** 10-15 minutes
- **Risk:** None - only improves email authentication

**2. Enable DMARC Reporting (Phase 1)**
- **Why:** Start collecting data on email authentication
- **Impact:** None on email delivery
- **Time:** 5 minutes
- **Risk:** None - monitoring only

### Within 30 Days (Medium Priority)

**3. Upgrade DMARC to p=quarantine (Phase 2)**
- **Why:** Provides actual protection against email spoofing
- **Impact:** Suspicious emails quarantined (sent to spam)
- **Time:** 5 minutes (after reviewing reports from Phase 1)
- **Risk:** Low - if SPF/DKIM are configured correctly

### Optional (Long-term)

**4. Consider DMARC p=reject (Phase 3)**
- **Why:** Maximum protection against spoofing
- **Impact:** Suspicious emails blocked entirely
- **Time:** 5 minutes (after confirming Phase 2 works well)
- **Risk:** Medium - could block legitimate email if misconfigured

---

## SUMMARY

### Current Email Security Score: 7/10

**What's Working:**
- ✅ MX records (Google Workspace)
- ✅ SPF record (Google + SendGrid)
- ✅ SendGrid DKIM
- ✅ DMARC monitoring

**What's Missing:**
- ❌ Google Workspace DKIM (High Priority)
- ⚠️ DMARC enforcement (Medium Priority)

### After Recommended Changes: 10/10

**With Google DKIM + DMARC Quarantine:**
- ✅ MX records (Google Workspace)
- ✅ SPF record (Google + SendGrid)
- ✅ Google Workspace DKIM
- ✅ SendGrid DKIM
- ✅ DMARC enforcement (quarantine)

**Email Deliverability:** Excellent
**Spoofing Protection:** Strong
**Industry Compliance:** Full

---

## REFERENCES

**Google Workspace Documentation:**
- DKIM Setup: https://support.google.com/a/answer/180504
- SPF Setup: https://support.google.com/a/answer/33786
- DMARC Setup: https://support.google.com/a/answer/2466580
- Email Authentication Best Practices: https://support.google.com/a/answer/10583557

**Email Authentication Standards:**
- SPF Standard (RFC 7208): https://tools.ietf.org/html/rfc7208
- DKIM Standard (RFC 6376): https://tools.ietf.org/html/rfc6376
- DMARC Standard (RFC 7489): https://tools.ietf.org/html/rfc7489

**Testing Tools:**
- Google Admin Toolbox: https://toolbox.googleapps.com/
- MXToolbox: https://mxtoolbox.com/
- DMARC Analyzer: https://dmarcian.com/
- Port25 Email Verifier: check-auth@verifier.port25.com

---

**Last Updated:** 2025-12-20
**Maintained by:** Thomas Vincent
**Next Review:** After Google DKIM is added (verify within 7 days)
