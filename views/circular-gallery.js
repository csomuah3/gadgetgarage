class CircularGallery {
    constructor(container, testimonials, options = {}) {
        this.container = container;
        this.testimonials = testimonials;
        this.options = {
            radius: 300,
            cardWidth: 280,
            cardHeight: 200,
            autoRotateSpeed: 0.5,
            perspective: 1000,
            bend: 0.3,
            ...options
        };

        this.currentRotation = 0;
        this.cards = [];
        this.isDestroyed = false;

        this.init();
    }

    init() {
        this.createStructure();
        this.createCards();
        this.positionCards();
        this.startAutoRotation();
        this.addEventListeners();
    }

    createStructure() {
        this.container.innerHTML = `
            <div class="circular-gallery-wrapper">
                <div class="circular-gallery-container">
                    <div class="circular-gallery-track">
                        <!-- Cards will be inserted here -->
                    </div>
                </div>
            </div>
        `;

        this.wrapper = this.container.querySelector('.circular-gallery-wrapper');
        this.galleryContainer = this.container.querySelector('.circular-gallery-container');
        this.track = this.container.querySelector('.circular-gallery-track');

        // Set up CSS 3D perspective
        this.wrapper.style.perspective = `${this.options.perspective}px`;
        this.wrapper.style.perspectiveOrigin = '50% 50%';
        this.galleryContainer.style.transformStyle = 'preserve-3d';
        this.track.style.transformStyle = 'preserve-3d';
    }

    createCards() {
        this.testimonials.forEach((testimonial, index) => {
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
    }

    positionCards() {
        const totalCards = this.cards.length;
        const angleStep = 360 / totalCards;

        this.cards.forEach((card, index) => {
            const angle = index * angleStep + this.currentRotation;
            const radians = (angle * Math.PI) / 180;

            // Calculate 3D position
            const x = Math.sin(radians) * this.options.radius;
            const z = Math.cos(radians) * this.options.radius;
            const y = Math.sin(radians * 2) * this.options.radius * this.options.bend;

            // Calculate scale and opacity based on z position
            const distanceFromFront = (z + this.options.radius) / (this.options.radius * 2);
            const scale = 0.7 + (distanceFromFront * 0.4);
            const opacity = 0.4 + (distanceFromFront * 0.6);

            // Apply transform
            card.style.transform = `
                translate3d(${x}px, ${y}px, ${z}px)
                rotateY(${-angle}deg)
                scale(${scale})
            `;

            card.style.opacity = opacity;
            card.style.zIndex = Math.round(distanceFromFront * 100);

            // Add active class to front cards
            if (distanceFromFront > 0.7) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
    }

    startAutoRotation() {
        const animate = () => {
            if (this.isDestroyed) return;

            this.currentRotation += this.options.autoRotateSpeed;
            if (this.currentRotation >= 360) {
                this.currentRotation -= 360;
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
        // Adjust radius based on container size
        const containerWidth = this.container.offsetWidth;
        const containerHeight = this.container.offsetHeight;

        if (containerWidth < 768) {
            this.options.radius = Math.min(containerWidth * 0.35, 200);
            this.options.cardWidth = 240;
            this.options.cardHeight = 160;
        } else if (containerWidth < 1200) {
            this.options.radius = Math.min(containerWidth * 0.3, 250);
            this.options.cardWidth = 260;
            this.options.cardHeight = 180;
        } else {
            this.options.radius = Math.min(containerWidth * 0.25, 300);
            this.options.cardWidth = 280;
            this.options.cardHeight = 200;
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
        window.circularGallery = new CircularGallery(container, testimonials, {
            radius: 300,
            autoRotateSpeed: 0.8,
            bend: 0.2,
            perspective: 1200
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (window.circularGallery) {
                window.circularGallery.destroy();
            }
        });
    }
});