// Chatbot Functionality
class ChatBot {
    constructor() {
        this.isModalOpen = false;
        this.expandedSections = new Set();
        this.popupMessages = [
            "Need help with your tech? Chat with us!",
            "We are available 24/7 for all your device needs!",
            "Questions about repairs or refurbished devices?",
            "Get instant support for your gadgets now!",
            "Need assistance with your order or repair?"
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
                    <button class="close-popup" id="closePopup">&times;</button>
                    <h4>Need support?</h4>
                    <p>How can we help?</p>
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
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="action-button send-message-btn" id="sendMessageBtn">
                            <h5>Send us a message</h5>
                            <i class="fas fa-chevron-right"></i>
                        </button>

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

        // Close popup
        document.getElementById('closePopup').addEventListener('click', () => {
            this.hidePopup();
        });

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
        // Show first popup after 10 seconds
        setTimeout(() => {
            this.showPopup();
        }, 10000);

        // Then show popup every 60 seconds
        setInterval(() => {
            this.showPopup();
        }, 60000);
    }

    openMessageForm() {
        // Redirect to message form page
        window.location.href = 'support_message.php';
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatBot();
});