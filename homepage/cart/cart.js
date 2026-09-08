// THE HANGAR - GUND-ORDER SYSTEM
// REAL-TIME CART & SUPPLY MANIFEST CONTROLLER (cart.js)
// Drawer pattern retired; direct full-page cart interaction with Section 6 & 7 design language.

(function() {
    'use strict';

    // Per-account cart isolation: localStorage is browser-wide (shared by every
    // account on this browser), so keys are namespaced per logged-in user id.
    // window.HANGAR_USER_ID is emitted by cart_modal.php (0 = guest bucket).
    const cartUid = (window.HANGAR_USER_ID && Number(window.HANGAR_USER_ID) > 0)
        ? Number(window.HANGAR_USER_ID)
        : 'guest';
    const STORAGE_KEY = 'hangar_cart_manifest_u' + cartUid;
    const PROMO_KEY = 'hangar_cart_promo_u' + cartUid;
    const PROMO_META_KEY = 'hangar_cart_promo_meta_u' + cartUid;

    // Path config — set via window.HANGAR_PATHS by the hosting PHP page
    const PATHS = Object.assign({
        cartPage:       'cart.php',
        searchPage:     '../search/search.php',
        productDetails: '../product-details.php',
        apiCheckout:    'api_checkout.php'
    }, window.HANGAR_PATHS || {});

    // Defensive image-path normalisation: ensure only a bare filename (or an
    // already-absolute http(s) URL) is ever used for thumbnails. This repairs
    // stale localStorage entries that may already carry a path prefix (e.g.
    // "promotional/xyz.webp"), which previously produced doubled/broken paths.
    function normalizeImageFilename(value) {
        if (!value || typeof value !== 'string') return 'Asset 8.png';
        if (value.startsWith('http') || value.startsWith('assets/')) return value;
        const parts = value.split('/');
        return parts[parts.length - 1] || 'Asset 8.png';
    }

    const HangarCart = {
        items: [],
        activePromo: null,
        promoDetails: null,
        promotionalPath: 'promotional',

        init: function() {
            this.promotionalPath = (window.HANGAR_PATHS && window.HANGAR_PATHS.promotionalBase)
                ? window.HANGAR_PATHS.promotionalBase
                : 'promotional';

            this.loadCart();
            this.setupEventListeners();
            this.updateNavBadges();
            this.renderDedicatedPage();
        },

        loadCart: function() {
            try {
                // One-time migration: carts stored before per-account isolation
                // lived under fixed keys. They are moved into the guest bucket
                // (their original owner is unknowable), then the legacy keys
                // are removed so old items never leak into a user's cart.
                const legacyManifest = localStorage.getItem('hangar_cart_manifest');
                if (legacyManifest) {
                    if (cartUid === 'guest' && !localStorage.getItem(STORAGE_KEY)) {
                        localStorage.setItem(STORAGE_KEY, legacyManifest);
                    }
                    localStorage.removeItem('hangar_cart_manifest');
                    localStorage.removeItem('hangar_cart_promo');
                    localStorage.removeItem('hangar_cart_promo_meta');
                }

                const stored = localStorage.getItem(STORAGE_KEY);
                this.items = stored ? JSON.parse(stored) : [];

                // Repair any stale image paths persisted before normalization existed
                let stale = false;
                this.items.forEach(it => {
                    const normalized = normalizeImageFilename(it.image_url);
                    if (it.image_url !== normalized) {
                        it.image_url = normalized;
                        stale = true;
                    }
                });
                if (stale) {
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
                    } catch (err) { /* non-fatal */ }
                }

                const promoStored = localStorage.getItem(PROMO_KEY);
                const promoMeta = localStorage.getItem(PROMO_META_KEY);
                if (promoStored) {
                    this.activePromo = promoStored;
                    if (promoMeta) {
                        try {
                            this.promoDetails = JSON.parse(promoMeta);
                        } catch (err) {
                            this.promoDetails = null;
                        }
                    }
                }
            } catch (e) {
                console.warn('Cart storage error:', e);
                this.items = [];
            }
        },

        saveCart: function(triggerRender = true) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
            } catch (e) {
                console.error('Failed to persist cart:', e);
            }

            this.updateNavBadges();
            if (triggerRender) {
                this.renderDedicatedPage();
            }
        },

        getItems: function() {
            return this.items;
        },

        addItem: function(product, qty = 1) {
            qty = parseInt(qty, 10);
            if (isNaN(qty) || qty <= 0) qty = 1;

            const existingIndex = this.items.findIndex(item => String(item.id) === String(product.id));

            if (existingIndex > -1) {
                this.items[existingIndex].quantity += qty;
            } else {
                this.items.push({
                    id: product.id,
                    name: product.name || 'Mobile Suit Kit',
                    grade: product.grade || 'GUNPLA',
                    brand: product.brand || 'BANDAI SPIRITS',
                    price: parseFloat(product.price) || 0,
                    image_url: normalizeImageFilename(product.image_url),
                    quantity: qty
                });
            }

            this.saveCart();
            this.showToast('UNIT REQUISITIONED', `Added ${qty}x ${product.name} to Supply Manifest`);
            this.bumpBadge();
        },

        removeItem: function(productId) {
            const item = this.items.find(i => String(i.id) === String(productId));
            this.items = this.items.filter(i => String(i.id) !== String(productId));
            this.saveCart();
            if (item) {
                this.showToast('MANIFEST UPDATED', `Discharged ${item.name} from Cart.`);
            }
        },

        updateQuantity: function(productId, newQty) {
            newQty = parseInt(newQty, 10);
            const index = this.items.findIndex(i => String(i.id) === String(productId));

            if (index > -1) {
                if (newQty <= 0) {
                    this.removeItem(productId);
                } else {
                    this.items[index].quantity = newQty;
                    this.saveCart();
                }
            }
        },

        clearCart: function() {
            if (this.items.length === 0) return;
            if (confirm('CONFIRM PURGE: Clear all Mobile Suits and equipment from Supply Manifest?')) {
                this.items = [];
                this.saveCart();
                this.showToast('MANIFEST CLEARED', 'All requisitions removed.');
            }
        },

        applyPromo: async function(code) {
            code = (code || '').trim().toUpperCase();
            if (!code) {
                this.activePromo = null;
                this.promoDetails = null;
                localStorage.removeItem(PROMO_KEY);
                localStorage.removeItem(PROMO_META_KEY);
                this.renderDedicatedPage();
                return;
            }

            try {
                const apiUrl = (PATHS.apiCheckout) ? PATHS.apiCheckout : 'api_checkout.php';
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'validate_promo', code: code })
                });

                const data = await response.json();
                if (response.ok && data.success && data.promo) {
                    this.activePromo = data.promo.code;
                    this.promoDetails = data.promo;
                    localStorage.setItem(PROMO_KEY, data.promo.code);
                    localStorage.setItem(PROMO_META_KEY, JSON.stringify(data.promo));
                    this.showToast('ACCESS CODE ACCEPTED', data.promo.label);
                } else {
                    this.activePromo = null;
                    this.promoDetails = null;
                    localStorage.removeItem(PROMO_KEY);
                    localStorage.removeItem(PROMO_META_KEY);
                    this.showToast('INVALID CODE', data.message || `"${code}" not recognized.`, true);
                }
            } catch (err) {
                console.error('Promo verification error:', err);
                this.showToast('VERIFICATION ERROR', 'Failed to communicate with promo verification service.', true);
            }

            this.renderDedicatedPage();
        },

        getSummary: function() {
            let totalQty = 0;
            let subtotal = 0;

            this.items.forEach(item => {
                totalQty += item.quantity;
                subtotal += item.price * item.quantity;
            });

            // Flat rate shipping
            let shipping = this.items.length > 0 ? 150.00 : 0;
            let discount = 0;

            if (this.activePromo && this.promoDetails) {
                const promo = this.promoDetails;
                if (promo.type === 'percent') {
                    discount = (subtotal * promo.value) / 100;
                } else if (promo.type === 'fixed') {
                    discount = Math.min(promo.value, subtotal);
                }
            }

            const total = Math.max(0, subtotal - discount + shipping);

            return {
                totalQty,
                subtotal,
                shipping,
                discount,
                total
            };
        },

        formatCurrency: function(val) {
            return '₱ ' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        updateNavBadges: function() {
            const summary = this.getSummary();
            const navCartButtons = document.querySelectorAll('.navCart');

            navCartButtons.forEach(btn => {
                let badge = btn.querySelector('.cartBadge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'cartBadge';
                    btn.appendChild(badge);
                }

                badge.textContent = summary.totalQty;
                if (summary.totalQty > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        },

        bumpBadge: function() {
            const badges = document.querySelectorAll('.cartBadge');
            badges.forEach(b => {
                b.classList.add('bump');
                setTimeout(() => b.classList.remove('bump'), 250);
            });
        },

        // Legacy compatibility helpers (drawer retired)
        openCart: function() {
            if (!window.location.pathname.endsWith('cart.php')) {
                window.location.href = PATHS.cartPage;
            }
        },

        closeCart: function() {},
        renderDrawer: function() {},

        renderDedicatedPage: function() {
            const listEl = document.getElementById('cartPageItemsList');
            const tableBody = document.getElementById('cartPageTableBody');
            const subtotalEl = document.getElementById('cartPageSubtotal');
            const totalEl = document.getElementById('cartPageTotal');
            const discountEl = document.getElementById('cartPageDiscount');
            const discountRowEl = document.getElementById('cartPageDiscountRow');
            const shippingEl = document.getElementById('cartPageShipping');
            const emptyEl = document.getElementById('cartPageEmptyMessage');
            const contentEl = document.getElementById('cartPageContent');
            const promoInput = document.getElementById('cartPagePromoInput');
            const promoStatus = document.getElementById('cartPagePromoStatus');
            const activeCountEl = document.getElementById('cartHeaderActiveCount');
            const totalCountEl = document.getElementById('cartHeaderTotalCount');

            // Determine target container (modern card list or fallback legacy table)
            const targetContainer = listEl || tableBody;
            if (!targetContainer && !emptyEl) return;

            const summary = this.getSummary();

            // Update header strip counter
            if (activeCountEl) {
                activeCountEl.textContent = String(summary.totalQty).padStart(2, '0');
            }
            if (totalCountEl) {
                totalCountEl.textContent = `${String(summary.totalQty).padStart(2, '0')} UNITS`;
            }

            // Restore promo input value if a promo is active
            if (promoInput && this.activePromo) {
                promoInput.value = this.activePromo;
            }

            // Show/hide promo status message
            if (promoStatus && this.activePromo) {
                const label = (this.promoDetails && this.promoDetails.label) ? this.promoDetails.label : this.activePromo;
                promoStatus.textContent = '✓ ' + label;
                promoStatus.style.display = 'block';
            } else if (promoStatus) {
                promoStatus.style.display = 'none';
            }

            if (this.items.length === 0) {
                if (emptyEl) emptyEl.style.display = 'flex';
                if (contentEl) contentEl.style.display = 'none';
                return;
            }

            if (emptyEl) emptyEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'grid';

            if (!targetContainer) return;

            if (listEl) {
                // Render modern card list (matching Section 6 card aesthetic extended with cart controls)
                let cardsHtml = '';
                this.items.forEach(item => {
                    const lineTotal = item.price * item.quantity;
                    const srcFile = normalizeImageFilename(item.image_url);
                    const imgSrc = (srcFile.startsWith('http') || srcFile.indexOf('/') !== -1) ? srcFile : `${this.promotionalPath}/${srcFile}`;

                    cardsHtml += `
                        <div class="cartProductCard" data-id="${item.id}">
                            <div class="cartCardThumbWrap">
                                <img src="${imgSrc}" alt="${item.name}" onerror="this.src='${this.promotionalPath}/Asset 8.png'">
                            </div>
                            <div class="cartCardInfo">
                                <div class="productBadges">
                                    <span class="productBadge">${item.brand || 'BANDAI'}</span>
                                    <span class="productBadge">${item.grade || 'GUNPLA'}</span>
                                </div>
                                <h3 class="cartCardTitle">
                                    <a href="${PATHS.productDetails}?id=${item.id}">${item.name}</a>
                                </h3>
                                <div class="cartCardUnitPrice">Unit: ${this.formatCurrency(item.price)}</div>
                            </div>
                            <div class="cartCardActions">
                                <div class="cartQtyPicker">
                                    <button type="button" class="cartQtyBtn" data-qty-dec="${item.id}" aria-label="Decrease quantity">-</button>
                                    <input type="text" class="cartQtyInput" value="${item.quantity}" readonly aria-label="Quantity">
                                    <button type="button" class="cartQtyBtn" data-qty-inc="${item.id}" aria-label="Increase quantity">+</button>
                                </div>
                                <div class="cartCardPricing">
                                    <span class="cartCardLineTotal">${this.formatCurrency(lineTotal)}</span>
                                </div>
                                <button type="button" class="cartItemRemoveBtn" data-remove="${item.id}" title="Remove item" aria-label="Remove item">&times;</button>
                            </div>
                        </div>
                    `;
                });
                listEl.innerHTML = cardsHtml;
            } else if (tableBody) {
                // Fallback table rendering
                let rowsHtml = '';
                this.items.forEach(item => {
                    const lineTotal = item.price * item.quantity;
                    const srcFile = normalizeImageFilename(item.image_url);
                    const imgSrc = (srcFile.startsWith('http') || srcFile.indexOf('/') !== -1) ? srcFile : `${this.promotionalPath}/${srcFile}`;

                    rowsHtml += `
                        <tr>
                            <td>
                                <div class="cartProductCell">
                                    <img src="${imgSrc}" alt="${item.name}" class="cartProductImg" onerror="this.src='${this.promotionalPath}/Asset 8.png'">
                                    <div class="cartProductInfo">
                                        <h4><a href="${PATHS.productDetails}?id=${item.id}" style="color:inherit; text-decoration:none;">${item.name}</a></h4>
                                        <span>${item.grade} &bull; ${item.brand}</span>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family:var(--font-system,'Poppins',sans-serif); font-weight:700;">${this.formatCurrency(item.price)}</td>
                            <td>
                                <div class="cartQtyPicker">
                                    <button type="button" class="cartQtyBtn" data-qty-dec="${item.id}">-</button>
                                    <input type="text" class="cartQtyInput" value="${item.quantity}" readonly>
                                    <button type="button" class="cartQtyBtn" data-qty-inc="${item.id}">+</button>
                                </div>
                            </td>
                            <td style="font-family:var(--font-system,'Poppins',sans-serif); font-weight:700; color:var(--brand-dark);">${this.formatCurrency(lineTotal)}</td>
                            <td>
                                <button type="button" class="cartItemRemoveBtn" data-remove="${item.id}" title="Remove item" style="font-size:1.5rem;">&times;</button>
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = rowsHtml;
            }

            if (subtotalEl) subtotalEl.textContent = this.formatCurrency(summary.subtotal);

            // Shipping
            if (shippingEl) {
                if (summary.shipping === 0) {
                    shippingEl.innerHTML = '<span style="color:#3FC4E1; font-weight:800;">FREE</span>';
                } else {
                    shippingEl.textContent = this.formatCurrency(summary.shipping);
                }
            }

            // Discount row
            if (discountRowEl) {
                discountRowEl.style.display = summary.discount > 0 ? 'flex' : 'none';
            }
            if (discountEl) discountEl.textContent = '- ' + this.formatCurrency(summary.discount);

            if (totalEl) totalEl.textContent = this.formatCurrency(summary.total);
        },

        setupEventListeners: function() {
            const self = this;

            // Note: .navCart links navigate normally to cart.php. No preventDefault or drawer popout.
            document.addEventListener('click', function(e) {
                // Clear all
                if (e.target.closest('#cartClearAllBtn') || e.target.closest('#cartPageClearBtn')) {
                    e.preventDefault();
                    self.clearCart();
                    return;
                }

                // Quantity decrease
                const decBtn = e.target.closest('[data-qty-dec]');
                if (decBtn) {
                    e.preventDefault();
                    const id = decBtn.getAttribute('data-qty-dec');
                    const item = self.items.find(i => String(i.id) === String(id));
                    if (item) {
                        self.updateQuantity(id, item.quantity - 1);
                    }
                    return;
                }

                // Quantity increase
                const incBtn = e.target.closest('[data-qty-inc]');
                if (incBtn) {
                    e.preventDefault();
                    const id = incBtn.getAttribute('data-qty-inc');
                    const item = self.items.find(i => String(i.id) === String(id));
                    if (item) {
                        self.updateQuantity(id, item.quantity + 1);
                    }
                    return;
                }

                // Remove item
                const removeBtn = e.target.closest('[data-remove]');
                if (removeBtn) {
                    e.preventDefault();
                    const id = removeBtn.getAttribute('data-remove');
                    self.removeItem(id);
                    return;
                }

                // Apply Promo on Dedicated Page
                if (e.target.closest('#cartPagePromoBtn')) {
                    e.preventDefault();
                    const input = document.getElementById('cartPagePromoInput');
                    if (input) self.applyPromo(input.value);
                    return;
                }
            });

            // Enter key support for promo input
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    if (document.activeElement && document.activeElement.id === 'cartPagePromoInput') {
                        e.preventDefault();
                        self.applyPromo(document.activeElement.value);
                    }
                }
            });

            // Multi-tab synchronization
            window.addEventListener('storage', function(e) {
                if (e.key === STORAGE_KEY || e.key === PROMO_KEY || e.key === PROMO_META_KEY) {
                    self.loadCart();
                    self.updateNavBadges();
                    self.renderDedicatedPage();
                }
            });
        },

        showToast: function(title, msg, isError = false) {
            let container = document.getElementById('hangarToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'hangarToastContainer';
                container.className = 'hangarToastContainer';
                document.body.appendChild(container);
            }

            const accentColor = isError ? '#FF5555' : '#3FC4E1';
            const toast = document.createElement('div');
            toast.className = 'hangarToast';
            toast.style.borderColor = accentColor;
            toast.style.borderLeftColor = accentColor;
            toast.innerHTML = `
                <div class="hangarToastIcon" style="color: ${accentColor};">
                    ${isError
                        ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>`
                        : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`
                    }
                </div>
                <div class="hangarToastContent">
                    <div class="hangarToastTitle" style="color: ${accentColor};">${title}</div>
                    <div class="hangarToastMsg">${msg}</div>
                </div>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 350);
            }, 3500);
        }
    };

    // Expose globally
    window.HangarCart = HangarCart;

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => HangarCart.init());
    } else {
        HangarCart.init();
    }
})();
