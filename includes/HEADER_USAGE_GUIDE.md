# 📋 HEADER COMPONENT USAGE GUIDE

## 📁 Files Created:
1. `includes/header.css` - All CSS styles
2. `includes/header.php` - All HTML structure & functionality

---

## ✅ HOW TO USE ON ANY PAGE:

### Step 1: Add to your `<head>` section
```html
<head>
    <!-- Other meta tags and links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- ADD THIS LINE -->
    <link href="../includes/header.css" rel="stylesheet">
    <!-- Or use: -->
    <link href="includes/header.css" rel="stylesheet">
    
</head>
```

### Step 2: Include in your `<body>` section
```php
<body>
    <!-- ADD THIS LINE AT THE TOP OF BODY -->
    <?php include '../includes/header.php'; ?>
    <!-- Or use: -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Your page content goes here -->
    
</body>
```

### Step 3: Add required JavaScript files before closing `</body>`
```html
    <!-- Before closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>
</body>
```

---

## 🎯 WHAT YOU GET:

### 1. ✨ Promo Banner (Sticky Top)
- Black Friday deals banner
- Countdown timer
- Shop Now link

### 2. 🏠 Main Header (Sticky)
- Logo with link to homepage
- Search bar (functional)
- Tech Revival section
- Wishlist icon with badge
- Cart icon with badge
- User dropdown with:
  - Account
  - My Orders
  - Track Orders
  - Notifications
  - Profile Picture upload
  - Language selector
  - Dark mode toggle
  - Logout

### 3. 🧭 Navigation Bar (Sticky)
- Shop by Brands dropdown
- HOME link
- SHOP mega dropdown (4 columns)
- REPAIR STUDIO link
- DEVICE DROP link
- MORE dropdown
- FLASH DEAL link

---

## 📝 REQUIREMENTS:

Make sure your page has:
```php
<?php
session_start();
require_once(__DIR__ . '/../settings/core.php');
// Header will automatically load cart count, brands, and categories
?>
```

---

## 🔄 PATH ADJUSTMENTS:

Depending on where your file is located, adjust paths:

**If file is in `views/` folder:**
```php
<?php include '../includes/header.php'; ?>
<link href="../includes/header.css" rel="stylesheet">
```

**If file is in root folder:**
```php
<?php include 'includes/header.php'; ?>
<link href="includes/header.css" rel="stylesheet">
```

---

## ✅ EXAMPLE COMPLETE PAGE:

```php
<?php
session_start();
require_once(__DIR__ . '/../settings/core.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Page - Gadget Garage</title>
    
    <!-- Required CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- HEADER CSS -->
    <link href="../includes/header.css" rel="stylesheet">
    
    <!-- Your custom CSS -->
    <style>
        /* Your page-specific styles */
    </style>
</head>

<body>
    <!-- INCLUDE HEADER -->
    <?php include '../includes/header.php'; ?>
    
    <!-- YOUR PAGE CONTENT -->
    <div class="container">
        <h1>Your Page Content</h1>
    </div>
    
    <!-- Required JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/header.js"></script>
    <script src="../js/dark-mode.js"></script>
</body>
</html>
```

---

## 🎨 FEATURES INCLUDED:

✅ Fully responsive
✅ Dark mode support
✅ Multi-language support
✅ All dropdowns working
✅ Hover effects
✅ Smooth animations
✅ Dynamic cart/wishlist counts
✅ User session handling
✅ All links functional

---

## 📞 NOTES:

- The header automatically checks if user is logged in
- Cart count updates dynamically
- All paths are relative (adjust based on your file location)
- Brands and categories load from database
- All original functionality preserved from cart page

---

**Created from cart.php - 100% exact copy with all functionality!**

