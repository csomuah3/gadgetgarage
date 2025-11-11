<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
	// Start session and include core functions
	require_once(__DIR__ . '/settings/core.php');
	require_once(__DIR__ . '/controllers/cart_controller.php');
	require_once(__DIR__ . '/helpers/image_helper.php');

	// Check login status and admin status
	$is_logged_in = check_login();
	$is_admin = false;

	if ($is_logged_in) {
		$is_admin = check_admin();
	}

	// Get cart count
	$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
	$ip_address = $_SERVER['REMOTE_ADDR'];
	$cart_count = get_cart_count_ctr($customer_id, $ip_address);

	// Initialize arrays for navigation
	$categories = [];
	$brands = [];

	// Try to load categories and brands safely
	try {
		require_once(__DIR__ . '/controllers/category_controller.php');
		$categories = get_all_categories_ctr();
	} catch (Exception $e) {
		// If categories fail to load, continue with empty array
		error_log("Failed to load categories: " . $e->getMessage());
	}

	try {
		require_once(__DIR__ . '/controllers/brand_controller.php');
		$brands = get_all_brands_ctr();
	} catch (Exception $e) {
		// If brands fail to load, continue with empty array
		error_log("Failed to load brands: " . $e->getMessage());
	}
} catch (Exception $e) {
	// If core fails, show error
	die("Critical error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gadget Garage</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Animate.css for animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    :root{
      --brand-blue:#2252d1;
      --brand-green:#008060;
      --brand-text:#1a1a1a;
      --muted:#666;
      --light-bg:#f8f9fc;
      --card-radius:14px;
      --shadow-1:0 3px 12px rgba(0,0,0,.08);
      --shadow-2:0 6px 16px rgba(0,0,0,.14);
      --bento-1: conic-gradient(from 180deg at 50% 50%, rgba(162,95,255,.6), rgba(64,0,255,.6), rgba(255,60,172,.6), rgba(162,95,255,.6));
    }
    html,body{font-family:"Poppins",system-ui,-apple-system,sans-serif;color:var(--brand-text);}
    img{max-width:100%;display:block}
    a{text-decoration:none}

    /* Floating Bubbles Animation */
    .floating-bubbles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
      overflow: hidden;
    }

    .bubble {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(139, 95, 191, 0.1), rgba(240, 147, 251, 0.05));
      animation: floatUp linear infinite;
      opacity: 0.8;
    }

    .bubble:nth-child(odd) {
      background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(139, 95, 191, 0.05));
    }

    .bubble:nth-child(3n) {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 95, 191, 0.05));
    }

    .bubble:nth-child(5n) {
      background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(240, 147, 251, 0.05));
    }

    @keyframes floatUp {
      from {
        transform: translateY(100vh) translateX(0) scale(1);
        opacity: 0;
      }
      10% {
        opacity: 0.8;
      }
      90% {
        opacity: 0.8;
      }
      to {
        transform: translateY(-200px) translateX(var(--drift, 0px)) scale(var(--scale, 1));
        opacity: 0;
      }
    }

    /* HEADER */
    .topbar{background:#fff;border-bottom:1px solid #eee;box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);}
    .brand{font-weight:800;font-size:1.4rem;color:var(--brand-blue)}
    .searchbar{max-width:560px;width:100%}
    .icon-link{color:#222;margin-left:16px;position:relative;padding: 8px;border-radius: 6px;transition: all 0.3s ease;}
    .icon-link:hover{color:var(--brand-blue);background: rgba(139, 95, 191, 0.1);}
    .cart-badge{position:absolute;top:-5px;right:-5px;background:var(--brand-green);color:white;border-radius:50%;width:20px;height:20px;font-size:11px;display:flex;align-items:center;justify-content:center;font-weight:600;}

    /* Main Navigation */
    .main-nav {
      background: #ffffff;
      border-bottom: 1px solid #e5e7eb;
      padding: 12px 0;
    }

    .nav-menu {
      display: flex;
      align-items: center;
      gap: 32px;
    }

    .shop-categories-btn {
      background: var(--brand-blue);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      position: relative;
      transition: all 0.3s ease;
    }

    .shop-categories-btn:hover {
      background: #1a42b3;
      transform: translateY(-1px);
    }

    .nav-item {
      color: #1f2937;
      text-decoration: none;
      font-weight: 500;
      padding: 8px 0;
      position: relative;
      transition: color 0.3s ease;
    }

    .nav-item:hover {
      color: var(--brand-green);
    }

    .nav-item.flash-deal {
      color: #ef4444;
      font-weight: 600;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    .dropdown-categories {
      position: absolute;
      top: 100%;
      left: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      padding: 20px;
      min-width: 600px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .dropdown-categories.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    /* HERO */
    .hero-banner-section{padding:28px 0;background:#fff}
    .hero-grid{display:grid;grid-template-columns:2fr 1fr;gap:28px;align-items:stretch;min-height:560px}
    .main-banner{display:grid;grid-template-columns:1.15fr 1fr;gap:24px;padding:48px;border-radius:var(--card-radius);background:#ff5b57;color:#fff}
    .banner-title{font-weight:800;line-height:1.05;font-size:clamp(34px,5.2vw,72px);margin:0 0 12px}
    .banner-price{font-size:clamp(18px,2vw,28px);margin:0 0 16px}
    .btn-primary-gg{display:inline-flex;align-items:center;justify-content:center;height:56px;padding:0 28px;background:var(--brand-blue);color:#fff;border-radius:10px;font-weight:700;transition:all 0.3s ease;}
    .btn-primary-gg:hover{background:#1a42b3;transform:translateY(-2px);box-shadow:0 8px 25px rgba(34, 82, 209, 0.3);}
    .banner-media{display:flex;align-items:end;justify-content:center}
    .side-banners{display:grid;grid-template-rows:1fr 1fr;gap:28px}
    .side-card{border-radius:var(--card-radius);padding:36px 28px;display:grid;grid-template-columns:1fr auto;align-items:center;gap:24px;transition:transform 0.3s ease;}
    .side-card:hover{transform:translateY(-3px);}
    .side-card.yellow{background:#ffd21f;color:#111}
    .side-card.purple{background:#6f45d8;color:#fff}
    .side-title{font-weight:800;line-height:1.12;font-size:clamp(22px,2.4vw,34px);margin:0 0 8px}
    .side-media{width:148px;height:148px;border-radius:12px;overflow:hidden}
    .side-media img{width:100%;height:100%;object-fit:cover}

    /* SERVICES STRIP */
    .services-strip{background:#ecfff0;padding:22px 0;margin-top:18px;border-radius:10px}
    .service-item{display:flex;align-items:center;justify-content:center;gap:10px;font-weight:600;color:#004a1f}

    /* POPULAR CATEGORIES */
    .popular-categories{padding:80px 0;background:var(--light-bg)}
    .section-title{font-weight:800;font-size:2rem;margin-bottom:8px}
    .section-sub{color:var(--muted);margin-bottom:36px}
    .category-card{background:#fff;border-radius:var(--card-radius);padding:24px 18px;text-align:center;box-shadow:var(--shadow-1);transition:.25s;height:100%;cursor:pointer;}
    .category-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-2)}
    .category-icon{width:100%;height:140px;border-radius:10px;overflow:hidden;margin-bottom:14px}
    .category-icon img{width:100%;height:100%;object-fit:cover}
    .category-card h4{font-size:1.1rem;font-weight:700;margin-bottom:6px}
    .category-card p{color:#555;font-size:.95rem;margin:0}
    .price{color:var(--brand-blue);font-weight:800}

    /* BRANDS — Infinite marquee + Magic Bento hover */
    .brands-area{background:#0b0b13;color:#eae9f7;padding:56px 0;position:relative;overflow:hidden}
    .brands-area h2{color:#fff}
    .brands-marquee{mask-image:linear-gradient(to right, transparent, black 10%, black 90%, transparent)}
    .marquee-track{display:flex;gap:24px;list-style:none;padding:0;margin:0;animation:scrollX 35s linear infinite}
    @keyframes scrollX{from{transform:translateX(0)}to{transform:translateX(-50%)}}
    .brand-card{width:140px;height:86px;border-radius:16px;position:relative;isolation:isolate;background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.08)}
    .brand-card img{max-width:90px;max-height:44px;filter:grayscale(100%) brightness(1.2);opacity:.9;transition:.3s}
    .brand-card:hover img{filter:none;opacity:1}
    /* magic bento glow */
    .brand-card.bento::before{
      content:"";position:absolute;inset:-2px;border-radius:18px;background:var(--bento-1);
      filter:blur(18px);opacity:0;transition:.35s;z-index:-1;
    }
    .brand-card.bento:hover::before{opacity:.75}

    /* TESTIMONIALS — circular orbit */
    .testimonials{background:#fff;padding:72px 0}
    .orbit-wrap{position:relative;width:420px;height:420px;margin:0 auto}
    .orbit-center{
      position:absolute;inset:0;margin:auto;width:220px;height:220px;border-radius:18px;
      background:#f8f9fc;box-shadow:var(--shadow-1);display:flex;align-items:center;justify-content:center;padding:18px;text-align:center
    }
    .orbit-center p{margin:0;font-size:.98rem;color:#333}
    .orbit{position:absolute;inset:0;border-radius:50%;animation:spin 24s linear infinite}
    @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
    .avatar{
      position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
      width:70px;height:70px;border-radius:50%;overflow:hidden;border:3px solid #fff;box-shadow:0 6px 16px rgba(0,0,0,.18);cursor:pointer;transition:transform .25s
    }
    .avatar img{width:100%;height:100%;object-fit:cover}
    .avatar:hover{transform:translate(-50%,-50%) scale(1.08)}
    /* positions (degrees) */
    .a1{transform:translate(-50%,-50%) rotate(0deg) translate(180px) rotate(0deg)}
    .a2{transform:translate(-50%,-50%) rotate(60deg) translate(180px) rotate(-60deg)}
    .a3{transform:translate(-50%,-50%) rotate(120deg) translate(180px) rotate(-120deg)}
    .a4{transform:translate(-50%,-50%) rotate(180deg) translate(180px) rotate(-180deg)}
    .a5{transform:translate(-50%,-50%) rotate(240deg) translate(180px) rotate(-240deg)}
    .a6{transform:translate(-50%,-50%) rotate(300deg) translate(180px) rotate(-300deg)}
    .orbit:hover{animation-play-state:paused}

    /* Live Chat Widget */
    .live-chat-widget {
      position: fixed;
      bottom: 20px;
      left: 20px;
      z-index: 1000;
    }

    .chat-trigger {
      width: 60px;
      height: 60px;
      background: var(--brand-green);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.4rem;
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(0, 128, 96, 0.3);
      transition: all 0.3s ease;
    }

    .chat-trigger:hover {
      background: #006b4e;
      transform: scale(1.1);
    }

    .chat-panel {
      position: absolute;
      bottom: 80px;
      left: 0;
      width: 350px;
      height: 450px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
      display: none;
      flex-direction: column;
    }

    .chat-panel.active {
      display: flex;
    }

    .chat-header {
      padding: 16px 20px;
      background: var(--brand-green);
      color: white;
      border-radius: 16px 16px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chat-header h4 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .chat-close {
      background: none;
      border: none;
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 0;
    }

    .chat-body {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .chat-message {
      margin-bottom: 16px;
    }

    .chat-message.bot p {
      background: #f3f4f6;
      padding: 12px 16px;
      border-radius: 18px;
      margin: 0;
      font-size: 0.9rem;
    }

    .chat-footer {
      padding: 16px 20px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      gap: 12px;
    }

    .chat-input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #e5e7eb;
      border-radius: 25px;
      outline: none;
      font-size: 0.9rem;
    }

    .chat-input:focus {
      border-color: var(--brand-green);
    }

    .chat-send {
      width: 40px;
      height: 40px;
      background: var(--brand-green);
      border: none;
      border-radius: 50%;
      color: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s ease;
    }

    .chat-send:hover {
      background: #006b4e;
    }

    /* Newsletter Popup */
    .newsletter-popup {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      display: none;
    }

    .newsletter-popup.show {
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }

    .newsletter-modal {
      background: white;
      border-radius: 20px;
      padding: 40px;
      max-width: 500px;
      margin: 20px;
      position: relative;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: scale(0.8) translateY(-50px);
      }
      to {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    .newsletter-close {
      position: absolute;
      top: 15px;
      right: 15px;
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }

    /* FOOTER */
    footer{background:#111;color:#ddd;padding:56px 0 28px}
    footer a{color:#fff;transition:color 0.3s ease;}
    footer a:hover{color:var(--brand-green);}
    .footer-note{border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;font-size:.9rem;color:#aaa}

    /* User dropdown */
    .user-menu {
      position: relative;
    }

    .dropdown-menu-custom {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      padding: 12px 0;
      min-width: 200px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .dropdown-menu-custom.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item-custom {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 20px;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
      color: #1f2937;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .dropdown-item-custom:hover {
      background: rgba(139, 95, 191, 0.1);
      color: var(--brand-green);
    }

    /* RESPONSIVE */
    @media (max-width:992px){
      .hero-grid{grid-template-columns:1fr;min-height:auto}
      .side-banners{grid-template-rows:none;grid-template-columns:1fr 1fr}
      .nav-menu{gap:20px;overflow-x:auto;padding-bottom:8px;}
      .nav-menu::-webkit-scrollbar{display:none;}
    }
    @media (max-width:640px){
      .main-banner{grid-template-columns:1fr;padding:28px}
      .side-banners{grid-template-columns:1fr}
      .side-media{width:112px;height:112px}
      .orbit-wrap{width:320px;height:320px}
      .orbit-center{width:200px;height:200px}
      .a1,.a2,.a3,.a4,.a5,.a6{transform:translate(-50%,-50%) rotate(var(--r,0)) translate(135px) rotate(calc(var(--r,0) * -1))}
      .chat-panel{width:calc(100vw - 40px);height:400px;}
      .live-chat-widget{bottom:15px;left:15px;}
      .newsletter-modal{margin:20px;padding:30px;}
    }
  </style>
</head>
<body>

  <!-- Floating Bubbles Background -->
  <div class="floating-bubbles" id="floatingBubbles"></div>

  <!-- HEADER -->
  <header class="topbar py-3 animate__animated animate__fadeInDown">
    <div class="container d-flex align-items-center justify-content-between gap-3">
      <a href="index.php" class="brand">Gadget Garage</a>
      <form class="searchbar d-none d-md-block" action="search_results.php" method="GET">
        <input type="search" name="q" class="form-control" placeholder="Search for products..." />
      </form>
      <div class="d-flex align-items-center">
        <?php if (!$is_logged_in): ?>
          <!-- Guest user: Login | Register -->
          <a href="views/login.php" class="icon-link">
            <i class="fas fa-sign-in-alt"></i> Login
          </a>
          <a href="views/register.php" class="icon-link">
            <i class="fas fa-user-plus"></i> Register
          </a>
        <?php elseif ($is_admin): ?>
          <!-- Admin logged in: Admin Panel | Cart | Logout -->
          <a href="admin/index.php" class="icon-link">
            <i class="fas fa-tachometer-alt"></i> Admin
          </a>
          <a href="cart.php" class="icon-link">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
          </a>
          <div class="user-menu">
            <a href="#" class="icon-link" onclick="toggleUserDropdown(event)">
              <i class="fas fa-user"></i>
            </a>
            <div class="dropdown-menu-custom" id="userDropdownMenu">
              <button class="dropdown-item-custom" onclick="openProfilePictureModal()">
                <i class="fas fa-camera"></i> Profile Picture
              </button>
              <a href="views/logout.php" class="dropdown-item-custom" style="text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            </div>
          </div>
        <?php else: ?>
          <!-- Regular user logged in: Cart | Logout -->
          <a href="cart.php" class="icon-link">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
          </a>
          <div class="user-menu">
            <a href="#" class="icon-link" onclick="toggleUserDropdown(event)">
              <i class="fas fa-user"></i>
            </a>
            <div class="dropdown-menu-custom" id="userDropdownMenu">
              <button class="dropdown-item-custom" onclick="openProfilePictureModal()">
                <i class="fas fa-camera"></i> Profile Picture
              </button>
              <a href="views/logout.php" class="dropdown-item-custom" style="text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Main Navigation -->
  <nav class="main-nav">
    <div class="container">
      <div class="nav-menu">
        <!-- Shop Categories Button -->
        <div class="shop-categories-btn" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
          <i class="fas fa-bars"></i> Shop by Categories
          <div class="dropdown-categories" id="shopDropdown">
            <div class="row">
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Categories</h6>
                <ul class="list-unstyled">
                  <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                      <li class="mb-2">
                        <a href="all_product.php?category=<?= $category['cat_id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($category['cat_name']) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li>No categories available</li>
                  <?php endif; ?>
                </ul>
              </div>
              <div class="col-md-6">
                <h6 class="fw-bold mb-3">Popular Brands</h6>
                <ul class="list-unstyled">
                  <?php if (!empty($brands)): ?>
                    <?php foreach (array_slice($brands, 0, 5) as $brand): ?>
                      <li class="mb-2">
                        <a href="all_product.php?brand=<?= $brand['brand_id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($brand['brand_name']) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li>No brands available</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Navigation Items -->
        <a href="index.php" class="nav-item">HOME</a>
        <a href="all_product.php" class="nav-item">SHOP</a>
        <a href="#" class="nav-item">COLLECTIONS</a>
        <a href="repair_services.php" class="nav-item">REPAIR STUDIO</a>
        <a href="#" class="nav-item">BLOG</a>
        <a href="#" class="nav-item">CONTACT</a>
        <a href="#" class="nav-item flash-deal">⚡ FLASH DEAL</a>
      </div>
    </div>
  </nav>

  <main>
    <!-- HERO -->
    <section class="hero-banner-section">
      <div class="container">
        <div class="hero-grid">
          <!-- Left big -->
          <article class="main-banner">
            <div>
              <h1 class="banner-title">Apple iPad Pro 11<br/>Ultra Retina XDR<br/>256GB</h1>
              <p class="banner-price">Starting at <strong>₵2,360</strong></p>
              <a href="all_product.php?category=tablets" class="btn-primary-gg">SHOP NOW</a>
            </div>
            <div class="banner-media">
              <img src="https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?q=80&w=1600&auto=format" alt="iPad Pro">
            </div>
          </article>
          <!-- Right stack -->
          <div class="side-banners">
            <article class="side-card yellow">
              <div>
                <h3 class="side-title">T900 Ultra<br/>Watch</h3>
                <p>Starting <strong>₵190</strong></p>
                <a href="all_product.php?category=accessories" class="text-decoration-underline fw-bold">SHOP NOW</a>
              </div>
              <div class="side-media">
                <img src="https://images.unsplash.com/photo-1603791452906-bcce5e6d47a5?q=80&w=1200&auto=format" alt="Watch">
              </div>
            </article>
            <article class="side-card purple">
              <div>
                <h3 class="side-title">Kids Wireless<br/>Headphones</h3>
                <p>Starting <strong>₵360</strong></p>
                <a href="all_product.php?category=accessories" class="text-decoration-underline fw-bold text-white">SHOP NOW</a>
              </div>
              <div class="side-media">
                <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?q=80&w=1200&auto=format" alt="Headphones">
              </div>
            </article>
          </div>
        </div>

        <!-- Services row -->
        <div class="services-strip mt-4">
          <div class="container">
            <div class="row text-center g-3">
              <div class="col-6 col-md-3"><div class="service-item">🚚 Free Shipping</div></div>
              <div class="col-6 col-md-3"><div class="service-item">💸 Money Return</div></div>
              <div class="col-6 col-md-3"><div class="service-item">🎁 Special Gifts</div></div>
              <div class="col-6 col-md-3"><div class="service-item">⭐ Member Discount</div></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- MOST POPULAR CATEGORIES (5) -->
    <section class="popular-categories">
      <div class="container-fluid">
        <h2 class="section-title text-center">Most Popular Categories</h2>
        <p class="section-sub text-center">Top picks across our store</p>

        <div class="row g-4 justify-content-center">
          <div class="col-lg-2 col-md-4 col-6">
            <div class="category-card" onclick="window.location.href='all_product.php?category=phones'">
              <div class="category-icon"><img src="https://images.unsplash.com/photo-1510557880182-3f8c5f7b63d9?q=80&w=600&auto=format" alt="Smartphones"></div>
              <h4>Smartphones</h4><p>From <span class="price">₵2,300</span></p>
            </div>
          </div>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="category-card" onclick="window.location.href='all_product.php?category=laptops'">
              <div class="category-icon"><img src="https://images.unsplash.com/photo-1587202372775-98927e7d2e1c?q=80&w=600&auto=format" alt="Laptops"></div>
              <h4>Laptops</h4><p>From <span class="price">₵4,800</span></p>
            </div>
          </div>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="category-card" onclick="window.location.href='all_product.php?category=cameras'">
              <div class="category-icon"><img src="https://images.unsplash.com/photo-1508896694512-7a3a7eede1b0?q=80&w=600&auto=format" alt="Cameras"></div>
              <h4>Cameras</h4><p>From <span class="price">₵1,900</span></p>
            </div>
          </div>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="category-card" onclick="window.location.href='all_product.php?category=tablets'">
              <div class="category-icon"><img src="https://images.unsplash.com/photo-1605902711622-cfb43c4437d7?q=80&w=600&auto=format" alt="Tablets"></div>
              <h4>Tablets</h4><p>From <span class="price">₵2,100</span></p>
            </div>
          </div>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="category-card" onclick="window.location.href='all_product.php?category=accessories'">
              <div class="category-icon"><img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?q=80&w=600&auto=format" alt="Accessories"></div>
              <h4>Accessories</h4><p>From <span class="price">₵150</span></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- BRANDS — Infinite scroll + magic bento hover -->
    <section class="brands-area">
      <div class="container">
        <h2 class="section-title text-center">Popular Brands</h2>
        <p class="section-sub text-center" style="color:#bdbbe7">Trusted makers of phones, cameras, laptops & accessories</p>

        <div class="brands-marquee">
          <ul class="marquee-track" id="brandTrack">
            <!-- ONE LOOP (PNG favicons via Clearbit) -->
            <li class="brand-card bento"><img src="https://logo.clearbit.com/apple.com" alt="Apple"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/dell.com" alt="Dell"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/hp.com" alt="HP"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/lenovo.com" alt="Lenovo"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/asus.com" alt="ASUS"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/acer.com" alt="Acer"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/canon.com" alt="Canon"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/nikon.com" alt="Nikon"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/sony.com" alt="Sony"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/panasonic.com" alt="Panasonic"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/fujifilm.com" alt="Fujifilm"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/gopro.com" alt="GoPro"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/dji.com" alt="DJI"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/samsung.com" alt="Samsung"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/google.com" alt="Google"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/xiaomi.com" alt="Xiaomi"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/huawei.com" alt="Huawei"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/microsoft.com" alt="Microsoft"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/msi.com" alt="MSI"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/razer.com" alt="Razer"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/amazon.com" alt="Amazon"></li>

            <!-- DUPLICATE LOOP for seamless infinite scroll -->
            <li class="brand-card bento"><img src="https://logo.clearbit.com/apple.com" alt="Apple"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/dell.com" alt="Dell"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/hp.com" alt="HP"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/lenovo.com" alt="Lenovo"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/asus.com" alt="ASUS"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/acer.com" alt="Acer"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/canon.com" alt="Canon"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/nikon.com" alt="Nikon"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/sony.com" alt="Sony"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/panasonic.com" alt="Panasonic"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/fujifilm.com" alt="Fujifilm"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/gopro.com" alt="GoPro"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/dji.com" alt="DJI"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/samsung.com" alt="Samsung"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/google.com" alt="Google"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/xiaomi.com" alt="Xiaomi"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/huawei.com" alt="Huawei"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/microsoft.com" alt="Microsoft"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/msi.com" alt="MSI"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/razer.com" alt="Razer"></li>
            <li class="brand-card bento"><img src="https://logo.clearbit.com/amazon.com" alt="Amazon"></li>
          </ul>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS — circular orbit -->
    <section class="testimonials">
      <div class="container">
        <h2 class="section-title text-center">What Customers Say</h2>
        <p class="section-sub text-center">Real voices from Gadget Garage shoppers</p>

        <div class="orbit-wrap">
          <div class="orbit-center">
            <p id="quote">"Fantastic service and fast delivery. My laptop arrived in two days!" — <strong>Yaw</strong></p>
          </div>
          <div class="orbit" id="orbit">
            <div class="avatar a1" data-quote=""Fantastic service and fast delivery. My laptop arrived in two days!" — Yaw">
              <img src="https://images.unsplash.com/photo-1544723795-3fb6469f5b39?q=80&w=200&auto=format" alt="Yaw">
            </div>
            <div class="avatar a2" data-quote=""The prices in GHS are great and checkout was smooth." — Akua">
              <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format" alt="Akua">
            </div>
            <div class="avatar a3" data-quote=""Customer support helped me pick the right camera." — Kofi">
              <img src="https://images.unsplash.com/photo-1547425260-76bcadfb4f2c?q=80&w=200&auto=format" alt="Kofi">
            </div>
            <div class="avatar a4" data-quote=""Authentic brands and solid warranty—highly recommend." — Ama">
              <img src="https://images.unsplash.com/photo-1545996124-0501ebae84d0?q=80&w=200&auto=format" alt="Ama">
            </div>
            <div class="avatar a5" data-quote=""Got my headphones the same day in Accra. Great!" — Nii">
              <img src="https://images.unsplash.com/photo-1541534401786-2077eed87a72?q=80&w=200&auto=format" alt="Nii">
            </div>
            <div class="avatar a6" data-quote=""Their deals of the week are unbeatable." — Abena">
              <img src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?q=80&w=200&auto=format" alt="Abena">
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <h5 class="mb-3">Gadget Garage</h5>
          <p>Premium gadgets, fast shipping, and great prices in Ghana.</p>
          <div class="social-links">
            <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
            <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
            <a href="#" class="me-3"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
        <div class="col-md-2">
          <h6 class="mb-3">Shop</h6>
          <ul class="list-unstyled m-0">
            <li class="mb-2"><a href="all_product.php?category=phones">Smartphones</a></li>
            <li class="mb-2"><a href="all_product.php?category=laptops">Laptops</a></li>
            <li class="mb-2"><a href="all_product.php?category=cameras">Cameras</a></li>
            <li class="mb-2"><a href="all_product.php?category=tablets">Tablets</a></li>
            <li class="mb-2"><a href="all_product.php?category=accessories">Accessories</a></li>
          </ul>
        </div>
        <div class="col-md-3">
          <h6 class="mb-3">Support</h6>
          <ul class="list-unstyled m-0">
            <li class="mb-2"><a href="#">Contact</a></li>
            <li class="mb-2"><a href="#">Returns</a></li>
            <li class="mb-2"><a href="#">Shipping Info</a></li>
            <li class="mb-2"><a href="#">Warranty</a></li>
            <li class="mb-2"><a href="repair_services.php">Device Repair</a></li>
          </ul>
        </div>
        <div class="col-md-3">
          <h6 class="mb-3">Newsletter</h6>
          <form class="d-flex gap-2" id="newsletterForm">
            <input type="email" class="form-control form-control-sm" placeholder="Your email" required />
            <button class="btn btn-sm btn-primary" type="submit">Subscribe</button>
          </form>
        </div>
      </div>

      <div class="footer-note text-center mt-4">
        © <span id="year"></span> Gadget Garage — All rights reserved.
      </div>
    </div>
  </footer>

  <!-- Newsletter Popup -->
  <div class="newsletter-popup" id="newsletterPopup">
    <div class="newsletter-modal">
      <button class="newsletter-close" onclick="closeNewsletter()">
        <i class="fas fa-times"></i>
      </button>
      <div class="text-center">
        <h3 class="mb-3">🎉 Stay Updated!</h3>
        <p class="mb-4">Get the latest deals and new arrivals delivered to your inbox.</p>
        <form id="popupNewsletterForm">
          <div class="mb-3">
            <input type="email" class="form-control" placeholder="Enter your email" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Subscribe Now</button>
        </form>
        <p class="text-muted mt-3 small">No spam, unsubscribe anytime.</p>
      </div>
    </div>
  </div>

  <!-- Live Chat Widget -->
  <div class="live-chat-widget" id="liveChatWidget">
    <div class="chat-trigger" onclick="toggleLiveChat()">
      <i class="fas fa-comments"></i>
    </div>
    <div class="chat-panel" id="chatPanel">
      <div class="chat-header">
        <h4>Live Chat</h4>
        <button class="chat-close" onclick="toggleLiveChat()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="chat-body">
        <div class="chat-message bot">
          <p>Hello! How can we help you today?</p>
        </div>
      </div>
      <div class="chat-footer">
        <input type="text" class="chat-input" placeholder="Type your message...">
        <button class="chat-send">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Footer year
    document.getElementById('year').textContent = new Date().getFullYear();

    // Testimonials: hover to change quote + pause orbit when hovering any avatar
    const quoteEl = document.getElementById('quote');
    const orbit = document.getElementById('orbit');
    document.querySelectorAll('.avatar').forEach(a=>{
      a.addEventListener('mouseenter', ()=>{
        quoteEl.textContent = a.dataset.quote;
        orbit.style.animationPlayState = 'paused';
      });
      a.addEventListener('mouseleave', ()=>{
        orbit.style.animationPlayState = 'running';
      });
    });

    // Dropdown navigation functions
    function showDropdown() {
      const dropdown = document.getElementById('shopDropdown');
      dropdown.classList.add('show');
    }

    function hideDropdown() {
      const dropdown = document.getElementById('shopDropdown');
      dropdown.classList.remove('show');
    }

    // User dropdown toggle
    function toggleUserDropdown(event) {
      event.preventDefault();
      const dropdown = document.getElementById('userDropdownMenu');
      dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const userDropdown = document.getElementById('userDropdownMenu');
      const userMenu = document.querySelector('.user-menu');

      if (!userMenu.contains(event.target)) {
        userDropdown.classList.remove('show');
      }
    });

    // Live chat functionality
    function toggleLiveChat() {
      const chatPanel = document.getElementById('chatPanel');
      chatPanel.classList.toggle('active');
    }

    // Add live chat event listeners
    document.addEventListener('DOMContentLoaded', function() {
      const chatInput = document.querySelector('.chat-input');
      const chatSend = document.querySelector('.chat-send');

      if (chatInput && chatSend) {
        chatInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            sendChatMessage();
          }
        });

        chatSend.addEventListener('click', sendChatMessage);
      }

      // Create floating bubbles
      createFloatingBubbles();

      // Newsletter forms
      const forms = ['newsletterForm', 'popupNewsletterForm'];
      forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
          form.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            alert('Thank you for subscribing with email: ' + email);
            this.reset();
            if (formId === 'popupNewsletterForm') {
              closeNewsletter();
            }
          });
        }
      });
    });

    function sendChatMessage() {
      const chatInput = document.querySelector('.chat-input');
      const chatBody = document.querySelector('.chat-body');
      const message = chatInput.value.trim();

      if (message) {
        // Add user message
        const userMessage = document.createElement('div');
        userMessage.className = 'chat-message user';
        userMessage.innerHTML = `<p style="background: var(--brand-green); color: white; padding: 12px 16px; border-radius: 18px; margin: 0; font-size: 0.9rem; text-align: right;">${message}</p>`;
        chatBody.appendChild(userMessage);

        // Clear input
        chatInput.value = '';

        // Simulate bot response
        setTimeout(() => {
          const botMessage = document.createElement('div');
          botMessage.className = 'chat-message bot';
          botMessage.innerHTML = `<p>Thank you for your message! Our team will get back to you shortly. For immediate assistance, please call our support line.</p>`;
          chatBody.appendChild(botMessage);
          chatBody.scrollTop = chatBody.scrollHeight;
        }, 1000);

        // Scroll to bottom
        chatBody.scrollTop = chatBody.scrollHeight;
      }
    }

    // Newsletter popup functions
    function closeNewsletter() {
      document.getElementById('newsletterPopup').classList.remove('show');
      localStorage.setItem('newsletterShown', 'true');
    }

    // Show newsletter popup after 15 seconds if not shown before
    setTimeout(function() {
      if (!localStorage.getItem('newsletterShown')) {
        document.getElementById('newsletterPopup').classList.add('show');
      }
    }, 15000);

    // Create floating bubbles
    function createFloatingBubbles() {
      const bubblesContainer = document.getElementById('floatingBubbles');
      const bubbleCount = 50;

      for (let i = 0; i < bubbleCount; i++) {
        const bubble = document.createElement('div');
        bubble.className = 'bubble';

        // Create distinct size categories
        const sizeCategory = Math.random();
        let size;

        if (sizeCategory < 0.5) {
          size = Math.random() * 20 + 15; // Small bubbles (15-35px)
        } else if (sizeCategory < 0.8) {
          size = Math.random() * 25 + 35; // Medium bubbles (35-60px)
        } else {
          size = Math.random() * 30 + 60; // Large bubbles (60-90px)
        }

        bubble.style.width = size + 'px';
        bubble.style.height = size + 'px';
        bubble.style.left = Math.random() * 100 + '%';

        // Animation duration based on size
        let duration;
        if (size < 35) {
          duration = Math.random() * 10 + 15; // Fast: 15-25s
        } else if (size < 60) {
          duration = Math.random() * 15 + 20; // Medium: 20-35s
        } else {
          duration = Math.random() * 20 + 25; // Slow: 25-45s
        }
        bubble.style.animationDuration = duration + 's';
        bubble.style.animationDelay = Math.random() * 15 + 's';

        // Opacity and drift effects
        const opacity = size < 35 ? 0.4 + Math.random() * 0.3 : 0.6 + Math.random() * 0.4;
        bubble.style.opacity = opacity;

        const drift = (Math.random() - 0.5) * 200;
        const scale = 0.8 + Math.random() * 0.4;
        bubble.style.setProperty('--drift', drift + 'px');
        bubble.style.setProperty('--scale', scale);

        bubblesContainer.appendChild(bubble);
      }
    }

    // Profile picture modal functionality
    function openProfilePictureModal() {
      alert('Profile picture upload functionality will be implemented');
    }
  </script>
</body>
</html>