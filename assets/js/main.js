// assets/js/main.js
// Placeholder for client-side enhancements (e.g., live total calculation).

document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll(".menu-filters button");
    const cards = document.querySelectorAll(".menu-card");

    if (buttons.length > 0 && cards.length > 0) {
        buttons.forEach(button => {
            button.addEventListener("click", () => {
                const filter = button.dataset.filter;

                // Show/hide cards
                cards.forEach(card => {
                    card.style.display = (filter === "all" || card.dataset.category === filter) ? "block" : "none";
                });

                // Highlight active button
                buttons.forEach(btn => {
                    btn.classList.remove("active");
                    btn.setAttribute("aria-pressed", "false");
                });
                button.classList.add("active");
                button.setAttribute("aria-pressed", "true");
            });
        });

        // Initialize aria-pressed
        buttons.forEach(btn => btn.setAttribute("aria-pressed", "false"));
    }



    // Reservation date and time (only if fields exist on this page)
    const dateInput = document.getElementById("reservation_date");
    const timeInput = document.getElementById("reservation_time");

    if (dateInput && timeInput) {
        const openTime = "11:00";
        const closeTime = "21:00";

        function updateTimeLimits() {
            const today = new Date().toISOString().split("T")[0];
            const selectedDate = dateInput.value;

            timeInput.min = openTime;
            timeInput.max = closeTime;

            // If user selects today's date → block past times
            if (selectedDate === today) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, "0");
                const minutes = String(now.getMinutes()).padStart(2, "0");
                const currentTime = `${hours}:${minutes}`;

                if (currentTime > openTime) {
                    timeInput.min = currentTime;
                }
            }
        }

        dateInput.addEventListener("change", updateTimeLimits);
        updateTimeLimits();
    }

    // Payment Page: Card input formatting & live validation
    const cardNumberInput = document.querySelector('input[name="card_number"]');
    const expiryInput = document.querySelector('input[name="expiry"]');
    const cvvInput = document.querySelector('input[name="cvv"]');

    if (cardNumberInput) {
        // Auto-space every 4 digits
        cardNumberInput.addEventListener('input', e => {
            let val = e.target.value.replace(/\D/g, '').slice(0, 16);
            e.target.value = val.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    if (expiryInput) {
        // Auto-insert slash MM/YY
        expiryInput.addEventListener('input', e => {
            let val = e.target.value.replace(/\D/g, '').slice(0, 4);
            if (val.length >= 3) {
                e.target.value = val.slice(0, 2) + '/' + val.slice(2);
            } else {
                e.target.value = val;
            }
        });
    }

    if (cvvInput) {
        // Only allow digits, max 3
        cvvInput.addEventListener('input', e => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    }

    // Promo Sliders (Enhanced)
    const promoSliders = document.querySelectorAll('[id^="promoSlider-"]');

    promoSliders.forEach(slider => {

        const slides = slider.querySelectorAll('.promo-slide');
        const section = slider.closest('.promo-section');

        const prevBtn = section.querySelector('.promo-prev');
        const nextBtn = section.querySelector('.promo-next');

        let currentIndex = 0;
        let autoRotate = null;
        let isAnimating = false;

        

        const updateSlide = (newIndex, direction = 'next') => {

            if (isAnimating) return;
            isAnimating = true;

            slides.forEach((slide, i) => {
                slide.classList.remove('active', 'prev', 'next');

                if (i === currentIndex) {
                    slide.classList.add(direction === 'next' ? 'prev' : 'next');
                }

                if (i === newIndex) {
                    slide.classList.add('active');
                }
            });

            currentIndex = newIndex;

            setTimeout(() => {
                isAnimating = false;
            }, 700); // match CSS transition
        };

        const goNext = () => {
            updateSlide((currentIndex + 1) % slides.length, 'next');
        };

        const goPrev = () => {
            updateSlide((currentIndex - 1 + slides.length) % slides.length, 'prev');
        };

        const stopAutoRotate = () => {
            if (autoRotate) {
                clearInterval(autoRotate);
                autoRotate = null;
            }
        };

        const startAutoRotate = () => {

            if (slides.length <= 1) return;

            stopAutoRotate();

            autoRotate = setInterval(() => {
                goNext();
            }, 6000); // slightly faster
        };

        // Button controls
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                goNext();
                startAutoRotate();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                goPrev();
                startAutoRotate();
            });
        }

        // Pause on hover
        slider.addEventListener('mouseenter', stopAutoRotate);
        slider.addEventListener('mouseleave', startAutoRotate);

        // 🔥 Improved swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        slider.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        slider.addEventListener('touchend', e => {

            touchEndX = e.changedTouches[0].screenX;
            const distance = touchEndX - touchStartX;

            if (Math.abs(distance) > 60) {
                if (distance > 0) {
                    goPrev();
                } else {
                    goNext();
                }
                startAutoRotate();
            }
        });

        // Init
        if (slides.length > 0) {
            updateSlide(0);
            startAutoRotate();
        }

    });


    // Countdown timers
    const countdowns = document.querySelectorAll('.promo-countdown');

    countdowns.forEach(el => {

        const endTime = new Date(el.dataset.end).getTime();

        const timer = setInterval(() => {

            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance <= 0) {
                el.innerHTML = "Expired";
                clearInterval(timer);
                return;
            }

            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            el.innerHTML = `Ends in: ${hours}h ${minutes}m ${seconds}s`;

        }, 1000);

    });


    // Pause sliders when tab not visible
    document.addEventListener("visibilitychange", () => {

        const sliders = document.querySelectorAll('[id^="promoSlider-"]');

        sliders.forEach(slider => {

            const event = document.hidden ? "mouseenter" : "mouseleave";

            slider.dispatchEvent(new Event(event));

        });

    });

});

