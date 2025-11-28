// Wishlist Manager
function updateWishlistBadge(count) {
    const badge = document.getElementById('wishlistBadge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
        console.log('Wishlist badge updated to:', count);
    }
}

// Function to handle wishlist actions response
function handleWishlistResponse(data) {
    if (data.success && typeof data.count !== 'undefined') {
        updateWishlistBadge(data.count);
    }
}

// Export for use in other scripts
window.updateWishlistBadge = updateWishlistBadge;
window.handleWishlistResponse = handleWishlistResponse;

