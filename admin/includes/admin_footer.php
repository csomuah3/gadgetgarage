    </div> <!-- End admin-container -->

    <!-- Footer -->
    <footer style="background: #f8fffe; border-top: 1px solid #e5f3f0; padding: 2rem 0; margin-top: 3rem;">
        <div class="container text-center">
            <p style="margin: 0; color: #6b7280;">
                &copy; <?php echo date('Y'); ?> GadgetGarage Admin Panel. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Common chart configuration for all admin pages
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.font.weight = '500';

        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hoverRadius = 6;
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.bar.borderRadius = 6;

        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 20;
    }

    // Common animation for counters
    function initializeCounters() {
        const counters = document.querySelectorAll('.counter');

        if (counters.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const target = parseInt(counter.getAttribute('data-target')) || 0;
                        const increment = target / 50;
                        let count = 0;

                        const updateCounter = () => {
                            if (count < target) {
                                count += increment;
                                counter.textContent = Math.ceil(count);
                                requestAnimationFrame(updateCounter);
                            } else {
                                counter.textContent = target;
                            }
                        };

                        updateCounter();
                        observer.unobserve(counter);
                    }
                });
            });

            counters.forEach(counter => observer.observe(counter));
        }
    }

    // Common animations for cards
    function initializeCardAnimations() {
        const cards = document.querySelectorAll('.admin-card, .analytics-card');

        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Initialize everything when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initializeCounters, 500);
        setTimeout(initializeCardAnimations, 100);
    });
    </script>
</body>
</html>