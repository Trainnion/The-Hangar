// THE HANGAR - GUND-ORDER SYSTEM
// REAL-TIME SEARCH HUD CONTROLLER & TARGET ACQUISITION SYSTEM

(function() {
    'use strict';

    if (window._hangarSearchInitialized) return;
    window._hangarSearchInitialized = true;

    // Cache DOM Elements
    const overlay         = document.getElementById('hangarSearchOverlay');
    const backdrop        = document.getElementById('hangarSearchBackdrop');
    const closeBtn        = document.getElementById('searchCloseBtn');
    const searchForm      = document.getElementById('searchHudForm');
    const searchInput     = document.getElementById('hangarSearchInput');
    const clearBtn        = document.getElementById('searchClearBtn');
    const resultsBox      = document.getElementById('hangarSearchResults');
    const statusEl        = document.getElementById('searchHudStatus');
    const filterChipsWrap = document.getElementById('searchFilterChips');
    const searchButtons   = document.querySelectorAll('.navBtnSearch');

    if (!overlay || !searchInput) {
        return;
    }

    let activeGrade = '';
    let debounceTimer = null;
    let currentAbortCtrl = null;
    let lastActiveElement = null;
    let selectedResultIndex = -1;
    let queryCache = new Map();

    // Default Popular Queries
    const popularQueries = [
        'Barbatos',
        'Sazabi',
        'Nu Gundam',
        'Freedom 2.0',
        'Vidar',
        'Sinanju',
        'Wing Zero'
    ];

    // ==========================================
    // 1. OPEN / CLOSE MODAL SYSTEM
    // ==========================================

    function openSearchModal() {
        lastActiveElement = document.activeElement;
        overlay.classList.add('isOpen');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('hangarModalOpen');

        // Initialise state
        selectedResultIndex = -1;
        updateClearButton();

        // If input has text, run search, else show popular/featured
        if (searchInput.value.trim().length > 0) {
            triggerSearch(searchInput.value.trim(), activeGrade);
        } else {
            renderInitialSuggestions();
        }

        // Focus input after CSS transition
        setTimeout(() => {
            searchInput.focus();
            if (searchInput.value) {
                searchInput.select();
            }
        }, 120);
    }

    function closeSearchModal() {
        overlay.classList.remove('isOpen');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('hangarModalOpen');

        if (currentAbortCtrl) {
            currentAbortCtrl.abort();
            currentAbortCtrl = null;
        }

        if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
            lastActiveElement.focus();
        }
    }

    // Attach click triggers to all search buttons in header
    searchButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openSearchModal();
        });
    });

    if (backdrop) {
        backdrop.addEventListener('click', closeSearchModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSearchModal);
    }

    // Global keyboard shortcuts: "/" or "Ctrl+K" / "Cmd+K" to open, "Escape" to close
    document.addEventListener('keydown', (e) => {
        const isModalOpen = overlay.classList.contains('isOpen');

        if (isModalOpen) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeSearchModal();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                navigateResults(1);
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                navigateResults(-1);
                return;
            }

            if (e.key === 'Enter') {
                const items = resultsBox.querySelectorAll('.searchResultItem');
                if (selectedResultIndex >= 0 && items[selectedResultIndex]) {
                    e.preventDefault();
                    items[selectedResultIndex].click();
                }
                return;
            }
        } else {
            // Trigger with '/' when not typing in an input
            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            const isTyping = activeTag === 'input' || activeTag === 'textarea' || document.activeElement.isContentEditable;

            if (!isTyping && e.key === '/') {
                e.preventDefault();
                openSearchModal();
            } else if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                openSearchModal();
            }
        }
    });

    // ==========================================
    // 2. INPUT & FILTER HANDLING
    // ==========================================

    function updateClearButton() {
        if (clearBtn) {
            clearBtn.style.display = searchInput.value.trim().length > 0 ? 'inline-flex' : 'none';
        }
    }

    searchInput.addEventListener('input', () => {
        updateClearButton();
        const query = searchInput.value.trim();

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            if (query.length > 0) {
                triggerSearch(query, activeGrade);
            } else {
                renderInitialSuggestions();
            }
        }, 180);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
            renderInitialSuggestions();
        });
    }

    // Grade Filter Chips Handling
    if (filterChipsWrap) {
        const chips = filterChipsWrap.querySelectorAll('.searchChip');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                activeGrade = chip.getAttribute('data-grade') || '';
                
                const query = searchInput.value.trim();
                triggerSearch(query, activeGrade);
            });
        });
    }

    // ==========================================
    // 3. AJAX SEARCH DISPATCHER
    // ==========================================

    function triggerSearch(query, grade) {
        selectedResultIndex = -1;

        if (!query && !grade) {
            renderInitialSuggestions();
            return;
        }

        const cacheKey = `${query.toLowerCase()}::${grade.toUpperCase()}`;
        if (queryCache.has(cacheKey)) {
            renderResults(queryCache.get(cacheKey), query, grade);
            return;
        }

        // Show scanning state
        renderLoading(query);

        if (currentAbortCtrl) {
            currentAbortCtrl.abort();
        }
        currentAbortCtrl = new AbortController();

        const params = new URLSearchParams({
            q: query,
            grade: grade,
            limit: 8
        });

        fetch(`api_search.php?${params.toString()}`, {
            signal: currentAbortCtrl.signal
        })
        .then(res => {
            if (!res.ok) throw new Error('Network radar fault');
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                queryCache.set(cacheKey, data);
                renderResults(data, query, grade);
            } else {
                renderEmpty(query, grade);
            }
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            console.error('Search query failed:', err);
            renderError('G.O.S RADAR INTERFERENCE: Connection disrupted.');
        });
    }

    // ==========================================
    // 4. RENDERING TEMPLATES
    // ==========================================

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        const safeText = escapeHtml(text);
        const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safeQuery})`, 'gi');
        return safeText.replace(regex, '<mark class="hudMark">$1</mark>');
    }

    function renderLoading(query) {
        if (statusEl) statusEl.textContent = 'RADAR SCANNING...';
        resultsBox.innerHTML = `
            <div class="searchHudLoading">
                <div class="scanningRadarBar"></div>
                <div class="scanningText">
                    <span>ACQUIRING TELEMETRY // SEARCHING FOR "${escapeHtml(query || 'TARGETS')}"...</span>
                </div>
            </div>
        `;
    }

    function renderError(msg) {
        if (statusEl) statusEl.textContent = 'RADAR OFFLINE';
        resultsBox.innerHTML = `
            <div class="searchHudEmpty">
                <div class="emptyIcon">⚠️</div>
                <h4>TELEMETRY ERROR</h4>
                <p>${escapeHtml(msg)}</p>
            </div>
        `;
    }

    function renderEmpty(query, grade) {
        if (statusEl) statusEl.textContent = '0 TARGETS DETECTED';
        
        let filterDesc = '';
        if (query && grade) {
            filterDesc = `for "<strong>${escapeHtml(query)}</strong>" in Grade <strong>${escapeHtml(grade)}</strong>`;
        } else if (query) {
            filterDesc = `for "<strong>${escapeHtml(query)}</strong>"`;
        } else if (grade) {
            filterDesc = `in Grade <strong>${escapeHtml(grade)}</strong>`;
        }

        resultsBox.innerHTML = `
            <div class="searchHudEmpty">
                <div class="emptyRadarReticle">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="22" y1="12" x2="18" y2="12"></line>
                        <line x1="6" y1="12" x2="2" y2="12"></line>
                        <line x1="12" y1="6" x2="12" y2="2"></line>
                        <line x1="12" y1="22" x2="12" y2="18"></line>
                        <circle cx="12" cy="12" r="4"></circle>
                    </svg>
                </div>
                <h4>NO TARGETS DETECTED</h4>
                <p>Radar scanning detected no mobile suits ${filterDesc}.</p>
                <div class="emptySuggestionsPills">
                    <span>TRY SEARCHING:</span>
                    ${popularQueries.slice(0, 5).map(q => `<button type="button" class="quickSearchTag" data-q="${q}">${q}</button>`).join('')}
                </div>
            </div>
        `;

        attachQuickTagListeners();
    }

    function renderResults(data, query, grade) {
        const count = data.count || 0;
        const results = data.results || [];

        if (count === 0 || results.length === 0) {
            renderEmpty(query, grade);
            return;
        }

        if (statusEl) {
            statusEl.textContent = `${count} TARGET${count === 1 ? '' : 'S'} ACQUIRED`;
        }

        let fullSearchUrl = `search.php?q=${encodeURIComponent(query)}`;
        if (grade) {
            fullSearchUrl += `&grade=${encodeURIComponent(grade)}`;
        }

        let html = `
            <div class="searchResultListHeader">
                <span>DETECTED TARGETS (${count})</span>
                <a href="${fullSearchUrl}" class="viewAllDeckLink">FULL RADAR DECK &rarr;</a>
            </div>
            <div class="searchResultList" role="listbox">
        `;

        results.forEach((p, idx) => {
            const gradeClass = `grade-${(p.grade || 'default').toLowerCase().replace(/\s+/g, '')}`;
            html += `
                <a href="${escapeHtml(p.url)}" class="searchResultItem" role="option" data-index="${idx}" id="searchResult-${idx}">
                    <div class="searchResultImgWrap">
                        <img src="${escapeHtml(p.image_full_path)}" alt="${escapeHtml(p.name)}" onerror="this.src='../promotional/Asset 8.png'">
                    </div>
                    <div class="searchResultInfo">
                        <div class="searchResultBadges">
                            <span class="searchGradeTag ${gradeClass}">${escapeHtml(p.grade)}</span>
                            <span class="searchScaleTag">${escapeHtml(p.scale)}</span>
                            <span class="searchStockTag">${escapeHtml(p.stock_status)}</span>
                        </div>
                        <h4 class="searchResultTitle">${highlightMatch(p.name, query)}</h4>
                        <div class="searchResultMeta">
                            <span class="searchResultPrice">${escapeHtml(p.formatted_price)}</span>
                            <span class="searchResultSold">SOLD ${escapeHtml(p.sold_count.toLocaleString())}</span>
                        </div>
                    </div>
                    <div class="searchResultAction">
                        <span class="engageText">ENGAGE</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            `;
        });

        html += `
            </div>
            <div class="searchResultListFooter">
                <a href="${fullSearchUrl}" class="searchAllResultsBtn">
                    <span>VIEW ALL RESULTS FOR "${escapeHtml(query || grade || 'ALL UNITS')}"</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        `;

        resultsBox.innerHTML = html;
        selectedResultIndex = -1;
    }

    function renderInitialSuggestions() {
        if (statusEl) statusEl.textContent = 'RADAR STANDBY';

        let html = `
            <div class="searchInitialDeck">
                <div class="initialSection">
                    <h5 class="initialHeading">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        POPULAR TARGET CALLSIGNS
                    </h5>
                    <div class="initialPillsList">
                        ${popularQueries.map(q => `<button type="button" class="quickSearchTag" data-q="${q}">${q}</button>`).join('')}
                    </div>
                </div>

                <div class="initialSection">
                    <h5 class="initialHeading">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                        BROWSE BY GRADE SPECIFICATION
                    </h5>
                    <div class="initialGradeGrid">
                        <button type="button" class="quickGradeCard" data-grade="MG">
                            <span class="gradeAbbr">MG</span>
                            <span class="gradeName">MASTER GRADE</span>
                            <span class="gradeScale">1/100 Scale</span>
                        </button>
                        <button type="button" class="quickGradeCard" data-grade="RG">
                            <span class="gradeAbbr">RG</span>
                            <span class="gradeName">REAL GRADE</span>
                            <span class="gradeScale">1/144 Scale</span>
                        </button>
                        <button type="button" class="quickGradeCard" data-grade="PG">
                            <span class="gradeAbbr">PG</span>
                            <span class="gradeName">PERFECT GRADE</span>
                            <span class="gradeScale">1/60 Scale</span>
                        </button>
                        <button type="button" class="quickGradeCard" data-grade="METAL BUILD">
                            <span class="gradeAbbr">MB</span>
                            <span class="gradeName">METAL BUILD</span>
                            <span class="gradeScale">Die-cast Spec</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        resultsBox.innerHTML = html;
        attachQuickTagListeners();
    }

    function attachQuickTagListeners() {
        const tags = resultsBox.querySelectorAll('.quickSearchTag');
        tags.forEach(tag => {
            tag.addEventListener('click', () => {
                const q = tag.getAttribute('data-q') || '';
                searchInput.value = q;
                updateClearButton();
                searchInput.focus();
                triggerSearch(q, activeGrade);
            });
        });

        const gradeCards = resultsBox.querySelectorAll('.quickGradeCard');
        gradeCards.forEach(gc => {
            gc.addEventListener('click', () => {
                const g = gc.getAttribute('data-grade') || '';
                activeGrade = g;

                // Sync chips
                if (filterChipsWrap) {
                    const chips = filterChipsWrap.querySelectorAll('.searchChip');
                    chips.forEach(c => {
                        c.classList.toggle('active', c.getAttribute('data-grade') === g);
                    });
                }

                triggerSearch(searchInput.value.trim(), activeGrade);
            });
        });
    }

    // ==========================================
    // 5. KEYBOARD NAVIGATION IN RESULTS
    // ==========================================

    function navigateResults(direction) {
        const items = resultsBox.querySelectorAll('.searchResultItem');
        if (items.length === 0) return;

        items.forEach(it => it.classList.remove('keyboardSelected'));

        selectedResultIndex += direction;
        if (selectedResultIndex >= items.length) {
            selectedResultIndex = 0;
        } else if (selectedResultIndex < 0) {
            selectedResultIndex = items.length - 1;
        }

        const activeItem = items[selectedResultIndex];
        if (activeItem) {
            activeItem.classList.add('keyboardSelected');
            activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

})();
