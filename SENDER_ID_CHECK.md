# 📱 Sender ID Check Guide

## ⚠️ IMPORTANT: Your Sender ID Must Match Brevo

**Current Sender ID in Code:** `Gadget-G`

**What You're Seeing:** "Gadget Garage" (contact name)

---

## 🔍 How to Check Your Approved Sender ID:

1. **Log into Brevo:** https://app.brevo.com/
2. **Go to:** SMS → Sender IDs
3. **Check what's listed and APPROVED:**
   - Is it "Gadget-G"?
   - Is it "GadgetGarage"?
   - Is it "Gadget Garage"?
   - Is it something else?

---

## 📋 Sender ID Rules:

- **Max 11 alphanumeric characters** (letters + numbers)
- **OR max 15 numeric characters** (numbers only)
- **Must be approved** in Brevo before SMS will work
- **No spaces** in alphanumeric sender IDs (usually)

---

## ✅ Options:

### Option 1: "Gadget-G" (8 characters) ✅
- Current setting
- Fits within limit
- Must be approved in Brevo

### Option 2: "GadgetGarage" (11 characters) ✅
- Also fits within limit
- More descriptive
- Must be approved in Brevo

### Option 3: "Gadget Garage" (12 characters) ❌
- **TOO LONG** - exceeds 11 character limit
- Won't work as sender ID

---

## 🔧 What to Do:

1. **Check Brevo Dashboard:**
   - Go to SMS → Sender IDs
   - See what's approved

2. **Update Code if Needed:**
   - If "GadgetGarage" is approved, change line 15 in `settings/sms_config.php`:
   ```php
   define('SMS_SENDER_ID', 'GadgetGarage');
   ```

3. **If Nothing is Approved:**
   - Submit a sender ID in Brevo dashboard
   - Wait for approval
   - Then update the code

---

## 🚨 CRITICAL:

**The sender ID in your code MUST match exactly what's approved in Brevo!**

If they don't match, SMS will fail with "Invalid sender ID" error.

---

## 📝 Quick Check:

**Answer these:**
1. What sender ID is approved in your Brevo dashboard?
2. Does it match `Gadget-G` in the code?
3. If not, I'll update the code to match!

