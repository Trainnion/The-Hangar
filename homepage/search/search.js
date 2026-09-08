// THE HANGAR - GUND-ORDER SYSTEM
// IN-PAGE LIVE SEARCH & SUGGESTION CONTROLLER (search.js)
// Replaces full-screen HUD overlay with lightweight in-page suggestion dropdown

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('spSearchInput');
        const dropdown = document.getElementById('searchDropdownMK');
        const searchForm = document.getElementById('spSearchForm');

        if (!searchInput || !dropdown) return;

        // Auto-focus if requested in URL parameter (?focus=1)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('focus') === '1') {
            searchInput.focus();
            if (searchInput.value) searchInput.select();
        }

        let debounceTimer = null;
        let currentAbortCtrl = null;
        let selectedIndex = -1;
        let activeItems = [];

        const promotionalPath = (window.HANGAR_PATHS && window.HANGAR_PATHS.promotionalBase) 
            ? window.HANGAR_PATHS.promotionalBase 
            : '../promotional';

        // Managed upload paths (assets/uploads/...) are stored relative to the project
        // ROOT. promotionalPath already climbs out of this page folder to reach the
        // root — derive the same climb so those images resolve correctly too.
        const rootPrefix = (function() {
            let p = promotionalPath, up = '';
            while (p.indexOf('../') === 0) { up += '../'; p = p.substring(3); }
            return up;
        })();

        const apiPath = (window.HANGAR_PATHS && window.HANGAR_PATHS.apiSearch) 
            ? window.HANGAR_PATHS.apiSearch 
            : 'api_search.php';

        const productDetailsBase = (window.HANGAR_PATHS && window.HANGAR_PATHS.productDetails)
            ? window.HANGAR_PATHS.productDetails
            : '../product-details.php';

        function hideDropdown() {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            selectedIndex = -1;
            activeItems = [];
        }

        function updateSelection() {
            const itemEls = dropdown.querySelectorAll('.searchSuggestItemMK');
            itemEls.forEach((el, i) => {
                if (i === selectedIndex) {
                    el.classList.add('selected');
                    el.scrollIntoView({ block: 'nearest' });
                } else {
                    el.classList.remove('selected');
                }
            });
        }

        async function performLiveSearch(query) {
            query = query.trim();
            if (query.length < 1) {
                hideDropdown();
                return;
            }

            if (currentAbortCtrl) {
                currentAbortCtrl.abort();
            }
            currentAbortCtrl = new AbortController();

            try {
                const res = await fetch(`${apiPath}?q=${encodeURIComponent(query)}&limit=6`, {
                    signal: currentAbortCtrl.signal
                });

                if (!res.ok) throw new Error('Search failed');

                const data = await res.json();
                renderDropdown(query, data.results || []);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    hideDropdown();
                }
            }
        }

        function renderDropdown(query, results) {
            selectedIndex = -1;
            activeItems = results;

            if (!results || results.length === 0) {
                dropdown.innerHTML = `
                    <div class="searchDropdownEmptyMK">
                        No units found matching "<strong>${escapeHtml(query)}</strong>"
                    </div>
                `;
                dropdown.style.display = 'flex';
                return;
            }

            let html = `
                <div class="searchDropdownHeaderMK">
                    SUGGESTED UNITS (${results.length})
                </div>
            `;

            results.forEach((item, idx) => {
                const imgSrc = (item.image_url && item.image_url.startsWith('http')) 
                    ? item.image_url 
                    : (item.image_url && item.image_url.indexOf('/') !== -1)
                    ? rootPrefix + item.image_url
                    : `${promotionalPath}/${item.image_url || 'Asset 8.png'}`;

                html += `
                    <a href="${productDetailsBase}?id=${item.id}" class="searchSuggestItemMK" data-index="${idx}">
                        <img src="${imgSrc}" alt="${escapeHtml(item.name)}" class="searchSuggestThumbMK" onerror="this.src='${promotionalPath}/Asset 8.png'">
                        <div class="searchSuggestInfoMK">
                            <span class="searchSuggestTitleMK">${escapeHtml(item.name)}</span>
                            <div class="searchSuggestMetaMK">
                                <span class="productBadge">${escapeHtml(item.grade || 'KIT')}</span>
                                <span>${escapeHtml(item.brand || 'BANDAI')}</span>
                            </div>
                        </div>
                        <span class="searchSuggestPriceMK">${item.formatted_price || ('₱ ' + Number(item.price).toFixed(2))}</span>
                    </a>
                `;
            });

            html += `
                <a href="search.php?q=${encodeURIComponent(query)}" class="searchDropdownFooterMK">
                    VIEW ALL RESULTS FOR "${escapeHtml(query)}" &rarr;
                </a>
            `;

            dropdown.innerHTML = html;
            dropdown.style.display = 'flex';
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        // Event: Type into input
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value;
            debounceTimer = setTimeout(() => {
                performLiveSearch(val);
            }, 200);
        });

        // Event: Keyboard Navigation
        searchInput.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') return;

            const itemEls = dropdown.querySelectorAll('.searchSuggestItemMK');
            if (itemEls.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % itemEls.length;
                updateSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + itemEls.length) % itemEls.length;
                updateSelection();
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && selectedIndex < itemEls.length) {
                    e.preventDefault();
                    itemEls[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        // Event: Dismiss on click outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                hideDropdown();
            }
        });
    });
})();
