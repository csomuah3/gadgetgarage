class HorizontalGallery {
    constructor(container, testimonials, options = {}) {
        this.container = container;
        this.testimonials = testimonials;
        this.options = {
            cardWidth: 320,
            cardHeight: 250, // 80% of 320px container height
            autoScrollSpeed: 1,
            cardSpacing: 30,
            ...options
        };

        this.currentOffset = 0;
        this.cards = [];
        this.isDestroyed = false;

        this.init();
    }

    init() {
        this.createStructure();
        this.createCards();
        this.startAutoScroll();
        this.addEventListeners();
    }

    createStructure() {
        this.container.innerHTML = `
            <div class="horizontal-gallery-wrapper">
                <div class="horizontal-gallery-container">
                    <div class="horizontal-gallery-track">
                        <!-- Cards will be inserted here -->
                    </div>
                </div>
            </div>
        `;

        this.wrapper = this.container.querySelector('.horizontal-gallery-wrapper');
        this.galleryContainer = this.container.querySelector('.horizontal-gallery-container');
        this.track = this.container.querySelector('.horizontal-gallery-track');
    }

    createCards() {
        // Duplicate testimonials for infinite loop effect
        const duplicatedTestimonials = [...this.testimonials, ...this.testimonials];

        duplicatedTestimonials.forEach((testimonial, index) => {
            const card = document.createElement('div');
            card.className = 'gallery-testimonial-card';
            card.setAttribute('data-index', index);

            const stars = '★'.repeat(testimonial.rating) + '☆'.repeat(5 - testimonial.rating);

            card.innerHTML = `
                <div class="card-inner">
                    <div class="testimonial-stars">${stars}</div>
                    <p class="testimonial-text">${testimonial.text.replace('\n', '<br>')}</p>
                    <div class="testimonial-author">
                        <strong>${testimonial.name}</strong>
                    </div>
                </div>
            `;

            // Set card dimensions
            card.style.width = this.options.cardWidth + 'px';
            card.style.height = this.options.cardHeight + 'px';

            this.track.appendChild(card);
            this.cards.push(card);
        });

        // Position cards horizontally
        this.positionCards();
    }

    positionCards() {
        this.cards.forEach((card, index) => {
            const x = (index * (this.options.cardWidth + this.options.cardSpacing)) + this.currentOffset;
            card.style.transform = `translateX(${x}px)`;
        });
    }

    startAutoScroll() {
        const animate = () => {
            if (this.isDestroyed) return;

            // Move cards to the left
            this.currentOffset -= this.options.autoScrollSpeed;

            // Reset position when first set of cards goes completely off screen
            const resetPoint = -(this.testimonials.length * (this.options.cardWidth + this.options.cardSpacing));
            if (this.currentOffset <= resetPoint) {
                this.currentOffset = 0;
            }

            this.positionCards();
            requestAnimationFrame(animate);
        };

        animate();
    }

    addEventListeners() {
        this.resizeHandler = this.handleResize.bind(this);
        window.addEventListener('resize', this.resizeHandler);
    }

    handleResize() {
        // Adjust card sizes based on container - cards always 80% of container height
        const containerWidth = this.container.offsetWidth;
        const containerHeight = this.wrapper.offsetHeight;

        if (containerWidth < 768) {
            this.options.cardWidth = 280;
            this.options.cardHeight = Math.floor(containerHeight * 0.8);
        } else if (containerWidth < 1200) {
            this.options.cardWidth = 300;
            this.options.cardHeight = Math.floor(containerHeight * 0.8);
        } else {
            this.options.cardWidth = 320;
            this.options.cardHeight = Math.floor(containerHeight * 0.8);
        }

        // Update card sizes
        this.cards.forEach(card => {
            card.style.width = this.options.cardWidth + 'px';
            card.style.height = this.options.cardHeight + 'px';
        });

        this.positionCards();
    }

    destroy() {
        this.isDestroyed = true;
        window.removeEventListener('resize', this.resizeHandler);
        this.container.innerHTML = '';
    }
}

// Testimonials data with short 2-line reviews and Ghanaian context
const testimonials = [
    {
        name: "Akosua Mensah",
        rating: 5,
        text: "Brought my old Nokia here - medaase! Now it works like new.\nFast service, honest pricing. Highly recommend!"
    },
    {
        name: "Kwame Asante",
        rating: 5,
        text: "Gaming laptop repair was perfect. Quick turnaround too.\nThese guys know their work - top quality service!"
    },
    {
        name: "Ama Osei",
        rating: 4,
        text: "iPhone screen replacement done beautifully.\nAffordable prices, professional work. Very satisfied!"
    },
    {
        name: "Kofi Adjei",
        rating: 5,
        text: "Desktop computer was completely dead. Now e work fine!\nExcellent technical skills and fair pricing."
    },
    {
        name: "Adwoa Boateng",
        rating: 5,
        text: "Tablet repair service was outstanding - medaase paa!\nFriendly staff, quick service, reasonable rates."
    },
    {
        name: "Yaw Appiah",
        rating: 4,
        text: "Brought my retired Samsung phone for data recovery.\nThey saved everything! Professional and trustworthy."
    },
    {
        name: "Efua Donkor",
        rating: 5,
        text: "MacBook repair exceeded my expectations completely.\nHigh quality work, delivered exactly on time."
    },
    {
        name: "Nana Owusu",
        rating: 5,
        text: "Gaming console fix was perfect - saa na ɛyɛ!\nExpert technicians, competitive pricing, excellent service."
    },
    {
        name: "Abena Asare",
        rating: 4,
        text: "Old tablet brought back to life beautifully.\nGreat customer service and very reliable work."
    },
    {
        name: "Kweku Darko",
        rating: 5,
        text: "iPhone battery replacement done perfectly - ayekoo!\nFast, professional, and reasonably priced service."
    },
    {
        name: "Akua Frimpong",
        rating: 5,
        text: "Desktop setup and repair service was excellent.\nTechnical expertise is top-notch, highly recommend!"
    },
    {
        name: "Fiifi Annan",
        rating: 4,
        text: "Brought my old phone for screen repair.\nQuick service, good quality work, fair pricing."
    }
];

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('testimonial-carousel-container');
    if (container) {
        window.horizontalGallery = new HorizontalGallery(container, testimonials, {
            cardWidth: 320,
            cardHeight: 250,
            autoScrollSpeed: 2,
            cardSpacing: 30
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (window.horizontalGallery) {
                window.horizontalGallery.destroy();
            }
        });
    }
});