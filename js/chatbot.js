// Chatbot Functionality
class ChatBot {
    constructor() {
        this.isModalOpen = false;
        this.expandedSections = new Set();
        this.popupMessages = [
            "Need help? Chat with us!",
            "We are available 24/7!",
            "Questions about your order?",
            "Device issues? We can help!",
            "Need repair assistance?"
        ];
        this.currentMessageIndex = 0;
        this.init();
    }

    init() {
        this.createChatbotHTML();
        this.bindEvents();
        this.startPeriodicMessages();
    }

    createChatbotHTML() {
        const chatbotHTML = `
            <div class="chatbot-container" id="chatbotContainer">
                <!-- Floating Icon -->
                <div class="chatbot-icon" id="chatbotIcon">
                    <i class="fas fa-comments"></i>
                </div>

                <!-- Popup Message -->
                <div class="chatbot-popup" id="chatbotPopup">
                    <p>Need help? Chat with us!</p>
                </div>

                <!-- Support Modal -->
                <div class="support-modal" id="supportModal">
                    <!-- Header -->
                    <div class="support-header">
                        <button class="close-modal" id="closeModal">&times;</button>
                        <h2>Need support?</h2>
                        <h3>How can we help?</h3>
                    </div>

                    <!-- Recent Message -->
                    <div class="recent-message">
                        <h4>Recent message</h4>
                        <div class="message-content">
                            <div class="message-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="message-text">
                                <h5>Your device repair is ready for pickup!</h5>
                                <p>Gadget Garage Team • 12h</p>
                            </div>
                            <div class="message-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="status-section">
                        <div class="status-indicator">
                            <div class="status-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="status-text">
                                <h4>Status: All Services Available</h4>
                                <p>Updated Nov 16, 09:21 UTC</p>
                            </div>
                        </div>

                        <!-- Send Message Button -->
                        <button class="action-button send-message-btn" id="sendMessageBtn" style="margin-top: 15px;">
                            <h5>Send us a message</h5>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Questions Title -->
                    <div style="padding: 8px 16px 6px 16px; border-bottom: 1px solid #e9ecef;">
                        <h4 style="margin: 0; color: #2c3e50; font-size: 13px; font-weight: 600;">Search for help</h4>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">

                        <button class="action-button" data-section="order">
                            <h5>My order status or refund issues</h5>
                            <i class="fas fa-chevron-right"></i>
                            <div class="expandable-content">
                                <p>Having trouble tracking your order or need a refund? We can help you check your order status, process returns for refurbished devices, or resolve payment issues. Most order problems can be resolved within 24 hours through our customer service team.</p>
                            </div>
                        </button>

                        <button class="action-button" data-section="device">
                            <h5>Issues with refurbished device quality</h5>
                            <i class="fas fa-chevron-right"></i>
                            <div class="expandable-content">
                                <p>Received a refurbished device that's not working as expected? All our devices come with quality guarantees. We can arrange a replacement, provide troubleshooting support, or process a return. Contact us with your device details and we'll resolve the issue quickly.</p>
                            </div>
                        </button>

                        <button class="action-button" data-section="repair">
                            <h5>Repair service appointment or pricing questions</h5>
                            <i class="fas fa-chevron-right"></i>
                            <div class="expandable-content">
                                <p>Need help with scheduling a repair, understanding pricing, or checking repair status? Our Repair Studio handles screen repairs, battery replacements, and more. We can help reschedule appointments, provide quotes, or update you on your device's repair progress.</p>
                            </div>
                        </button>

                        <button class="action-button" data-section="drop">
                            <h5>Device Drop service or trade-in value questions</h5>
                            <i class="fas fa-chevron-right"></i>
                            <div class="expandable-content">
                                <p>Questions about dropping off old devices or trade-in values? Our Device Drop service accepts old tech for recycling or trade-in credit. We can help you understand what devices we accept, current trade-in values, or schedule a drop-off appointment.</p>
                            </div>
                        </button>

                        <button class="action-button" data-section="tech">
                            <h5>Tech Revival service issues (055-138-7578)</h5>
                            <i class="fas fa-chevron-right"></i>
                            <div class="expandable-content">
                                <p>Having trouble reaching our Tech Revival hotline or need help with bringing in retired tech? Call 055-138-7578 for immediate assistance with device recycling, trade-ins, or scheduling pickup services. If the line is busy, we can schedule a callback for you.</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insert chatbot HTML into the body
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    }

    bindEvents() {
        // Chatbot icon click
        document.getElementById('chatbotIcon').addEventListener('click', () => {
            this.toggleModal();
        });

        // Close popup (if exists)
        const closePopup = document.getElementById('closePopup');
        if (closePopup) {
            closePopup.addEventListener('click', () => {
                this.hidePopup();
            });
        }

        // Close modal
        document.getElementById('closeModal').addEventListener('click', () => {
            this.closeModal();
        });

        // Send message button
        document.getElementById('sendMessageBtn').addEventListener('click', () => {
            this.openMessageForm();
        });

        // Expandable sections
        document.querySelectorAll('.action-button[data-section]').forEach(button => {
            button.addEventListener('click', () => {
                const section = button.getAttribute('data-section');
                this.toggleSection(section, button);
            });
        });

        // Click outside to close modal
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('supportModal');
            const chatbotContainer = document.getElementById('chatbotContainer');

            if (this.isModalOpen && !chatbotContainer.contains(e.target)) {
                this.closeModal();
            }
        });
    }

    toggleModal() {
        if (this.isModalOpen) {
            this.closeModal();
        } else {
            this.openModal();
        }
    }

    openModal() {
        const modal = document.getElementById('supportModal');
        modal.style.display = 'block';
        this.isModalOpen = true;
        this.hidePopup();

        // Animate modal opening
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    closeModal() {
        const modal = document.getElementById('supportModal');
        modal.classList.add('closing');

        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('closing', 'show');
            this.isModalOpen = false;
        }, 300);
    }

    toggleSection(sectionName, button) {
        const content = button.querySelector('.expandable-content');
        const icon = button.querySelector('i');

        if (this.expandedSections.has(sectionName)) {
            // Collapse
            content.classList.remove('expanded');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
            this.expandedSections.delete(sectionName);
        } else {
            // Expand
            content.classList.add('expanded');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
            this.expandedSections.add(sectionName);
        }
    }

    showPopup() {
        if (this.isModalOpen) return;

        const popup = document.getElementById('chatbotPopup');
        const message = this.popupMessages[this.currentMessageIndex];

        // Update message content
        popup.querySelector('p').textContent = message;

        // Show popup
        popup.classList.add('show');

        // Auto hide after 5 seconds
        setTimeout(() => {
            this.hidePopup();
        }, 5000);

        // Move to next message
        this.currentMessageIndex = (this.currentMessageIndex + 1) % this.popupMessages.length;
    }

    hidePopup() {
        const popup = document.getElementById('chatbotPopup');
        popup.classList.remove('show');
    }

    startPeriodicMessages() {
        // Show first popup after 5 seconds
        setTimeout(() => {
            this.showPopup();
        }, 5000);

        // Then show popup every 15 seconds
        setInterval(() => {
            this.showPopup();
        }, 15000);
    }

    openMessageForm() {
        // Open message form within the modal instead of redirecting
        this.showMessageForm();
    }

    showMessageForm() {
        const modalContent = document.querySelector('#supportModal .action-buttons').parentElement;

        // Hide action buttons and show message form
        document.querySelector('#supportModal .action-buttons').style.display = 'none';
        document.querySelector('#supportModal .recent-message').style.display = 'none';
        document.querySelector('#supportModal .status-section').style.display = 'none';

        // Create message form HTML
        const messageFormHTML = `
            <div id="messageForm" class="message-form-container">
                <div class="form-header">
                    <button type="button" class="back-btn" id="backToMenu">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h4>Send us a message</h4>
                </div>

                <form id="supportMessageForm">
                    <div class="form-group" id="guestNameField" style="display: none;">
                        <label>Name</label>
                        <input type="text" name="guest_name" placeholder="Your name">
                    </div>

                    <div class="form-group" id="guestEmailField" style="display: none;">
                        <label>Email</label>
                        <input type="email" name="guest_email" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject" required>
                            <option value="">Select a topic</option>
                            <option value="order">Order Status & Refunds</option>
                            <option value="device_quality">Refurbished Device Issues</option>
                            <option value="repair">Repair Service Questions</option>
                            <option value="device_drop">Device Drop & Trade-ins</option>
                            <option value="tech_revival">Tech Revival Service</option>
                            <option value="billing">Billing & Payment</option>
                            <option value="account">Account Issues</option>
                            <option value="general">General Question</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="4" placeholder="Describe your issue..." required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>

                <div id="messageSuccess" class="success-message" style="display: none;">
                    <i class="fas fa-check-circle"></i>
                    <h4>Message Sent!</h4>
                    <p>We will respond as soon as we receive your message.</p>
                </div>
            </div>
        `;

        // Insert message form
        modalContent.insertAdjacentHTML('beforeend', messageFormHTML);

        // Check if user is logged in and show/hide guest fields accordingly
        this.checkUserLoginStatus();

        // Bind events
        document.getElementById('backToMenu').addEventListener('click', () => {
            this.hideMessageForm();
        });

        document.getElementById('supportMessageForm').addEventListener('submit', (e) => {
            this.handleMessageSubmit(e);
        });
    }

    checkUserLoginStatus() {
        // Simple check - look for login button or user info
        const loginBtn = document.querySelector('a[href*="login"]');
        const isLoggedIn = !loginBtn || loginBtn.style.display === 'none';

        // If not logged in, show guest fields and make them required
        if (!isLoggedIn) {
            const nameField = document.getElementById('guestNameField');
            const emailField = document.getElementById('guestEmailField');

            if (nameField && emailField) {
                nameField.style.display = 'block';
                emailField.style.display = 'block';
                nameField.querySelector('input').required = true;
                emailField.querySelector('input').required = true;
            }
        }
    }

    hideMessageForm() {
        // Remove message form and show original content
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.remove();
        }

        // Show original content
        document.querySelector('#supportModal .action-buttons').style.display = 'block';
        document.querySelector('#supportModal .recent-message').style.display = 'block';
        document.querySelector('#supportModal .status-section').style.display = 'block';
    }

    async handleMessageSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const submitBtn = form.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('subject', form.subject.value);
        formData.append('message', form.message.value);

        // Add guest name and email if provided
        if (form.guest_name && form.guest_name.value) {
            formData.append('guest_name', form.guest_name.value);
        }
        if (form.guest_email && form.guest_email.value) {
            formData.append('guest_email', form.guest_email.value);
        }

        formData.append('send_message', '1');

        try {
            const response = await fetch('actions/send_support_message.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Show success message
                form.style.display = 'none';
                document.getElementById('messageSuccess').style.display = 'block';

                // Hide success message and return to menu after 3 seconds
                setTimeout(() => {
                    this.hideMessageForm();
                }, 3000);
            } else {
                throw new Error(result.message || 'Failed to send message');
            }
        } catch (error) {
            alert('Error sending message: ' + error.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatBot();
});