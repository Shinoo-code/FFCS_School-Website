// Function to filter activity cards based on category
function filterCards(category) {
  const cards = document.querySelectorAll('.activity-card');
  cards.forEach(card => {
    if (category === 'all' || card.dataset.category === category) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}
window.filterCards = filterCards; // Make it globally accessible if called from HTML onclick

// Initialize AOS (Animate On Scroll)
if (typeof AOS !== 'undefined') {
  AOS.init({
    duration: 1000,
    once: true
  });
}

// Back to Top Button Functionality has been removed.

// Parent's Feedback Carousel Functionality
document.addEventListener('DOMContentLoaded', function () {
    const feedbackTrack = document.querySelector('.feedback-slider-track');
    const prevButton = document.querySelector('.feedback-prev-btn');
    const nextButton = document.querySelector('.feedback-next-btn');
    const feedbackItems = document.querySelectorAll('.feedback-card-item');

    // Early exit if essential carousel elements are missing or no items
    if (!feedbackTrack || feedbackItems.length === 0) {
        console.log('Feedback carousel track or items not found. Carousel not initialized.');
        if (prevButton) prevButton.style.display = 'none';
        if (nextButton) nextButton.style.display = 'none';
        return;
    }

    // If buttons are specifically missing but track and items exist, log it.
    if (!prevButton || !nextButton) {
        console.warn('Feedback carousel navigation buttons not found. Carousel will display but not navigate.');
        // If one button exists but not the other, hide the one that exists.
        if (prevButton) prevButton.style.display = 'none';
        if (nextButton) nextButton.style.display = 'none';
    }

    let currentIndex = 0;
    const totalItems = feedbackItems.length;
    let itemsPerPage = 3; // Default for desktop

    function updateItemsPerPage() {
        if (window.innerWidth <= 767.98) { // Small devices (e.g., phones)
            itemsPerPage = 1;
        } else if (window.innerWidth <= 991.98) { // Medium devices (e.g., tablets)
            itemsPerPage = 2;
        } else { // Large devices (desktops)
            itemsPerPage = 3;
        }
    }

    function updateCarouselState() {
        if (!feedbackTrack || feedbackItems.length === 0) return; // Should have been caught earlier, but good for safety

        const itemWidth = feedbackItems[0].offsetWidth;
        if (itemWidth === 0 && feedbackItems.length > 0) {
            // This can happen if the carousel is initially hidden (e.g. display:none on a parent)
            // and offsetWidth is calculated before it's visible.
            // A more robust solution might involve MutationObserver or ensuring it's visible before init.
            // For now, we'll log and proceed, hoping it becomes visible.
            console.warn("Feedback item width is 0. Carousel might not display correctly if hidden initially.");
        }
        const newTransformValue = -currentIndex * itemWidth;
        feedbackTrack.style.transform = `translateX(${newTransformValue}px)`;

        // Update button visibility and disabled state
        if (prevButton) {
            if (totalItems > itemsPerPage && currentIndex > 0) {
                prevButton.style.display = 'flex'; // Use 'flex' as per CSS
                prevButton.disabled = false;
            } else {
                prevButton.style.display = 'none';
                prevButton.disabled = true;
            }
        }

        if (nextButton) {
            if (totalItems > itemsPerPage && currentIndex < (totalItems - itemsPerPage)) {
                nextButton.style.display = 'flex'; // Use 'flex' as per CSS
                nextButton.disabled = false;
            } else {
                nextButton.style.display = 'none';
                nextButton.disabled = true;
            }
        }
    }

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            if (currentIndex < totalItems - itemsPerPage) {
                currentIndex++;
                updateCarouselState();
            }
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarouselState();
            }
        });
    }

    window.addEventListener('resize', () => {
        updateItemsPerPage();
        // Adjust currentIndex if it becomes out of bounds after resize
        if (currentIndex > totalItems - itemsPerPage) {
            currentIndex = Math.max(0, totalItems - itemsPerPage);
        }
        // If totalItems becomes less than or equal to itemsPerPage, reset index
        if (totalItems <= itemsPerPage) {
            currentIndex = 0;
        }
        updateCarouselState();
    });

    // Initial setup
    updateItemsPerPage();
    updateCarouselState(); // Call this to set initial button states and position
});

// Announcements carousel (scrolling viewport approach - more robust)
document.addEventListener('DOMContentLoaded', function () {
    const viewport = document.querySelector('.announcements-viewport');
    const items = document.querySelectorAll('.announcement-item');
    const btnPrev = document.querySelector('.announce-prev');
    const btnNext = document.querySelector('.announce-next');

    if (!viewport || items.length === 0) {
        if (btnPrev) btnPrev.style.display = 'none';
        if (btnNext) btnNext.style.display = 'none';
        return;
    }

    const gap = parseFloat(getComputedStyle(document.querySelector('.announcements-track')).gap) || 0;

    function itemScrollAmount() {
        const w = items[0].offsetWidth || items[0].getBoundingClientRect().width || viewport.clientWidth;
        return Math.max(1, Math.round(w + gap));
    }

    function updateButtons() {
        if (!btnPrev || !btnNext) return;
        // show prev when scrolled > 5px
        btnPrev.style.display = (viewport.scrollLeft > 5) ? 'flex' : 'none';
        // show next when there's more to scroll
        btnNext.style.display = (viewport.scrollLeft + viewport.clientWidth + 5 < viewport.scrollWidth) ? 'flex' : 'none';
    }

    btnNext && btnNext.addEventListener('click', function () {
        viewport.scrollBy({ left: itemScrollAmount(), behavior: 'smooth' });
    });
    btnPrev && btnPrev.addEventListener('click', function () {
        viewport.scrollBy({ left: -itemScrollAmount(), behavior: 'smooth' });
    });

    viewport.addEventListener('scroll', updateButtons);
    window.addEventListener('resize', function () {
        // small delay to allow layout to settle
        setTimeout(updateButtons, 80);
    });

    // initial
    setTimeout(updateButtons, 50);
});