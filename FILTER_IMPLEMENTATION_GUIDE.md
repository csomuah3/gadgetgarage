# Product Filter System - Implementation Guide

## ✅ Files Created

1. **`css/product-filters.css`** - All filter styling
2. **`js/product-filters.js`** - All filter logic  
3. **`includes/product-filters.php`** - Reusable filter sidebar

---

## 📖 How to Use

### Step 1: Include CSS & JS in Page Header

```php
<!DOCTYPE html>
<html>
<head>
    <!-- Other head content -->
    
    <!-- Product Filters CSS -->
    <link href="../css/product-filters.css" rel="stylesheet">
</head>
<body>
    <!-- Page content -->
    
    <!-- Product Filters JS (before closing body tag) -->
    <script src="../js/product-filters.js"></script>
</body>
</html>
```

---

### Step 2: Configure & Include Filters

#### For **INDIVIDUAL CATEGORY PAGES** (smartphones.php, ipads.php, laptops.php)
**Shows:** Brand filter only, no category filter

```php
<?php
// Get products for this category
$all_products = get_all_products_ctr(); // or filter by category

// Configure filters - NO CATEGORY FILTER
$filter_config = [
    'show_category_filter' => false,  // ❌ Hide category filter
    'show_brand_filter' => true,      // ✅ Show brand filter
    'show_price_filter' => true,      // ✅ Show price slider
    'show_rating_filter' => true,     // ✅ Show ratings
    'fixed_category_id' => 1          // Category ID for smartphones
];
?>

<!-- Page Layout -->
<div class="container">
    <div class="row">
        <!-- Filters Sidebar (3 columns) -->
        <div class="col-lg-3">
            <?php include '../includes/product-filters.php'; ?>
        </div>
        
        <!-- Products Grid (9 columns) -->
        <div class="col-lg-9 product-grid-container">
            <div class="product-count">
                <i class="fas fa-box"></i> Showing <?php echo count($all_products); ?> products
            </div>
            
            <div id="productGrid" class="product-grid">
                <?php foreach ($all_products as $product): ?>
                    <!-- Product cards here -->
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
```

---

#### For **JOINT CATEGORY PAGES** (mobile_devices.php, computing.php, photography_video.php)
**Shows:** Both category AND brand filters

```php
<?php
// Get all products (or filter by allowed categories)
$all_products = get_all_products_ctr();

// Configure filters - SHOW CATEGORY FILTER
$filter_config = [
    'show_category_filter' => true,   // ✅ Show category filter
    'show_brand_filter' => true,      // ✅ Show brand filter
    'show_price_filter' => true,      // ✅ Show price slider
    'show_rating_filter' => true,     // ✅ Show ratings
    'fixed_category_id' => null,      // No fixed category
    'allowed_categories' => [1, 2]    // Only show Smartphones & Tablets
];
?>

<!-- Same layout as above -->
<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/product-filters.php'; ?>
        </div>
        
        <div class="col-lg-9 product-grid-container">
            <!-- Products grid -->
        </div>
    </div>
</div>
```

---

## 🎨 Features

### ✅ Slide-Out Sidebar
- **Desktop**: Click "Hide Filters" button to slide sidebar out
- **Mobile**: Filters appear in overlay panel
- **Remembers state**: Saves your preference in localStorage

### ✅ Search with Keywords
- Real-time search suggestions
- Searches product title, description, and keywords
- Click suggestion to select

### ✅ Price Range Slider
- Drag to set min/max price
- Real-time display updates
- Visual range indicator

### ✅ Tag Filters
- Click to select category/brand
- Only one active at a time
- Visual active state

### ✅ Responsive Design
- Desktop: Full sidebar
- Mobile: Overlay panel with close button
- Smooth animations

---

## 🔧 Customization

### Change Price Range
Edit in `includes/product-filters.php`:

```html
<input type="range" id="minPriceSlider" min="0" max="100000" value="0">
<input type="range" id="maxPriceSlider" min="0" max="100000" value="100000">
```

### Change AJAX URL
Edit in `js/product-filters.js`:

```javascript
let filterConfig = {
    ajaxUrl: '../actions/product_actions.php'  // Change this
};
```

### Customize Colors
Edit in `css/product-filters.css`:

```css
.apply-filters-btn {
    background: linear-gradient(135deg, #2563eb, #1e40af); /* Change colors */
}
```

---

## 📱 Mobile Behavior

- Filters hidden by default
- Floating "Filters" button in bottom-right
- Click to open overlay with filters
- Click X or outside to close
- Apply filters closes overlay automatically

---

## 🐛 Debugging

### Check Browser Console
The filter system logs everything:
- `🎯 Product Filters JS Loaded` - Script loaded
- `🚀 Initializing Product Filters...` - Starting init
- `🎯 Apply Filters Clicked` - Button clicked
- `📡 Fetch URL: ...` - AJAX request being made
- `✅ Filter response: [...]` - Data received

### Common Issues

**Filters not appearing?**
- Check if you included CSS file
- Check if you included the PHP file

**Apply button not working?**
- Check browser console for errors
- Verify AJAX URL is correct (`../actions/product_actions.php`)

**Price slider not moving?**
- Check if element IDs match (`minPriceSlider`, `maxPriceSlider`)

---

## 📦 Example Pages to Update

1. `views/smartphones.php` - Individual category (no category filter)
2. `views/ipads.php` - Individual category (no category filter)
3. `views/laptops.php` - Individual category (no category filter)
4. `views/mobile_devices.php` - Joint page (with category filter)
5. `views/computing.php` - Joint page (with category filter)
6. `views/photography_video.php` - Joint page (with category filter)
7. `views/all_product.php` - All products (with category filter)

---

## ✨ That's It!

Your filter system is now:
- ✅ Modular & reusable
- ✅ Easy to maintain
- ✅ Fully responsive
- ✅ Highly customizable
- ✅ Clean & organized

Just include the 3 lines and configure!

