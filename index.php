<?php
try {
	// Essential includes with error handling
	if (file_exists(__DIR__ . '/settings/core.php')) {
		require_once(__DIR__ . '/settings/core.php');
	}

	if (file_exists(__DIR__ . '/controllers/cart_controller.php')) {
		require_once(__DIR__ . '/controllers/cart_controller.php');
	}

	if (file_exists(__DIR__ . '/helpers/image_helper.php')) {
		require_once(__DIR__ . '/helpers/image_helper.php');
	}

	// Check login status
	$is_logged_in = function_exists('check_login') ? check_login() : false;
	$is_admin = false;

	if ($is_logged_in && function_exists('check_admin')) {
		$is_admin = check_admin();
		if ($is_admin && !isset($_GET['view_customer'])) {
			header("Location: admin/index.php");
			exit();
		}
	}

	// Get cart count
	$customer_id = $is_logged_in ? ($_SESSION['user_id'] ?? null) : null;
	$ip_address = $_SERVER['REMOTE_ADDR'];
	$cart_count = function_exists('get_cart_count_ctr') ? get_cart_count_ctr($customer_id, $ip_address) : 0;

	// Initialize arrays
	$categories = [];
	$brands = [];

	// Safe loading of categories and brands
	try {
		if (file_exists(__DIR__ . '/controllers/category_controller.php')) {
			require_once(__DIR__ . '/controllers/category_controller.php');
			$categories = function_exists('get_all_categories_ctr') ? get_all_categories_ctr() : [];
		}
	} catch (Exception $e) {
		$categories = [];
	}

	try {
		if (file_exists(__DIR__ . '/controllers/brand_controller.php')) {
			require_once(__DIR__ . '/controllers/brand_controller.php');
			$brands = function_exists('get_all_brands_ctr') ? get_all_brands_ctr() : [];
		}
	} catch (Exception $e) {
		$brands = [];
	}

	// Safe loading of products
	$featured_products = [];
	try {
		if (file_exists(__DIR__ . '/controllers/product_controller.php')) {
			require_once(__DIR__ . '/controllers/product_controller.php');
			$featured_products = function_exists('get_all_products_ctr') ? array_slice(get_all_products_ctr(), 0, 8) : [];
		}
	} catch (Exception $e) {
		$featured_products = [];
	}

} catch (Exception $e) {
	// Fallback values
	$is_logged_in = false;
	$is_admin = false;
	$cart_count = 0;
	$categories = [];
	$brands = [];
	$featured_products = [];
}

// Safe translation function
function t($key) {
	// Try to load translations if available
	try {
		if (file_exists(__DIR__ . '/includes/language_config.php')) {
			static $loaded = false;
			if (!$loaded) {
				require_once(__DIR__ . '/includes/language_config.php');
				$loaded = true;
			}
			global $translations, $current_language;
			$lang = $current_language ?? 'en';
			return $translations[$lang][$key] ?? $key;
		}
	} catch (Exception $e) {
		// Fallback
	}
	return $key;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= t('gadget_garage_title') ?></title>
	<link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
	<style>
		:root {
			--primary-color: #007bff;
			--secondary-color: #6c757d;
			--success-color: #28a745;
			--danger-color: #dc3545;
			--warning-color: #ffc107;
			--info-color: #17a2b8;
			--light-color: #f8f9fa;
			--dark-color: #343a40;
		}

		body {
			font-family: 'Inter', sans-serif;
			line-height: 1.6;
		}

		.hero-section {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 80vh;
			display: flex;
			align-items: center;
			color: white;
			position: relative;
			overflow: hidden;
		}

		.hero-content {
			z-index: 2;
			position: relative;
		}

		.product-card {
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			border: none;
			border-radius: 15px;
			overflow: hidden;
			height: 100%;
		}

		.product-card:hover {
			transform: translateY(-10px);
			box-shadow: 0 20px 40px rgba(0,0,0,0.1);
		}

		.product-image {
			height: 250px;
			object-fit: cover;
			width: 100%;
		}

		.category-card {
			background: white;
			border-radius: 20px;
			padding: 2rem;
			text-align: center;
			transition: all 0.3s ease;
			border: 2px solid transparent;
			height: 100%;
		}

		.category-card:hover {
			border-color: var(--primary-color);
			transform: translateY(-5px);
		}

		.category-icon {
			font-size: 3rem;
			color: var(--primary-color);
			margin-bottom: 1rem;
		}
	</style>
</head>

<body>
	<?php include __DIR__ . '/includes/header.php'; ?>

	<!-- Hero Section -->
	<section class="hero-section">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-6">
					<div class="hero-content animate__animated animate__fadeInLeft">
						<h1 class="display-4 fw-bold mb-4"><?= t('welcome_to_gadget_garage') ?></h1>
						<p class="lead mb-4"><?= t('hero_description') ?></p>
						<div class="d-flex flex-wrap gap-3">
							<a href="all_product.php" class="btn btn-light btn-lg">
								<i class="fas fa-shopping-bag me-2"></i><?= t('shop_now') ?>
							</a>
							<a href="repair_services.php" class="btn btn-outline-light btn-lg">
								<i class="fas fa-tools me-2"></i><?= t('repair_services') ?>
							</a>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="text-center animate__animated animate__fadeInRight">
						<img src="http://169.239.251.102:442/~chelsea.somuah/uploads/GadgetGarageLOGO.png"
							 alt="<?= t('gadget_garage') ?>" class="img-fluid" style="max-height: 400px;">
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Categories Section -->
	<section class="py-5 bg-light">
		<div class="container">
			<div class="text-center mb-5">
				<h2 class="fw-bold"><?= t('shop_by_category') ?></h2>
				<p class="text-muted"><?= t('browse_our_categories') ?></p>
			</div>

			<div class="row g-4">
				<div class="col-md-3 col-sm-6">
					<div class="category-card">
						<div class="category-icon">
							<i class="fas fa-mobile-alt"></i>
						</div>
						<h5><?= t('mobile_devices') ?></h5>
						<p class="text-muted mb-3"><?= t('smartphones_tablets') ?></p>
						<a href="mobile_devices.php" class="btn btn-outline-primary"><?= t('view_all') ?></a>
					</div>
				</div>

				<div class="col-md-3 col-sm-6">
					<div class="category-card">
						<div class="category-icon">
							<i class="fas fa-laptop"></i>
						</div>
						<h5><?= t('computing') ?></h5>
						<p class="text-muted mb-3"><?= t('laptops_desktops') ?></p>
						<a href="computing.php" class="btn btn-outline-primary"><?= t('view_all') ?></a>
					</div>
				</div>

				<div class="col-md-3 col-sm-6">
					<div class="category-card">
						<div class="category-icon">
							<i class="fas fa-camera"></i>
						</div>
						<h5><?= t('photography_video') ?></h5>
						<p class="text-muted mb-3"><?= t('cameras_equipment') ?></p>
						<a href="photography_video.php" class="btn btn-outline-primary"><?= t('view_all') ?></a>
					</div>
				</div>

				<div class="col-md-3 col-sm-6">
					<div class="category-card">
						<div class="category-icon">
							<i class="fas fa-tools"></i>
						</div>
						<h5><?= t('repair_studio') ?></h5>
						<p class="text-muted mb-3"><?= t('professional_repair') ?></p>
						<a href="repair_services.php" class="btn btn-outline-primary"><?= t('learn_more') ?></a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Featured Products -->
	<section class="py-5">
		<div class="container">
			<div class="text-center mb-5">
				<h2 class="fw-bold"><?= t('featured_products') ?></h2>
				<p class="text-muted"><?= t('discover_latest_tech') ?></p>
			</div>

			<div class="row g-4">
				<?php if (!empty($featured_products)): ?>
					<?php foreach (array_slice($featured_products, 0, 8) as $product): ?>
						<div class="col-lg-3 col-md-4 col-sm-6">
							<div class="card product-card">
								<img src="<?php echo function_exists('get_image_url') ? get_image_url($product['product_image'], 300, 250) : 'https://via.placeholder.com/300x250'; ?>"
									 class="product-image" alt="<?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?>">
								<div class="card-body">
									<h6 class="card-title"><?php echo htmlspecialchars($product['product_title'] ?? 'Product'); ?></h6>
									<p class="card-text text-muted small"><?php echo htmlspecialchars(substr($product['product_desc'] ?? '', 0, 60)); ?>...</p>
									<div class="d-flex justify-content-between align-items-center">
										<span class="fw-bold text-primary">GH¢<?php echo number_format($product['product_price'] ?? 0, 2); ?></span>
										<a href="single_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">
											<?= t('view_details') ?>
										</a>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="col-12 text-center">
						<p class="text-muted"><?= t('no_products_available') ?></p>
						<a href="all_product.php" class="btn btn-primary"><?= t('browse_products') ?></a>
					</div>
				<?php endif; ?>
			</div>

			<div class="text-center mt-5">
				<a href="all_product.php" class="btn btn-primary btn-lg">
					<i class="fas fa-th-large me-2"></i><?= t('view_all_products') ?>
				</a>
			</div>
		</div>
	</section>

	<!-- Call to Action -->
	<section class="py-5 bg-primary text-white">
		<div class="container text-center">
			<div class="row justify-content-center">
				<div class="col-lg-8">
					<h2 class="fw-bold mb-3"><?= t('need_tech_repair') ?></h2>
					<p class="lead mb-4"><?= t('tech_revival_description') ?></p>
					<div class="d-flex flex-wrap justify-content-center gap-3">
						<a href="device_drop.php" class="btn btn-light btn-lg">
							<i class="fas fa-hand-holding me-2"></i><?= t('device_drop') ?>
						</a>
						<a href="repair_services.php" class="btn btn-outline-light btn-lg">
							<i class="fas fa-phone me-2"></i><?= t('call_now') ?> 055-138-7578
						</a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php include __DIR__ . '/includes/footer.php'; ?>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script>
		// Fix crypto.randomUUID for older browsers
		if (!crypto.randomUUID) {
			crypto.randomUUID = function() {
				return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
					var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
					return v.toString(16);
				});
			};
		}

		// Initialize translation system
		window.currentLanguage = '<?= $current_language ?? 'en' ?>';

		// Load translation.js if it exists
		<?php if (file_exists(__DIR__ . '/js/translation.js')): ?>
		const translationScript = document.createElement('script');
		translationScript.src = 'js/translation.js';
		document.head.appendChild(translationScript);
		<?php endif; ?>

		// Smooth scrolling for anchor links
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function (e) {
				e.preventDefault();
				document.querySelector(this.getAttribute('href')).scrollIntoView({
					behavior: 'smooth'
				});
			});
		});
	</script>
</body>
</html>