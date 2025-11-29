# 📱 Brevo Order Confirmation SMS Template

## Template to Use in Brevo Dashboard

**Copy this message into the Brevo "Order Confirmation" SMS template:**

```
Hi {{contact.FIRSTNAME}}! Your order #{{params.ORDER_ID}} has been confirmed. Total: GH¢{{params.AMOUNT}}. Expected delivery: {{params.DELIVERY_DATE}}. Track: {{params.TRACKING_URL}}
```

---

## ⚠️ IMPORTANT: Brevo Template Variables

Brevo uses **different variable formats** depending on how you send:

### Option 1: Using Contact Attributes (Recommended)
```
Hi {{contact.FIRSTNAME}}! Your order #{{params.ORDER_ID}} has been confirmed. Total: GH¢{{params.AMOUNT}}
```

### Option 2: Using Parameters
```
Hi {{params.CUSTOMER_NAME}}! Your order #{{params.ORDER_ID}} has been confirmed. Total: GH¢{{params.AMOUNT}}. Expected delivery: {{params.DELIVERY_DATE}}
```

---

## 📝 Full Template Options

### Short Version (Under 160 chars):
```
Hi {{contact.FIRSTNAME}}! Order #{{params.ORDER_ID}} confirmed. GH¢{{params.AMOUNT}}. Delivery: {{params.DELIVERY_DATE}}. Track: {{params.TRACKING_URL}}
```

### Medium Version:
```
Hi {{contact.FIRSTNAME}}! Your order #{{params.ORDER_ID}} has been confirmed. Total: GH¢{{params.AMOUNT}}. Expected delivery: {{params.DELIVERY_DATE}}. Track your order: {{params.TRACKING_URL}}
```

### Detailed Version:
```
Hello {{contact.FIRSTNAME}}! 🎉

Your order #{{params.ORDER_ID}} has been confirmed.

Total: GH¢{{params.AMOUNT}}
Delivery: {{params.DELIVERY_DATE}}

Track: {{params.TRACKING_URL}}

Thank you for shopping with Gadget Garage!
```

---

## 🔧 How to Set This Up in Brevo

1. **Go to Brevo Dashboard:**
   - Navigate to: **Transactional → SMS → Templates**
   - OR click on the template you see in the screenshot

2. **Fill in the Message:**
   - Paste one of the templates above
   - Use the short version if you want to stay under 160 characters

3. **Personalization:**
   - `{{contact.FIRSTNAME}}` - Will use customer's first name from Brevo contacts
   - `{{params.ORDER_ID}}` - Order ID (passed when sending)
   - `{{params.AMOUNT}}` - Order total (passed when sending)
   - `{{params.DELIVERY_DATE}}` - Expected delivery date
   - `{{params.TRACKING_URL}}` - Tracking link

4. **Save the Template**

---

## ⚠️ CRITICAL: Our PHP Code Needs Update

**Current Issue:** Our PHP code sends plain text messages, but if you're using Brevo templates, we need to:

1. **Either use template ID** (if template is created in Brevo)
2. **Or send plain text** (which we're currently doing)

**We need to decide:**
- Option A: Use Brevo template (requires template ID and different API call)
- Option B: Keep plain text (simpler, works now)

Let me know which approach you prefer!

---

## 📋 Variables We Need to Pass

When sending SMS via API, we need to pass:

```php
[
    'recipient' => '+233551387578',
    'templateId' => 123, // If using template
    'params' => [
        'ORDER_ID' => '12345',
        'AMOUNT' => '150.00',
        'DELIVERY_DATE' => 'Dec 15, 2024',
        'TRACKING_URL' => 'https://...',
        'CUSTOMER_NAME' => 'John'
    ]
]
```

---

## 🎯 RECOMMENDED TEMPLATE (Copy This):

```
Hi {{contact.FIRSTNAME}}! Order #{{params.ORDER_ID}} confirmed. GH¢{{params.AMOUNT}}. Delivery: {{params.DELIVERY_DATE}}. Track: {{params.TRACKING_URL}}
```

**Character count:** ~120 characters (fits in 1 SMS)

