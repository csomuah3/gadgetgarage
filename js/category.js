/**
 * Category Management JavaScript
 * Works with the clean category.php HTML structure
 */

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    setupAddForm();
});

/**
 * Load categories and populate table
 */
function loadCategories() {
    fetch('../actions/fetch_category_action.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateTable(data.data);
            } else {
                showMessage('Failed to load categories: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            showMessage('Error loading categories', 'danger');
        });
}

/**
 * Populate the categories table
 */
function populateTable(categories) {
    const tbody = document.querySelector('#catTable tbody');
    tbody.innerHTML = '';

    if (!categories || categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-muted text-center">No categories yet</td></tr>';
        return;
    }

    categories.forEach(category => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <span class="category-name" data-id="${category.cat_id}">${escapeHtml(category.cat_name)}</span>
                <input type="text" class="form-control edit-input d-none" 
                       value="${escapeHtml(category.cat_name)}" data-id="${category.cat_id}">
            </td>
            <td>
                <div class="normal-actions">
                    <button class="btn btn-sm btn-success me-1" onclick="startEdit(${category.cat_id})">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.cat_id}, '${escapeHtml(category.cat_name)}')">Delete</button>
                </div>
                <div class="edit-actions d-none">
                    <button class="btn btn-sm btn-primary me-1" onclick="saveEdit(${category.cat_id})">Save</button>
                    <button class="btn btn-sm btn-secondary me-1" onclick="cancelEdit(${category.cat_id})">Cancel</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.cat_id}, '${escapeHtml(category.cat_name)}')">Delete</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Setup add category form
 */
function setupAddForm() {
    const form = document.getElementById('addForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const categoryName = formData.get('category_name').trim();
        
        // Validate category information, check type
        if (!validateCategoryName(categoryName)) {
            return;
        }
        
        // Disable form during submission
        const submitBtn = this.querySelector('button');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding...';
        
        // Asynchronously invoke add_category_action.php
        fetch('../actions/add_category_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Inform user of success/failure using pop-up/message
            if (data.success) {
                showMessage(data.message, 'success');
                this.reset();
                loadCategories(); // Refresh table
            } else {
                showMessage(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error adding category', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}

/**
 * Validate category information, check type
 */
function validateCategoryName(name) {
    if (typeof name !== 'string') {
        showMessage('Category name must be text', 'danger');
        return false;
    }
    
    if (name.length === 0) {
        showMessage('Category name is required', 'danger');
        return false;
    }
    
    if (name.length < 2) {
        showMessage('Category name must be at least 2 characters', 'danger');
        return false;
    }
    
    if (name.length > 100) {
        showMessage('Category name too long (max 100 characters)', 'danger');
        return false;
    }
    
    return true;
}

/**
 * Start editing a category
 */
function startEdit(categoryId) {
    console.log('Starting edit for category ID:', categoryId);
    
    const row = document.querySelector(`[data-id="${categoryId}"]`).closest('tr');
    const nameSpan = row.querySelector('.category-name');
    const editInput = row.querySelector('.edit-input');
    const normalActions = row.querySelector('.normal-actions');
    const editActions = row.querySelector('.edit-actions');
    
    // Hide name, show input
    nameSpan.classList.add('d-none');
    editInput.classList.remove('d-none');
    editInput.focus();
    
    // Hide normal buttons, show edit buttons
    normalActions.classList.add('d-none');
    editActions.classList.remove('d-none');
}

/**
 * Cancel editing
 */
function cancelEdit(categoryId) {
    console.log('Cancelling edit for category ID:', categoryId);
    
    const row = document.querySelector(`[data-id="${categoryId}"]`).closest('tr');
    const nameSpan = row.querySelector('.category-name');
    const editInput = row.querySelector('.edit-input');
    const normalActions = row.querySelector('.normal-actions');
    const editActions = row.querySelector('.edit-actions');
    
    // Reset input value
    editInput.value = nameSpan.textContent;
    
    // Show name, hide input
    nameSpan.classList.remove('d-none');
    editInput.classList.add('d-none');
    
    // Show normal buttons, hide edit buttons
    normalActions.classList.remove('d-none');
    editActions.classList.add('d-none');
}

/**
 * Save category edit
 */
function saveEdit(categoryId) {
    console.log('Saving edit for category ID:', categoryId);
    
    const row = document.querySelector(`[data-id="${categoryId}"]`).closest('tr');
    const editInput = row.querySelector('.edit-input');
    const newName = editInput.value.trim();
    
    // Validate new name
    if (!validateCategoryName(newName)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('category_id', categoryId);
    formData.append('category_name', newName);
    
    console.log('Sending update data:', {category_id: categoryId, category_name: newName});
    
    // Asynchronously invoke update_category_action.php
    fetch('../actions/update_category_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        // Inform user of success/failure using pop-up/message
        if (data.success) {
            showMessage(data.message, 'success');
            // Update the display name
            const nameSpan = row.querySelector('.category-name');
            nameSpan.textContent = newName;
            cancelEdit(categoryId);
        } else {
            showMessage(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating category:', error);
        showMessage('Error updating category', 'danger');
    });
}

/**
 * Delete a category
 */
function deleteCategory(categoryId, categoryName) {
    console.log('Deleting category ID:', categoryId, 'Name:', categoryName);
    
    // Confirm deletion
    if (!confirm(`Are you sure you want to delete "${categoryName}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('category_id', categoryId);
    
    console.log('Sending delete for category ID:', categoryId);
    
    // Asynchronously invoke delete_category_action.php
    fetch('../actions/delete_category_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Delete response:', data);
        // Inform user of success/failure using pop-up/message
        if (data.success) {
            showMessage(data.message, 'success');
            loadCategories(); // Refresh table
        } else {
            showMessage(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting category:', error);
        showMessage('Error deleting category', 'danger');
    });
}

/**
 * Show message to user (pop-up/modal functionality)
 */
function showMessage(message, type = 'info') {
    const msgDiv = document.getElementById('addMsg');
    const alertClass = type === 'danger' ? 'text-danger' : 
                       type === 'success' ? 'text-success' : 'text-info';
    
    msgDiv.className = `small ${alertClass}`;
    msgDiv.textContent = message;
    
    // Clear message after 5 seconds
    setTimeout(() => {
        msgDiv.textContent = '';
    }, 5000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}