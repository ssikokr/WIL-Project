/**
 * REAL HOMES LUXURY PROPERTIES - CORE CLIENT SCRIPT
 * Manages Navigation, Modals, LocalStorage Favorites, Currency Formatting
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('show');
            const isOpen = navMenu.classList.contains('show');
            mobileToggle.innerHTML = isOpen ? '&#10005;' : '&#9776;';
        });
    }

    // 2. Active Navigation Item Highlight
    const currentPath = window.location.pathname.split('/').pop() || 'HomePage.html';
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentPath || href.endsWith(currentPath))) {
            link.classList.add('active');
        } else if ((currentPath === '' || currentPath === 'index.html' || currentPath === 'HomePage.html') && href && href.includes('HomePage.html')) {
            link.classList.add('active');
        }
    });

    // 3. Initialize Favorites from localStorage
    initFavorites();

    // Keep the seller consultation available from the shared site navigation.
    initSellEnquiryModal();
});

function initSellEnquiryModal() {
    if (!document.querySelector('[data-open-sell-modal]')) return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="sell-modal-backdrop" id="sellEnquiryModal" role="dialog" aria-modal="true" aria-labelledby="sellModalTitle" aria-hidden="true">
            <section class="sell-modal">
                <header class="sell-modal-header">
                    <div>
                        <span class="badge badge-teal">Sell with confidence</span>
                        <h2 id="sellModalTitle">Request a selling consultation</h2>
                        <p>Tell us a little about your property and our team will be in touch.</p>
                    </div>
                    <button class="sell-modal-close" type="button" data-close-sell-modal aria-label="Close selling consultation">&times;</button>
                </header>
                <form class="sell-modal-form" action="../PHP/contactUs.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="sellModalName">Your name</label>
                            <input class="form-control" id="sellModalName" name="sellerName" type="text" autocomplete="name" maxlength="120" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sellModalEmail">Email address</label>
                            <input class="form-control" id="sellModalEmail" name="email" type="email" autocomplete="email" maxlength="254" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="sellModalPhone">Phone number</label>
                            <input class="form-control" id="sellModalPhone" name="phone" type="tel" autocomplete="tel" maxlength="40" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sellModalAddress">Property suburb or address</label>
                            <input class="form-control" id="sellModalAddress" name="propertyAddress" type="text" maxlength="180" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sellModalDetails">Property details and preferred selling timeframe</label>
                        <textarea class="form-control" id="sellModalDetails" name="propertyDetails" rows="4" maxlength="5000" required></textarea>
                    </div>
                    <input type="hidden" name="message" value="">
                    <p class="sell-modal-feedback" role="status" aria-live="polite" hidden></p>
                    <div class="sell-modal-actions">
                        <button class="btn btn-outline" type="button" data-close-sell-modal>Cancel</button>
                        <button class="btn btn-gold" type="submit">Request Consultation</button>
                    </div>
                </form>
            </section>
        </div>`;
    const backdrop = wrapper.firstElementChild;
    document.body.appendChild(backdrop);

    const form = backdrop.querySelector('form');
    const feedback = backdrop.querySelector('.sell-modal-feedback');
    const submitButton = form.querySelector('button[type="submit"]');
    let lastTrigger = null;

    function closeModal() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sell-modal-open');
        if (lastTrigger) lastTrigger.focus();
    }

    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        const trigger = event.target.closest('[data-open-sell-modal]');
        if (trigger) {
            event.preventDefault();
            lastTrigger = trigger;
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sell-modal-open');
            const navMenu = trigger.closest('.nav-menu');
            if (navMenu && navMenu.classList.contains('show')) {
                navMenu.classList.remove('show');
                const mobileToggle = document.querySelector('.mobile-toggle');
                if (mobileToggle) mobileToggle.innerHTML = '&#9776;';
            }
            form.querySelector('input').focus();
            return;
        }

        if (event.target.closest('[data-close-sell-modal]')) closeModal();
    });

    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) closeModal();
    });

    document.addEventListener('keydown', event => {
        if (!backdrop.classList.contains('is-open')) return;
        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key === 'Tab') {
            const focusable = Array.from(backdrop.querySelectorAll('button, input, textarea'))
                .filter(element => !element.disabled);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        feedback.hidden = true;
        feedback.textContent = '';
        const values = new FormData(form);
        values.set('message', [
            `Property seller: ${values.get('sellerName')}`,
            `Phone: ${values.get('phone')}`,
            `Property suburb or address: ${values.get('propertyAddress')}`,
            '',
            'Property details and preferred selling timeframe:',
            values.get('propertyDetails')
        ].join('\n'));

        submitButton.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: values
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'The consultation request could not be sent.');
            }

            form.reset();
            closeModal();
            showToast('Your selling consultation request has been sent.');
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Please try again.';
            console.error('Selling consultation request failed:', error);
            feedback.textContent = message;
            feedback.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    });
}

// Helper for formatting ZAR Currency
function formatZAR(amount) {
    if (!amount) return 'R 0';
    return 'R ' + Number(amount).toLocaleString('en-ZA');
}

// Favorites Management
function getFavorites() {
    try {
        return JSON.parse(localStorage.getItem('realhomes_favorites') || '[]');
    } catch (e) {
        return [];
    }
}

function toggleFavorite(propertyId, btnElement) {
    let favs = getFavorites();
    const index = favs.indexOf(propertyId);
    
    if (index > -1) {
        favs.splice(index, 1);
        if (btnElement) btnElement.classList.remove('active');
        showToast('Removed from saved properties');
    } else {
        favs.push(propertyId);
        if (btnElement) btnElement.classList.add('active');
        showToast('Property saved to your favorites!');
    }
    
    localStorage.setItem('realhomes_favorites', JSON.stringify(favs));
    updateFavoritesBadge();
}

function initFavorites() {
    const favs = getFavorites();
    document.querySelectorAll('.property-fav-btn').forEach(btn => {
        const id = parseInt(btn.getAttribute('data-id'));
        if (favs.includes(id)) {
            btn.classList.add('active');
        }
    });
    updateFavoritesBadge();
}

function updateFavoritesBadge() {
    const favs = getFavorites();
    const badges = document.querySelectorAll('.fav-count-badge');
    badges.forEach(b => {
        b.textContent = favs.length;
        b.style.display = favs.length > 0 ? 'inline-flex' : 'none';
    });
}

// Toast Notification
function showToast(message, type = 'success') {
    let toast = document.getElementById('site-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'site-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0f172a;
            color: #ffffff;
            padding: 14px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            font-size: 14.5px;
            font-weight: 600;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #b89562;
            transition: all 0.3s ease;
            transform: translateY(100px);
            opacity: 0;
        `;
        document.body.appendChild(toast);
    }
    
    toast.innerHTML = `<span>&#10003;</span> ${message}`;
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
    
    setTimeout(() => {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
    }, 3200);
}
