<?php
// THE HANGAR - G.O.S ADMIN
// STATIC PAGES: edit the About / Privacy storefront page content through a
// user-friendly rich-text editor (no HTML knowledge required). Content is stored
// as a small HTML fragment in the `settings` table (keys: page_about /
// page_privacy) and rendered by homepage/page.php. Empty settings fall back to
// the built-in defaults defined in shared/static_pages.php.
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../shared/db.php';
require_once __DIR__ . '/upload_helper.php';
require_once __DIR__ . '/../shared/static_pages.php';

$pdo = getDBConnection();

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!csrfValid()) {
        header('Location: pages.php?msg=csrf');
        exit;
    }
    $action = $_POST['form_action'] ?? '';

    if ($action === 'save') {
        // Save ONLY the page whose form was actually submitted, so the other
        // page is never clobbered by an empty field coming back from the POST.
        $submittedPage = trim((string)($_POST['submitted_page'] ?? ''));
        $targetPageKey  = in_array($submittedPage, ['about', 'privacy']) ? $submittedPage : 'about';
        if (isset($_POST['page_' . $targetPageKey])) {
            setSetting($pdo, 'page_' . $targetPageKey, hangarSanitizePageHtml($_POST['page_' . $targetPageKey]));
        }
        header('Location: pages.php?msg=saved&page=' . $targetPageKey);
        exit;
    }

    if ($action === 'restore') {
        $page = $_POST['page'] ?? '';
        if ($page === 'about' || $page === 'privacy') {
            setSetting($pdo, 'page_' . $page, '');
            header('Location: pages.php?msg=reset&page=' . $page);
            exit;
        }
    }
}

// ---- Load current page content (stored value, falling back to the built-in default) ----
$staticPages = [
    'about'   => [
        'name' => 'ABOUT PAGE',
        'link' => '../homepage/page.php?p=about',
        'desc' => 'The storefront "about us" page reached from the footer ABOUT THE HANGAR column.',
    ],
    'privacy' => [
        'name' => 'PRIVACY POLICY PAGE',
        'link' => '../homepage/page.php?p=privacy',
        'desc' => 'The storefront "policy of privacy" page reached from the footer ABOUT THE HANGAR column.',
    ],
];
foreach ($staticPages as $pageKey => $pageInfo) {
    $staticPages[$pageKey]['content']      = hangarSanitizePageHtml(getStaticPageContent($pdo, $pageKey));
    $staticPages[$pageKey]['usingDefault'] = ($pdo ? trim((string)getSetting($pdo, 'page_' . $pageKey, '')) : '') === '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STATIC PAGES | THE HANGAR ADMIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Rich-text editor for STATIC PAGES */
        .staticEditorWrap { background: #0d0d0d; border: 1px solid #3a3f47; border-radius: 6px; overflow: hidden; }
        .staticEditorToolbar {
            display: flex; flex-wrap: wrap; gap: 0.3rem; align-items: center;
            padding: 0.5rem 0.65rem; background: #15181e; border-bottom: 1px solid #3a3f47;
        }
        .staticEditorBtn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 28px; height: 28px; padding: 0 0.45rem;
            background: #1d2128; color: #d6dbe2; border: 1px solid #3a3f47; border-radius: 4px;
            font-family: var(--font-body); font-size: 0.78rem; line-height: 1; cursor: pointer;
            transition: all 0.15s ease;
        }
        .staticEditorBtn:hover { background: #2a2f38; color: #fff; }
        .staticEditorBtn.is-active { background: var(--brand-cyan); color: #080808; border-color: var(--brand-cyan); }
        .staticEditorBtn strong { font-size: 0.82rem; }
        .staticEditorBtn em { font-size: 0.82rem; }
        .staticEditorBtn u { text-decoration: underline; }
        .staticEditorSep { width: 1px; height: 18px; background: #3a3f47; margin: 0 0.15rem; flex-shrink: 0; }
        .staticEditorBody {
            min-height: 240px; max-height: 420px; overflow-y: auto;
            padding: 1rem 1.1rem; color: #e6e6e6; font-size: 0.92rem; line-height: 1.75;
            outline: none; cursor: text; word-wrap: break-word;
        }
        .staticEditorBody:focus { box-shadow: inset 0 0 0 1px rgba(63, 196, 225, 0.45); }
        .staticEditorBody:empty::before { content: 'Type your page content here\2026'; color: #5a616b; }
        .staticEditorBody h3 {
            font-family: var(--font-heading, 'Poppins', sans-serif); font-size: 1.05rem; font-weight: 700;
            letter-spacing: 0.06em; color: #ffffff; margin: 1.2rem 0 0.4rem;
        }
        .staticEditorBody h3:first-child { margin-top: 0; }
        .staticEditorBody p { margin: 0 0 0.6rem; }
        .staticEditorBody ul, .staticEditorBody ol { padding-left: 1.4rem; margin: 0 0 0.7rem; }
        .staticEditorBody li { margin-bottom: 0.25rem; }
        .staticEditorBody a { color: var(--brand-cyan); }
        .staticEditorBody b, .staticEditorBody strong { color: #ffffff; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="adminMain">
        <header class="topBar">
            <div class="topBarTitle">
                <span>//</span> STATIC PAGES
            </div>
        </header>

        <div class="contentArea">
            <?php if (isset($_GET['msg'])): ?>
                <div class="adminCard" style="background: rgba(63, 196, 225, 0.12); border-color: var(--brand-cyan); padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--brand-cyan);">System Status:</strong>
                    <?php
                        $msg = $_GET['msg'] ?? '';
                        if ($msg === 'saved') echo 'Static page content saved. The storefront about / privacy pages update immediately.';
                        elseif ($msg === 'reset') echo 'Built-in default content restored for this page.';
                        elseif ($msg === 'csrf') echo 'Security token mismatch. Please try again.';
                        else echo 'Update saved.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="adminCard" style="margin-bottom: 1.5rem;">
                <h2 class="cardTitle">STATIC PAGE CONTENT</h2>
                <p style="color: #8a8f98; font-size: 0.85rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Use the tools above each editor to format the About and Privacy Policy page text &mdash; no coding needed.
                    Bold, headings, bullet lists and links behave like a regular document editor. Changes go live on the storefront as soon as you hit SAVE.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($staticPages as $pageKey => $pageInfo): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.25rem;">
                            <h3 class="cardTitle" style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($pageInfo['name']); ?></h3>
                            <p style="color: #8a8f98; font-size: 0.8rem; line-height: 1.55; margin-bottom: 0.75rem;">
                                <?php echo htmlspecialchars($pageInfo['desc']); ?>
                                <a href="<?php echo htmlspecialchars($pageInfo['link']); ?>" target="_blank" style="color: var(--brand-cyan);">VIEW PAGE &rarr;</a>
                                <br>
                                Status:
                                <?php if ($pageInfo['usingDefault']): ?>
                                    <strong style="color: var(--brand-cyan);">showing built-in default</strong>
                                <?php else: ?>
                                    <strong>custom content saved</strong>
                                <?php endif; ?>
                            </p>

                            <form method="POST" action="pages.php" autocomplete="off" class="staticEditorForm" data-page="<?php echo htmlspecialchars($pageKey); ?>">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="form_action" value="save">
                                <input type="hidden" name="submitted_page" value="<?php echo htmlspecialchars($pageKey); ?>">

                                <div class="staticEditorWrap">
                                    <div class="staticEditorToolbar" role="toolbar" aria-label="Formatting tools">
                                        <button type="button" class="staticEditorBtn" data-cmd="undo" title="Undo">&larr;</button>
                                        <button type="button" class="staticEditorBtn" data-cmd="redo" title="Redo">&rarr;</button>
                                        <span class="staticEditorSep"></span>
                                        <button type="button" class="staticEditorBtn" data-cmd="bold" title="Bold"><strong>B</strong></button>
                                        <button type="button" class="staticEditorBtn" data-cmd="italic" title="Italic"><em>I</em></button>
                                        <button type="button" class="staticEditorBtn" data-cmd="underline" title="Underline"><u>U</u></button>
                                        <span class="staticEditorSep"></span>
                                        <button type="button" class="staticEditorBtn" data-cmd="formatBlock" data-value="H3" title="Section heading">H</button>
                                        <button type="button" class="staticEditorBtn" data-cmd="formatBlock" data-value="P" title="Normal paragraph">&para;</button>
                                        <span class="staticEditorSep"></span>
                                        <button type="button" class="staticEditorBtn" data-cmd="insertUnorderedList" title="Bullet list">&bull; List</button>
                                        <button type="button" class="staticEditorBtn" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                                        <span class="staticEditorSep"></span>
                                        <button type="button" class="staticEditorBtn" data-cmd="createLink" title="Insert link">Link</button>
                                        <button type="button" class="staticEditorBtn" data-cmd="unlink" title="Remove link">Unlink</button>
                                        <button type="button" class="staticEditorBtn" data-cmd="removeFormat" title="Clear formatting">Clear</button>
                                    </div>
                                    <div class="staticEditorBody" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Page content editor"><?php echo $pageInfo['content']; ?></div>
                                </div>
                                <input type="hidden" name="page_<?php echo htmlspecialchars($pageKey); ?>" value="">

                                <div style="display:flex; gap:0.75rem; align-items:center; margin-top:1rem; flex-wrap:wrap;">
                                    <button type="submit" class="btnPrimary">SAVE PAGE</button>
                                    <button type="button" class="btnSecondary btnDanger" data-restore="<?php echo htmlspecialchars($pageKey); ?>">RESTORE DEFAULT</button>
                                </div>
                            </form>

                            <form method="POST" action="pages.php" id="restore_form_<?php echo htmlspecialchars($pageKey); ?>" class="staticRestoreForm">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="form_action" value="restore">
                                <input type="hidden" name="page" value="<?php echo htmlspecialchars($pageKey); ?>">
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
<script>
(function () {
    'use strict';

    var editorForms = document.querySelectorAll('form.staticEditorForm');

    function toggleActive(toolbar, cmd) {
        var on = false;
        try { on = document.queryCommandState(cmd); } catch (e) { /* not supported */ }
        var btn = toolbar.querySelector('[data-cmd="' + cmd + '"]');
        if (btn) btn.classList.toggle('is-active', on);
    }

    function refreshToolbar(toolbar) {
        ['bold', 'italic', 'underline', 'insertUnorderedList', 'insertOrderedList'].forEach(function (c) {
            toggleActive(toolbar, c);
        });
    }

    editorForms.forEach(function (form) {
        var toolbar = form.querySelector('.staticEditorToolbar');
        var body = form.querySelector('.staticEditorBody');
        var hidden = form.querySelector('input[type="hidden"][name^="page_"]');
        if (!toolbar || !body || !hidden) return;

        // Keep the cursor/selection inside the editor while a toolbar button is pressed.
        toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });

        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('.staticEditorBtn');
            if (!btn) return;
            var cmd = btn.getAttribute('data-cmd');
            var val = btn.getAttribute('data-value') || null;
            body.focus();

            if (cmd === 'createLink') {
                var url = prompt('Paste the web address (URL) for the link:');
                if (url === null) return;
                url = url.trim();
                if (url === '') return;
                if (!/^(https?:)?\/\//i.test(url)) url = 'http://' + url;
                document.execCommand('createLink', false, url);
            } else if (cmd === 'formatBlock') {
                document.execCommand('formatBlock', false, val);
            } else {
                document.execCommand(cmd, false, val);
            }
            refreshToolbar(toolbar);
        });

        // Paste becomes plain text so no junk HTML ever creeps in.
        body.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            if (text) document.execCommand('insertText', false, text);
        });

        // Copy the rich-text content into the hidden field just before submitting.
        form.addEventListener('submit', function (e) {
            if (!form.classList.contains('staticRestoreForm')) {
                hidden.value = body.innerHTML;
            }
        });

        // Live highlight of active formatting for the current selection.
        body.addEventListener('mouseup', function () { refreshToolbar(toolbar); });
        body.addEventListener('keyup', function () { refreshToolbar(toolbar); });
        body.addEventListener('click', function () { refreshToolbar(toolbar); });
    });

    // RESTORE DEFAULT buttons live outside their save form; wire them to the hidden restore form.
    document.querySelectorAll('[data-restore]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm('Restore the built-in default content? Any custom text for this page will be discarded.')) return;
            var form = document.getElementById('restore_form_' + btn.getAttribute('data-restore'));
            if (form) form.submit();
        });
    });
})();
</script>
</body>
</html>