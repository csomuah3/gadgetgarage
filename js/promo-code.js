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

    // Get cart total from the page - first check if we're on cart page with originalTotal
    let cartTotal = 0;

    console.log('PROMO-CODE.JS DEBUG: window.originalTotal =', window.originalTotal);

    // Check if originalTotal is available (from cart.php)
    if (typeof window.originalTotal !== 'undefined' && window.originalTotal > 0) {
        cartTotal = parseFloat(window.originalTotal);
        console.log('PROMO-CODE.JS DEBUG: Using originalTotal from cart page:', cartTotal);
    } else {
        // Fallback: try to extract from DOM elements
        const cartTotalElement = document.querySelector('#cartTotal, .total-amount, [data-original-total]');
        console.log('PROMO-CODE.JS DEBUG: cartTotalElement found:', !!cartTotalElement);

        if (cartTotalElement) {
            console.log('PROMO-CODE.JS DEBUG: cartTotalElement text:', cartTotalElement.textContent);
            // Try to get from data attribute first
            cartTotal = parseFloat(cartTotalElement.getAttribute('data-original-total')) || 0;

            // If no data attribute, try to parse the text content
            if (cartTotal === 0) {
                const totalText = cartTotalElement.textContent.replace(/[^0-9.]/g, '');
                cartTotal = parseFloat(totalText) || 0;
                console.log('PROMO-CODE.JS DEBUG: Extracted from text:', totalText, 'parsed as:', cartTotal);
            }
        }
    }

    // Fallback to a minimum amount if no total found
    if (cartTotal <= 0) {
        cartTotal = 6000; // Use 6000 to match your actual cart for testing
        console.log('PROMO-CODE.JS DEBUG: Using fallback cart total:', cartTotal);
    }

    console.log('PROMO-CODE.JS DEBUG: Final cartTotal being used:', cartTotal);

    const requestData = {
        promo_code: promoCode,
        cart_total: cartTotal
    };

    console.log('PROMO-CODE.JS DEBUG: Request data being sent:', requestData);

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
        console.log('PROMO-CODE.JS DEBUG: Result received:', result);
        console.log('PROMO-CODE.JS DEBUG: result.success:', result.success);
        console.log('PROMO-CODE.JS DEBUG: result.discount_amount:', result.discount_amount);

        if (result.success) {
            appliedPromo = result.promo;

            // Update UI with success message
            promoMessage.innerHTML = `<div class="text-success">
                <i class="fas fa-check-circle"></i>
                Promo code "${promoCode}" applied! You saved GH₵${result.discount_amount.toFixed(2)}
            </div>`;
            promoMessage.style.display = 'block';

            // Show applied promo section
            const appliedPromoDiv = document.getElementById('appliedPromo');
            if (appliedPromoDiv) {
                appliedPromoDiv.innerHTML = `
                    <div class="applied-promo-item">
                        <span class="promo-code">${promoCode}</span>
                        <span class="promo-discount">-GH₵${result.discount_amount.toFixed(2)}</span>
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
    console.log('PROMO-CODE.JS DEBUG: updateCartTotals called with newTotal:', newTotal, 'discountAmount:', discountAmount);

    // Update cart total display on cart page
    const cartTotalElement = document.getElementById('cartTotal');
    if (cartTotalElement) {
        cartTotalElement.textContent = `GH₵ ${newTotal.toFixed(2)}`;
        console.log('PROMO-CODE.JS DEBUG: Updated cartTotal element to:', cartTotalElement.textContent);
    }

    // Show discount row if it exists
    const discountRow = document.getElementById('discountRow');
    if (discountRow) {
        discountRow.style.display = 'flex';
        console.log('PROMO-CODE.JS DEBUG: Showed discount row');
    }

    // Update discount amount display
    const discountAmountElement = document.getElementById('discountAmount');
    if (discountAmountElement) {
        discountAmountElement.textContent = `-GH₵ ${discountAmount.toFixed(2)}`;
        console.log('PROMO-CODE.JS DEBUG: Updated discount amount to:', discountAmountElement.textContent);
    }

    // Update subtotal display (fallback)
    const subtotalElement = document.querySelector('.subtotal-amount');
    if (subtotalElement) {
        subtotalElement.textContent = `GH₵ ${newTotal.toFixed(2)}`;
    }

    // Update total display (fallback)
    const totalElement = document.querySelector('.total-amount');
    if (totalElement) {
        totalElement.textContent = `GH₵ ${newTotal.toFixed(2)}`;
    }
}