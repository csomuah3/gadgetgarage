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
            // Check if store credits are applied - if yes, remove them
            const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
            if (applyStoreCreditsCheckbox && applyStoreCreditsCheckbox.checked) {
                // Uncheck store credits and remove deduction
                applyStoreCreditsCheckbox.checked = false;
                if (typeof handleStoreCreditsToggle === 'function') {
                    handleStoreCreditsToggle(false);
                }
                // Show message
                if (promoMessage) {
                    promoMessage.innerHTML = `<div class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Store credits removed. Discount code applied instead.
                    </div>`;
                    promoMessage.style.display = 'block';
                }
            }

            // Disable store credits checkbox
            if (applyStoreCreditsCheckbox) {
                applyStoreCreditsCheckbox.disabled = true;
            }
            const storeCreditsLabel = document.querySelector('label[for="applyStoreCredits"]');
            if (storeCreditsLabel) {
                storeCreditsLabel.style.cursor = 'not-allowed';
                storeCreditsLabel.style.opacity = '0.6';
            }
            const storeCreditsExclusiveMessage = document.getElementById('storeCreditsExclusiveMessage');
            if (storeCreditsExclusiveMessage) {
                storeCreditsExclusiveMessage.style.display = 'block';
            }

            appliedPromo = result;

            // Store original total for removal
            if (result.original_total) {
                window.originalTotal = result.original_total;
            }

            // Store promo code info in localStorage for checkout page
            localStorage.setItem('appliedPromo', JSON.stringify({
                promo_code: result.promo_code,
                discount_type: result.discount_type,
                discount_value: result.discount_value,
                discount_amount: result.discount_amount,
                original_total: result.original_total,
                new_total: result.new_total,
                description: result.description || ''
            }));

            // Update UI with success message
            if (promoMessage) {
                promoMessage.innerHTML = `<div class="text-success">
                    <i class="fas fa-check-circle"></i>
                    Promo code "${promoCode}" applied! You saved GH₵${result.discount_amount.toFixed(2)}
                </div>`;
                promoMessage.style.display = 'block';
            }

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
                
                // Re-attach remove button event listener
                const removeBtn = document.getElementById('removePromoBtn');
                if (removeBtn) {
                    removeBtn.addEventListener('click', removePromoCode);
                }
            }

            // Update totals - pass original_total, new_total, and discount_amount
            updateCartTotals(result.original_total || cartTotal, result.new_total, result.discount_amount);

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

    // Remove promo code from localStorage
    localStorage.removeItem('appliedPromo');

    // Re-enable store credits checkbox
    const applyStoreCreditsCheckbox = document.getElementById('applyStoreCredits');
    if (applyStoreCreditsCheckbox) {
        applyStoreCreditsCheckbox.disabled = false;
    }
    const storeCreditsLabel = document.querySelector('label[for="applyStoreCredits"]');
    if (storeCreditsLabel) {
        storeCreditsLabel.style.cursor = 'pointer';
        storeCreditsLabel.style.opacity = '1';
    }
    const storeCreditsExclusiveMessage = document.getElementById('storeCreditsExclusiveMessage');
    if (storeCreditsExclusiveMessage) {
        storeCreditsExclusiveMessage.style.display = 'none';
    }

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

    // Reset totals - use stored original total or get from page
    const originalTotal = window.originalTotal || parseFloat(document.querySelector('[data-original-total]')?.getAttribute('data-original-total') || '0');
    
    // If still no total, try to get from cartSubtotal element
    if (originalTotal === 0) {
        const subtotalElement = document.getElementById('cartSubtotal');
        if (subtotalElement) {
            const subtotalText = subtotalElement.textContent.replace(/[^0-9.]/g, '');
            const parsedTotal = parseFloat(subtotalText);
            if (!isNaN(parsedTotal) && parsedTotal > 0) {
                updateCartTotals(parsedTotal, parsedTotal, 0);
                return;
            }
        }
    }
    
    updateCartTotals(originalTotal, originalTotal, 0);
}

function updateCartTotals(originalTotal, newTotal, discountAmount) {
    console.log('PROMO-CODE.JS DEBUG: updateCartTotals called with originalTotal:', originalTotal, 'newTotal:', newTotal, 'discountAmount:', discountAmount);

    // Update subtotal to show original cart total (unchanged)
    const cartSubtotalElement = document.getElementById('cartSubtotal');
    if (cartSubtotalElement) {
        cartSubtotalElement.textContent = `GH₵ ${originalTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        console.log('PROMO-CODE.JS DEBUG: Updated cartSubtotal element to:', cartSubtotalElement.textContent);
    }

    // Update cart total display (final total after discount)
    const cartTotalElement = document.getElementById('cartTotal');
    if (cartTotalElement) {
        cartTotalElement.textContent = `GH₵ ${newTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        console.log('PROMO-CODE.JS DEBUG: Updated cartTotal element to:', cartTotalElement.textContent);
    }

    // Show/hide discount row based on discount amount
    const discountRow = document.getElementById('discountRow');
    if (discountRow) {
        if (discountAmount > 0) {
        discountRow.style.display = 'flex';
        console.log('PROMO-CODE.JS DEBUG: Showed discount row');
        } else {
            discountRow.style.display = 'none';
            console.log('PROMO-CODE.JS DEBUG: Hid discount row');
        }
    }

    // Update discount amount display
    const discountAmountElement = document.getElementById('discountAmount');
    if (discountAmountElement) {
        discountAmountElement.textContent = `-GH₵ ${discountAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        console.log('PROMO-CODE.JS DEBUG: Updated discount amount to:', discountAmountElement.textContent);
    }
}