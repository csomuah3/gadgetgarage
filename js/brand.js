$(document).ready(function() {
    loadBrands();
    loadCategoriesAsCheckboxes();

    // Add brand form submission
    $('#addBrandForm').submit(function(e) {
        e.preventDefault();

        var brandName = $('#brand_name').val().trim();
        var categoryIds = getSelectedCategories('category_checkboxes');

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

        if (categoryIds.length === 0) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select at least one category!',
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
                category_ids: categoryIds
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
                    clearCategoryCheckboxes('category_checkboxes');
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
        var categoryIds = getSelectedCategories('edit_category_checkboxes');

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

        if (categoryIds.length === 0) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select at least one category!',
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
                category_ids: categoryIds
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

// Get selected category IDs from checkboxes
function getSelectedCategories(containerId) {
    var categoryIds = [];
    $('#' + containerId + ' input[type="checkbox"]:checked').each(function() {
        categoryIds.push(parseInt($(this).val()));
    });
    return categoryIds;
}

// Clear all category checkboxes
function clearCategoryCheckboxes(containerId) {
    $('#' + containerId + ' input[type="checkbox"]').prop('checked', false);
}

// Set category checkboxes based on array of IDs
function setCategoryCheckboxes(containerId, categoryIds) {
    clearCategoryCheckboxes(containerId);
    if (categoryIds && categoryIds.length > 0) {
        categoryIds.forEach(function(id) {
            $('#' + containerId + ' input[value="' + id + '"]').prop('checked', true);
        });
    }
}

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

// Display brands with their categories
function displayBrands(brands) {
    var tbody = $('#brandTable tbody');
    tbody.empty();

    if (brands.length === 0) {
        tbody.append('<tr><td colspan="3" class="text-center">No brands found</td></tr>');
        return;
    }

    brands.forEach(function(brand) {
        var categoriesDisplay = brand.categories || 'No categories';
        var categoryIds = brand.category_ids ? brand.category_ids.split(',').map(id => parseInt(id.trim())) : [];

        var row = '<tr>' +
            '<td>' + categoriesDisplay + '</td>' +
            '<td>' + brand.brand_name + '</td>' +
            '<td>' +
                '<button class="btn btn-edit btn-sm me-2" onclick="editBrand(' + brand.brand_id + ', \'' + brand.brand_name.replace(/'/g, "\\'") + '\', [' + categoryIds.join(',') + '])">Edit</button>' +
                '<button class="btn btn-delete btn-sm" onclick="deleteBrand(' + brand.brand_id + ', \'' + brand.brand_name.replace(/'/g, "\\'") + '\')">Delete</button>' +
            '</td>' +
            '</tr>';
        tbody.append(row);
    });
}

// Load categories as checkboxes
function loadCategoriesAsCheckboxes() {
    $.ajax({
        url: '../actions/fetch_category_action.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Categories response:', response); // Debug log

            // Handle both response formats
            if (response.status === 'success' || response.success === true) {
                populateCategoryCheckboxes(response.data);
            } else {
                console.error('Failed to load categories:', response.message);
                // Try to show a user-friendly message
                $('#category_checkboxes').html('<p class="text-danger">Could not load categories: ' + (response.message || 'Unknown error') + '</p>');
                $('#edit_category_checkboxes').html('<p class="text-danger">Could not load categories: ' + (response.message || 'Unknown error') + '</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading categories:', error);
            $('#category_checkboxes').html('<p class="text-danger">Failed to load categories.</p>');
            $('#edit_category_checkboxes').html('<p class="text-danger">Failed to load categories.</p>');
        }
    });
}

// Populate category checkboxes
function populateCategoryCheckboxes(categories) {
    var addCheckboxes = $('#category_checkboxes');
    var editCheckboxes = $('#edit_category_checkboxes');

    addCheckboxes.empty();
    editCheckboxes.empty();

    if (categories && categories.length > 0) {
        categories.forEach(function(category) {
            // Handle different data structures
            var catId = category.cat_id || category.category_id || category.id;
            var catName = category.cat_name || category.category_name || category.name;

            if (catId && catName) {
                var checkboxHtml =
                    '<div class="form-check mb-2">' +
                        '<input class="form-check-input" type="checkbox" value="' + catId + '" id="cat_' + catId + '">' +
                        '<label class="form-check-label" for="cat_' + catId + '">' +
                            catName +
                        '</label>' +
                    '</div>';

                var editCheckboxHtml =
                    '<div class="form-check mb-2">' +
                        '<input class="form-check-input" type="checkbox" value="' + catId + '" id="edit_cat_' + catId + '">' +
                        '<label class="form-check-label" for="edit_cat_' + catId + '">' +
                            catName +
                        '</label>' +
                    '</div>';

                addCheckboxes.append(checkboxHtml);
                editCheckboxes.append(editCheckboxHtml);
            }
        });

        console.log('Populated checkboxes with', categories.length, 'categories');
    } else {
        console.log('No categories found to populate');
        addCheckboxes.append('<p class="text-muted">No categories available</p>');
        editCheckboxes.append('<p class="text-muted">No categories available</p>');
    }
}

// Edit brand
function editBrand(brandId, brandName, categoryIds) {
    $('#edit_brand_id').val(brandId);
    $('#edit_brand_name').val(brandName);
    setCategoryCheckboxes('edit_category_checkboxes', categoryIds);
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