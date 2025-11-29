# 📱 Brevo SMS Template Setup Instructions

## ✅ STEP 1: Fill in the Message Template

**Copy this message and paste it into the Brevo "Order Confirmation" template:**

```
Hi {{contact.FIRSTNAME}}! Your order #{{params.ORDER_ID}} has been confirmed. Total: GH¢{{params.AMOUNT}}. Expected delivery: {{params.DELIVERY_DATE}}. Track: {{params.TRACKING_URL}}
```

**Character count:** ~150 characters (fits in 1 SMS segment)

---

## 📋 Alternative Shorter Version (if needed):

```
Hi {{contact.FIRSTNAME}}! Order #{{params.ORDER_ID}} confirmed. GH¢{{params.AMOUNT}}. Delivery: {{params.DELIVERY_DATE}}. Track: {{params.TRACKING_URL}}
```

**Character count:** ~120 characters

---

## 🎯 What Each Variable Does:

1. **`{{contact.FIRSTNAME}}`** 
   - Uses the customer's first name from your Brevo contacts
   - Example: "John", "Sarah"

2. **`{{params.ORDER_ID}}`**
   - The order number we'll pass when sending SMS
   - Example: "12345"

3. **`{{params.AMOUNT}}`**
   - Total order amount
   - Example: "150.00"

4. **`{{params.DELIVERY_DATE}}`**
   - Expected delivery date
   - Example: "Dec 15, 2024"

5. **`{{params.TRACKING_URL}}`**
   - Link to track the order
   - Example: "http://yoursite.com/track?order=12345"

---

## ⚠️ IMPORTANT NOTES:

### Option 1: Using Brevo Templates (What you're setting up now)

If you want to use **Brevo templates** (like what you're filling in now), we need to:

1. **Save this template in Brevo** and get the template ID
2. **Update our PHP code** to use the template ID instead of plain text
3. **Pass parameters** when sending SMS

### Option 2: Using Plain Text (Current Implementation)

Currently, our PHP code sends **plain text messages** directly (not using Brevo templates). This means:
- ✅ Works immediately
- ✅ No template setup needed
- ❌ Won't use the template you're creating in Brevo dashboard

---

## 🔧 To Use Brevo Templates, We Need To:

**Update our PHP code to use template ID:**

```php
// Instead of sending plain text, send with template ID
$api_data = [
    'recipient' => $phone,
    'templateId' => 123, // Your template ID from Brevo
    'params' => [
        'ORDER_ID' => $order_id,
        'AMOUNT' => $amount,
        'DELIVERY_DATE' => $delivery_date,
        'TRACKING_URL' => $tracking_url
    ]
];
```

---

## 📝 NEXT STEPS:

1. ✅ **Paste the template message above into Brevo** (what you're doing now)
2. ✅ **Save the template in Brevo**
3. ⚠️ **Get the Template ID** (you'll see it after saving)
4. ⚠️ **Tell me the Template ID** - I'll update the PHP code to use it
5. ⚠️ **Make sure customer names are in Brevo contacts** - For `{{contact.FIRSTNAME}}` to work

---

## 🤔 Which Approach Do You Want?

### **Option A: Use Brevo Template (What you're setting up)**
- ✅ Better template management in Brevo dashboard
- ✅ Can edit templates without changing code
- ❌ Requires template setup and updating our code

### **Option B: Keep Plain Text (Current)**
- ✅ Already working (once sender ID is approved)
- ✅ Simpler
- ❌ Template changes require code updates

**Let me know which you prefer!**

---

## 📞 For Now:

1. **Fill in the template** with the message I provided above
2. **Save it** in Brevo
3. **Get the Template ID** from Brevo
4. **Share the Template ID** with me
5. I'll update the PHP code to use your Brevo template!

---

## 🎨 Template Preview:

When a customer orders, they'll receive:

```
Hi John! Your order #12345 has been confirmed. Total: GH¢150.00. Expected delivery: Dec 15, 2024. Track: http://yoursite.com/track?order=12345
```

The customer's name will automatically be inserted from your Brevo contacts!

