<?php
// THE HANGAR - GUND-ORDER SYSTEM
// GLOBAL ERROR HANDLING SAFETY NET
//
// Included at the top of shared/db.php so EVERY entry point (storefront,
// admin, login, REST APIs) is covered. Goals:
//   1. RAW PHP ERRORS ARE NEVER SHOWN TO USERS (display_errors forced off).
//   2. Warnings/notices are logged server-side but never break the page.
//   3. Uncaught exceptions/fatals render a friendly, on-brand 500 page —
//      or a clean JSON payload for API endpoints — with a reference code
//      the crew can match against the PHP error log for diagnosis.

if (!defined('HANGAR_ERROR_NET')) {
    define('HANGAR_ERROR_NET', true);

    // Never print raw PHP errors to end users — log them instead.
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');

    /**
     * True when the current response is (or should be) JSON — dedicated API
     * endpoints (api_*.php), JSON request bodies, or JSON-only Accept headers.
     */
    function hangarWantsJson(): bool {
        if (defined('HANGAR_JSON_ENDPOINT')) return true;
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if (strpos($script, 'api_') === 0) return true;
        $ct = (string)($_SERVER['CONTENT_TYPE'] ?? '');
        if (stripos($ct, 'application/json') !== false) return true;
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if (stripos($accept, 'application/json') !== false && stripos($accept, 'text/html') === false) return true;
        return false;
    }

    /** Short reference code so a user-visible message can be matched to the log. */
    function hangarErrorRef(): string {
        return 'HGR-ERR-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
    }

    /** Log full technical detail server-side; return the user-facing ref. */
    function hangarLogThrowable(string $kind, Throwable $e): string {
        $ref = hangarErrorRef();
        error_log(sprintf('[%s][%s] %s in %s:%d', $ref, $kind, $e->getMessage(), $e->getFile(), $e->getLine()));
        return $ref;
    }

    /** Project-root URL derived from the current script path (for the home link). */
    function hangarRootUrl(): string {
        $path = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
        foreach (['/homepage/', '/admin/', '/login/'] as $marker) {
            $pos = strpos($path, $marker);
            if ($pos !== false) {
                return rtrim(substr($path, 0, $pos + 1), '/') . '/homepage/index.php';
            }
        }
        return 'homepage/index.php';
    }
/** Friendly, on-brand 500 page (or compact box if output already started). */
    function hangarSendFatalPage(string $ref): void {
        if (headers_sent()) {
            echo '<div style="font-family:Poppins,Arial,sans-serif;background:#11141a;border:1px solid #3FC4E1;color:#fff;padding:18px;margin:10px;text-align:center;">'
               . '<strong style="color:#3FC4E1;">SIGNAL LOST — TEMPORARY HANGAR MALFUNCTION.</strong><br>'
               . 'The crew has been alerted. Reference: ' . htmlspecialchars($ref) . '</div>';
            return;
        }
        http_response_code(500);
        $home   = htmlspecialchars(hangarRootUrl());
        $refTxt = htmlspecialchars($ref);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>SYSTEM MALFUNCTION | THE HANGAR</title>'
           . '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">'
           . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#080808;color:#fff;font-family:Poppins,Arial,sans-serif;text-align:center}'
           . '.hz{background:#11141a;border:1px solid #3FC4E1;padding:48px 40px;max-width:520px;width:90%}'
           . '.hz .code{color:#3FC4E1;letter-spacing:4px;font-size:.8rem;font-weight:700;margin-bottom:14px}'
           . '.hz h1{font-size:1.5rem;letter-spacing:2px;margin:0 0 14px;text-transform:uppercase}'
           . '.hz p{color:#9ea4b0;font-size:.9rem;line-height:1.6;margin:0 0 10px}'
           . '.hz .ref{font-size:.78rem;color:#6b7280}.hz .ref span{color:#3FC4E1;font-weight:700}'
           . '.hz a{display:inline-block;margin-top:18px;padding:12px 26px;background:#3FC4E1;color:#080808;text-decoration:none;font-weight:700;letter-spacing:1px;font-size:.82rem;text-transform:uppercase}</style></head>'
           . '<body><div class="hz"><div class="code">▲ SIGNAL LOST ▲</div>'
           . '<h1>Temporary Hangar Malfunction</h1>'
           . '<p>Something went wrong on our side. The hangar crew has been alerted and is running diagnostics — please try again in a few minutes.</p>'
           . '<p class="ref">REFERENCE: <span>' . $refTxt . '</span></p>'
           . '<a href="' . $home . '">RETURN TO STOREFRONT</a></div></body></html>';
    }

    // ---- Uncaught exceptions: log details, show a friendly response ----------
    set_exception_handler(function (Throwable $e) {
        $ref = hangarLogThrowable('UNCAUGHT', $e);
        if (hangarWantsJson()) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'message' => 'System error. The hangar crew has been notified. (Ref: ' . $ref . ')']);
        } else {
            hangarSendFatalPage($ref);
        }
        exit;
    });

    // ---- Warnings/notices/deprecations: log only, never break the page ------
    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return true; // respect @-suppression
        }
        error_log(sprintf('[PHP NOTICE] %s in %s:%d', $message, $file, $line));
        return true; // suppress default output; request continues normally
    });

    // ---- Last resort: fatal errors (white-screen prevention) -----------------
    register_shutdown_function(function () {
        $err = error_get_last();
        if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }
        $ref = hangarErrorRef();
        error_log(sprintf('[%s][FATAL] %s in %s:%d', $ref, $err['message'], $err['file'], $err['line']));
        if (hangarWantsJson()) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(['success' => false, 'message' => 'System error. The hangar crew has been notified. (Ref: ' . $ref . ')']);
        } elseif (!headers_sent()) {
            hangarSendFatalPage($ref);
        } else {
            echo '<div style="font-family:Poppins,Arial,sans-serif;background:#11141a;border:1px solid #3FC4E1;color:#fff;padding:18px;margin:10px;text-align:center;"><strong style="color:#3FC4E1;">SIGNAL LOST — TEMPORARY HANGAR MALFUNCTION.</strong><br>The crew has been alerted. Reference: ' . htmlspecialchars($ref) . '</div>';
        }
    });
}
