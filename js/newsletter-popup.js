// Newsletter Popup for Black Friday Deals
function showNewsletterPopup() {
    // Check if popup already exists
    if (document.getElementById('newsletterPopup')) {
        return;
    }

    const popup = document.createElement('div');
    popup.id = 'newsletterPopup';
    popup.className = 'newsletter-popup-overlay';
    popup.innerHTML = `
        <div class="newsletter-popup">
            <button class="newsletter-close" onclick="closeNewsletterPopup()">
                <i class="fas fa-times"></i>
            </button>

            <div class="newsletter-content">
                <div class="newsletter-icon">
                    🎯
                </div>

                <h2>Exclusive Black Friday Deals!</h2>
                <p>Join our newsletter to get early access to amazing Black Friday discounts and special offers!</p>

                <form id="newsletterForm" onsubmit="submitNewsletterForm(event)">
                    <div class="email-input-container">
                        <input
                            type="email"
                            id="newsletterEmail"
                            placeholder="Enter your email address"
                            required
                            autocomplete="email"
                        >
                        <button type="submit" id="subscribeBtn" class="subscribe-btn">
                            Subscribe Now
                        </button>
                    </div>
                </form>

                <p class="newsletter-disclaimer">
                    Get exclusive deals, early access to sales, and special promotions delivered to your inbox.
                </p>
            </div>
        </div>
    `;

    // Add CSS styles
    const style = document.createElement('style');
    style.textContent = `
        .newsletter-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            animation: fadeIn 0.3s ease forwards;
        }

        .newsletter-popup {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            position: relative;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.8);
            animation: popupSlide 0.3s ease 0.1s forwards;
        }

        .newsletter-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f5f5f5;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
        }

        .newsletter-close:hover {
            background: #e0e0e0;
            color: #333;
        }

        .newsletter-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .newsletter-popup h2 {
            color: #1f2937;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .newsletter-popup p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .email-input-container {
            display: flex;
            gap: 0;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        #newsletterEmail {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            font-size: 1rem;
            outline: none;
            border-right: none;
        }

        #newsletterEmail:focus {
            border-color: #4f46e5;
        }

        .subscribe-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .subscribe-btn:hover {
            background: linear-gradient(135deg, #ff5252, #ff7979);
            transform: translateY(-1px);
        }

        .subscribe-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .newsletter-disclaimer {
            font-size: 0.85rem;
            color: #9ca3af;
            margin: 0;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes popupSlide {
            to { transform: scale(1); }
        }

        @media (max-width: 576px) {
            .newsletter-popup {
                padding: 30px 20px;
                margin: 20px;
            }

            .newsletter-popup h2 {
                font-size: 1.5rem;
            }

            .email-input-container {
                flex-direction: column;
            }

            #newsletterEmail {
                border-right: 2px solid #e5e7eb;
                border-bottom: none;
            }
        }
    `;

    document.head.appendChild(style);
    document.body.appendChild(popup);

    // Mark popup as shown for this user
    markPopupAsShown();
}

async function submitNewsletterForm(event) {
    event.preventDefault();

    const email = document.getElementById('newsletterEmail').value.trim();
    const submitBtn = document.getElementById('subscribeBtn');

    if (!email) {
        showNewsletterMessage('Please enter your email address', 'error');
        return;
    }

    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Subscribing...';

    try {
        const response = await fetch('../actions/subscribe_newsletter_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            document.getElementById('newsletterForm').innerHTML = `
                <div style="color: #10b981; font-size: 1.1rem; font-weight: 600; margin: 20px 0;">
                    <i class="fas fa-check-circle"></i> ${data.message}
                </div>
                <button onclick="closeNewsletterPopup()" class="subscribe-btn" style="background: #10b981;">
                    Continue Shopping
                </button>
            `;

            // Auto-close after 3 seconds
            setTimeout(() => {
                closeNewsletterPopup();
            }, 3000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Newsletter subscription error:', error);
        showNewsletterMessage('Failed to subscribe. Please try again.', 'error');

        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Subscribe Now';
    }
}

function showNewsletterMessage(message, type) {
    // Remove existing messages
    const existingMsg = document.querySelector('.newsletter-message');
    if (existingMsg) existingMsg.remove();

    const messageDiv = document.createElement('div');
    messageDiv.className = 'newsletter-message';
    messageDiv.style.cssText = `
        padding: 10px;
        margin: 10px 0;
        border-radius: 8px;
        font-size: 0.9rem;
        ${type === 'error' ?
            'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' :
            'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'
        }
    `;
    messageDiv.textContent = message;

    const form = document.getElementById('newsletterForm');
    form.appendChild(messageDiv);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 3000);
}

function closeNewsletterPopup() {
    const popup = document.getElementById('newsletterPopup');
    if (popup) {
        popup.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            popup.remove();
        }, 200);
    }
}

async function markPopupAsShown() {
    try {
        await fetch('../actions/mark_popup_shown_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });
    } catch (error) {
        console.error('Failed to mark popup as shown:', error);
    }
}

// CSS for fade out animation
const fadeOutStyle = document.createElement('style');
fadeOutStyle.textContent = `
    @keyframes fadeOut {
        to { opacity: 0; transform: scale(0.9); }
    }
`;
document.head.appendChild(fadeOutStyle);