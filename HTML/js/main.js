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
});

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
