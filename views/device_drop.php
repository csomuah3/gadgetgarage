<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../settings/core.php');
require_once(__DIR__ . '/../controllers/cart_controller.php');

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

// Try to load categories and brands for navigation
$categories = [];
$brands = [];

try {
    require_once(__DIR__ . '/../controllers/category_controller.php');
    $categories = get_all_categories_ctr();
} catch (Exception $e) {
    error_log("Failed to load categories: " . $e->getMessage());
}

try {
    require_once(__DIR__ . '/../controllers/brand_controller.php');
    $brands = get_all_brands_ctr();
} catch (Exception $e) {
    error_log("Failed to load brands: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Device Drop - Gadget Garage</title>
    <link rel="icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link rel="shortcut icon" type="image/png" href="http://169.239.251.102:442/~chelsea.somuah/uploads/Screenshot2025-11-17at10.07.19AM.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../includes/header.css" rel="stylesheet">
    <link href="../includes/chatbot-styles.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #ffffff;
            color: #1a1a1a;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('http://169.239.251.102:442/~chelsea.somuah/uploads/ChatGPTImageNov19202511_50_42PM.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.45;
            z-index: -1;
            pointer-events: none;
        }

        .device-drop-container {
            padding: 60px 0;
            min-height: 80vh;
        }

        .page-title {
            font-size: 2.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1.15rem;
            text-align: center;
            margin-bottom: 50px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        /* Progress Indicator */
        .form-progress {
            max-width: 800px;
            margin: 0 auto 50px;
            background: white;
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }

        .progress-step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .progress-step:hover .step-number {
            transform: scale(1.1);
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .progress-step.active .step-number {
            background: #2563EB;
            border-color: #2563EB;
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .step-label {
            font-size: 0.85rem;
            color: #9ca3af;
            font-weight: 500;
            text-align: center;
        }

        .progress-step.active .step-label {
            color: #2563EB;
            font-weight: 600;
        }

        .form-container {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.15);
            max-width: 900px;
            margin: 0 auto;
            border: 2px solid #bfdbfe;
        }

        .form-section {
            margin-bottom: 45px;
            padding: 35px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 16px;
            border: 2px solid #bfdbfe;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563EB, #1e40af);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 16px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .section-title {
            font-size: 1.7rem;
            font-weight: 800;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 10px;
            font-size: 1.1rem;
            letter-spacing: 0.2px;
        }

        .form-label.required::after {
            content: ' *';
            color: #ef4444;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #93c5fd;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
            color: #1f2937;
            font-family: inherit;
        }

        .form-input:hover,
        .form-select:hover,
        .form-textarea:hover {
            border-color: #d1d5db;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #2563EB;
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #9ca3af;
            font-size: 1.2rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .condition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .condition-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .condition-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: transparent;
            transition: all 0.3s ease;
        }

        .condition-option:hover {
            border-color: #2563EB;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .condition-option:hover::before {
            background: #2563EB;
        }

        .condition-option.selected {
            border-color: #2563EB;
            background: #dbeafe;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.2);
        }

        .condition-option.selected::before {
            background: #2563EB;
        }

        .condition-option input[type="radio"] {
            display: none;
        }

        .condition-title {
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 8px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .condition-title::before {
            content: '✓';
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .condition-option.selected .condition-title::before {
            background: #2563EB;
            color: white;
        }

        .condition-description {
            font-size: 1.05rem;
            font-weight: 500;
            color: #374151;
            line-height: 1.6;
            margin-top: 8px;
        }

        .image-upload {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .image-upload::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.05), transparent);
            transition: left 0.5s ease;
        }

        .image-upload:hover::before {
            left: 100%;
        }

        .image-upload:hover {
            border-color: #2563EB;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .image-upload.dragover {
            border-color: #2563EB;
            background: #dbeafe;
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.2);
        }

        .upload-icon {
            font-size: 3.5rem;
            color: #9ca3af;
            margin-bottom: 18px;
            transition: all 0.3s ease;
        }

        .image-upload:hover .upload-icon {
            color: #2563EB;
            transform: scale(1.1);
        }

        .upload-text {
            color: #1e3a8a;
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .upload-subtext {
            font-size: 1.05rem;
            font-weight: 500;
            color: #374151;
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
            border-radius: 8px;
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
            background: linear-gradient(135deg, #2563EB, #1e40af);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.15rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #1e40af, #2563EB);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .process-info {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-left: 5px solid #3b82f6;
            padding: 28px;
            border-radius: 12px;
            margin-top: 35px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .process-info h4 {
            color: #1e40af;
            margin-bottom: 14px;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .process-info h4 i {
            font-size: 1.3rem;
        }

        .process-info p {
            color: #1e3a8a;
            margin: 0;
            line-height: 1.8;
            font-size: 1rem;
        }

        /* Checkbox Styles */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 14px;
            margin-top: 10px;
        }

        .checkbox-option {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            border: 2px solid #93c5fd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e3a8a;
        }

        .checkbox-option:hover {
            border-color: #2563EB;
            background: #eff6ff;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .checkbox-option input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
            background: white;
            flex-shrink: 0;
        }

        .checkbox-option input[type="checkbox"]:checked+.checkmark {
            background: #2563EB;
            border-color: #2563EB;
        }

        .checkbox-option input[type="checkbox"]:checked+.checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .checkbox-option input[type="checkbox"]:checked~span:not(.checkmark) {
            color: #2563EB;
            font-weight: 600;
        }

        .checkbox-option input[type="checkbox"]:checked~.checkmark {
            background: #2563EB;
            border-color: #2563EB;
        }

        .checkbox-option input[type="checkbox"]:checked {
            background: #dbeafe;
            border-color: #2563EB;
        }

        /* Success Modal Styles */
        .success-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .success-modal-overlay.show {
            display: flex;
        }

        .success-modal {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 20px;
        }

        .success-message {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .success-button {
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .success-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .device-drop-container {
                padding: 40px 0;
            }

            .page-title {
                font-size: 2rem;
            }

            .page-subtitle {
                font-size: 1rem;
                margin-bottom: 30px;
            }

            .form-progress {
                padding: 20px;
                margin-bottom: 30px;
            }

            .step-label {
                font-size: 0.75rem;
            }

            .step-number {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .form-container {
                padding: 25px 20px;
                border-radius: 16px;
            }

            .form-section {
                padding: 25px 20px;
                margin-bottom: 30px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .section-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 1.3rem;
            }

            .condition-grid,
            .checkbox-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .success-modal {
                padding: 30px 20px;
            }

            .success-title {
                font-size: 1.5rem;
            }

            .success-message {
                font-size: 1rem;
            }
        }

        /* Footer Styles */
        .main-footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0 20px;
            margin-top: 0;
        }

        .footer-brand {
            margin-bottom: 30px;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .footer-logo img {
            height: 50px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .footer-logo .garage {
            background: linear-gradient(135deg, #1E3A5F, #2563EB);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
        }

        .footer-description {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: #2563EB;
            color: white;
            transform: translateY(-2px);
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links li a {
            color: #6b7280;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .footer-links li a:hover {
            color: #2563EB;
            transform: translateX(4px);
        }

        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 40px 0 20px;
        }

        .footer-bottom {
            padding-top: 20px;
        }

        .copyright {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
        }

        /* Newsletter Signup Section */
        .newsletter-signup-section {
            background: transparent;
            padding: 0;
            text-align: left;
            max-width: 100%;
            height: fit-content;
        }

        .newsletter-title {
            color: #1f2937;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .newsletter-form {
            display: flex;
            width: 100%;
            margin: 0 0 15px 0;
            gap: 0;
            border-radius: 50px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .newsletter-input {
            flex: 1;
            padding: 14px 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a1a1a;
            background: #e5e7eb;
        }

        .newsletter-input::placeholder {
            color: #6b7280;
        }

        .newsletter-submit-btn {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border: none;
            background: #9ca3af;
            color: #ffffff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            padding: 0;
        }

        .newsletter-submit-btn:hover {
            background: #6b7280;
            transform: scale(1.05);
        }

        .newsletter-disclaimer {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 8px 0 0 0;
            text-align: left;
        }

        .newsletter-disclaimer a {
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .newsletter-disclaimer a:hover {
            color: #1d4ed8;
        }

        @media (max-width: 991px) {
            .newsletter-signup-section {
                margin-top: 20px;
            }
        }

        /* AI Valuation Result Styles */
        .valuation-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            animation: slideUp 0.5s ease-out;
        }

        .valuation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .valuation-header h3 {
            color: #1e40af;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .condition-badge {
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .value-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .value-option {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .value-option:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }

        .value-option.selected {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
        }

        .value-option.recommended::before {
            content: "RECOMMENDED";
            position: absolute;
            top: -8px;
            right: 15px;
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .value-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .value-header h4 {
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bonus-badge {
            background: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .value-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }

        .value-description {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .valuation-details {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            margin-bottom: 1rem;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item strong {
            color: #1e40af;
            font-size: 0.95rem;
        }

        .detail-item p {
            margin: 0.5rem 0 0 0;
            color: #4b5563;
            line-height: 1.5;
        }

        .valuation-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .accept-valuation-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .accept-valuation-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .decline-valuation-btn {
            background: #f3f4f6;
            color: #6b7280;
            border: 2px solid #e5e7eb;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .decline-valuation-btn:hover {
            background: #e5e7eb;
            color: #4b5563;
            transform: translateY(-2px);
        }

        .ai-valuation-btn {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .ai-valuation-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .ai-valuation-btn:hover::before {
            left: 100%;
        }

        .ai-valuation-btn:hover {
            background: linear-gradient(135deg, #6d28d9, #4c1d95);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.4);
        }

        .ai-valuation-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .ai-valuation-btn .fas {
            font-size: 1.2rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .payment-preference-options {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .payment-option {
            flex: 1;
            position: relative;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-label {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            gap: 1rem;
        }

        .payment-label:hover {
            border-color: #3B82F6;
            background: #f8faff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .payment-option input[type="radio"]:checked + .payment-label {
            border-color: #3B82F6;
            background: linear-gradient(135deg, #3B82F6, #1e40af);
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .payment-option input[type="radio"]:checked + .payment-label .payment-desc {
            color: #e0e9ff;
        }

        .payment-icon {
            font-size: 2rem;
            min-width: 3rem;
            text-align: center;
        }

        .payment-details {
            display: flex;
            flex-direction: column;
        }

        .payment-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .payment-desc {
            font-size: 0.9rem;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        @media (max-width: 768px) {
            .payment-preference-options {
                flex-direction: column;
            }
        }

        .payment-method-indicator {
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 1rem;
        }

        .valuation-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .valuation-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .submit-btn.valuation-accepted {
            background: linear-gradient(135deg, #10b981, #059669);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }
        }

        @media (max-width: 768px) {
            .value-options {
                grid-template-columns: 1fr;
            }

            .valuation-actions {
                flex-direction: column;
            }

            .value-amount {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <!-- Device Drop Content -->
    <div class="device-drop-container">
        <div class="container">
            <h1 class="page-title">Device Drop Request</h1>
            <p class="page-subtitle">
                Submit your device information for evaluation. We'll review your submission and get back to you within 3-7 business days to schedule a pickup appointment.
            </p>

            <!-- Progress Indicator -->
            <div class="form-progress">
                <div class="progress-steps">
                    <div class="progress-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Device Info</div>
                        </div>
                    <div class="progress-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Condition</div>
                    </div>
                    <div class="progress-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Details</div>
                    </div>
                    <div class="progress-step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">Photos</div>
                    </div>
                    <div class="progress-step" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-label">Contact</div>
                    </div>
                    </div>
                </div>

            <div class="form-container">
                <form id="deviceDropForm" enctype="multipart/form-data" method="POST">
                    <!-- Device Information Section -->
                    <div class="form-section" data-section="1">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3 class="section-title">Device Information</h3>
                        </div>

                        <div class="form-group">
                            <label for="deviceType" class="form-label required">Device Type</label>
                            <select id="deviceType" name="device_type" class="form-select" required>
                                <option value="">Select Device Type</option>
                                <option value="smartphone">Smartphone</option>
                                <option value="tablet">Tablet / iPad</option>
                                <option value="laptop">Laptop</option>
                                <option value="desktop">Desktop Computer</option>
                                <option value="camera">Camera</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deviceBrand" class="form-label required">Brand</label>
                            <input type="text" id="deviceBrand" name="device_brand" class="form-input" placeholder="e.g., Apple, Samsung, Dell, Sony" required>
                            </div>

                        <div class="form-group">
                            <label for="deviceModel" class="form-label required">Model</label>
                            <input type="text" id="deviceModel" name="device_model" class="form-input" placeholder="e.g., iPhone 12 Pro, Galaxy S21, MacBook Pro 2020" required>
                                    </div>
                                </div>

                    <!-- Condition Section -->
                    <div class="form-section" data-section="2">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-clipboard-check"></i>
                                        </div>
                            <h3 class="section-title">Device Condition</h3>
                                    </div>
                        <label class="form-label required">Select the condition that best describes your device</label>

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
                    <div class="form-section" data-section="3">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h3 class="section-title">Additional Details</h3>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Why are you giving up this device? (Select all that apply)</label>
                            <div class="checkbox-grid">
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="upgraded_to_newer_model">
                                    <span class="checkmark"></span>
                                    Upgraded to newer model
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="screen_damaged">
                                    <span class="checkmark"></span>
                                    Screen is cracked/damaged
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="battery_issues">
                                    <span class="checkmark"></span>
                                    Battery issues/poor life
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="performance_issues">
                                    <span class="checkmark"></span>
                                    Slow performance/lagging
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="no_longer_needed">
                                    <span class="checkmark"></span>
                                    No longer needed/used
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="hardware_failure">
                                    <span class="checkmark"></span>
                                    Hardware malfunction/failure
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="too_old">
                                    <span class="checkmark"></span>
                                    Device is too old/outdated
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="switching_platforms">
                                    <span class="checkmark"></span>
                                    Switching platforms (iOS to Android, etc.)
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="reasons[]" value="need_cash">
                                    <span class="checkmark"></span>
                                    Need cash/emergency sale
                                </label>
                                    </div>
                                </div>

                        <div class="form-group">
                            <label for="deviceDescription" class="form-label">Additional Details & Issues</label>
                            <textarea id="deviceDescription" name="description" class="form-textarea" rows="4"
                                placeholder="Please provide details about: **Battery health, years of usage, if it has been repaired before**, and any issues, damage, or special features of your device..."></textarea>
                        </div>

                        <!-- Payment Preference Selection -->
                        <div class="form-group">
                            <label class="form-label">Preferred Payment Method</label>
                            <div class="payment-preference-options">
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" value="cash" id="cash_option" checked>
                                    <label for="cash_option" class="payment-label">
                                        <div class="payment-icon">💰</div>
                                        <div class="payment-details">
                                            <span class="payment-title">Cash Payment</span>
                                            <span class="payment-desc">Receive money directly</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" value="store_credit" id="store_credit_option">
                                    <label for="store_credit_option" class="payment-label">
                                        <div class="payment-icon">🎁</div>
                                        <div class="payment-details">
                                            <span class="payment-title">Store Credit</span>
                                            <span class="payment-desc">Get 10% bonus for future purchases</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- AI Valuation Button -->
                        <div class="form-group" style="text-align: center; margin-top: 2rem;">
                            <button type="button" id="getAIValuationBtn" class="ai-valuation-btn">
                                <i class="fas fa-robot me-2"></i>
                                Get Instant AI Valuation
                            </button>
                            <p style="color: #6b7280; font-size: 0.9rem; margin-top: 0.75rem;">
                                <i class="fas fa-info-circle"></i> Our AI will analyze your device and provide an instant quote
                            </p>
                        </div>

                        <!-- AI Valuation Result (Hidden initially) -->
                        <div id="aiValuationResult" style="display: none; margin-top: 2rem;"></div>
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-section" data-section="4">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h3 class="section-title">Device Photos</h3>
                        </div>

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
                    <div class="form-section" data-section="5">
                        <div class="section-header">
                            <div class="section-icon">
                            <i class="fas fa-user"></i>
                </div>
                            <h3 class="section-title">Contact Information</h3>
            </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="firstName" class="form-label required">First Name</label>
                                <input type="text" id="firstName" name="first_name" class="form-input" 
                                    value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['customer_name'] ?? '') : ''; ?>" required>
        </div>

                            <div class="form-group">
                                <label for="lastName" class="form-label required">Last Name</label>
                                <input type="text" id="lastName" name="last_name" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label required">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                value="<?php echo $is_logged_in ? htmlspecialchars($_SESSION['customer_contact'] ?? '') : ''; ?>" 
                                placeholder="(xxx) xxx-xxxx" required>
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

    <!-- Success Modal -->
    <div class="success-modal-overlay" id="successModal">
        <div class="success-modal">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="success-title">Request Submitted!</h2>
            <p class="success-message">
                Your device drop request has been submitted successfully. We will review your submission and get back to you within 3-7 business days to schedule a pickup appointment.
            </p>
            <button class="success-button" onclick="returnToHome()">
                Return to Home
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="../js/dark-mode.js"></script>
    <script>
        // Progress indicator functionality
        function updateProgress() {
            const sections = document.querySelectorAll('.form-section');
            const steps = document.querySelectorAll('.progress-step');
            
            sections.forEach((section, index) => {
                const sectionNumber = parseInt(section.getAttribute('data-section'));
                const inputs = section.querySelectorAll('input, select, textarea');
                let hasValue = false;
                
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) hasValue = true;
                    } else if (input.value.trim() !== '') {
                        hasValue = true;
                    }
                });
                
                if (hasValue && sectionNumber <= steps.length) {
                    steps[sectionNumber - 1].classList.add('active');
                }
            });
        }

        // Update progress on input
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('deviceDropForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('input', updateProgress);
                input.addEventListener('change', updateProgress);
            });
            
            // Initial progress update
            updateProgress();
            
            // Scroll to section on progress step click
            document.querySelectorAll('.progress-step').forEach(step => {
                step.addEventListener('click', function() {
                    const stepNumber = this.getAttribute('data-step');
                    const section = document.querySelector(`.form-section[data-section="${stepNumber}"]`);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });

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
            
            // Update progress
            updateProgress();
        }

        // Image preview functionality
        let selectedFiles = [];

        function previewImages() {
            const input = document.getElementById('images');
            const previewContainer = document.getElementById('imagePreview');

            // Add new files to selectedFiles array
            Array.from(input.files).forEach(file => {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'File Too Large',
                            text: `File ${file.name} is too large. Maximum size is 5MB.`,
                            icon: 'warning',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    }
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Request...';
            submitBtn.disabled = true;

            try {
                // Create form data with all fields including images
                const formData = new FormData(this);

                // Add selected images to form data
                selectedFiles.forEach((file, index) => {
                    formData.append(`images[${index}]`, file);
                });

                // Submit to backend
                const response = await fetch('../actions/submit_device_drop.php', {
                    method: 'POST',
                    body: formData
                });

                // Check if response is OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error:', response.status, errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                }

                const result = await response.json();

                if (result.success) {
                    // Show success modal
                    showSuccessModal();

                    // Reset form
                    this.reset();
                    selectedFiles = [];
                    document.getElementById('imagePreview').innerHTML = '';
                    document.querySelectorAll('.condition-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                } else {
                    console.error('Server error:', result);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error: ' + (result.message || 'There was an error submitting your request.'),
                            icon: 'error',
                            confirmButtonColor: '#D19C97',
                            confirmButtonText: 'OK'
                        });
                    }
                }

            } catch (error) {
                console.error('Network error:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                });
                
                let errorMessage = 'There was a network error submitting your request.';
                if (error.message) {
                    errorMessage += '\n\nError: ' + error.message;
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#D19C97',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error: ' + errorMessage);
                }
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

        // Success modal functions
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('show');
        }

        function hideSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('show');
        }

        function returnToHome() {
            window.location.href = '../index.php';
        }

        // Close modal when clicking outside
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideSuccessModal();
            }
        });

        // AI Valuation functionality
        let currentValuation = null;

        // Add event listeners to payment method radio buttons
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // If there's a current valuation, update the display
                if (currentValuation) {
                    const selectedPaymentMethod = this.value;
                    currentValuation.selectedPaymentMethod = selectedPaymentMethod;
                    displayValuationResult({ valuation: currentValuation }, selectedPaymentMethod);
                }
            });
        });

        document.getElementById('getAIValuationBtn').addEventListener('click', async function() {
            // Get form data
            const deviceType = document.getElementById('deviceType').value;
            const brand = document.getElementById('deviceBrand').value;
            const model = document.getElementById('deviceModel').value;
            const condition = document.querySelector('input[name="condition"]:checked')?.value;
            const description = document.getElementById('deviceDescription').value;
            const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

            // Validate required fields
            if (!deviceType || !brand || !condition) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in device type, brand, and condition before getting valuation.',
                    confirmButtonColor: '#3B82F6'
                });
                return;
            }

            // Validate payment method selection
            if (!selectedPaymentMethod) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Payment Method Required',
                    text: 'Please select your preferred payment method.',
                    confirmButtonColor: '#3B82F6'
                });
                return;
            }

            // Show loading state
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting AI Valuation...';
            btn.disabled = true;

            try {
                const response = await fetch('../actions/device_valuation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        device_type: deviceType,
                        brand: brand,
                        model: model,
                        condition: condition,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    currentValuation = result.valuation;
                    currentValuation.selectedPaymentMethod = selectedPaymentMethod;
                    displayValuationResult(result, selectedPaymentMethod);
                } else {
                    throw new Error(result.message || 'Failed to get valuation');
                }

            } catch (error) {
                console.error('Valuation error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Valuation Failed',
                    text: error.message || 'Unable to get device valuation. Please try again.',
                    confirmButtonColor: '#3B82F6'
                });
            } finally {
                // Restore button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        function displayValuationResult(result, selectedPaymentMethod) {
            const resultContainer = document.getElementById('aiValuationResult');
            const valuation = result.valuation;

            // Generate content based on selected payment method
            let paymentOptionHtml = '';
            let selectedValue = '';
            let selectedDescription = '';

            if (selectedPaymentMethod === 'store_credit') {
                selectedValue = valuation.credit_value.toFixed(2);
                selectedDescription = `+GH₵ ${valuation.bonus_amount.toFixed(2)} bonus value (10% extra)`;
                paymentOptionHtml = `
                    <div class="value-option selected recommended" data-payment="store_credit">
                        <div class="value-header">
                            <h4><i class="fas fa-gift"></i> Store Credit</h4>
                            <span class="bonus-badge">+10% BONUS</span>
                        </div>
                        <div class="value-amount">GH₵ ${selectedValue}</div>
                        <div class="value-description">${selectedDescription}</div>
                    </div>`;
            } else {
                selectedValue = valuation.cash_value.toFixed(2);
                selectedDescription = 'Immediate cash payment';
                paymentOptionHtml = `
                    <div class="value-option selected" data-payment="cash">
                        <div class="value-header">
                            <h4><i class="fas fa-money-bill-wave"></i> Cash Payment</h4>
                        </div>
                        <div class="value-amount">GH₵ ${selectedValue}</div>
                        <div class="value-description">${selectedDescription}</div>
                    </div>`;
            }

            const html = `
                <div class="valuation-card">
                    <div class="valuation-header">
                        <h3><i class="fas fa-robot"></i> AI Device Valuation</h3>
                        <div class="condition-badge">${valuation.condition_grade}</div>
                        <div class="payment-method-indicator">
                            <span>Payment: ${selectedPaymentMethod === 'store_credit' ? 'Store Credit' : 'Cash'}</span>
                        </div>
                    </div>

                    <div class="value-options">
                        ${paymentOptionHtml}
                    </div>

                    <div class="valuation-details">
                        <div class="detail-item">
                            <strong>Value Reasoning:</strong>
                            <p>${valuation.value_reasoning}</p>
                        </div>
                        <div class="detail-item">
                            <strong>Market Comparison:</strong>
                            <p>${valuation.market_comparison}</p>
                        </div>
                        <div class="detail-item">
                            <strong>Recommendations:</strong>
                            <p>${valuation.recommendations}</p>
                        </div>
                    </div>

                    <div class="valuation-actions">
                        <button type="button" class="accept-valuation-btn" onclick="acceptValuation()">
                            <i class="fas fa-check"></i> Accept This Valuation
                        </button>
                        <button type="button" class="decline-valuation-btn" onclick="declineValuation()">
                            <i class="fas fa-times"></i> Get New Valuation
                        </button>
                    </div>
                </div>
            `;

            resultContainer.innerHTML = html;
            resultContainer.style.display = 'block';

            // Scroll to result
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function acceptValuation() {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;

            if (!selectedPayment) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    text: 'Please select either Cash or Store Credit payment option.',
                    confirmButtonColor: '#3B82F6'
                });
                return;
            }

            // Set hidden form fields
            const valuationData = {
                ai_valuation: selectedPayment === 'cash' ? currentValuation.cash_value : currentValuation.credit_value,
                payment_method: selectedPayment,
                final_amount: selectedPayment === 'cash' ? currentValuation.cash_value : currentValuation.credit_value,
                condition_grade: currentValuation.condition_grade,
                value_reasoning: currentValuation.value_reasoning
            };

            // Add hidden inputs to form
            Object.keys(valuationData).forEach(key => {
                let input = document.getElementById('hidden_' + key);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.id = 'hidden_' + key;
                    input.name = key;
                    document.getElementById('deviceDropForm').appendChild(input);
                }
                input.value = valuationData[key];
            });

            // Show success message and enable form submission
            Swal.fire({
                icon: 'success',
                title: 'Valuation Accepted!',
                text: `You've selected ${selectedPayment === 'cash' ? 'cash payment' : 'store credit'} for GH₵ ${valuationData.final_amount.toFixed(2)}. You can now submit your device drop request.`,
                confirmButtonColor: '#3B82F6'
            });

            // Update submit button to show acceptance
            const submitBtn = document.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Device Drop Request';
            submitBtn.classList.add('valuation-accepted');
        }

        function declineValuation() {
            document.getElementById('aiValuationResult').style.display = 'none';
            currentValuation = null;

            // Remove hidden inputs
            ['ai_valuation', 'payment_method', 'final_amount', 'condition_grade', 'value_reasoning'].forEach(field => {
                const input = document.getElementById('hidden_' + field);
                if (input) input.remove();
            });

            Swal.fire({
                icon: 'info',
                title: 'Valuation Declined',
                text: 'You can adjust your device details and get a new valuation.',
                confirmButtonColor: '#3B82F6'
            });
        }
    </script>

</body>
</html>