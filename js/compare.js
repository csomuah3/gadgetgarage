// Product Compare Functionality

function addToCompare(productId, productTitle) {
    // Check if user is logged in (you can check session or a global variable)
    fetch('../actions/add_to_compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Added to Compare!',
                html: `
                    <p>${data.message}</p>
                    <p style="margin-top: 10px;">
                        <strong>${data.count} products</strong> in compare list
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-balance-scale"></i> Go to Compare',
                cancelButtonText: 'Continue Shopping',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'compare.php';
                }
            });
            
            // Update compare count in UI if element exists
            updateCompareCount(data.count);
        } else if (data.status === 'info') {
            Swal.fire({
                icon: 'info',
                title: 'Already Added',
                text: data.message,
                confirmButtonColor: '#2563eb'
            });
        } else if (data.status === 'error' && data.message.includes('login')) {
            Swal.fire({
                icon: 'warning',
                title: 'Login Required',
                text: data.message,
                showCancelButton: true,
                confirmButtonText: 'Login Now',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../login/user_login.php';
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to add product to compare list',
            confirmButtonColor: '#dc2626'
        });
    });
}

function updateCompareCount(count) {
    // Update sidebar count if it exists
    const compareCountElement = document.getElementById('compareCount');
    if (compareCountElement) {
        compareCountElement.textContent = count;
        if (count > 0) {
            compareCountElement.style.display = 'inline-block';
        } else {
            compareCountElement.style.display = 'none';
        }
    }
    
    // Update header badge
    const compareBadge = document.getElementById('compareBadge');
    if (compareBadge) {
        compareBadge.textContent = count;
        if (count > 0) {
            compareBadge.style.display = 'flex';
        } else {
            compareBadge.style.display = 'none';
        }
    }
}

// Toggle compare button active state
function toggleCompareButton(button) {
    button.classList.toggle('active');
}

