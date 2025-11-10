$(document).ready(function() {
    loadProducts();
    loadCategories();
    loadBrands();

    // Bulk upload form submission
    $('#bulkUploadForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData();
        var files = $('#bulk_images')[0].files;

        if (files.length === 0) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select at least one image!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        // Append files to FormData
        for (var i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        // Add image prefix if provided
        var imagePrefix = $('#image_prefix').val().trim();
        if (imagePrefix) {
            formData.append('image_prefix', imagePrefix);
        }

        // Show progress
        $('#upload-progress').show();
        var $btn = $('#bulkUploadForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Uploading...');

        // AJAX upload
        $.ajax({
            url: '../actions/bulk_upload_action.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonColor: '#8b5fbf',
                        timer: 2000,
                        timerProgressBar: true
                    });

                    // Display uploaded images
                    displayUploadedImages(response.files);
                    $('#bulkUploadForm')[0].reset();

                    if (response.warnings && response.warnings.length > 0) {
                        setTimeout(function() {
                            Swal.fire({
                                title: 'Some files had issues',
                                html: response.warnings.join('<br>'),
                                icon: 'warning',
                                confirmButtonColor: '#8b5fbf'
                            });
                        }, 2500);
                    }
                } else {
                    Swal.fire({
                        title: 'Upload Failed',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Upload Error',
                    text: 'Failed to upload images. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#8b5fbf'
                });
            },
            complete: function() {
                $('#upload-progress').hide();
                $('.progress-bar').css('width', '0%');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Profile upload form submission
    $('#profileUploadForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData();
        var file = $('#profile_image')[0].files[0];

        if (!file) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a profile picture!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        formData.append('profile_image', file);

        var $btn = $('#profileUploadForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Uploading...');

        $.ajax({
            url: '../actions/profile_upload_action.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonColor: '#8b5fbf',
                        timer: 2000,
                        timerProgressBar: true
                    });

                    // Update profile image
                    $('#current-profile').attr('src', response.full_url + '?t=' + new Date().getTime());
                    $('#profileUploadForm')[0].reset();
                } else {
                    Swal.fire({
                        title: 'Upload Failed',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Upload Error',
                    text: 'Failed to upload profile picture. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#8b5fbf'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Add product form submission
    $('#addProductForm').submit(function(e) {
        e.preventDefault();

        var productTitle = $('#product_title').val().trim();
        var productPrice = $('#product_price').val();
        var productDesc = $('#product_desc').val().trim();
        var productImage = $('#product_image')[0].files[0];
        var productKeywords = $('#product_keywords').val().trim();
        var categoryId = $('#category_id').val();
        var brandId = $('#brand_id').val();

        // Validate input
        if (productTitle === '') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Product title is required!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (productPrice === '' || productPrice <= 0) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please enter a valid price!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (categoryId === '' || categoryId === '0') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a category!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (brandId === '' || brandId === '0') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a brand!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        // Show loading state
        var $btn = $('#addProductForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Adding...');

        // Create FormData for file upload
        var formData = new FormData();
        formData.append('product_title', productTitle);
        formData.append('product_price', productPrice);
        formData.append('product_desc', productDesc);
        formData.append('product_keywords', productKeywords);
        formData.append('category_id', categoryId);
        formData.append('brand_id', brandId);

        if (productImage) {
            formData.append('product_image', productImage);
        }

        // AJAX request
        $.ajax({
            url: '../actions/add_product_action.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonColor: '#8b5fbf',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    $('#addProductForm')[0].reset();
                    loadProducts();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Connection Error',
                    text: 'Failed to connect to server. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#8b5fbf'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Update product form submission
    $('#updateProductForm').submit(function(e) {
        e.preventDefault();

        var productId = $('#edit_product_id').val();
        var productTitle = $('#edit_product_title').val().trim();
        var productPrice = $('#edit_product_price').val();
        var productDesc = $('#edit_product_desc').val().trim();
        var productImageFile = $('#edit_product_image')[0].files[0];
        var productKeywords = $('#edit_product_keywords').val().trim();
        var categoryId = $('#edit_category_id').val();
        var brandId = $('#edit_brand_id').val();

        // Validate input
        if (productTitle === '') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Product title is required!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (productPrice === '' || productPrice <= 0) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please enter a valid price!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (categoryId === '' || categoryId === '0') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a category!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        if (brandId === '' || brandId === '0') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a brand!',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
            return;
        }

        // Show loading state
        var $btn = $('#updateProductForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Updating...');

        // Create FormData for file upload
        var formData = new FormData();
        formData.append('product_id', productId);
        formData.append('product_title', productTitle);
        formData.append('product_price', productPrice);
        formData.append('product_desc', productDesc);
        formData.append('product_keywords', productKeywords);
        formData.append('category_id', categoryId);
        formData.append('brand_id', brandId);

        if (productImageFile) {
            formData.append('product_image', productImageFile);
        }

        // AJAX request
        $.ajax({
            url: '../actions/update_product_action.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonColor: '#8b5fbf',
                        timer: 2000,
                        timerProgressBar: true
                    });
                    $('#editProductModal').modal('hide');
                    loadProducts();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Connection Error',
                    text: 'Failed to connect to server. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#8b5fbf'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});

// Load products
function loadProducts() {
    $.ajax({
        url: '../actions/fetch_product_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                displayProducts(response.data);
            } else {
                console.error('Error fetching products:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Display products
function displayProducts(products) {
    var tbody = $('#productTable tbody');
    tbody.empty();

    if (products.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">No products found</td></tr>');
        return;
    }

    products.forEach(function(product) {
        // Create image element with proper data attributes
        var imageHtml = '';
        if (product.product_image && product.product_image.trim() !== '') {
            // Product has an image - create img element
            imageHtml = '<img class="product-image" width="50" height="50" style="object-fit: cover; border-radius: 8px;" data-product-id="' + product.product_id + '" data-product-title="' + product.product_title.replace(/'/g, "&apos;") + '" src="../uploads/products/' + product.product_image + '" alt="' + product.product_title + '">';
        } else {
            // No image - create placeholder
            var initials = product.product_title.substring(0, 2).toUpperCase();
            imageHtml = '<div class="product-image" style="width: 50px; height: 50px; background: #8b5fbf; color: white; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: bold;" data-product-id="' + product.product_id + '" data-product-title="' + product.product_title.replace(/'/g, "&apos;") + '">' + initials + '</div>';
        }

        var priceDisplay = 'GH₵' + parseFloat(product.product_price).toFixed(2);

        var row = '<tr>' +
            '<td>' + imageHtml + '</td>' +
            '<td><strong>' + product.product_title + '</strong></td>' +
            '<td>' + priceDisplay + '</td>' +
            '<td>' + (product.cat_name || 'N/A') + '</td>' +
            '<td>' + (product.brand_name || 'N/A') + '</td>' +
            '<td>' +
                '<button class="btn btn-edit btn-sm me-2" onclick="editProduct(' +
                    product.product_id + ', \'' +
                    product.product_title.replace(/'/g, "\\'") + '\', ' +
                    product.product_price + ', \'' +
                    (product.product_desc || '').replace(/'/g, "\\'") + '\', \'' +
                    (product.product_image || '').replace(/'/g, "\\'") + '\', \'' +
                    (product.product_keywords || '').replace(/'/g, "\\'") + '\', ' +
                    product.product_cat + ', ' +
                    product.product_brand + ')">Edit</button>' +
                '<button class="btn btn-delete btn-sm" onclick="deleteProduct(' +
                    product.product_id + ', \'' +
                    product.product_title.replace(/'/g, "\\'") + '\')">Delete</button>' +
            '</td>' +
            '</tr>';
        tbody.append(row);
    });

    // Load product images after table is populated
    loadProductImages();
}

// Load categories for dropdown
function loadCategories() {
    $.ajax({
        url: '../actions/fetch_category_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Categories response:', response);

            // Handle both response formats
            if (response.status === 'success' || response.success === true) {
                populateCategoryDropdowns(response.data);
            } else {
                console.error('Failed to load categories:', response.message);
                Swal.fire({
                    title: 'Warning',
                    text: 'Could not load categories. You may need to create categories first.',
                    icon: 'warning',
                    confirmButtonColor: '#8b5fbf'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading categories:', error);
            console.error('Response:', xhr.responseText);

            Swal.fire({
                title: 'Error',
                text: 'Failed to load categories. Please check if categories exist.',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
        }
    });
}

// Load brands for dropdown
function loadBrands() {
    $.ajax({
        url: '../actions/fetch_brand_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Brands response:', response);

            if (response.status === 'success') {
                populateBrandDropdowns(response.data);
            } else {
                console.error('Failed to load brands:', response.message);
                Swal.fire({
                    title: 'Warning',
                    text: 'Could not load brands. You may need to create brands first.',
                    icon: 'warning',
                    confirmButtonColor: '#8b5fbf'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading brands:', error);
            console.error('Response:', xhr.responseText);

            Swal.fire({
                title: 'Error',
                text: 'Failed to load brands. Please check if brands exist.',
                icon: 'error',
                confirmButtonColor: '#8b5fbf'
            });
        }
    });
}

// Populate category dropdowns
function populateCategoryDropdowns(categories) {
    var addSelect = $('#category_id');
    var editSelect = $('#edit_category_id');

    addSelect.empty().append('<option value="">Select Category</option>');
    editSelect.empty().append('<option value="">Select Category</option>');

    if (categories && categories.length > 0) {
        categories.forEach(function(category) {
            var catId = category.cat_id || category.category_id || category.id;
            var catName = category.cat_name || category.category_name || category.name;

            if (catId && catName) {
                var option = '<option value="' + catId + '">' + catName + '</option>';
                addSelect.append(option);
                editSelect.append(option);
            }
        });

        console.log('Populated category dropdowns with', categories.length, 'categories');
    } else {
        console.log('No categories found to populate');
        addSelect.append('<option disabled>No categories available</option>');
        editSelect.append('<option disabled>No categories available</option>');
    }
}

// Populate brand dropdowns
function populateBrandDropdowns(brands) {
    var addSelect = $('#brand_id');
    var editSelect = $('#edit_brand_id');

    addSelect.empty().append('<option value="">Select Brand</option>');
    editSelect.empty().append('<option value="">Select Brand</option>');

    if (brands && brands.length > 0) {
        brands.forEach(function(brand) {
            var brandId = brand.brand_id;
            var brandName = brand.brand_name;

            if (brandId && brandName) {
                var option = '<option value="' + brandId + '">' + brandName + '</option>';
                addSelect.append(option);
                editSelect.append(option);
            }
        });

        console.log('Populated brand dropdowns with', brands.length, 'brands');
    } else {
        console.log('No brands found to populate');
        addSelect.append('<option disabled>No brands available</option>');
        editSelect.append('<option disabled>No brands available</option>');
    }
}

// Edit product
function editProduct(productId, productTitle, productPrice, productDesc, productImage, productKeywords, categoryId, brandId) {
    $('#edit_product_id').val(productId);
    $('#edit_product_title').val(productTitle);
    $('#edit_product_price').val(productPrice);
    $('#edit_product_desc').val(productDesc);
    $('#edit_product_image').val(productImage);
    $('#edit_product_keywords').val(productKeywords);
    $('#edit_category_id').val(categoryId);
    $('#edit_brand_id').val(brandId);
    $('#editProductModal').modal('show');
}

// Delete product
function deleteProduct(productId, productTitle) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to delete the product "' + productTitle + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../actions/delete_product_action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    product_id: productId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#8b5fbf',
                            timer: 2000,
                            timerProgressBar: true
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonColor: '#8b5fbf'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Connection Error',
                        text: 'Failed to connect to server. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#8b5fbf'
                    });
                }
            });
        }
    });
}

// Display uploaded images as a list
function displayUploadedImages(files) {
    var container = $('#uploaded-images');
    container.empty();

    if (files && files.length > 0) {
        var listHtml = '<div class="uploaded-files-list"><h6 class="mb-3">Uploaded Images (' + files.length + '):</h6>';

        files.forEach(function(file) {
            listHtml +=
                '<div class="uploaded-file-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">' +
                    '<div class="file-info">' +
                        '<i class="fas fa-image text-success me-2"></i>' +
                        '<strong>' + file.file_name + '</strong>' +
                        '<br><small class="text-muted">Original: ' + file.original_name + '</small>' +
                    '</div>' +
                    '<div class="file-actions">' +
                        '<button class="btn btn-sm btn-outline-primary" onclick="copyImageUrl(\'' + file.full_url + '\')">' +
                            '<i class="fas fa-copy me-1"></i>Copy URL' +
                        '</button>' +
                    '</div>' +
                '</div>';
        });

        listHtml += '</div>';
        container.html(listHtml);
    }
}

// Copy image URL to clipboard
function copyImageUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({
            title: 'Copied!',
            text: 'Image URL copied to clipboard',
            icon: 'success',
            confirmButtonColor: '#8b5fbf',
            timer: 1500,
            timerProgressBar: true
        });
    }).catch(function() {
        // Fallback for older browsers
        var textArea = document.createElement("textarea");
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        Swal.fire({
            title: 'Copied!',
            text: 'Image URL copied to clipboard',
            icon: 'success',
            confirmButtonColor: '#8b5fbf',
            timer: 1500,
            timerProgressBar: true
        });
    });
}

// Load product images using centralized image system
function loadProductImages() {
    // Add a small delay to ensure DOM is ready
    setTimeout(() => {
        const productImages = document.querySelectorAll('.product-image');
        console.log('Loading images for', productImages.length, 'products');

        productImages.forEach((element, index) => {
            const productId = element.getAttribute('data-product-id');
            const productTitle = element.getAttribute('data-product-title');

            console.log('Loading image for product', productId, productTitle);

            // If it's already an img element with src, skip loading
            if (element.tagName === 'IMG' && element.src && !element.src.includes('placeholder')) {
                console.log('Image already loaded for product', productId);
                return;
            }

            if (productId) {
                // Add loading indicator for div placeholders
                if (element.tagName === 'DIV') {
                    element.style.opacity = '0.5';
                }

                fetch('../actions/upload_product_image_action.php?action=get_image_url&product_id=' + productId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Image response for product', productId, data);

                        if (data.success && data.url) {
                            // Replace placeholder div with actual image
                            if (element.tagName === 'DIV') {
                                const img = document.createElement('img');
                                img.className = 'product-image';
                                img.width = 50;
                                img.height = 50;
                                img.style.objectFit = 'cover';
                                img.style.borderRadius = '8px';
                                img.setAttribute('data-product-id', productId);
                                img.setAttribute('data-product-title', productTitle);
                                img.src = data.url;
                                img.alt = productTitle;
                                element.parentNode.replaceChild(img, element);
                                console.log('✅ Image loaded and replaced placeholder for product', productId);
                            } else if (element.tagName === 'IMG') {
                                element.src = data.url;
                                console.log('✅ Image loaded for product', productId);
                            }
                        } else {
                            // Keep placeholder but restore opacity
                            if (element.tagName === 'DIV') {
                                element.style.opacity = '1';
                            }
                            console.log('No image found for product', productId, '- keeping placeholder');
                        }
                    })
                    .catch(error => {
                        console.error('Image load error for product', productId, error);
                        // Keep placeholder but restore opacity
                        if (element.tagName === 'DIV') {
                            element.style.opacity = '1';
                        }
                    });
            }
        });
    }, 100);
}