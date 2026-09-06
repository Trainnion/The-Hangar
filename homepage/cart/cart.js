// THE HANGAR - GUND-ORDER SYSTEM
// REAL-TIME CART & SUPPLY MANIFEST CONTROLLER (cart.js)

(function() {
    'use strict';

    const STORAGE_KEY = 'hangar_cart_manifest';
    const PROMO_KEY = 'hangar_cart_promo';
    const PROMO_META_KEY = 'hangar_cart_promo_meta';

    // Path config — set via window.HANGAR_PATHS by the hosting PHP page
    const PATHS = Object.assign({
        cartPage:       'cart.php',
        searchPage:     '../search/search.php',
        productDetails: '../product-details.php',
        apiCheckout:    'api_checkout.php'
    }, window.HANGAR_PATHS || {});

    const HangarCart = {
        items: [],
        activePromo: null,
        promoDetails: null,
        promotionalPath: 'promotional',

        init: function() {
            // Determine relative promotional path based on directory
            this.promotionalPath = document.querySelector('img[src*="promotional/"]') ? 'promotional' : '../promotional';

            this.loadCart();
            this.setupEventListeners();
            this.updateNavBadges();
            this.renderDrawer();
        },

        loadCart: function() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                this.items = stored ? JSON.parse(stored) : [];

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
                this.renderDrawer();
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
                    image_url: product.image_url || 'Asset 8.png',
                    quantity: qty
                });
            }

            this.saveCart();
            this.showToast('UNIT REQUISITIONED', `Added ${qty}x ${product.name} to Supply Manifest`);
            this.bumpBadge();
            this.openCart();
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
                this.renderDrawer();
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

            this.renderDrawer();
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

        openCart: function() {
            const overlay = document.getElementById('hangarCartOverlay');
            if (overlay) {
                overlay.classList.add('isOpen');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('hangarModalOpen');
                this.renderDrawer();
            }
        },

        closeCart: function() {
            const overlay = document.getElementById('hangarCartOverlay');
            if (overlay) {
                overlay.classList.remove('isOpen');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('hangarModalOpen');
            }
        },

        renderDrawer: function() {
            const bodyEl = document.getElementById('hangarCartBody');
            const footerEl = document.getElementById('hangarCartFooter');
            const countEl = document.getElementById('cartStatusCount');

            if (!bodyEl || !footerEl) return;

            const summary = this.getSummary();
            if (countEl) {
                countEl.textContent = `${summary.totalQty} REQUISITION${summary.totalQty === 1 ? '' : 'S'} ALLOCATED`;
            }

            // Empty State
            if (this.items.length === 0) {
                bodyEl.innerHTML = `
                    <div class="cartEmptyState">
                        <svg class="cartEmptyIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            <line x1="3" y1="3" x2="21" y2="21" stroke="#FF5555" stroke-width="2"></line>
                        </svg>
                        <h3 class="cartEmptyTitle">MANIFEST EMPTY</h3>
                        <p class="cartEmptyDesc">No Mobile Suit units or equipment requisitioned for this sortie.</p>
                        <a href="${PATHS.searchPage}" class="cartExploreBtn">
                            <span>ACCESS CATALOGUE</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                `;

                footerEl.innerHTML = `
                    <div class="cartTrustBadge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>BANDAI SPIRITS &bull; OFFICIAL STOREFRONT DECK</span>
                    </div>
                `;
                return;
            }

            // Render Items
            let itemsHtml = '';
            this.items.forEach(item => {
                const lineTotal = item.price * item.quantity;
                const imgSrc = (item.image_url && item.image_url.startsWith('http')) 
                    ? item.image_url 
                    : `${this.promotionalPath}/${item.image_url}`;

                itemsHtml += `
                    <div class="cartItem" data-id="${item.id}">
                        <div class="cartItemThumbWrap">
                            <img src="${imgSrc}" alt="${item.name}" class="cartItemThumb" onerror="this.src='${this.promotionalPath}/Asset 8.png'">
                            <span class="cartItemGradeBadge">${item.grade || 'KIT'}</span>
                        </div>
                        <div class="cartItemDetails">
                            <div class="cartItemHeader">
                                <h4 class="cartItemTitle">
                                    <a href="${PATHS.productDetails}?id=${item.id}">${item.name}</a>
                                </h4>
                                <button type="button" class="cartItemRemoveBtn" data-remove="${item.id}" title="Discharge Unit">&times;</button>
                            </div>
                            <div class="cartItemMeta">
                                <span class="cartItemMetaTag">${item.brand || 'BANDAI'}</span>
                                <span>&bull;</span>
                                <span>Unit: ${this.formatCurrency(item.price)}</span>
                            </div>
                            <div class="cartItemFooter">
                                <div class="cartQtyPicker">
                                    <button type="button" class="cartQtyBtn" data-qty-dec="${item.id}">-</button>
                                    <input type="text" class="cartQtyInput" value="${item.quantity}" readonly>
                                    <button type="button" class="cartQtyBtn" data-qty-inc="${item.id}">+</button>
                                </div>
                                <div class="cartItemPriceBox">
                                    <span class="cartItemLineTotal">${this.formatCurrency(lineTotal)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            bodyEl.innerHTML = itemsHtml;

            // Render Footer with Summary & Actions
            let discountRow = '';
            if (summary.discount > 0) {
                discountRow = `
                    <div class="cartSummaryRow" style="color: #00E676;">
                        <span class="cartSummaryLabel">PROMO DISCOUNT:</span>
                        <span class="cartSummaryVal">- ${this.formatCurrency(summary.discount)}</span>
                    </div>
                `;
            }

            footerEl.innerHTML = `
                <div class="cartSummaryRow">
                    <span class="cartSummaryLabel">SUBTOTAL:</span>
                    <span class="cartSummaryVal">${this.formatCurrency(summary.subtotal)}</span>
                </div>
                ${discountRow}
                <div class="cartSummaryRow">
                    <span class="cartSummaryLabel">LOGISTICS DISPATCH:</span>
                    <span class="cartSummaryVal">${summary.shipping === 0 ? '<span style="color:#3FC4E1">FREE (PROMO)</span>' : this.formatCurrency(summary.shipping)}</span>
                </div>
                <div class="cartSummaryRow totalRow">
                    <span class="cartSummaryLabel">TOTAL REQUISITION:</span>
                    <span class="cartTotalVal">${this.formatCurrency(summary.total)}</span>
                </div>

                <div class="cartPromoRow">
                    <input type="text" id="cartPromoInput" class="cartPromoInput" placeholder="PILOT CLEARANCE CODE" value="${this.activePromo || ''}">
                    <button type="button" id="cartPromoBtn" class="cartPromoBtn">APPLY</button>
                </div>

                <div class="cartActionGroup">
                    <a href="${PATHS.cartPage}" class="cartCheckoutBtn">
                        <span>INITIATE ORDER DISPATCH</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <div class="cartSecondaryActions">
                        <a href="${PATHS.cartPage}" class="cartFullPageBtn">EXPAND MANIFEST</a>
                        <button type="button" class="cartContinueBtn" id="cartCloseBtnAction">STANDBY</button>
                    </div>
                </div>

                <div class="cartTrustBadge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>100% AUTHENTIC BANDAI SPIRITS GUARANTEED</span>
                </div>
            `;
        },

        renderDedicatedPage: function() {
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

            if (!tableBody) return;

            const summary = this.getSummary();

            // Restore promo input value if a promo is active
            if (promoInput && this.activePromo) {
                promoInput.value = this.activePromo;
            }

            // Show/hide promo status message
            if (promoStatus && this.activePromo && ACTIVE_PROMOS[this.activePromo]) {
                promoStatus.textContent = '✓ ' + ACTIVE_PROMOS[this.activePromo].label;
                promoStatus.style.display = 'block';
            } else if (promoStatus) {
                promoStatus.style.display = 'none';
            }

            if (this.items.length === 0) {
                if (emptyEl) emptyEl.style.display = 'block';
                if (contentEl) contentEl.style.display = 'none';
                return;
            }

            if (emptyEl) emptyEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'grid';

            let rowsHtml = '';
            this.items.forEach(item => {
                const lineTotal = item.price * item.quantity;
                const imgSrc = (item.image_url && item.image_url.startsWith('http')) 
                    ? item.image_url 
                    : `${this.promotionalPath}/${item.image_url}`;

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
                        <td style="font-family:'Orbitron', monospace; font-weight:700;">${this.formatCurrency(item.price)}</td>
                        <td>
                            <div class="cartQtyPicker">
                                <button type="button" class="cartQtyBtn" data-qty-dec="${item.id}">-</button>
                                <input type="text" class="cartQtyInput" value="${item.quantity}" readonly>
                                <button type="button" class="cartQtyBtn" data-qty-inc="${item.id}">+</button>
                            </div>
                        </td>
                        <td style="font-family:'Orbitron', monospace; font-weight:800; color:var(--brand-dark);">${this.formatCurrency(lineTotal)}</td>
                        <td>
                            <button type="button" class="cartItemRemoveBtn" data-remove="${item.id}" title="Remove item" style="font-size:1.5rem;">&times;</button>
                        </td>
                    </tr>
                `;
            });

            tableBody.innerHTML = rowsHtml;

            if (subtotalEl) subtotalEl.textContent = this.formatCurrency(summary.subtotal);

            // Shipping — show FREE in cyan when applicable
            if (shippingEl) {
                if (summary.shipping === 0) {
                    shippingEl.innerHTML = '<span style="color:#3FC4E1; font-weight:800;">FREE</span>';
                } else {
                    shippingEl.textContent = this.formatCurrency(summary.shipping);
                }
            }

            // Discount row — only show if there's an active discount
            if (discountRowEl) {
                discountRowEl.style.display = summary.discount > 0 ? 'flex' : 'none';
            }
            if (discountEl) discountEl.textContent = '- ' + this.formatCurrency(summary.discount);

            if (totalEl) totalEl.textContent = this.formatCurrency(summary.total);
        },

        setupEventListeners: function() {
            const self = this;

            // Header Cart buttons trigger modal (skip if already on cart page)
            const isCartPage = !!document.getElementById('cartPageTableBody');
            document.addEventListener('click', function(e) {
                const cartLink = e.target.closest('.navCart');
                if (cartLink) {
                    if (isCartPage) return; // let the link navigate normally on cart.php
                    e.preventDefault();
                    self.openCart();
                    return;
                }

                // Close cart triggers
                if (e.target.closest('#cartCloseBtn') || e.target.closest('#cartCloseBtnAction') || e.target.closest('#hangarCartBackdrop')) {
                    e.preventDefault();
                    self.closeCart();
                    return;
                }

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

                // Apply Promo in Drawer
                if (e.target.closest('#cartPromoBtn')) {
                    e.preventDefault();
                    const input = document.getElementById('cartPromoInput');
                    if (input) self.applyPromo(input.value);
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

            // Keyboard ESC to close cart
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const overlay = document.getElementById('hangarCartOverlay');
                    if (overlay && overlay.classList.contains('isOpen')) {
                        self.closeCart();
                    }
                }
            });

            // Enter key support for promo inputs
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    if (document.activeElement && document.activeElement.id === 'cartPromoInput') {
                        e.preventDefault();
                        self.applyPromo(document.activeElement.value);
                    } else if (document.activeElement && document.activeElement.id === 'cartPagePromoInput') {
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
                    self.renderDrawer();
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
                        ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
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
