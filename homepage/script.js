// Section 1: Hero Carousel & Counter Sync
function initHeroSlider() {
    const slider = document.querySelector('.promotionalContainer .slider');
    const slides = document.querySelectorAll('.promotionalContainer .slide');
    const navBtns = document.querySelectorAll('.sliderNav button');
    const activeNumEl = document.getElementById('heroActiveNumSec1');
    const totalNumEl = document.getElementById('heroTotalNumSec1');

    if (!slider || slides.length === 0) return;

    if (totalNumEl) {
        totalNumEl.textContent = String(slides.length).padStart(2, '0');
    }

    function setActiveSlide(index) {
        if (index < 0 || index >= slides.length) return;

        slides[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });

        navBtns.forEach((btn, i) => {
            btn.classList.toggle('active', i === index);
        });

        if (activeNumEl) {
            activeNumEl.textContent = String(index + 1).padStart(2, '0');
        }
    }

    navBtns.forEach((btn, index) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            setActiveSlide(index);
        });
    });

    // Sync on manual scroll
    slider.addEventListener('scroll', () => {
        const slideWidth = slider.clientWidth;
        const scrollLeft = slider.scrollLeft;
        const currentIndex = Math.round(scrollLeft / slideWidth);

        navBtns.forEach((btn, i) => {
            btn.classList.toggle('active', i === currentIndex);
        });

        if (activeNumEl) {
            activeNumEl.textContent = String(currentIndex + 1).padStart(2, '0');
        }
    });
}
initHeroSlider();

// Section 2: Reprint Automatic Carousel & Timer Slap Progress Bar
function initReprintAutoSlider() {
    const track = document.getElementById('heroTrackSec2');
    const slides = document.querySelectorAll('.heroSlideSec2');
    const dashes = document.querySelectorAll('.dashSec2');
    const timerFills = document.querySelectorAll('.heroTimerFillSec2');

    if (!track || slides.length === 0) return;

    // Start on slide 1 (index 0)
    let currentIndex = 0;
    const duration = 5000; // 5 seconds per slide
    const intervalTick = 50;
    let elapsed = 0;
    let timerId = null;

    let isDragging = false;
    let startPos = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID = 0;

    function goToSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        currentTranslate = currentIndex * -window.innerWidth;
        prevTranslate = currentTranslate;
        
        track.style.transition = 'transform 0.45s cubic-bezier(0.25, 1, 0.5, 1)';
        track.style.transform = `translateX(${currentTranslate}px)`;

        dashes.forEach((dash, i) => {
            dash.classList.toggle('activeDashSec2', i === currentIndex);
        });

        // Reset progress bar
        elapsed = 0;
        timerFills.forEach(fill => {
            fill.style.width = '0%';
            fill.style.transition = 'none'; // Snap back
        });
        
        // Restore transition after snap
        requestAnimationFrame(() => {
            timerFills.forEach(fill => {
                fill.style.transition = 'width 0.05s linear';
            });
        });
    }

    function startTimer() {
        if (timerId) clearInterval(timerId);

        timerId = setInterval(() => {
            if (isDragging) return; // Pause timer while dragging

            elapsed += intervalTick;
            const progress = Math.min((elapsed / duration) * 100, 100);

            timerFills.forEach(fill => {
                fill.style.width = `${progress}%`;
            });

            if (elapsed >= duration) {
                goToSlide(currentIndex + 1);
            }
        }, intervalTick);
    }

    // Set initial position
    goToSlide(currentIndex);
    startTimer();

    // Dash click interaction
    dashes.forEach((dash, idx) => {
        dash.style.cursor = 'pointer';
        dash.addEventListener('click', () => {
            goToSlide(idx);
            startTimer();
        });
    });

    // --- Drag and Swipe Functionality ---

    function touchStart(index) {
        return function (event) {
            isDragging = true;
            startPos = getPositionX(event);
            animationID = requestAnimationFrame(animation);
            track.style.transition = 'none'; // Disable transition for real-time drag
            track.style.cursor = 'grabbing';
            if (timerId) clearInterval(timerId); // Pause auto-advance
        }
    }

    function touchMove(event) {
        if (isDragging) {
            const currentPosition = getPositionX(event);
            const diff = currentPosition - startPos;
            // Add resistance at the edges
            if ((currentIndex === 0 && diff > 0) || (currentIndex === slides.length - 1 && diff < 0)) {
                currentTranslate = prevTranslate + diff * 0.3;
            } else {
                currentTranslate = prevTranslate + diff;
            }
        }
    }

    function touchEnd() {
        isDragging = false;
        cancelAnimationFrame(animationID);
        track.style.cursor = 'grab';

        const movedBy = currentTranslate - prevTranslate;
        const threshold = window.innerWidth * 0.15; // 15% of screen width

        if (movedBy < -threshold && currentIndex < slides.length - 1) currentIndex += 1;
        if (movedBy > threshold && currentIndex > 0) currentIndex -= 1;

        goToSlide(currentIndex);
        startTimer(); // Resume auto-advance
    }

    function getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }

    function animation() {
        track.style.transform = `translateX(${currentTranslate}px)`;
        if (isDragging) requestAnimationFrame(animation);
    }

    // Attach drag events to the carousel container
    const carouselContainer = document.querySelector('.heroCarouselSec2');
    if (carouselContainer) {
        track.style.cursor = 'grab';
        
        // Touch events
        carouselContainer.addEventListener('touchstart', touchStart(currentIndex), { passive: true });
        carouselContainer.addEventListener('touchmove', touchMove, { passive: true });
        carouselContainer.addEventListener('touchend', touchEnd);
        
        // Mouse events
        carouselContainer.addEventListener('mousedown', touchStart(currentIndex));
        carouselContainer.addEventListener('mousemove', touchMove);
        carouselContainer.addEventListener('mouseup', touchEnd);
        carouselContainer.addEventListener('mouseleave', () => {
            if (isDragging) touchEnd();
        });
    }

    // Handle window resize
    window.addEventListener('resize', () => {
        goToSlide(currentIndex);
    });
}
initReprintAutoSlider();

// Section 3: Product Track Slider
function initProductSlider() {
    const track = document.getElementById('productTrackNR');
    const prevBtn = document.getElementById('prevBtnNR');
    const nextBtn = document.getElementById('nextBtnNR');
    const activeCountEl = document.getElementById('activeCountNR');
    const totalCountEl = document.getElementById('totalCountNR');

    if (!track || !prevBtn || !nextBtn) {
        console.warn('Section 3 slider elements missing.');
        return;
    }

    const cards = track.querySelectorAll('.productCard');
    if (cards.length === 0) return;

    let currentIndex = 0;
    const totalCardsCount = cards.length;

    if (totalCountEl) {
        totalCountEl.textContent = String(totalCardsCount).padStart(2, '0');
    }

    function getStepWidth() {
        const firstCard = cards[0];
        const cardWidth = firstCard.getBoundingClientRect().width || 384; 
        const trackStyle = window.getComputedStyle(track);
        const gap = parseFloat(trackStyle.gap) || 24; 
        return cardWidth + gap;
    }

    function getMaxIndex() {
        const container = track.parentElement;
        if (!container) return 0;
        const step = getStepWidth();
        const maxScroll = track.scrollWidth - container.clientWidth;
        if (maxScroll <= 0) return 0;
        return Math.ceil(maxScroll / step);
    }

    function updateSlider() {
        const step = getStepWidth();
        const maxIndex = getMaxIndex();

        if (currentIndex > maxIndex) currentIndex = maxIndex;
        if (currentIndex < 0) currentIndex = 0;

        track.style.transform = `translateX(-${currentIndex * step}px)`;

        if (activeCountEl) {
            const displayedCount = Math.min(currentIndex + 4, totalCardsCount);
            activeCountEl.textContent = String(displayedCount).padStart(2, '0');
        }

        prevBtn.disabled = (currentIndex === 0);
        nextBtn.disabled = (currentIndex >= maxIndex && maxIndex > 0);
    }

    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const maxIndex = getMaxIndex();
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });

    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });

    updateSlider();
    window.addEventListener('resize', updateSlider);
}

initProductSlider();

// Section 6: Model Kits Slider & Filter Tabs
function initModelKitsSlider() {
    const track = document.getElementById('productTrackMK');
    const prevBtn = document.getElementById('prevBtnMK');
    const nextBtn = document.getElementById('nextBtnMK');
    const activeCountEl = document.getElementById('activeCountMK');
    const totalCountEl = document.getElementById('totalCountMK');

    if (!track || !prevBtn || !nextBtn) return;

    const cards = track.querySelectorAll('.productCard');
    if (cards.length === 0) return;

    let currentIndex = 0;
    const totalCount = cards.length;

    if (totalCountEl) {
        totalCountEl.textContent = String(totalCount).padStart(2, '0');
    }

    function getStepWidth() {
        const firstCard = cards[0];
        const cardWidth = firstCard.getBoundingClientRect().width || 350; 
        const trackStyle = window.getComputedStyle(track);
        const gap = parseFloat(trackStyle.gap) || 28; 
        return cardWidth + gap;
    }

    function getMaxIndex() {
        const container = track.parentElement;
        if (!container) return 0;
        const step = getStepWidth();
        const maxScroll = track.scrollWidth - container.clientWidth;
        if (maxScroll <= 0) return 0;
        return Math.ceil(maxScroll / step);
    }

    function updateSlider() {
        const step = getStepWidth();
        const maxIndex = getMaxIndex();

        if (currentIndex > maxIndex) currentIndex = maxIndex;
        if (currentIndex < 0) currentIndex = 0;

        track.style.transform = `translateX(-${currentIndex * step}px)`;

        if (activeCountEl) {
            const displayedItem = Math.min(currentIndex + 4, totalCount);
            activeCountEl.textContent = String(displayedItem).padStart(2, '0');
        }

        prevBtn.disabled = (currentIndex === 0);
        nextBtn.disabled = (currentIndex >= maxIndex && maxIndex > 0);
    }

    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const maxIndex = getMaxIndex();
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });

    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });

    updateSlider();
    window.addEventListener('resize', updateSlider);

    // Filter Buttons (popular, latest, Top sales)
    const filterBtns = document.querySelectorAll('.filterBtnMK');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Grade Select dropdown
    const gradeSelect = document.querySelector('.filterSelectMK');
    if (gradeSelect) {
        gradeSelect.addEventListener('change', (e) => {
            const selectedGrade = e.target.value.toLowerCase();
            cards.forEach(card => {
                const title = card.querySelector('.productTitle')?.textContent.toLowerCase() || '';
                if (!selectedGrade || title.includes(selectedGrade)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            currentIndex = 0;
            updateSlider();
        });
    }
}

initModelKitsSlider();

// Section 7: Top Banner Automatic Carousel with Timer Bar & Swipe
function initFeaturedTopSlider() {
    const sliderContainer = document.getElementById('featuredTopSlider');
    const track = document.getElementById('featuredTopTrack');
    const timerFill = document.getElementById('featuredTimerFill');

    if (!sliderContainer || !track) return;

    const slides = track.querySelectorAll('.topBannerSlideFT');
    if (slides.length === 0) return;

    let currentIndex = 0;
    const duration = 5000; // 5 seconds per banner
    const intervalTick = 50;
    let elapsed = 0;
    let timerId = null;

    let isDragging = false;
    let startPos = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID = 0;

    function getSlideWidth() {
        return sliderContainer.clientWidth || window.innerWidth;
    }

    function goToSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        const slideWidth = getSlideWidth();
        currentTranslate = currentIndex * -slideWidth;
        prevTranslate = currentTranslate;

        track.style.transition = 'transform 0.45s cubic-bezier(0.25, 1, 0.5, 1)';
        track.style.transform = `translateX(${currentTranslate}px)`;

        // Reset progress bar
        elapsed = 0;
        if (timerFill) {
            timerFill.style.transition = 'none';
            timerFill.style.width = '0%';
            requestAnimationFrame(() => {
                timerFill.style.transition = 'width 0.05s linear';
            });
        }
    }

    function startTimer() {
        if (timerId) clearInterval(timerId);

        timerId = setInterval(() => {
            if (isDragging) return;

            elapsed += intervalTick;
            const progress = Math.min((elapsed / duration) * 100, 100);

            if (timerFill) {
                timerFill.style.width = `${progress}%`;
            }

            if (elapsed >= duration) {
                goToSlide(currentIndex + 1);
            }
        }, intervalTick);
    }

    goToSlide(currentIndex);
    startTimer();

    // Drag / Swipe Gestures
    function touchStart(event) {
        isDragging = true;
        startPos = getPositionX(event);
        animationID = requestAnimationFrame(animation);
        track.style.transition = 'none';
        track.style.cursor = 'grabbing';
        if (timerId) clearInterval(timerId);
    }

    function touchMove(event) {
        if (!isDragging) return;
        const currentPosition = getPositionX(event);
        const diff = currentPosition - startPos;
        const slideWidth = getSlideWidth();

        if ((currentIndex === 0 && diff > 0) || (currentIndex === slides.length - 1 && diff < 0)) {
            currentTranslate = prevTranslate + diff * 0.3;
        } else {
            currentTranslate = prevTranslate + diff;
        }
    }

    function touchEnd() {
        if (!isDragging) return;
        isDragging = false;
        cancelAnimationFrame(animationID);
        track.style.cursor = 'grab';

        const movedBy = currentTranslate - prevTranslate;
        const threshold = getSlideWidth() * 0.15;

        if (movedBy < -threshold && currentIndex < slides.length - 1) {
            currentIndex += 1;
        } else if (movedBy > threshold && currentIndex > 0) {
            currentIndex -= 1;
        }

        goToSlide(currentIndex);
        startTimer();
    }

    function getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }

    function animation() {
        track.style.transform = `translateX(${currentTranslate}px)`;
        if (isDragging) requestAnimationFrame(animation);
    }

    track.style.cursor = 'grab';

    // Touch events
    sliderContainer.addEventListener('touchstart', touchStart, { passive: true });
    sliderContainer.addEventListener('touchmove', touchMove, { passive: true });
    sliderContainer.addEventListener('touchend', touchEnd);

    // Mouse events
    sliderContainer.addEventListener('mousedown', touchStart);
    sliderContainer.addEventListener('mousemove', touchMove);
    sliderContainer.addEventListener('mouseup', touchEnd);
    sliderContainer.addEventListener('mouseleave', () => {
        if (isDragging) touchEnd();
    });

    window.addEventListener('resize', () => {
        goToSlide(currentIndex);
    });
}
initFeaturedTopSlider();

window.addEventListener('load', () => {
    window.dispatchEvent(new Event('resize'));
});