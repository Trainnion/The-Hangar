// Section 1: Navigation Smooth Scroll
document.querySelectorAll('.sliderNav button').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Section 3: Product Track Slider
function initProductSlider() {
    const track = document.getElementById('productTrackNR');
    const prevBtn = document.getElementById('prevBtnNR');
    const nextBtn = document.getElementById('nextBtnNR');

    if (!track || !prevBtn || !nextBtn) {
        console.warn('Section 3 slider elements missing.');
        return;
    }

    const cards = track.querySelectorAll('.productCard');
    if (cards.length === 0) return;

    let currentIndex = 0;

    function getStepWidth() {
        const firstCard = cards[0];
        // 384px (24rem) fallback if DOM width evaluates to 0
        const cardWidth = firstCard.getBoundingClientRect().width || 384; 
        
        // Dynamically fetch the CSS gap property
        const trackStyle = window.getComputedStyle(track);
        const gap = parseFloat(trackStyle.gap) || 24; 
        
        return cardWidth + gap;
    }

    function getMaxIndex() {
        const container = track.parentElement;
        if (!container) return 0;
        
        const step = getStepWidth();
        
        // Calculate the actual hidden overflow distance
        const maxScroll = track.scrollWidth - container.clientWidth;
        
        if (maxScroll <= 0) return 0;
        return Math.ceil(maxScroll / step);
    }

    function updateSlider() {
        const step = getStepWidth();
        const maxIndex = getMaxIndex();

        // Keep index within safety bounds
        if (currentIndex > maxIndex) currentIndex = maxIndex;
        if (currentIndex < 0) currentIndex = 0;

        // Apply slide animation
        track.style.transform = `translateX(-${currentIndex * step}px)`;

        // Toggle button states cleanly
        prevBtn.disabled = (currentIndex === 0);
        nextBtn.disabled = (currentIndex >= maxIndex && maxIndex > 0);
    }

    // Next click event
    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const maxIndex = getMaxIndex();
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });

    // Prev click event
    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });

    // Run layout calculations instantly
    updateSlider();
    window.addEventListener('resize', updateSlider);
}

// Initialize the slider immediately to guarantee buttons are clickable
initProductSlider();

// Force a recalculation once all external CSS and images finish loading
window.addEventListener('load', () => {
    window.dispatchEvent(new Event('resize'));
});

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

    // Set total count dynamically based on total cards
    if (totalCountEl) {
        totalCountEl.textContent = String(cards.length).padStart(2, '0');
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

        // Slide transform
        track.style.transform = `translateX(-${currentIndex * step}px)`;

        // Displays current leading card index starting at 01
        if (activeCountEl) {
            const currentItem = currentIndex + 1;
            activeCountEl.textContent = String(currentItem).padStart(2, '0');
        }

        // Toggle buttons
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

initModelKitsSlider();