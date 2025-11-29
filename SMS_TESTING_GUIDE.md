# 📱 SMS Testing & Troubleshooting Guide

## 🚨 ISSUE: No SMS Logs in Brevo Dashboard

If you see "Configuration ✅ Done" and "Verification ✅ Done" but **no SMS logs**, it means SMS messages aren't reaching Brevo. This could be due to:

1. ❌ API format/endpoint issues
2. ❌ Sender ID not approved
3. ❌ API key permissions
4. ❌ SMS function not being called

## ✅ STEP 1: Test SMS Directly

**Run the test script to verify SMS works:**

1. Open: `test_sms_direct.php` in your browser
2. **IMPORTANT**: Change the test phone number on line 13 to YOUR phone number
3. Run the script and check:
   - Does it show "✅ SMS sent successfully"?
   - What HTTP code does it return?
   - Any error messages?

**Expected Results:**

- ✅ HTTP 200 or 201 = Success
- ❌ HTTP 400 = Bad request (check sender ID or format)
- ❌ HTTP 401 = Invalid API key
- ❌ HTTP 403 = API key doesn't have SMS permissions

## ✅ STEP 2: Verify Sender ID is Approved

**This is CRITICAL - Brevo won't send SMS if sender ID isn't approved:**

1. Log into Brevo: https://app.brevo.com/
2. Go to: **SMS → Sender IDs**
3. Check if "Gadget-G" is listed and **APPROVED**
4. If not approved:
   - Submit it for approval
   - OR change `SMS_SENDER_ID` in `settings/sms_config.php` to an approved sender ID

**Current Sender ID:** `Gadget-G` (must be approved!)

## ✅ STEP 3: Check PHP Error Logs

SMS errors are logged to PHP error log. Check:

1. **XAMPP Error Log Location:**

   ```
   /Applications/XAMPP/xamppfiles/logs/php_error_log
   ```

2. **Look for these messages:**

   - `=== SMS SENDING ATTEMPT ===`
   - `✅ SMS sent successfully` (success)
   - `❌ Brevo API Error` (failure)
   - `HTTP Error XXX` (API error)

3. **Search for "SMS" in the log:**
   ```bash
   grep -i "sms" /Applications/XAMPP/xamppfiles/logs/php_error_log | tail -20
   ```

## ✅ STEP 4: Verify Order Flow Triggers SMS

**Check if SMS is being called:**

1. Place a test order
2. After payment, check error logs for:
   - `Customer order confirmation SMS sent successfully`
   - OR `Failed to send customer order confirmation SMS`

**SMS is called in:**

- `actions/paystack_verify_payment.php` (after payment verification)
- `actions/process_checkout_action.php` (alternative checkout flow)

## ✅ STEP 5: Check Brevo Account Status

1. Log into Brevo dashboard
2. Check **SMS Credits/Balance** - Do you have SMS credits?
3. Go to **Transactional → SMS → Logs**
4. Filter by today's date - Are there ANY logs?

**If NO logs at all:**

- SMS isn't reaching Brevo (API issue)
- Check Step 1 (test script)

**If logs show FAILED:**

- Check the error message in logs
- Common errors: Invalid sender ID, Invalid phone number

## 🔍 COMMON ISSUES & FIXES

### Issue 1: "Invalid sender ID"

**Fix:**

- Approve sender ID in Brevo dashboard
- OR change to an approved sender ID

### Issue 2: "API key invalid" or HTTP 401

**Fix:**

- Verify API key in `settings/sms_config.php`
- Make sure API key has SMS permissions in Brevo

### Issue 3: "Invalid phone number format"

**Fix:**

- Phone must be: `+233551387578` (with country code)
- NOT: `0551387578` or `233551387578`

### Issue 4: HTTP 400 Bad Request

**Fix:**

- Check request format matches Brevo requirements
- Verify sender ID is approved
- Check message content isn't too long

### Issue 5: No errors, but no SMS received

**Possible causes:**

- Carrier filtering
- Phone number incorrect
- SMS delivered but phone not receiving
- Check Brevo logs for delivery status

## 📋 CHECKLIST

- [ ] API key set in `settings/sms_config.php` ✅
- [ ] Sender ID "Gadget-G" approved in Brevo dashboard ⚠️ **CHECK THIS**
- [ ] Test script (`test_sms_direct.php`) runs successfully
- [ ] PHP error logs show SMS attempts
- [ ] Brevo dashboard shows SMS logs (after test)
- [ ] Customer phone numbers are in correct format (+233...)

## 🆘 STILL NOT WORKING?

1. **Check test script output:**

   - What HTTP code did it return?
   - What was the error message?

2. **Check PHP error logs:**

   - Are there any SMS-related errors?
   - Copy the error message

3. **Check Brevo dashboard:**

   - Go to SMS → Logs
   - Any failed attempts?
   - What's the error message?

4. **Contact Brevo support:**
   - If API key and sender ID are correct but still not working
   - Provide them with error logs

---

## 📞 Quick Test Command

Run this to check recent SMS errors:

```bash
tail -50 /Applications/XAMPP/xamppfiles/logs/php_error_log | grep -i sms
```
