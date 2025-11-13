<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/settings/core.php');
require_once(__DIR__ . '/controllers/cart_controller.php');

// Check login status
$is_logged_in = check_login();
$is_admin = false;

if ($is_logged_in) {
    $is_admin = check_admin();
}

// Get cart count
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$cart_count = get_cart_count_ctr($customer_id, $ip_address);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Device Drop - Gadget Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
        }

        .main-header {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 40px;
        }

        .logo .garage {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .device-drop-container {
            padding: 40px 0;
            min-height: 80vh;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            text-align: center;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #008060;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #008060;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .condition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .condition-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .condition-option:hover {
            border-color: #008060;
            background: #f0fdf4;
        }

        .condition-option.selected {
            border-color: #008060;
            background: #ecfdf5;
        }

        .condition-option input[type="radio"] {
            display: none;
        }

        .condition-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .condition-description {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .image-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .image-upload:hover {
            border-color: #008060;
            background: #f0fdf4;
        }

        .image-upload.dragover {
            border-color: #008060;
            background: #ecfdf5;
        }

        .upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .upload-text {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .upload-subtext {
            font-size: 0.9rem;
            color: #9ca3af;
        }

        #imagePreview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        .preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .preview-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
        }

        .submit-btn {
            background: linear-gradient(135deg, #008060, #006b4e);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #006b4e, #008060);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 128, 96, 0.3);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .process-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .process-info h4 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .process-info p {
            color: #1e3a8a;
            margin: 0;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }

            .condition-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    Gadget<span class="garage">Garage</span>
                </a>

                <!-- Navigation -->
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                    <?php if ($is_logged_in): ?>
                        <a href="cart.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-success"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Device Drop Content -->
    <div class="device-drop-container">
        <div class="container">
            <h1 class="page-title">Device Drop Request</h1>
            <p class="page-subtitle">
                Submit your device information for evaluation. We'll review your submission and get back to you within 3-7 business days to schedule a pickup appointment.
            </p>

            <div class="form-container">
                <form id="deviceDropForm" enctype="multipart/form-data" method="POST">
                    <!-- Device Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Information</h3>

                        <div class="form-group">
                            <label for="deviceType" class="form-label">Device Type *</label>
                            <select id="deviceType" name="device_type" class="form-select" required>
                                <option value="">Select Device Type</option>
                                <option value="smartphone">Smartphone</option>
                                <option value="tablet">Tablet / iPad</option>
                                <option value="laptop">Laptop</option>
                                <option value="desktop">Desktop Computer</option>
                                <option value="camera">Camera</option>
                                <option value="gaming">Gaming Console</option>
                                <option value="smartwatch">Smartwatch</option>
                                <option value="headphones">Headphones / Earbuds</option>
                                <option value="other">Other Electronic Device</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deviceBrand" class="form-label">Brand *</label>
                            <input type="text" id="deviceBrand" name="device_brand" class="form-input" placeholder="e.g., Apple, Samsung, Dell, Sony" required>
                        </div>

                        <div class="form-group">
                            <label for="deviceModel" class="form-label">Model *</label>
                            <input type="text" id="deviceModel" name="device_model" class="form-input" placeholder="e.g., iPhone 12 Pro, Galaxy S21, MacBook Pro 2020" required>
                        </div>
                    </div>

                    <!-- Condition Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Condition</h3>
                        <label class="form-label">Select the condition that best describes your device *</label>

                        <div class="condition-grid">
                            <div class="condition-option" onclick="selectCondition('excellent')">
                                <input type="radio" name="condition" value="excellent" id="excellent" required>
                                <div class="condition-title">Excellent</div>
                                <div class="condition-description">
                                    Device looks and functions like new. No visible scratches, dents, or wear. Screen is pristine. All buttons and ports work perfectly. Battery life is excellent.
                                </div>
                            </div>

                            <div class="condition-option" onclick="selectCondition('good')">
                                <input type="radio" name="condition" value="good" id="good" required>
                                <div class="condition-title">Good</div>
                                <div class="condition-description">
                                    Device has minor cosmetic wear but functions normally. May have light scratches or small scuffs. Screen is in good condition. All features work properly. Battery life is good.
                                </div>
                            </div>

                            <div class="condition-option" onclick="selectCondition('fair')">
                                <input type="radio" name="condition" value="fair" id="fair" required>
                                <div class="condition-title">Fair</div>
                                <div class="condition-description">
                                    Device shows noticeable wear but still functions. Visible scratches, dents, or cracks may be present. Some features may not work perfectly. Battery life may be reduced.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="form-section">
                        <h3 class="section-title">Additional Details</h3>

                        <div class="form-group">
                            <label for="description" class="form-label">Why are you giving up this device?</label>
                            <textarea id="description" name="description" class="form-textarea" placeholder="e.g., Upgraded to newer model, screen cracked, battery issues, no longer needed..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="askingPrice" class="form-label">Asking Price (Optional)</label>
                            <input type="number" id="askingPrice" name="asking_price" class="form-input" placeholder="Enter amount in USD" min="0" step="0.01">
                            <small style="color: #6b7280; font-size: 0.9rem;">Leave blank if you prefer our evaluation</small>
                        </div>
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-section">
                        <h3 class="section-title">Device Photos</h3>

                        <div class="form-group">
                            <label class="form-label">Upload images of your device (Recommended)</label>
                            <div class="image-upload" onclick="document.getElementById('images').click()">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <div class="upload-text">Click to upload photos or drag and drop</div>
                                <div class="upload-subtext">Supports: JPG, PNG, GIF (Max: 5MB each)</div>
                            </div>
                            <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages()">
                            <div id="imagePreview"></div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">Contact Information</h3>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" id="firstName" name="first_name" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" id="lastName" name="last_name" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="(xxx) xxx-xxxx" required>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Pickup Address</label>
                            <textarea id="address" name="address" class="form-textarea" placeholder="Enter your address for device pickup (optional - we can discuss this later)"></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Request Device Drop
                    </button>

                    <!-- Process Information -->
                    <div class="process-info">
                        <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                        <p>
                            <strong>Review:</strong> We'll evaluate your submission within 3-7 business days.<br>
                            <strong>Approval:</strong> Once approved, we'll contact you to schedule a convenient pickup appointment.<br>
                            <strong>Pickup:</strong> Our team will collect your device at the scheduled time and provide payment if applicable.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Condition selection functionality
        function selectCondition(condition) {
            // Remove selected class from all options
            document.querySelectorAll('.condition-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');

            // Check the radio button
            document.getElementById(condition).checked = true;
        }

        // Image preview functionality
        let selectedFiles = [];

        function previewImages() {
            const input = document.getElementById('images');
            const previewContainer = document.getElementById('imagePreview');

            // Add new files to selectedFiles array
            Array.from(input.files).forEach(file => {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }

                if (selectedFiles.length < 10) { // Limit to 10 images
                    selectedFiles.push(file);
                }
            });

            // Clear and rebuild preview
            previewContainer.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        <button type="button" class="preview-remove" onclick="removeImage(${index})">×</button>
                    `;
                    previewContainer.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            });

            // Clear the input
            input.value = '';
        }

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            previewImages(); // Rebuild preview
        }

        // Drag and drop functionality
        const imageUpload = document.querySelector('.image-upload');

        imageUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        imageUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        imageUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = Array.from(e.dataTransfer.files);
            files.forEach(file => {
                if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024 && selectedFiles.length < 10) {
                    selectedFiles.push(file);
                }
            });

            previewImages();
        });

        // Form submission
        document.getElementById('deviceDropForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = document.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading Images...';
            submitBtn.disabled = true;

            try {
                // Upload images first
                const imageUrls = [];

                if (selectedFiles.length > 0) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading Images...';

                    for (let i = 0; i < selectedFiles.length; i++) {
                        const file = selectedFiles[i];
                        const imageFormData = new FormData();
                        imageFormData.append('image', file);

                        const uploadResponse = await fetch('http://169.239.251.102:442/~chelsea.somuah/upload.php', {
                            method: 'POST',
                            body: imageFormData
                        });

                        const result = await uploadResponse.json();

                        if (result.success) {
                            imageUrls.push({
                                url: result.url,
                                filename: file.name
                            });
                        } else {
                            console.error('Image upload failed:', result.message);
                        }
                    }
                }

                // Now submit the form data
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Request...';

                const formData = new FormData(this);

                // Add image URLs to form data
                imageUrls.forEach((image, index) => {
                    formData.append(`image_urls[${index}]`, image.url);
                    formData.append(`image_filenames[${index}]`, image.filename);
                });

                // Submit to your backend (you'll need to create this endpoint)
                const response = await fetch('actions/submit_device_drop.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    alert('Thank you! Your device drop request has been submitted. We will review your submission and get back to you within 3-7 business days.');

                    // Reset form
                    this.reset();
                    selectedFiles = [];
                    document.getElementById('imagePreview').innerHTML = '';
                    document.querySelectorAll('.condition-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                } else {
                    throw new Error('Submission failed');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('There was an error submitting your request. Please try again or contact us directly.');
            } finally {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = `(${value.slice(0,3)}) ${value.slice(3,6)}-${value.slice(6,10)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0,3)}) ${value.slice(3)}`;
            }
            e.target.value = value;
        });
    </script>
</body>

</html>