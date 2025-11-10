$(document).ready(function() {
    loadBrands();
    loadCategories();

    // Add brand form submission
    $('#addBrandForm').submit(function(e) {
        e.preventDefault();

        var brandName = $('#brand_name').val().trim();
        var categoryId = $('#category_id').val();

        // Validate input
        if (brandName === '') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Brand name is required!',
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

        // Show loading state
        var $btn = $('#addBrandForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Adding...');

        // AJAX request
        $.ajax({
            url: '../actions/add_brand_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                brand_name: brandName,
                category_id: categoryId
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
                    $('#addBrandForm')[0].reset();
                    loadBrands();
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

    // Update brand form submission
    $('#updateBrandForm').submit(function(e) {
        e.preventDefault();

        var brandId = $('#edit_brand_id').val();
        var brandName = $('#edit_brand_name').val().trim();
        var categoryId = $('#edit_category_id').val();

        // Validate input
        if (brandName === '') {
            Swal.fire({
                title: 'Validation Error',
                text: 'Brand name is required!',
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

        // Show loading state
        var $btn = $('#updateBrandForm button[type="submit"]');
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Updating...');

        // AJAX request
        $.ajax({
            url: '../actions/update_brand_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                brand_id: brandId,
                brand_name: brandName,
                category_id: categoryId
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
                    $('#editBrandModal').modal('hide');
                    loadBrands();
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

// Load brands
function loadBrands() {
    $.ajax({
        url: '../actions/fetch_brand_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                displayBrands(response.data);
            } else {
                console.error('Error fetching brands:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Display brands organized by category
function displayBrands(brands) {
    var tbody = $('#brandTable tbody');
    tbody.empty();

    if (brands.length === 0) {
        tbody.append('<tr><td colspan="3" class="text-center">No brands found</td></tr>');
        return;
    }

    // Group brands by category
    var groupedBrands = {};
    brands.forEach(function(brand) {
        if (!groupedBrands[brand.cat_name]) {
            groupedBrands[brand.cat_name] = [];
        }
        groupedBrands[brand.cat_name].push(brand);
    });

    // Display grouped brands
    Object.keys(groupedBrands).forEach(function(categoryName) {
        var categoryBrands = groupedBrands[categoryName];

        categoryBrands.forEach(function(brand, index) {
            var row = '<tr>' +
                '<td>' + (index === 0 ? '<strong>' + categoryName + '</strong>' : '') + '</td>' +
                '<td>' + brand.brand_name + '</td>' +
                '<td>' +
                    '<button class="btn btn-edit btn-sm me-2" onclick="editBrand(' + brand.brand_id + ', \'' + brand.brand_name + '\', ' + brand.category_id + ')">Edit</button>' +
                    '<button class="btn btn-delete btn-sm" onclick="deleteBrand(' + brand.brand_id + ', \'' + brand.brand_name + '\')">Delete</button>' +
                '</td>' +
                '</tr>';
            tbody.append(row);
        });
    });
}

// Load categories for dropdown
function loadCategories() {
    $.ajax({
        url: '../actions/fetch_category_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Categories response:', response); // Debug log

            // Handle both response formats
            if (response.status === 'success' || response.success === true) {
                populateCategoryDropdowns(response.data);
            } else {
                console.error('Failed to load categories:', response.message);
                // Try to show a user-friendly message
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

            // Show user-friendly error
            Swal.fire({
                title: 'Error',
                text: 'Failed to load categories. Please check if categories exist.',
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
            // Handle different data structures
            var catId = category.cat_id || category.category_id || category.id;
            var catName = category.cat_name || category.category_name || category.name;

            if (catId && catName) {
                var option = '<option value="' + catId + '">' + catName + '</option>';
                addSelect.append(option);
                editSelect.append(option);
            }
        });

        console.log('Populated dropdowns with', categories.length, 'categories');
    } else {
        console.log('No categories found to populate');
        // Add a message to dropdowns
        addSelect.append('<option disabled>No categories available</option>');
        editSelect.append('<option disabled>No categories available</option>');
    }
}

// Edit brand
function editBrand(brandId, brandName, categoryId) {
    $('#edit_brand_id').val(brandId);
    $('#edit_brand_name').val(brandName);
    $('#edit_category_id').val(categoryId);
    $('#editBrandModal').modal('show');
}

// Delete brand
function deleteBrand(brandId, brandName) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to delete the brand "' + brandName + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../actions/delete_brand_action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    brand_id: brandId
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
                        loadBrands();
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