class TestimonialCarousel {
    constructor(container, testimonials) {
        this.container = container;
        this.testimonials = testimonials;
        this.currentIndex = 0;
        this.isAnimating = false;
        this.autoScrollInterval = null;

        this.init();
    }

    init() {
        this.createCarousel();
        this.startAutoScroll();
    }

    createCarousel() {
        this.container.innerHTML = `
            <div class="testimonial-carousel">
                <div class="carousel-track">
                    ${this.testimonials.map((testimonial, index) => this.createTestimonialCard(testimonial, index)).join('')}
                </div>
            </div>
        `;

        this.track = this.container.querySelector('.carousel-track');
        this.cards = this.container.querySelectorAll('.testimonial-card');

        this.positionCards();
        this.setActiveCard(0);
    }

    createTestimonialCard(testimonial, index) {
        const stars = '★'.repeat(testimonial.rating) + '☆'.repeat(5 - testimonial.rating);

        return `
            <div class="testimonial-card" data-index="${index}">
                <div class="testimonial-content">
                    <div class="stars">${stars}</div>
                    <p class="testimonial-text">${testimonial.text.replace('\n', '<br>')}</p>
                    <div class="testimonial-author">- ${testimonial.name}</div>
                </div>
            </div>
        `;
    }

    positionCards() {
        const radius = 280;
        const centerX = 0;
        const centerY = 0;
        const total = this.cards.length;

        this.cards.forEach((card, index) => {
            const angle = (index / total) * 2 * Math.PI - Math.PI / 2;
            const x = centerX + radius * Math.cos(angle);
            const y = centerY + radius * Math.sin(angle);

            card.style.transform = `translate3d(${x}px, ${y}px, 0) rotateY(${angle + Math.PI / 2}rad)`;
            card.style.position = 'absolute';
        });
    }

    setActiveCard(index) {
        this.cards.forEach((card, i) => {
            card.classList.toggle('active', i === index);
            card.classList.toggle('visible', Math.abs(i - index) <= 2 || Math.abs(i - index) >= this.cards.length - 2);
        });
    }

    rotateToNext() {
        if (this.isAnimating) return;

        this.isAnimating = true;
        this.currentIndex = (this.currentIndex + 1) % this.testimonials.length;

        const rotationAngle = -(this.currentIndex * (360 / this.testimonials.length));
        this.track.style.transform = `rotateY(${rotationAngle}deg)`;

        this.setActiveCard(this.currentIndex);

        setTimeout(() => {
            this.isAnimating = false;
        }, 800);
    }

    startAutoScroll() {
        this.autoScrollInterval = setInterval(() => {
            this.rotateToNext();
        }, 4000);
    }

    stopAutoScroll() {
        if (this.autoScrollInterval) {
            clearInterval(this.autoScrollInterval);
            this.autoScrollInterval = null;
        }
    }

    destroy() {
        this.stopAutoScroll();
    }
}

// Testimonials data
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

// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const carouselContainer = document.getElementById('testimonial-carousel-container');
    if (carouselContainer) {
        const carousel = new TestimonialCarousel(carouselContainer, testimonials);

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            carousel.destroy();
        });
    }
});