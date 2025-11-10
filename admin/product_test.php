<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../settings/core.php';
require_admin(); // only admins
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Product Test Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Product Management Test</h1>
        <p>This is a test page to ensure product management works.</p>

        <div class="row">
            <div class="col-md-6">
                <h3>User Info</h3>
                <p>User ID: <?= get_user_id() ?></p>
                <p>User Name: <?= get_user_name() ?></p>
                <p>Is Admin: <?= check_admin() ? 'Yes' : 'No' ?></p>
            </div>
            <div class="col-md-6">
                <h3>Navigation</h3>
                <a href="category.php" class="btn btn-primary me-2">Categories</a>
                <a href="brand.php" class="btn btn-primary me-2">Brands</a>
                <a href="product.php" class="btn btn-success">Full Product Page</a>
            </div>
        </div>

        <div class="mt-4">
            <h3>Test Upload</h3>
            <form enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="test_image" class="form-label">Test Image Upload</label>
                    <input type="file" class="form-control" id="test_image" accept="image/*">
                </div>
                <button type="button" class="btn btn-primary" onclick="testUpload()">Test Upload</button>
            </form>
            <div id="upload-result" class="mt-3"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function testUpload() {
            var file = $('#test_image')[0].files[0];
            if (!file) {
                alert('Please select a file');
                return;
            }

            var formData = new FormData();
            formData.append('profile_image', file);

            $.ajax({
                url: '../actions/profile_upload_action.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#upload-result').html('<div class="alert alert-success">Upload successful: ' + JSON.stringify(response) + '</div>');
                },
                error: function(xhr) {
                    $('#upload-result').html('<div class="alert alert-danger">Upload failed: ' + xhr.responseText + '</div>');
                }
            });
        }
    </script>
</body>
</html>