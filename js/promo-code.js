// Promo Code Functionality
let appliedPromo = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Promo code script loaded');

    const promoInput = document.getElementById('promoCode');
    const applyBtn = document.getElementById('applyPromoBtn');
    const removeBtn = document.getElementById('removePromoBtn');

    console.log('Elements found:', {
        promoInput: !!promoInput,
        applyBtn: !!applyBtn,
        removeBtn: !!removeBtn
    });

    // Apply promo code on button click
    if (applyBtn) {
        applyBtn.addEventListener('click', applyPromoCode);
        console.log('Click event listener added to Apply button');
    }

    // Apply promo code on Enter key
    if (promoInput) {
        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyPromoCode();
            }
        });
    }

    // Remove promo code
    if (removeBtn) {
        removeBtn.addEventListener('click', removePromoCode);
    }
});

async function applyPromoCode() {
    console.log('ApplyPromoCode function called');

    const promoInput = document.getElementById('promoCode');
    const applyBtn = document.getElementById('applyPromoBtn');
    const promoMessage = document.getElementById('promoMessage');

    console.log('Elements found in function:', {
        promoInput: !!promoInput,
        applyBtn: !!applyBtn,
        promoMessage: !!promoMessage
    });

    if (!promoInput || !applyBtn || !promoMessage) {
        console.error('Required elements not found');
        return;
    }

    const promoCode = promoInput.value.trim();

    if (!promoCode) {
        promoMessage.innerHTML = '<div class="text-danger">Please enter a promo code</div>';
        promoMessage.style.display = 'block';
        return;
    }

    // Get cart total from the page
    const cartTotalElement = document.querySelector('.total-amount, [data-original-total]');
    let cartTotal = 0;

    if (cartTotalElement) {
        // Try to get from data attribute first
        cartTotal = parseFloat(cartTotalElement.getAttribute('data-original-total')) || 0;

        // If no data attribute, try to parse the text content
        if (cartTotal === 0) {
            const totalText = cartTotalElement.textContent.replace(/[^0-9.]/g, '');
            cartTotal = parseFloat(totalText) || 0;
        }
    }

    // Fallback to a minimum amount if no total found
    if (cartTotal <= 0) {
        cartTotal = 1; // Use 1 as minimum to allow promo code testing
    }

    const requestData = {
        promo_code: promoCode,
        cart_total: cartTotal
    };

    console.log('Request data:', requestData);

    try {
        applyBtn.disabled = true;
        applyBtn.textContent = 'Applying...';
        promoMessage.style.display = 'none';

        const response = await fetch('../actions/validate_promo_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });

        console.log('Response status:', response.status);
        console.log('Response:', response);

        const result = await response.json();
        console.log('Result:', result);

        if (result.success) {
            appliedPromo = result.promo;

            // Update UI with success message
            promoMessage.innerHTML = `<div class="text-success">
                <i class="fas fa-check-circle"></i>
                Promo code "${promoCode}" applied! You saved $${result.discount_amount.toFixed(2)}
            </div>`;
            promoMessage.style.display = 'block';

            // Show applied promo section
            const appliedPromoDiv = document.getElementById('appliedPromo');
            if (appliedPromoDiv) {
                appliedPromoDiv.innerHTML = `
                    <div class="applied-promo-item">
                        <span class="promo-code">${promoCode}</span>
                        <span class="promo-discount">-$${result.discount_amount.toFixed(2)}</span>
                        <button type="button" id="removePromoBtn" class="remove-promo-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                appliedPromoDiv.style.display = 'block';
            }

            // Update totals
            updateCartTotals(result.new_total, result.discount_amount);

            // Clear input
            promoInput.value = '';

        } else {
            promoMessage.innerHTML = `<div class="text-danger">
                <i class="fas fa-exclamation-circle"></i>
                ${result.message}
            </div>`;
            promoMessage.style.display = 'block';
        }

    } catch (error) {
        console.error('Error applying promo code:', error);
        promoMessage.innerHTML = `<div class="text-danger">
            <i class="fas fa-exclamation-circle"></i>
            Error applying promo code. Please try again.
        </div>`;
        promoMessage.style.display = 'block';
    } finally {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply';
    }
}

function removePromoCode() {
    console.log('RemovePromoCode function called');

    appliedPromo = null;

    // Hide applied promo section
    const appliedPromoDiv = document.getElementById('appliedPromo');
    if (appliedPromoDiv) {
        appliedPromoDiv.style.display = 'none';
    }

    // Hide message
    const promoMessage = document.getElementById('promoMessage');
    if (promoMessage) {
        promoMessage.style.display = 'none';
    }

    // Reset totals - you'll need to get the original total from PHP
    const originalTotal = parseFloat(document.querySelector('[data-original-total]')?.getAttribute('data-original-total') || '0');
    updateCartTotals(originalTotal, 0);
}

function updateCartTotals(newTotal, discountAmount) {
    // Update subtotal display
    const subtotalElement = document.querySelector('.subtotal-amount');
    if (subtotalElement) {
        subtotalElement.textContent = `$${newTotal.toFixed(2)}`;
    }

    // Update total display
    const totalElement = document.querySelector('.total-amount');
    if (totalElement) {
        totalElement.textContent = `$${newTotal.toFixed(2)}`;
    }
}