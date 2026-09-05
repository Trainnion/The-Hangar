<?php
// THE HANGAR - GUND-ORDER SYSTEM
// SEARCH HUD MODAL OVERLAY COMPONENT (Self-contained & Reusable)
// $_searchAssetPrefix should be set by the including page (e.g. 'search/' from homepage/, './' from search/)
if (!isset($_searchAssetPrefix)) $_searchAssetPrefix = './';
$buttonsPath = isset($buttonsPath) ? $buttonsPath : (is_dir('buttons') ? 'buttons' : '../buttons');
$promotionalPath = isset($promotionalPath) ? $promotionalPath : (is_dir('promotional') ? 'promotional' : '../promotional');
?>
<!-- Search Component Modular Stylesheet -->
<link rel="stylesheet" href="<?php echo $_searchAssetPrefix; ?>search.css?v=<?php echo time(); ?>">

<!-- GUND-ORDER SYSTEM SEARCH HUD OVERLAY -->
<div id="hangarSearchOverlay" class="hangarSearchOverlay" aria-hidden="true">
    <div class="hangarSearchBackdrop" id="hangarSearchBackdrop"></div>
    
    <div class="hangarSearchDialog" role="dialog" aria-modal="true" aria-labelledby="searchHudTitle">
        <!-- Sci-Fi Corner Brackets -->
        <span class="hudCorner cornerTL"></span>
        <span class="hudCorner cornerTR"></span>
        <span class="hudCorner cornerBL"></span>
        <span class="hudCorner cornerBR"></span>

        <!-- HUD Header Strip -->
        <div class="searchHudHeader">
            <div class="searchHudSystem">
                <span class="radarPulse"></span>
                <span id="searchHudTitle" class="searchHudCode">GUND-ORDER SEARCH // TARGET ACQUISITION</span>
            </div>
            <div class="searchHudControls">
                <span class="searchKeyHint"><kbd>ESC</kbd> to close</span>
                <button type="button" class="searchCloseBtn" id="searchCloseBtn" aria-label="Close search radar">&times;</button>
            </div>
        </div>

        <!-- Main Search Form -->
        <form class="searchHudForm" action="search.php" method="GET" id="searchHudForm" role="search">
            <div class="searchHudInputWrapper">
                <div class="searchHudIcon">
                    <img src="<?php echo $buttonsPath; ?>/Search.svg" alt="" aria-hidden="true">
                </div>
                <input 
                    type="search" 
                    id="hangarSearchInput" 
                    name="q" 
                    class="searchHudInput" 
                    placeholder="SEARCH MOBILE SUIT, GRADE (MG, RG, PG), WEAPON..." 
                    autocomplete="off" 
                    autocorrect="off" 
                    autocapitalize="off" 
                    spellcheck="false"
                    aria-autocomplete="list"
                    aria-controls="hangarSearchResults"
                >
                <button type="button" class="searchClearBtn" id="searchClearBtn" aria-label="Clear query">&times;</button>
                <button type="submit" class="searchSubmitAction" id="searchSubmitAction" aria-label="Engage search">
                    <span>ENGAGE</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- Grade Filter Chips -->
            <div class="searchFilterChips" id="searchFilterChips">
                <span class="chipsLabel">FILTER:</span>
                <button type="button" class="searchChip active" data-grade="">ALL</button>
                <button type="button" class="searchChip" data-grade="MG">MG 1/100</button>
                <button type="button" class="searchChip" data-grade="RG">RG 1/144</button>
                <button type="button" class="searchChip" data-grade="PG">PG 1/60</button>
                <button type="button" class="searchChip" data-grade="HG">HG</button>
                <button type="button" class="searchChip" data-grade="METAL BUILD">METAL BUILD</button>
            </div>
        </form>

        <!-- Dynamic Results Container -->
        <div class="searchResultsContainer" id="hangarSearchResults" role="region" aria-live="polite">
            <!-- Initial, loading, or dynamic results populated via JS -->
        </div>

        <!-- HUD Footer Status Bar -->
        <div class="searchHudFooter">
            <div class="searchHudKeyGuides">
                <span><kbd>↑</kbd> <kbd>↓</kbd> NAVIGATE</span>
                <span><kbd>ENTER</kbd> SELECT</span>
                <span><kbd>/</kbd> OR <kbd>CTRL+K</kbd> OPEN</span>
            </div>
            <div class="searchHudStatus" id="searchHudStatus">RADAR STANDBY</div>
        </div>
    </div>
</div>

<!-- Search Controller Script -->
<script src="<?php echo $_searchAssetPrefix; ?>search.js?v=<?php echo time(); ?>"></script>
