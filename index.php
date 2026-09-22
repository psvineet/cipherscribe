<?php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Kolkata');

if (!ini_get('zlib.output_compression') && function_exists('ob_gzhandler')
    && strpos((string)($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

const RENDER_CACHE_VERSION = 5;

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? '1' : '0');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
if (session_status() === PHP_SESSION_NONE) session_start();

define('PP_LIST_SESSION_KEY', 'pp_list_unlocked');
define('PP_LOG_SESSION_KEY', 'pp_log_unlocked');
define('PP_SESSION_IDLE_MAX', 8 * 3600);
define('PP_SESSION_REGEN_INTERVAL', 900);

(function () {
    $now = time();
    $last = (int)($_SESSION['_pp_last_seen'] ?? 0);
    if ($last > 0 && ($now - $last) > PP_SESSION_IDLE_MAX) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_regenerate_id(true);
    }
    $regenAt = (int)($_SESSION['_pp_regen_at'] ?? 0);
    if ($regenAt === 0 || $now > $regenAt) {
        session_regenerate_id(false);
        $_SESSION['_pp_regen_at'] = $now + PP_SESSION_REGEN_INTERVAL;
    }
    $_SESSION['_pp_last_seen'] = $now;
})();

$CSP_NONCE = base64_encode(random_bytes(16));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 0');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$CSP_NONCE}'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https: http:; media-src 'self' https: http:; frame-src https: http:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function md_extra_css() {
    return <<<'CSS'
.markdown [id]{scroll-margin-top:80px;}
.markdown h5,.markdown h6{font-family:'Manrope','Inter',sans-serif;font-weight:700;color:var(--ink2);}
.markdown h5{font-size:14.5px;}
.markdown h6{font-size:12.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);}
.markdown .heading-anchor{opacity:0;margin-left:8px;font-size:.8em;font-weight:500;color:var(--ink3);text-decoration:none;transition:opacity .15s;}
.markdown h1:hover .heading-anchor,.markdown h2:hover .heading-anchor,.markdown h3:hover .heading-anchor,.markdown h4:hover .heading-anchor,.markdown h5:hover .heading-anchor,.markdown h6:hover .heading-anchor,.markdown .heading-anchor:focus{opacity:1;}

.markdown mark{background:rgba(250,204,21,.38);color:inherit;padding:1px 5px;border-radius:4px;-webkit-box-decoration-break:clone;box-decoration-break:clone;}
.markdown del{color:var(--ink3);text-decoration-thickness:1.5px;}
.markdown sup,.markdown sub{font-size:.75em;line-height:0;position:relative;vertical-align:baseline;}
.markdown sup{top:-.5em;}
.markdown sub{bottom:-.25em;}
.markdown .tok-var{color:#82AAFF;}

.markdown .admonition-note{--adm:#2F6FEB;--adm-rgb:47,111,235;}
.markdown .admonition-tip{--adm:#168A5C;--adm-rgb:22,138,92;}
.markdown .admonition-important{--adm:#8250DF;--adm-rgb:130,80,223;}
.markdown .admonition-warning{--adm:#B86E00;--adm-rgb:184,110,0;}
.markdown .admonition-caution{--adm:#D1345B;--adm-rgb:209,52,91;}
[data-theme="dark"] .markdown .admonition-note{--adm:#6EA2FF;--adm-rgb:110,162,255;}
[data-theme="dark"] .markdown .admonition-tip{--adm:#3DD9A0;--adm-rgb:61,217,160;}
[data-theme="dark"] .markdown .admonition-important{--adm:#B18CFF;--adm-rgb:177,140,255;}
[data-theme="dark"] .markdown .admonition-warning{--adm:#F0A93E;--adm-rgb:240,169,62;}
[data-theme="dark"] .markdown .admonition-caution{--adm:#FF7A98;--adm-rgb:255,122,152;}
.markdown .admonition{margin:22px 0;padding:14px 18px 14px 16px;border-radius:10px;border:1px solid rgba(var(--adm-rgb),.28);border-left:4px solid var(--adm);background:rgba(var(--adm-rgb),.09);color:var(--ink2);}
.markdown .admonition-title{display:flex;align-items:center;gap:8px;margin:0 0 8px;font-family:'Manrope','Inter',sans-serif;font-weight:800;font-size:12.5px;letter-spacing:.06em;text-transform:uppercase;line-height:1.2;color:var(--adm);}
.markdown .admonition-title svg{width:16px;height:16px;flex:0 0 16px;}
.markdown .admonition-body>:first-child{margin-top:0;}
.markdown .admonition-body>:last-child{margin-bottom:0;}
.markdown .admonition-body p{margin:8px 0;}
.markdown .admonition-body :not(pre)>code{background:rgba(var(--adm-rgb),.14);color:var(--adm);}
.markdown .admonition-body .code-block{margin:12px 0;}

.markdown sup.fn-ref{margin-left:1px;font-size:.7em;top:-.55em;}
.markdown sup.fn-ref a{display:inline-block;min-width:1.55em;padding:1px 5px;border-radius:6px;background:var(--accent-soft);color:var(--accent);font-family:'JetBrains Mono',monospace;font-weight:700;line-height:1.4;text-align:center;text-decoration:none;}
.markdown sup.fn-ref a:hover,.markdown sup.fn-ref a:target{background:var(--accent);color:#fff;opacity:1;}
.markdown .footnotes{margin-top:44px;font-size:13.5px;line-height:1.65;color:var(--ink2);}
.markdown .footnotes hr{margin:0 0 14px;}
.markdown .footnotes ol{margin:0;padding-left:1.7em;}
.markdown .footnotes li{margin:0;padding:6px 8px;border-radius:8px;}
.markdown .footnotes li::marker{color:var(--accent);font-weight:700;font-family:'JetBrains Mono',monospace;font-size:.9em;}
.markdown .footnotes li:target{background:var(--accent-soft);}
.markdown .footnotes .fn-back{display:inline-flex;align-items:center;justify-content:center;gap:1px;width:auto;min-width:20px;height:20px;padding:0 4px;margin-left:6px;border-radius:6px;vertical-align:-4px;color:var(--ink3);text-decoration:none;font-size:11px;font-weight:700;line-height:1;}
.markdown .footnotes .fn-back svg{width:12px;height:12px;flex:0 0 12px;max-width:12px;}
.markdown .footnotes .fn-back:hover{color:var(--accent);background:var(--accent-soft);opacity:1;}

.markdown dl{margin:22px 0;}
.markdown dt{margin-top:16px;font-family:'Manrope','Inter',sans-serif;font-weight:700;color:var(--ink);}
.markdown dt:first-child{margin-top:0;}
.markdown dd{margin:6px 0 0;padding:2px 0 2px 16px;border-left:2px solid var(--accent);color:var(--ink2);}

.markdown li.task-item{list-style:none;margin-left:-1.4em;padding-left:0;}
.markdown li.task-item input[type=checkbox]{width:15px;height:15px;margin:0 8px 0 0;vertical-align:-2px;accent-color:var(--accent);}

.markdown details{margin:20px 0;padding:0 16px;border:1px solid var(--border);border-radius:10px;background:var(--surface2);}
.markdown details[open]{padding-bottom:14px;}
.markdown summary{display:flex;align-items:center;gap:10px;margin:0 -16px;padding:12px 16px;cursor:pointer;list-style:none;font-weight:700;color:var(--ink);user-select:none;}
.markdown summary::-webkit-details-marker{display:none;}
.markdown summary::before{content:"";flex:0 0 7px;width:7px;height:7px;border-right:2px solid var(--ink3);border-bottom:2px solid var(--ink3);transform:rotate(-45deg);transition:transform .15s;}
.markdown details[open]>summary::before{transform:rotate(45deg);}
.markdown details[open]>summary{margin-bottom:10px;border-bottom:1px solid var(--border);}
.markdown .custom-box{margin:20px 0;padding:16px 20px;border:1px dashed var(--border);border-radius:12px;background:var(--surface2);}
.markdown .custom-box>:first-child{margin-top:0;}
.markdown .custom-box>:last-child{margin-bottom:0;}

.markdown blockquote>:first-child{margin-top:12px;}
.markdown blockquote>:last-child{margin-bottom:12px;}
.markdown blockquote .code-block{margin:12px 0;}
.markdown tbody tr:hover td{background:var(--surface2);}

.markdown .toc{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin:20px 0;}
.markdown .toc-title{font-family:'Manrope','Inter',sans-serif;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);margin-bottom:6px;}
.markdown .toc-tree{list-style:none;padding:0;margin:0;}
.markdown .toc-tree .toc-tree{padding-left:16px;margin-top:2px;}
.markdown .toc-node{margin:1px 0;position:relative;}
.markdown .toc-branch{margin:0;}
.markdown .toc-branch-body{display:grid;grid-template-rows:0fr;transition:grid-template-rows .2s ease;}
.markdown .toc-branch[open]>.toc-branch-body{grid-template-rows:1fr;}
.markdown .toc-branch-body>.toc-tree{overflow:hidden;min-height:0;border-left:1px solid var(--border);margin-left:8px;}
.markdown .toc-branch>.toc-summary{display:flex;align-items:center;gap:7px;list-style:none;margin-inline-start:0;cursor:pointer;padding:3px 4px;border-radius:7px;transition:background-color .15s;}
.markdown .toc-branch>.toc-summary:hover{background:var(--accent-soft);}
.markdown .toc-branch>.toc-summary::-webkit-details-marker{display:none;}
.markdown .toc-branch>.toc-summary::marker{content:"";}
.markdown .toc-caret{flex:0 0 16px;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:5px;background:var(--accent-soft);color:var(--accent);font-weight:800;font-size:12px;line-height:1;font-family:'JetBrains Mono',monospace;user-select:none;transition:transform .15s,background-color .15s;}
.markdown .toc-caret::before{content:"+";}
.markdown .toc-branch[open]>.toc-summary .toc-caret{background:var(--accent);color:#fff;}
.markdown .toc-branch[open]>.toc-summary .toc-caret::before{content:"\2212";}
.markdown .toc-link{display:block;flex:1;padding:4px 8px;border-radius:7px;font-size:13.5px;line-height:1.4;color:var(--ink2);text-decoration:none;transition:background-color .15s,color .15s;}
.markdown .toc-link:hover{color:var(--accent);background:var(--accent-soft);opacity:1;}
.markdown .toc-node:not(.has-children){padding-left:23px;}
.markdown .toc-lvl-2{color:var(--ink);font-weight:700;}
.markdown .toc-lvl-3,.markdown .toc-lvl-4,.markdown .toc-lvl-5,.markdown .toc-lvl-6{color:var(--ink3);font-weight:500;font-size:12.5px;}
.markdown .toc-link.active{position:relative;color:var(--accent);font-weight:700;background:var(--accent-soft);}
.markdown .toc-link.active::before{content:"";position:absolute;left:-4px;top:3px;bottom:3px;width:2.5px;border-radius:2px;background:var(--accent);}
.markdown hr{border:0;height:0;margin:32px 0;border-top:1px solid var(--border);}
.markdown hr.hr-solid{border-top:1px solid var(--border);}
.markdown hr.hr-dashed{border-top:2px dashed var(--ink3);opacity:.6;}
.markdown hr.hr-dotted{border-top:3px dotted var(--ink3);opacity:.6;}
.markdown .footnotes hr{border-top:1px solid var(--border);opacity:1;}
.markdown ul{list-style:disc;}
.markdown ul ul{list-style:circle;}
.markdown ul ul ul{list-style:square;}
.markdown ul>li::marker{color:var(--accent);}
.markdown ol>li::marker{color:var(--ink3);font-weight:700;}
.markdown li.task-item::marker{content:"";}
.code-head{display:inline-flex;align-items:center;gap:10px;min-width:0;}
.code-title{color:#C9D1E3;font-weight:600;text-transform:none;letter-spacing:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60vw;}
.code-block.has-ln pre{display:flex;align-items:flex-start;overflow-x:auto;white-space:pre;}
.code-block.has-ln pre,.code-block.has-ln code,.code-block.has-ln .code-gutter{line-height:1.6;font-size:13px;font-family:'JetBrains Mono',monospace;}
.code-block.has-ln code{white-space:pre;overflow-wrap:normal;word-break:normal;flex:1 0 auto;padding-left:14px;}
.code-block.has-ln .code-gutter{flex:0 0 auto;text-align:right;padding-right:12px;border-right:1px solid rgba(255,255,255,.1);color:#6C7280;white-space:pre;-webkit-user-select:none;user-select:none;}
CSS;
}

$CFG = [
    'version'       => '2.0.0',
    'site_name'     => 'CipherScribe',
    'rss_title'     => 'CipherScribe — Security Research & Tech Notes',
    'rss_desc'      => 'Field notes on cybersecurity research, CTF writeups, vulnerability analysis and technical articles.',
    'posts_dir'     => __DIR__ . '/posts',
    'assets_dir'    => __DIR__ . '/assets',
    'home_url'      => './',
    'accent'        => 'indigo',
    'per_page'      => 8,
    'self'          => basename(__FILE__),
    'base_url'      => '',
    'cdn_url'       => '',
    'protected_ttl'       => 30 * 60,
    'protected_max_fails' => 5,
    'protected_ip_max'    => 20,
    'protected_post_max'  => 60,
    'protected_window'    => 900,
    'protected_password'  => '',
    'protected_list_password' => '',
    'access_log_password' => '',
    'trusted_ip_header'   => '',
];

foreach ([$CFG['posts_dir'], $CFG['assets_dir']] as $d) {
    if (!is_dir($d)) @mkdir($d, 0755, true);
}

$postsHtaccess = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
if (is_dir($CFG['posts_dir']) && @file_get_contents($CFG['posts_dir'] . '/.htaccess') !== $postsHtaccess) {
    @file_put_contents($CFG['posts_dir'] . '/.htaccess', $postsHtaccess);
}

$assetsHtaccess = "Options -Indexes -ExecCGI\n"
    . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
    . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
if (is_dir($CFG['assets_dir']) && @file_get_contents($CFG['assets_dir'] . '/.htaccess') !== $assetsHtaccess) {
    @file_put_contents($CFG['assets_dir'] . '/.htaccess', $assetsHtaccess);
}

function install_htaccess($CFG) {
    $root = __DIR__;
    $self = $CFG['self'];
    $sentinelPath = $root . '/.htaccess-meta.json';

    $rules = <<<HTACCESS
DirectoryIndex {$self}
Options -Indexes -MultiViews

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^robots\.txt$ {$self}?robots=1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^sitemap\.xml$ {$self}?sitemap=1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^rss\.xml$ {$self}?rss=1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^p/([A-Za-z0-9]{1,20})$ {$self}?id=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^img/([A-Za-z0-9_\-]{10,600})$ {$self}?img=$1 [L,QSA]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^tag/([A-Za-z0-9_\-]{1,200})$ {$self}?tag=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^cat/([A-Za-z0-9_\-]{1,200})$ {$self}?category=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^diff/([A-Za-z0-9_\-]{1,200})$ {$self}?difficulty=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^event/([A-Za-z0-9_\-]{1,200})$ {$self}?event=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^protected/?$ {$self}?protected_list=1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^access-log/?$ {$self}?access_log=1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^export/([A-Za-z0-9]{1,20})/html$ {$self}?export=html&id=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^export/([A-Za-z0-9]{1,20})/md$ {$self}?export=md&id=$1 [L,QSA,NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^md/([A-Za-z0-9]{1,20})$ {$self}?export=md&id=$1&view=1 [L,QSA,NC]
</IfModule>

<FilesMatch "(^\.|\.(md|json|log|bak|old|orig|sql|ini|inc)$)">
    Require all denied
</FilesMatch>
<IfModule !mod_authz_core.c>
    <FilesMatch "(^\.|\.(md|json|log|bak|old|orig|sql|ini|inc)$)">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>
<IfModule mod_headers.c>
    Header set X-Author "Vineet Pratap Singh"
    Header set X-Content-Type-Options "nosniff"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/xml text/javascript
    AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json
    AddOutputFilterByType DEFLATE application/xml application/rss+xml application/atom+xml
    AddOutputFilterByType DEFLATE image/svg+xml font/ttf font/otf application/vnd.ms-fontobject
    <IfModule mod_setenvif.c>
        BrowserMatch "MSIE [1-6]" no-gzip
    </IfModule>
</IfModule>

HTACCESS;

    $sentinel = $CFG['version'] . '|' . $self . '|' . substr(md5($rules), 0, 10);
    $prevSentinel = is_file($sentinelPath) ? trim(file_get_contents($sentinelPath)) : '';
    if ($prevSentinel === $sentinel && is_file($root . '/.htaccess')) return false;

    $ok = @file_put_contents($root . '/.htaccess', $rules) !== false;
    if ($ok) @file_put_contents($sentinelPath, $sentinel);
    if (!$ok) error_log('CipherScribe: failed to write .htaccess — check folder write permissions.');
    return $ok;
}
$justInstalled = install_htaccess($CFG);

function url_scheme_ok($url) {
    $c = preg_replace('/[\x00-\x20\x7F-\x9F]+/', '', (string)$url);
    if ($c === null) return false;
    if (preg_match('/^([A-Za-z][A-Za-z0-9+.\-]*):/', $c, $m)) {
        return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel', 'ftp'], true);
    }
    return true;
}

class MDX {
    private $footnotes = [];
    private $headings  = [];
    private $slugCounts = [];
    private $linkRefs = [];
    private $fnOrder = [];
    private $fnRefCount = [];
    public $meta = [];
    public $cleanSrc = '';

    public function parseFrontMatter($src) {
        $this->meta = [];
        $src = preg_replace('/^\xEF\xBB\xBF/', '', $src);
        $src = ltrim(str_replace(["\r\n", "\r"], "\n", $src), "\n");
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n?/s', $src, $m)) {
            $yaml = $m[1];
            foreach (preg_split('/\r\n|\r|\n/', $yaml) as $line) {
                if (preg_match('/^([A-Za-z0-9_\-]+)\s*:\s*(.*)$/', trim($line), $mm)) {
                    $key = strtolower($mm[1]);
                    $val = trim($mm[2], " \t\"'");
                    $this->meta[$key] = $val;
                }
            }
            $src = substr($src, strlen($m[0]));
        }
        return $src;
    }

    private function extractLinkRefs($src) {
        return preg_replace_callback(
            '/^ {0,3}\[(?!\^)([^\]]+)\]:\s*<?([^\s>]+)>?(?:\s+["\'(]([^"\')]*)["\')])?\s*$/m',
            function($m) {
                $key = strtolower(trim($m[1]));
                $this->linkRefs[$key] = ['url' => $m[2], 'title' => $m[3] ?? ''];
                return '';
            },
            $src
        );
    }

    private function fnSlug($id) {
        return $this->esc(preg_replace('/[^A-Za-z0-9_\-]/', '-', (string)$id));
    }

    private function extractFootnotes($src) {
        $lines = explode("\n", $src);
        $out = [];
        $n = count($lines);
        $fence = null;
        for ($i = 0; $i < $n; $i++) {
            $line = $lines[$i];
            if ($fence === null && preg_match('/^ {0,3}(```+|~~~+)/', $line, $fm)) {
                $fence = $fm[1][0];
            } elseif ($fence !== null && preg_match('/^ {0,3}' . preg_quote($fence, '/') . '{3,}\s*$/', $line)) {
                $fence = null;
                $out[] = $line;
                continue;
            }
            if ($fence === null && preg_match('/^ {0,3}\[\^([^\]]+)\]:\s*(.*)$/', $line, $m)) {
                $text = trim($m[2]);
                while ($i + 1 < $n && preg_match('/^(?: {2,}|\t)\S/', $lines[$i + 1])) {
                    $i++;
                    $text .= ' ' . trim($lines[$i]);
                }
                $this->footnotes[$m[1]] = $text;
                continue;
            }
            $out[] = $line;
        }
        return implode("\n", $out);
    }

    private function sanitizeInlineHtml($html) {
        $allowedTags = [
            'p','div','span','br','hr','strong','em','b','i','u','s','del','ins','mark','sub','sup',
            'ul','ol','li','blockquote','pre','code','table','thead','tbody','tr','th','td',
            'h1','h2','h3','h4','h5','h6','a','img','video','audio','source','figure','figcaption',
            'details','summary'
        ];
        $allowedAttrs = [
            'a'      => ['href','title','target','rel'],
            'img'    => ['src','alt','title','width','height','loading'],
            'video'  => ['src','controls','width','height','playsinline','poster'],
            'audio'  => ['src','controls'],
            'source' => ['src','type'],
            'table'  => ['align'],
            'td'     => ['align','colspan','rowspan'],
            'th'     => ['align','colspan','rowspan'],
            '*'      => ['class','id'],
        ];
        $prevLibxml = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<!DOCTYPE html><html><body><div>' . $html . '</div></body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prevLibxml);
        $body = $doc->getElementsByTagName('body')->item(0);
        $root = $body ? $body->getElementsByTagName('div')->item(0) : null;
        if (!$root) return $this->esc(strip_tags($html));
        $this->sanitizeNode($doc, $root, $allowedTags, $allowedAttrs);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private function sanitizeNode($doc, $node, $allowedTags, $allowedAttrs) {
        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                foreach (iterator_to_array($child->childNodes) as $grandchild) {
                    $node->insertBefore($grandchild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            if ($child->hasAttributes()) {
                $allowed = array_merge($allowedAttrs['*'] ?? [], $allowedAttrs[$tag] ?? []);
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    $value = $attr->value;
                    $remove = false;
                    if (!in_array($name, $allowed, true)) {
                        $remove = true;
                    } elseif (strpos($name, 'on') === 0) {
                        $remove = true;
                    } elseif (($name === 'href' || $name === 'src' || $name === 'poster') && !url_scheme_ok($value)) {
                        $remove = true;
                    }
                    if ($remove) $child->removeAttribute($attr->name);
                }
                if ($tag === 'a') $child->setAttribute('rel', 'noopener noreferrer nofollow ugc');
            }
            $this->sanitizeNode($doc, $child, $allowedTags, $allowedAttrs);
        }
    }

    private function fenceAttrs($raw) {
        $r = ['title' => '', 'lines' => false, 'start' => 1];
        $raw = trim((string)$raw);
        if ($raw === '') return $r;
        if (!preg_match_all('/([A-Za-z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))|(\S+)/', $raw, $all, PREG_SET_ORDER)) return $r;
        foreach ($all as $a) {
            if (isset($a[1]) && $a[1] !== '') {
                $k = strtolower($a[1]);
                $v = $a[2] ?? '';
                if ($v === '' && isset($a[3]) && $a[3] !== '') $v = $a[3];
                if ($v === '' && isset($a[4]) && $a[4] !== '') $v = $a[4];
                if (in_array($k, ['title', 'file', 'filename'], true)) {
                    $r['title'] = mb_substr($v, 0, 120, 'UTF-8');
                } elseif (in_array($k, ['linenos', 'ln', 'numbers', 'start'], true)) {
                    if (in_array(strtolower($v), ['false', 'off', 'no'], true)) { $r['lines'] = false; continue; }
                    $r['lines'] = true;
                    if ($v !== '' && ctype_digit($v) && (int)$v < 100000) $r['start'] = max(1, (int)$v);
                }
            } elseif (isset($a[5]) && $a[5] !== '') {
                $tok = $a[5];
                if (in_array(strtolower($tok), ['linenos', 'ln', 'numbers'], true)) $r['lines'] = true;
                elseif ($r['title'] === '' && preg_match('/^[\w.\-\/@+]{1,120}$/', $tok)) $r['title'] = $tok;
            }
        }
        return $r;
    }

    private function highlightCode($src, $lang) {
        $sets = [
            'php'    => ['function','return','if','else','elseif','foreach','while','for','class','public','private','protected','static','new','use','namespace','try','catch','finally','throw','echo','print','switch','case','break','continue','default','const','array','null','true','false','extends','implements','interface','abstract','trait','yield','match','fn'],
            'js'     => ['function','return','if','else','for','while','const','let','var','class','new','try','catch','finally','throw','switch','case','break','continue','default','import','export','from','async','await','typeof','instanceof','null','undefined','true','false','this','extends','super','yield','of','in'],
            'python' => ['def','return','if','elif','else','for','while','class','import','from','as','try','except','finally','raise','with','lambda','yield','pass','break','continue','None','True','False','and','or','not','in','is','global','nonlocal','async','await'],
            'bash'   => ['if','then','else','elif','fi','for','while','do','done','case','esac','function','return','local','export','echo','exit','break','continue','in'],
            'sql'    => ['select','from','where','insert','into','values','update','set','delete','join','left','right','inner','outer','on','group','by','order','having','limit','create','table','alter','drop','and','or','not','null','as','distinct','union','case','when','then','end','asc','desc'],
            'java'   => ['public','private','protected','class','interface','extends','implements','static','final','void','new','return','if','else','for','while','do','switch','case','break','continue','default','try','catch','finally','throw','throws','import','package','this','super','null','true','false'],
            'c'      => ['int','char','float','double','void','struct','typedef','if','else','for','while','do','switch','case','break','continue','default','return','static','const','sizeof','include','define'],
        ];
        $alias = ['javascript'=>'js','ts'=>'js','typescript'=>'js','jsx'=>'js','py'=>'python','sh'=>'bash','shell'=>'bash','zsh'=>'bash','cpp'=>'c','c++'=>'c','h'=>'c',
            'jsonc'=>'json','json5'=>'json','yml'=>'yaml','ps1'=>'powershell','pwsh'=>'powershell','psm1'=>'powershell','ps'=>'powershell','golang'=>'go','rs'=>'rust',
            'nasm'=>'asm','x86'=>'asm','x86asm'=>'asm','x64'=>'asm','assembly'=>'asm','masm'=>'asm','gas'=>'asm','s'=>'asm','https'=>'http','request'=>'http','response'=>'http'];
        $key = $alias[$lang] ?? $lang;
        $ext = [
            'json' => [
                'kw' => ['true','false','null'],
                'c' => '"(?:\\\\.|[^"\\\\\n])*"(?=\s*:)',
                'g1' => 'tok-var',
                'n' => '(-?\b\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\b)',
            ],
            'yaml' => [
                'kw' => ['true','false','null','yes','no','on','off'],
                'c' => '(?<!\S)#[^\n]*',
                'v' => '([\w.\/\-]+)(?=[ \t]*:(?:[ \t\n]|\z))',
                'x' => '(?<![\w])[&*][A-Za-z_][\w\-]*',
                'f' => 'si',
            ],
            'http' => [
                'kw' => ['GET','POST','PUT','DELETE','PATCH','HEAD','OPTIONS','CONNECT','TRACE','HTTP'],
                'c' => '(?!x)x',
                's' => '"(?:\\\\.|[^"\\\\\n])*"',
                'v' => '([A-Za-z][A-Za-z0-9\-]*)(?=:[ \t])',
            ],
            'powershell' => [
                'kw' => ['function','param','if','elseif','else','foreach','for','while','do','until','switch','return','try','catch','finally','throw','break','continue','begin','process','end','in','class','using','exit','trap','filter','workflow','data','default'],
                'c' => '#[^\n]*|<#.*?#>',
                's' => '@".*?"@|@\'.*?\'@|"(?:`.|[^"`\n])*"|\'(?:\'\'|[^\'\n])*\'',
                'v' => '(\$(?:\{[^}\n]*\}|[\w:]+))',
                'x' => '\b(?:Get|Set|New|Remove|Add|Invoke|Start|Stop|Write|Read|Import|Export|Out|Select|Where|ForEach|Test|Enable|Disable|Copy|Move|Clear|Format|Join|Split|Convert|ConvertTo|ConvertFrom|Compare|Measure|Sort|Group|Register|Unregister|Enter|Exit|Install|Uninstall|Update|Find|Rename|Resolve|Restart|Resume|Suspend|Wait|Show|Send|Receive|Push|Pop|Grant|Revoke|Block|Unblock|Connect|Disconnect)-[A-Za-z][A-Za-z0-9]*|(?<![\w\-])-[A-Za-z][A-Za-z0-9]*\b',
                'f' => 'si',
            ],
            'go' => [
                'kw' => ['break','case','chan','const','continue','default','defer','else','fallthrough','for','func','go','goto','if','import','interface','map','package','range','return','select','struct','switch','type','var','nil','true','false','iota'],
                'c' => '\/\/[^\n]*|\/\*.*?\*\/',
                's' => '"(?:\\\\.|[^"\\\\\n])*"|`[^`]*`|\'(?:\\\\.|[^\'\\\\\n])*\'',
                'n' => '(\b(?:0[xX][0-9A-Fa-f_]+|\d[\d_]*(?:\.\d+)?)\b)',
            ],
            'rust' => [
                'kw' => ['as','async','await','break','const','continue','crate','dyn','else','enum','extern','false','fn','for','if','impl','in','let','loop','match','mod','move','mut','pub','ref','return','self','Self','static','struct','super','trait','true','type','unsafe','use','where','while'],
                'c' => '\/\/[^\n]*|\/\*.*?\*\/',
                's' => '"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\(?:u\{[0-9A-Fa-f]+\}|x[0-9A-Fa-f]{2}|.)|[^\'\\\\\n])\'',
                'v' => '(\'[A-Za-z_]\w*)(?!\')',
                'n' => '(\b(?:0[xX][0-9A-Fa-f_]+|0b[01_]+|\d[\d_]*(?:\.\d+)?)\b)',
                'x' => '\b[a-z_][a-z0-9_]*!(?=[(\[{])',
            ],
            'asm' => [
                'kw' => ['mov','movzx','movsx','movsb','movsw','movsd','movsq','push','pop','call','ret','retn','jmp','je','jne','jz','jnz','jg','jge','jl','jle','ja','jae','jb','jbe','js','jns','cmp','test','add','sub','mul','imul','div','idiv','inc','dec','and','or','xor','not','neg','shl','shr','sar','sal','rol','ror','lea','nop','int','syscall','sysenter','leave','enter','xchg','cdq','cqo','cwd','rep','repe','repne','stosb','stosd','stosq','lodsb','scasb','cmpsb','loop','hlt','ud2','cld','std','clc','stc','pushad','popad','pushfd','popfd','section','segment','global','extern','db','dw','dd','dq','equ','times','bits','org','resb','resw','resd','resq','byte','word','dword','qword','ptr','offset'],
                'c' => ';[^\n]*|\/\/[^\n]*|\/\*.*?\*\/',
                'v' => '(%?\b(?:[re]?[abcd]x|[abcd][lh]|[re]?(?:si|di|bp|sp)|[sd]il|[sb]pl|r(?:8|9|1[0-5])[dwb]?|[re]?ip|[xyz]mm\d{1,2}|[cdefgs]s|st\d?|cr[0-4]|eflags|rflags)\b)',
                'n' => '((?<![\w.])(?:0[xX][0-9A-Fa-f]+|[0-9][0-9A-Fa-f]*h|\d+)\b)',
                'x' => '(?<![\w.])\.(?:text|data|bss|rodata|globl|global|section|byte|word|long|quad|ascii|asciz|string|align|equ|set|type|size)\b',
                'f' => 'si',
            ],
        ];
        foreach ($ext as $ek => $ev) $sets[$ek] = $ev['kw'];
        if (!isset($sets[$key])) return $this->esc($src);
        $e = $ext[$key] ?? [];

        $comments = [
            'php'    => '(?:\/\/|#)[^\n]*|\/\*.*?\*\/',
            'js'     => '\/\/[^\n]*|\/\*.*?\*\/',
            'python' => '#[^\n]*',
            'bash'   => '(?<![\$\w])#[^\n]*',
            'sql'    => '--[^\n]*|\/\*.*?\*\/',
            'java'   => '\/\/[^\n]*|\/\*.*?\*\/',
            'c'      => '\/\/[^\n]*|\/\*.*?\*\/',
        ];
        $strings = '"(?:\\\\.|[^"\\\\\n])*"|\'(?:\\\\.|[^\'\\\\\n])*\'';
        if ($key === 'js') $strings .= '|`(?:\\\\.|[^`\\\\])*`';
        $commentRe = $e['c'] ?? $comments[$key];
        $stringRe  = $e['s'] ?? $strings;
        $varRe     = $e['v'] ?? (in_array($key, ['php', 'bash'], true) ? '(\$\w+)' : '(\x00)');
        $numRe     = $e['n'] ?? '(\b\d+(?:\.\d+)?\b)';
        $flags     = $e['f'] ?? ($key === 'sql' ? 'si' : 's');
        $g1Class   = $e['g1'] ?? 'tok-comment';
        $kw = implode('|', array_map(function($k) { return preg_quote($k, '~'); }, $sets[$key]));
        $kwRe = '\b(?:' . $kw . ')\b';
        if (!empty($e['x'])) $kwRe = '(?:' . $e['x'] . '|' . $kwRe . ')';
        $re = '~(' . $commentRe . ')|(' . $stringRe . ')|' . $varRe . '|' . $numRe . '|(' . $kwRe . ')~' . $flags;

        if (!preg_match_all($re, $src, $ms, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) return $this->esc($src);
        $out = '';
        $pos = 0;
        foreach ($ms as $m) {
            $tok = $m[0][0];
            $off = $m[0][1];
            $out .= $this->esc(substr($src, $pos, $off - $pos));
            $pos = $off + strlen($tok);
            if (isset($m[1]) && $m[1][1] >= 0)     $cls = $g1Class;
            elseif (isset($m[2]) && $m[2][1] >= 0) $cls = 'tok-string';
            elseif (isset($m[3]) && $m[3][1] >= 0) $cls = 'tok-var';
            elseif (isset($m[4]) && $m[4][1] >= 0) $cls = 'tok-number';
            else                                   $cls = 'tok-keyword';
            $out .= '<span class="' . $cls . '">' . $this->esc($tok) . '</span>';
        }
        $out .= $this->esc(substr($src, $pos));
        return $out;
    }

    private function slugify($text) {
        $s = strtolower(trim(preg_replace('/[^\w\s-]/', '', strip_tags($text))));
        $s = preg_replace('/[\s_]+/', '-', $s);
        $s = trim($s, '-');
        if ($s === '') $s = 'section';
        if (isset($this->slugCounts[$s])) {
            $this->slugCounts[$s]++;
            $s .= '-' . $this->slugCounts[$s];
        } else {
            $this->slugCounts[$s] = 0;
        }
        return $s;
    }

    private function esc($t) { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }

    private function safeUrl($url) {
        $url = trim($url);
        return url_scheme_ok($url) ? $url : '#';
    }

    private function inline($text) {
        $codes = [];
        $escapes = [];
        $text = preg_replace_callback('/\\\\([\\\\`*_{}\[\]()#+\-.!>~=|"\'&<])|(`+)(.+?)\2/s', function($m) use (&$codes, &$escapes) {
            if (($m[1] ?? '') !== '') {
                $key = "\x01ESC" . count($escapes) . "\x01";
                $escapes[$key] = $m[1];
                return $key;
            }
            $key = "\x01CODE" . count($codes) . "\x01";
            $codes[$key] = '<code>' . $this->esc(trim($m[3])) . '</code>';
            return $key;
        }, $text);

        $text = preg_replace_callback('/!\[([^\]]*)\]\(((?:[^()\s]|\([^()\s]*\))+)(?:\s+"([^"]*)")?(?:\s*=(\d+)x(\d*))?\)/', function($m) {
            $alt = $this->esc($m[1]);
            $src = $this->esc(asset_url($this->safeUrl($m[2])));
            $title = !empty($m[3]) ? ' title="' . $this->esc($m[3]) . '"' : '';
            $dims = '';
            if (!empty($m[4])) {
                $dims = ' width="' . intval($m[4]) . '"';
                if (!empty($m[5])) $dims .= ' height="' . intval($m[5]) . '"';
            }
            $isVideo = preg_match('/\.(mp4|webm|mov|ogg)$/i', $m[2]);
            if ($isVideo) {
                $cap = $alt ? "<figcaption>{$alt}</figcaption>" : '';
                return "<figure class=\"md-figure\"><video src=\"{$src}\" controls playsinline{$dims}></video>{$cap}</figure>";
            }
            $cap = $alt ? "<figcaption>{$alt}</figcaption>" : '';
            return "<figure class=\"md-figure\"><img src=\"{$src}\" alt=\"{$alt}\"{$title}{$dims} loading=\"lazy\">{$cap}</figure>";
        }, $text);

        $text = preg_replace_callback('/\[\^([^\]]+)\]/', function($m) {
            $id = $m[1];
            if (!isset($this->footnotes[$id])) return $m[0];
            if (!isset($this->fnOrder[$id])) $this->fnOrder[$id] = count($this->fnOrder) + 1;
            $num = $this->fnOrder[$id];
            $this->fnRefCount[$id] = ($this->fnRefCount[$id] ?? 0) + 1;
            $k = $this->fnRefCount[$id];
            $safe = $this->fnSlug($id);
            $refId = $k === 1 ? "fnref-{$safe}" : "fnref-{$safe}-{$k}";
            return "<sup class=\"fn-ref\"><a href=\"#fn-{$safe}\" id=\"{$refId}\" title=\"Footnote {$num}\">{$num}</a></sup>";
        }, $text);

        $text = preg_replace_callback('/\[([^\]]+)\]\(((?:[^()\s]|\([^()\s]*\))+)(?:\s+"([^"]*)")?\)/', function($m) {
            $label = $m[1];
            $href = $this->esc($this->safeUrl($m[2]));
            $title = !empty($m[3]) ? ' title="' . $this->esc($m[3]) . '"' : '';
            $external = preg_match('#^https?://#', $m[2]) ? ' target="_blank" rel="noopener noreferrer"' : '';
            return "<a href=\"{$href}\"{$title}{$external}>" . $this->inlineSimple($label) . "</a>";
        }, $text);

        $text = preg_replace_callback('/\[([^\]]+)\]\[([^\]]*)\]/', function($m) {
            $label = $m[1];
            $key = strtolower(trim($m[2] !== '' ? $m[2] : $m[1]));
            if (!isset($this->linkRefs[$key])) return $m[0];
            $ref = $this->linkRefs[$key];
            $href = $this->esc($this->safeUrl($ref['url']));
            $title = $ref['title'] ? ' title="' . $this->esc($ref['title']) . '"' : '';
            $external = preg_match('#^https?://#', $ref['url']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            return "<a href=\"{$href}\"{$title}{$external}>" . $this->inlineSimple($label) . "</a>";
        }, $text);

        $text = preg_replace_callback('/\[([^\]\^][^\]]*)\](?!\(|\[|:)/', function($m) {
            $key = strtolower(trim($m[1]));
            if (!isset($this->linkRefs[$key])) return $m[0];
            $ref = $this->linkRefs[$key];
            $href = $this->esc($this->safeUrl($ref['url']));
            $title = $ref['title'] ? ' title="' . $this->esc($ref['title']) . '"' : '';
            $external = preg_match('#^https?://#', $ref['url']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            return "<a href=\"{$href}\"{$title}{$external}>" . $this->inlineSimple($m[1]) . "</a>";
        }, $text);

        $text = preg_replace_callback('/<((?:https?|ftp):\/\/[^\s>]+)>/', function($m) {
            $u = $this->esc($m[1]);
            return "<a href=\"{$u}\" target=\"_blank\" rel=\"noopener noreferrer\">{$u}</a>";
        }, $text);

        $text = preg_replace('/(\*\*\*|___)(.+?)\1/s', '<strong><em>$2</em></strong>', $text);
        $text = preg_replace('/(\*\*|__)(.+?)\1/s', '<strong>$2</strong>', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\*)(.+?)\*(?!\*)/s', '<em>$1</em>', $text);
        $text = preg_replace('/(?<![\w_])_(?!_)(.+?)_(?!_)/s', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $text);
        $text = preg_replace('/==(.+?)==/s', '<mark>$1</mark>', $text);
        $text = preg_replace('/\^([^\s^]+)\^/', '<sup>$1</sup>', $text);
        $text = preg_replace('/(?<!~)~([^\s~]+)~(?!~)/', '<sub>$1</sub>', $text);
        $text = preg_replace_callback('/:([a-z0-9_+\-]+):/', function($m) {
            $map = [
                'smile'=>'😄','laughing'=>'😆','joy'=>'😂','wink'=>'😉','heart'=>'❤️','fire'=>'🔥',
                'rocket'=>'🚀','tada'=>'🎉','warning'=>'⚠️','white_check_mark'=>'✅','x'=>'❌',
                'bulb'=>'💡','eyes'=>'👀','thumbsup'=>'👍','thumbsdown'=>'👎','100'=>'💯',
                'star'=>'⭐','bug'=>'🐛','lock'=>'🔒','unlock'=>'🔓','key'=>'🔑','shield'=>'🛡️',
                'zap'=>'⚡','clap'=>'👏','pray'=>'🙏','skull'=>'💀','ghost'=>'👻','robot'=>'🤖',
                'computer'=>'💻','books'=>'📚','pushpin'=>'📌','memo'=>'📝','link'=>'🔗',
            ];
            return $map[$m[1]] ?? $m[0];
        }, $text);

        $text = preg_replace('/( {2,}|\\\\)\n/', "<br>\n", $text);

        foreach ($codes as $k => $v) $text = str_replace($k, $v, $text);
        foreach ($escapes as $k => $v) $text = str_replace($k, $this->esc($v), $text);
        return $text;
    }

    private function inlineSimple($text) {
        $text = $this->esc($text);
        $text = preg_replace('/(\*\*|__)(.+?)\1/s', '<strong>$2</strong>', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\*)(.+?)\*(?!\*)/s', '<em>$1</em>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        return $text;
    }

    public function toHTML($src) {
        $src = str_replace(["\r\n", "\r"], "\n", $src);
        $src = $this->parseFrontMatter($src);
        $src = $this->extractFootnotes($src);
        $src = $this->extractLinkRefs($src);
        $this->cleanSrc = $src;
        $lines = explode("\n", $src);
        $html = $this->blocks($lines);

        if (!empty($this->footnotes) && !empty($this->fnOrder)) {
            $arrow = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 6 6v1"/></svg>';
            $html .= "<div class=\"footnotes\"><hr><ol>";
            foreach ($this->fnOrder as $id => $num) {
                if (!isset($this->footnotes[$id])) continue;
                $safe = $this->fnSlug($id);
                $count = $this->fnRefCount[$id] ?? 1;
                $backs = '';
                for ($k = 1; $k <= $count; $k++) {
                    $refId = $k === 1 ? "fnref-{$safe}" : "fnref-{$safe}-{$k}";
                    $label = $k === 1 ? '' : "<sup>{$k}</sup>";
                    $backs .= "<a href=\"#{$refId}\" class=\"fn-back\" aria-label=\"Back to reference {$num}\" title=\"Back to reference\">{$arrow}{$label}</a>";
                }
                $html .= "<li id=\"fn-{$safe}\"><span class=\"fn-text\">" . $this->inline($this->footnotes[$id]) . "</span>{$backs}</li>";
            }
            $html .= "</ol></div>";
        }
        return $html;
    }

    public function getTOC() { return $this->headings; }

    private function blocks($lines) {
        $out = '';
        $n = count($lines);
        $i = 0;
        while ($i < $n) {
            $line = $lines[$i];

            if (trim($line) === '') { $i++; continue; }

            if (preg_match('/^\s*\[\[\s*TOC\s*\]\]\s*$/i', $line) || preg_match('/^\s*\[toc\]\s*$/i', $line)) {
                $out .= "\x02TOC\x02";
                $i++; continue;
            }

            if (preg_match('/^ {0,3}([-*_])( *\1){2,} *$/', $line, $hm)) {
                $hrClass = ['-' => 'hr-solid', '*' => 'hr-dashed', '_' => 'hr-dotted'][$hm[1]];
                $out .= "<hr class=\"{$hrClass}\">\n"; $i++; continue;
            }

            if (preg_match('/^ {0,3}(```+|~~~+)\s*([A-Za-z0-9_+\-]*)((?:\s+[^`]*)?)$/', $line, $m)) {
                $fenceChar = substr($m[1], 0, 1);
                $fenceLen = strlen($m[1]);
                $lang = strtolower($m[2]);
                $fa = $this->fenceAttrs($m[3] ?? '');
                $code = [];
                $i++;
                while ($i < $n && !preg_match('/^ {0,3}' . preg_quote($fenceChar, '/') . '{' . $fenceLen . ',}\s*$/', $lines[$i])) {
                    $code[] = $lines[$i]; $i++;
                }
                $i++;
                $codeSrc = implode("\n", $code);
                $codeStr = $this->highlightCode($codeSrc, $lang);
                $langLabel = $lang ? "<span class=\"code-lang\">{$this->esc($lang)}</span>" : '<span class="code-lang">text</span>';
                $langClass = $lang ? " language-{$this->esc($lang)}" : '';
                if ($fa['title'] !== '') $langLabel = "<span class=\"code-head\">{$langLabel}<span class=\"code-title\">{$this->esc($fa['title'])}</span></span>";
                $gutter = '';
                $blockClass = 'code-block';
                if ($fa['lines']) {
                    $nums = [];
                    $total = max(1, count($code));
                    for ($ln = 0; $ln < $total; $ln++) $nums[] = $fa['start'] + $ln;
                    $gutter = '<span class="code-gutter" aria-hidden="true">' . implode("\n", $nums) . '</span>';
                    $blockClass .= ' has-ln';
                }
                $out .= "<div class=\"{$blockClass}\"><div class=\"code-bar\">{$langLabel}<button class=\"code-copy\" type=\"button\" data-copy-code=\"1\">Copy</button></div><pre>{$gutter}<code class=\"{$langClass}\">{$codeStr}</code></pre></div>\n";
                continue;
            }

            if (preg_match('/^ {0,3}>\s*\[!(NOTE|TIP|WARNING|IMPORTANT|CAUTION)\]\s*(.*)$/i', $line, $m)) {
                $type = strtolower($m[1]);
                $customTitle = trim($m[2]);
                $body = [];
                $i++;
                while ($i < $n && preg_match('/^ {0,3}>\s?(.*)$/', $lines[$i], $bm)
                       && !preg_match('/^ {0,3}>\s*\[!(?:NOTE|TIP|WARNING|IMPORTANT|CAUTION)\]/i', $lines[$i])) {
                    $body[] = $bm[1]; $i++;
                }
                $icons = [
                    'note'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
                    'tip'       => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6M10 22h4"/>',
                    'important' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v4M12 14h.01"/>',
                    'warning'   => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/><path d="M12 9v4M12 17h.01"/>',
                    'caution'   => '<path d="M7.86 2h8.28L22 7.86v8.28L16.14 22H7.86L2 16.14V7.86z"/><path d="M12 8v4M12 16h.01"/>',
                ];
                $titleHtml = $customTitle !== '' ? $this->inline($customTitle) : ucfirst($type);
                $out .= "<div class=\"admonition admonition-{$type}\" role=\"note\"><div class=\"admonition-title\"><svg class=\"admonition-icon\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">{$icons[$type]}</svg><span>{$titleHtml}</span></div><div class=\"admonition-body\">" . $this->blocks($body) . "</div></div>\n";
                continue;
            }

            if (preg_match('/^ {0,3}>\s?(.*)$/', $line)) {
                $body = [];
                while ($i < $n) {
                    if (preg_match('/^ {0,3}>\s?(.*)$/', $lines[$i], $bm)) {
                        $body[] = $bm[1]; $i++;
                    } else {
                        break;
                    }
                }
                $out .= "<blockquote>" . $this->blocks($body) . "</blockquote>\n";
                continue;
            }

            if (preg_match('/^ {0,3}(#{1,6})\s+(.+?)\s*(?:\{#([A-Za-z0-9_\-]+)\})?\s*#*\s*$/', $line, $m)) {
                $level = strlen($m[1]);
                $text = $m[2];
                $id = !empty($m[3]) ? $m[3] : $this->slugify($text);
                $this->headings[] = ['level' => $level, 'text' => strip_tags($this->inline($text)), 'id' => $id];
                $out .= "<h{$level} id=\"{$id}\">" . $this->inline($text) . "<a class=\"heading-anchor\" href=\"#{$id}\">#</a></h{$level}>\n";
                $i++; continue;
            }

            if (isset($lines[$i+1]) && trim($line) !== '' && preg_match('/^ {0,3}(=+|-+)\s*$/', $lines[$i+1])) {
                $level = ($lines[$i+1][0] === '=') ? 1 : 2;
                $text = trim($line);
                $id = $this->slugify($text);
                $this->headings[] = ['level' => $level, 'text' => strip_tags($this->inline($text)), 'id' => $id];
                $out .= "<h{$level} id=\"{$id}\">" . $this->inline($text) . "<a class=\"heading-anchor\" href=\"#{$id}\">#</a></h{$level}>\n";
                $i += 2; continue;
            }

            if (preg_match('/^ {0,3}\[\^([^\]]+)\]:\s*(.*)$/', $line, $m)) {
                $this->footnotes[$m[1]] = $m[2];
                $i++; continue;
            }

            if (isset($lines[$i+1]) && strpos($line, '|') !== false && preg_match('/^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/', $lines[$i+1])) {
                $splitRow = function($row) {
                    $row = str_replace('\|', "\x03PIPE\x03", $row);
                    $cells = array_map('trim', explode('|', trim(trim($row), '|')));
                    return array_map(function($c) { return str_replace("\x03PIPE\x03", '|', $c); }, $cells);
                };
                $headerCells = $splitRow($line);
                $alignCells = array_map('trim', explode('|', trim(trim($lines[$i+1]), '|')));
                $aligns = array_map(function($a) {
                    $l = substr($a,0,1)===':'; $r = substr($a,-1)===':';
                    if ($l && $r) return 'center'; if ($r) return 'right'; if ($l) return 'left'; return '';
                }, $alignCells);
                $rows = [];
                $i += 2;
                while ($i < $n && strpos($lines[$i], '|') !== false && trim($lines[$i]) !== '') {
                    $rows[] = $splitRow($lines[$i]);
                    $i++;
                }
                $out .= "<div class=\"table-wrap\"><table style=\"--cols:" . max(1, count($headerCells)) . "\"><thead><tr>";
                foreach ($headerCells as $idx => $c) {
                    $style = ($aligns[$idx] ?? '') ? ' style="text-align:' . $aligns[$idx] . '"' : '';
                    $out .= "<th{$style}>" . $this->inline($c) . "</th>";
                }
                $out .= "</tr></thead><tbody>";
                foreach ($rows as $row) {
                    $out .= "<tr>";
                    foreach ($row as $idx => $c) {
                        $style = ($aligns[$idx] ?? '') ? ' style="text-align:' . $aligns[$idx] . '"' : '';
                        $out .= "<td{$style}>" . $this->inline($c) . "</td>";
                    }
                    $out .= "</tr>";
                }
                $out .= "</tbody></table></div>\n";
                continue;
            }

            if (preg_match('/^(\s*)([-*+]|\d+[.)])\s+(.*)$/', $line)) {
                list($listHtml, $consumed) = $this->parseList($lines, $i);
                $out .= $listHtml;
                $i += $consumed;
                continue;
            }

            if (isset($lines[$i+1]) && trim($line) !== '' && !preg_match('/^:\s+/', $line) && preg_match('/^:\s+(.*)$/', $lines[$i+1])) {
                $out .= "<dl>";
                while ($i < $n) {
                    if (!(isset($lines[$i+1]) && trim($lines[$i]) !== '' && !preg_match('/^:\s+/', $lines[$i]) && preg_match('/^:\s+/', $lines[$i+1]))) break;
                    $out .= "<dt>" . $this->inline(trim($lines[$i])) . "</dt>";
                    $i++;
                    while ($i < $n && preg_match('/^:\s+(.*)$/', $lines[$i], $dm)) {
                        $out .= "<dd>" . $this->inline($dm[1]) . "</dd>";
                        $i++;
                    }
                    $j = $i;
                    while ($j < $n && trim($lines[$j]) === '') $j++;
                    if ($j > $i && isset($lines[$j+1]) && trim($lines[$j]) !== '' && preg_match('/^:\s+/', $lines[$j+1])) $i = $j;
                }
                $out .= "</dl>\n";
                continue;
            }

            if (preg_match('/^\s*(https?:\/\/\S+)\s*$/', $line, $um)) {
                $out .= $this->embedUrl($um[1]);
                $i++; continue;
            }

            if (preg_match('/^\s*([\w\-. ]+\.(jpe?g|png|gif|webp|svg))\s*$/i', $line, $im)) {
                $fname = trim($im[1]);
                $src = $this->esc(asset_url('assets/' . $fname));
                $alt = $this->esc($fname);
                $out .= "<figure class=\"md-figure\"><img src=\"{$src}\" alt=\"{$alt}\" loading=\"lazy\"><figcaption>{$alt}</figcaption></figure>\n";
                $i++; continue;
            }

            if (preg_match('/^\s*<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', $line) && !preg_match('/^\s*<(https?):/', $line)) {
                $buf = [];
                while ($i < $n && trim($lines[$i]) !== '') { $buf[] = $lines[$i]; $i++; }
                $out .= $this->sanitizeInlineHtml(implode("\n", $buf)) . "\n";
                continue;
            }

            $para = [];
            while ($i < $n && trim($lines[$i]) !== ''
                && !preg_match('/^ {0,3}(#{1,6})\s+/', $lines[$i])
                && !preg_match('/^ {0,3}>/', $lines[$i])
                && !preg_match('/^ {0,3}(```+|~~~+)/', $lines[$i])
                && !preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $lines[$i])
                && !preg_match('/^ {0,3}([-*_])( *\1){2,} *$/', $lines[$i])
            ) {
                $para[] = $lines[$i]; $i++;
                if (isset($lines[$i]) && preg_match('/^ {0,3}(=+|-+)\s*$/', $lines[$i]) && count($para) === 1) break;
            }
            $text = trim(implode("\n", $para));
            if ($text !== '') {
                $inl = $this->inline($text);
                if (preg_match('/^(?:<figure class="md-figure">.*?<\/figure>\s*)+$/s', $inl)) {
                    $out .= $inl . "\n";
                } else {
                    $out .= "<p>" . $inl . "</p>\n";
                }
            }
        }
        return $out;
    }

    private function embedUrl($url) {
        $u = $this->esc($url);
        if (preg_match('#youtu(?:be\.com/watch\?v=|\.be/|be\.com/embed/)([A-Za-z0-9_-]+)#', $url, $m)) {
            $vid = $this->esc($m[1]);
            return "<div class=\"md-embed md-embed-video\"><iframe src=\"https://www.youtube.com/embed/{$vid}\" loading=\"lazy\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></div>\n";
        }
        if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
            $vid = $this->esc($m[1]);
            return "<div class=\"md-embed md-embed-video\"><iframe src=\"https://player.vimeo.com/video/{$vid}\" loading=\"lazy\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe></div>\n";
        }
        return "<div class=\"md-embed md-embed-link\"><iframe src=\"{$u}\" loading=\"lazy\" referrerpolicy=\"no-referrer\" sandbox=\"allow-popups\"></iframe><a class=\"md-embed-fallback\" href=\"{$u}\" target=\"_blank\" rel=\"noopener noreferrer\">{$u}</a></div>\n";
    }

    private function parseList($lines, $start) {
        $n = count($lines);
        $i = $start;
        preg_match('/^(\s*)([-*+]|\d+[.)])\s+/', $lines[$i], $fm);
        $baseIndent = strlen($fm[1]);
        $ordered = preg_match('/\d+[.)]/', $fm[2]);
        $tag = $ordered ? 'ol' : 'ul';
        $startAttr = ($ordered && intval($fm[2]) !== 1) ? ' start="' . intval($fm[2]) . '"' : '';
        $items = [];
        while ($i < $n) {
            if (trim($lines[$i]) === '') {
                $j = $i;
                while ($j < $n && trim($lines[$j]) === '') $j++;
                if ($j < $n && preg_match('/^(\s*)([-*+]|\d+[.)])\s+(.*)$/', $lines[$j], $lm) && strlen($lm[1]) === $baseIndent) {
                    $i = $j;
                } else {
                    break;
                }
            }
            if (!preg_match('/^(\s*)([-*+]|\d+[.)])\s+(.*)$/', $lines[$i], $m)) break;
            $indent = strlen($m[1]);
            if ($indent !== $baseIndent) break;
            if ((bool)preg_match('/^\d/', $m[2]) !== (bool)$ordered) break;
            $content = $m[3];
            $i++;
            $subLines = [];
            while ($i < $n && (trim($lines[$i]) === '' || strlen($lines[$i]) - strlen(ltrim($lines[$i])) > $baseIndent)) {
                if (trim($lines[$i]) === '' && (!isset($lines[$i+1]) || strlen($lines[$i+1]) - strlen(ltrim($lines[$i+1])) <= $baseIndent)) {
                    break;
                }
                $subLines[] = preg_replace('/^ {0,' . ($baseIndent + 2) . '}/', '', $lines[$i]);
                $i++;
            }
            $isTask = preg_match('/^\[( |x|X)\]\s+(.*)$/', $content, $tm);
            if ($isTask) {
                $checked = strtolower($tm[1]) === 'x';
                $inner = ($checked ? '<input type="checkbox" checked disabled> ' : '<input type="checkbox" disabled> ') . $this->inline($tm[2]);
                $liClass = ' class="task-item"';
            } else {
                $inner = $this->inline($content);
                $liClass = '';
            }
            $nested = '';
            if (!empty($subLines)) {
                $nested = $this->blocks($subLines);
                $nested = preg_replace('/^<p>(<[uo]l>.*<\/[uo]l>)<\/p>\s*$/s', '$1', $nested);
            }
            $items[] = "<li{$liClass}>{$inner}{$nested}</li>";
        }
        return ["<{$tag}{$startAttr}>" . implode('', $items) . "</{$tag}>\n", $i - $start];
    }
}

function post_files($dir) {
    if (!is_dir($dir)) return [];
    $out = [];
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
    } catch (\Throwable $e) {
        return $out;
    }
    $it->rewind();
    while (true) {
        try {
            if (!$it->valid()) break;
            $item = $it->current();
        } catch (\Throwable $e) {
            try { $it->next(); } catch (\Throwable $e2) { break; }
            continue;
        }
        try {
            $rel = substr($item->getPathname(), strlen($dir) + 1);
            $parts = explode(DIRECTORY_SEPARATOR, $rel);
            $skip = false;
            foreach ($parts as $part) {
                if ($part === '' || $part[0] === '.' || $part[0] === '_') { $skip = true; break; }
            }
            if (!$skip && $item->isFile() && substr($item->getFilename(), -3) === '.md') {
                $out[] = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            }
        } catch (\Throwable $e) {
        }
        try {
            $it->next();
        } catch (\Throwable $e) {
            break;
        }
    }
    sort($out);
    return $out;
}

function slug_from_file($file) { return substr($file, 0, -3); }

function post_id($slug) {
    return substr(strtoupper(base_convert(hash('crc32b', 'pid:' . $slug), 16, 36)), 0, 8);
}

function resolve_post_id($dir, $id) {
    if (!preg_match('/^[A-Z0-9]{1,8}$/', $id)) return null;
    foreach (post_files($dir) as $f) {
        $slug = slug_from_file($f);
        if (post_id($slug) === $id) return $slug;
    }
    return null;
}

function toc_build_tree($toc, $minLevel = 2) {
    $root = ['children' => []];
    $stack = [&$root];
    $stackLevels = [$minLevel - 1];
    foreach ($toc as $h) {
        if ($h['level'] < $minLevel) continue;
        $lvl = (int)$h['level'];
        while (count($stackLevels) > 1 && end($stackLevels) >= $lvl) {
            array_pop($stackLevels);
            array_pop($stack);
        }
        $node = ['id' => $h['id'], 'text' => $h['text'], 'level' => $lvl, 'children' => []];
        $stack[count($stack) - 1]['children'][] = $node;
        $lastIdx = count($stack[count($stack) - 1]['children']) - 1;
        $stack[] = &$stack[count($stack) - 1]['children'][$lastIdx];
        $stackLevels[] = $lvl;
    }
    return $root['children'];
}

function toc_render_nodes($nodes, $depth = 0, $defaultOpen = true) {
    if (empty($nodes)) return '';
    $out = '<ul class="toc-tree' . ($depth === 0 ? ' toc-tree-root' : '') . '">';
    foreach ($nodes as $n) {
        $hasKids = !empty($n['children']);
        $label = '<a href="#' . htmlspecialchars($n['id']) . '" class="toc-link toc-lvl-' . $n['level'] . '">' . htmlspecialchars($n['text']) . '</a>';
        if ($hasKids) {
            $openAttr = $defaultOpen ? ' open' : '';
            $out .= '<li class="toc-node has-children">';
            $out .= '<details class="toc-branch"' . $openAttr . '><summary class="toc-summary">';
            $out .= '<span class="toc-caret" aria-hidden="true"></span>';
            $out .= $label;
            $out .= '</summary>';
            $out .= '<div class="toc-branch-body">' . toc_render_nodes($n['children'], $depth + 1, $defaultOpen) . '</div>';
            $out .= '</details></li>';
        } else {
            $out .= '<li class="toc-node">' . $label . '</li>';
        }
    }
    $out .= '</ul>';
    return $out;
}

function md_inject_toc($html, $toc) {
    if (strpos($html, "\x02TOC\x02") === false) return $html;
    $tree = toc_build_tree($toc, 2);
    $tocHtml = '<nav class="toc"><div class="toc-title">Contents</div>' . toc_render_nodes($tree) . '</nav>';
    return str_replace("\x02TOC\x02", $tocHtml, $html);
}

function strip_md($s) {
    $s = (string)$s;
    $s = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $s);
    $s = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $s);
    $s = preg_replace('/\[([^\]]*)\]\[[^\]]*\]/u', '$1', $s);
    $s = preg_replace('/\[\^[^\]]*\]/u', '', $s);
    $s = preg_replace('/<https?:\/\/([^>]+)>/u', '$1', $s);
    $s = preg_replace('/`+([^`]*)`+/u', '$1', $s);
    $s = preg_replace('/(\*\*\*|\*\*|~~|==|__)/u', '', $s);
    $s = preg_replace('/(?<![\w*])\*|\*(?![\w*])/u', '', $s);
    $s = preg_replace('/(?<!\w)_|_(?!\w)/u', '', $s);
    $s = preg_replace('/^\s{0,3}(#{1,6}\s+|>\s?|[-+*]\s+|\d+[.)]\s+)/mu', '', $s);
    $s = preg_replace('/\\\\([\\\\`*_{}\[\]()#+\-.!>~=|"\'&<])/u', '$1', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s === null ? '' : trim($s);
}

function plain_text($html) {
    $s = preg_replace('~<div class="code-bar">.*?</div>~s', ' ', $html);
    if ($s === null) $s = $html;
    $s = preg_replace('~<a class="(?:heading-anchor|fn-back)"[^>]*>.*?</a>|<sup class="fn-ref">.*?</sup>~s', '', $s);
    if ($s === null) $s = '';
    $s = preg_replace('~</?(?:p|div|li|ul|ol|tr|td|th|table|thead|tbody|h[1-6]|pre|blockquote|figure|figcaption|dl|dt|dd|details|summary|br|hr|nav)\b[^>]*>~i', ' ', $s);
    if ($s === null) $s = '';
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('/[\s\x{00A0}\x02]+/u', ' ', $s);
    return $s === null ? '' : trim($s);
}

function resolve_cover($value, $assetsForBare = true) {
    $v = trim((string)$value);
    if ($v === '' || strlen($v) > 500 || preg_match('/[\x00-\x1F\x7F\s"\'<>\\\\]/', $v)) return null;
    if (preg_match('~^https?://[^/]+~i', $v)) return $v;
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $v) || strpos($v, '//') === 0) return null;
    $v = ltrim(preg_replace('~^(\./)+~', '', $v), '/');
    if ($v === '' || $v[0] === '#' || strpos($v, '..') !== false) return null;
    return ($assetsForBare && strpos($v, '/') === false) ? 'assets/' . $v : $v;
}

function is_hidden_source($raw) {
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
    $raw = ltrim(str_replace(["\r\n", "\r"], "\n", $raw), "\n \t");
    if (!preg_match('/^---[ \t]*\n(.*?)\n---[ \t]*(?:\n|$)/s', $raw, $m)) return false;
    foreach (explode("\n", $m[1]) as $line) {
        if (preg_match('/^\s*(?:hidden|unlisted|exclude)\s*:\s*(.*)$/i', $line, $mm)) {
            $v = strtolower(trim($mm[1], " \t\"'"));
            if (in_array($v, ['1', 'true', 'yes', 'on', 'y'], true)) return true;
        }
    }
    return false;
}

function tag_slug($name) {
    $s = mb_strtolower(trim((string)$name), 'UTF-8');
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    $s = trim((string)$s, '-');
    return mb_substr($s, 0, 40, 'UTF-8');
}

function post_tags($meta) {
    $raw = $meta['tags'] ?? ($meta['tag'] ?? '');
    if (!is_string($raw)) return [];
    $raw = trim($raw);
    if ($raw === '') return [];
    if ($raw[0] === '[' && substr($raw, -1) === ']') $raw = substr($raw, 1, -1);
    $out = [];
    foreach (explode(',', $raw) as $t) {
        $name = trim((string)preg_replace('/\s+/u', ' ', trim($t, " \t\"'#")));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 40) continue;
        $slug = tag_slug($name);
        if ($slug === '' || isset($out[$slug])) continue;
        $out[$slug] = ['name' => $name, 'slug' => $slug];
        if (count($out) >= 30) break;
    }
    return array_values($out);
}

function facet_counts($posts, $get) {
    $idx = [];
    foreach ($posts as $p) {
        foreach ($get($p) as $t) {
            if (!isset($idx[$t['slug']])) $idx[$t['slug']] = ['name' => $t['name'], 'slug' => $t['slug'], 'count' => 0];
            $idx[$t['slug']]['count']++;
        }
    }
    uasort($idx, function ($a, $b) {
        if ($a['count'] !== $b['count']) return $b['count'] <=> $a['count'];
        return strcmp($a['slug'], $b['slug']);
    });
    return $idx;
}

function tag_index($posts) {
    return facet_counts($posts, function ($p) { return $p['tags'] ?? []; });
}

function ctf_facet($posts, $field) {
    return facet_counts($posts, function ($p) use ($field) {
        $c = $p['ctf'] ?? [];
        return !empty($c[$field]) && !empty($c[$field . '_slug']) ? [['name' => ($field === 'difficulty' ? ucfirst($c[$field]) : $c[$field]), 'slug' => $c[$field . '_slug']]] : [];
    });
}

function tag_url($slug) {
    return pretty_dir() . '/tag/' . rawurlencode($slug);
}

function facet_url($params) {
    global $CFG;
    $map = ['category' => 'cat', 'difficulty' => 'diff', 'event' => 'event', 'tag' => 'tag'];
    $parts = [];
    foreach (['category', 'difficulty', 'event', 'tag'] as $k) {
        if (isset($params[$k]) && is_string($params[$k]) && $params[$k] !== '') {
            $parts[$k] = $params[$k];
        }
    }
    if (count($parts) === 1) {
        $k = array_key_first($parts);
        return pretty_dir() . '/' . $map[$k] . '/' . rawurlencode($parts[$k]);
    }
    if ($parts) {
        $q = [];
        foreach ($parts as $k => $v) $q[$k] = $v;
        return pretty_dir() . '/?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    }
    return home_url();
}

function ctf_clean($v, $max) {
    $v = (string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$v);
    $v = trim((string)preg_replace('/\s+/u', ' ', $v));
    return mb_strlen($v, 'UTF-8') > $max ? mb_substr($v, 0, $max, 'UTF-8') : $v;
}

function post_ctf($meta) {
    $event = ctf_clean($meta['event'] ?? '', 80);
    $category = ctf_clean($meta['category'] ?? '', 30);
    $difficulty = mb_strtolower(ctf_clean($meta['difficulty'] ?? '', 20), 'UTF-8');
    $pointsRaw = trim((string)($meta['points'] ?? ''));
    $points = preg_match('/^\d{1,6}$/', $pointsRaw) ? (int)$pointsRaw : null;
    $eventSlug = tag_slug($event);
    $categorySlug = tag_slug($category);
    $difficultySlug = tag_slug($difficulty);
    if ($eventSlug === '') $event = '';
    if ($categorySlug === '') $category = '';
    if ($difficultySlug === '') $difficulty = '';
    if ($event === '' && $category === '' && $difficulty === '' && $points === null) return [];
    return [
        'event' => $event, 'event_slug' => $event === '' ? '' : $eventSlug,
        'category' => $category, 'category_slug' => $category === '' ? '' : $categorySlug,
        'difficulty' => $difficulty, 'difficulty_slug' => $difficulty === '' ? '' : $difficultySlug,
        'difficulty_class' => in_array($difficultySlug, ['easy', 'medium', 'hard', 'insane'], true) ? $difficultySlug : 'other',
        'points' => $points,
    ];
}

function post_matches_filter($p, $key, $value) {
    if ($key === 'tag') {
        foreach ($p['tags'] ?? [] as $t) {
            if ($t['slug'] === $value) return true;
        }
        return false;
    }
    return (($p['ctf'][$key . '_slug'] ?? '') === $value);
}

function render_ctf_badges($p, $linked = true) {
    $c = $p['ctf'] ?? [];
    if (!$c) return '';
    $wrap = function ($cls, $text, $key = null, $slug = '') use ($linked) {
        $inner = htmlspecialchars((string)$text);
        if ($linked && $key !== null && $slug !== '') {
            return '<a class="ctf-badge ' . $cls . '" href="' . htmlspecialchars(facet_url([$key => $slug])) . '">' . $inner . '</a>';
        }
        return '<span class="ctf-badge ' . $cls . '">' . $inner . '</span>';
    };
    $out = '';
    if ($c['category'] !== '') $out .= $wrap('cat', $c['category'], 'category', $c['category_slug']);
    if ($c['difficulty'] !== '') $out .= $wrap('diff diff-' . $c['difficulty_class'], ucfirst($c['difficulty']), 'difficulty', $c['difficulty_slug']);
    if ($c['points'] !== null) $out .= $wrap('pts', $c['points'] . ' pts');
    if ($c['event'] !== '') $out .= $wrap('evt', $c['event'], 'event', $c['event_slug']);
    return $out === '' ? '' : '<div class="ctf-badges">' . $out . '</div>';
}

function fm_unquote($v) {
    $v = trim((string)$v, " \t");
    $n = strlen($v);
    if ($n >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[$n - 1] === $v[0]) $v = substr($v, 1, -1);
    return $v;
}

function fm_fields($raw) {
    $s = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
    $s = str_replace(["\r\n", "\r"], "\n", (string)$s);
    $blocks = [];
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n?/s', ltrim($s, "\n"), $m)) $blocks[] = $m[1];
    if (preg_match('/^---[ \t]*\n(.*?)\n---[ \t]*(?:\n|$)/s', ltrim($s, "\n \t"), $m)) $blocks[] = $m[1];
    if (!$blocks) {
        $t = ltrim($s, "\n \t");
        if (strncmp($t, '---', 3) === 0) $blocks[] = implode("\n", array_slice(explode("\n", $t, 62), 1, 60));
    }
    $out = [];
    foreach ($blocks as $blk) {
        foreach (explode("\n", $blk) as $line) {
            if (preg_match('/^\s*([A-Za-z0-9_\-]+)\s*:\s*(.*)$/', $line, $mm)) {
                $out[strtolower($mm[1])][] = fm_unquote($mm[2]);
            }
        }
    }
    return $out;
}

function post_flags($raw) {
    $f = fm_fields($raw);
    $falsy = ['', '0', 'false', 'no', 'off', 'n', 'none', 'null'];
    $flagSeen = false;
    $flag = false;
    foreach (['protected', 'password_protected', 'password-protected'] as $k) {
        foreach ($f[$k] ?? [] as $v) {
            $v = strtolower(trim($v));
            if ($v === '') continue;
            $flagSeen = true;
            if (!in_array($v, $falsy, true)) $flag = true;
        }
    }
    $secret = '';
    foreach (['password', 'password_hash'] as $k) {
        foreach ($f[$k] ?? [] as $v) {
            if ($v !== '') $secret = $v;
        }
    }
    $hidden = is_hidden_source($raw);
    if (!$hidden) {
        foreach (['hidden', 'unlisted', 'exclude'] as $k) {
            foreach ($f[$k] ?? [] as $v) {
                if (filter_var($v, FILTER_VALIDATE_BOOLEAN)) $hidden = true;
            }
        }
    }
    $hasPwKey = isset($f['password']) || isset($f['password_hash']);
    return ['hidden' => $hidden, 'protected' => $flagSeen ? $flag : $hasPwKey, 'secret' => $secret];
}

function post_path($dir, $slug) {
    if (!is_string($slug) || $slug === '' || preg_match('~(^|/)[._]~', $slug) || strpos($slug, '..') !== false) return null;
    $path = realpath($dir . '/' . $slug . '.md');
    $dirReal = realpath($dir);
    if ($path === false || $dirReal === false || strpos($path, $dirReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) return null;
    return $path;
}

function post_access($CFG, $slug) {
    $path = post_path($CFG['posts_dir'], $slug);
    if ($path === null) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $fl = post_flags($raw);
    if ($fl['hidden']) return null;
    $secret = $fl['secret'];
    $hasOwnSecret = $secret !== '';
    if ($secret === '') $secret = (string)($CFG['protected_password'] ?? '');
    return ['path' => $path, 'protected' => $fl['protected'], 'secret' => $secret, 'has_own_secret' => $hasOwnSecret];
}

function md_redact_asset_paths($CFG, $raw) {
    $raw = preg_replace_callback(
        '/!\[([^\]]*)\]\(((?:[^()\s]|\([^()\s]*\))+)((?:\s+"[^"]*")?(?:\s*=\d+x\d*)?)\)/',
        function ($m) use ($CFG) {
            $url = trim($m[2]);
            if (preg_match('~^(?:[a-z][a-z0-9+.\-]*:)?//~i', $url) || preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url)) return $m[0];
            $rel = ltrim(preg_replace('~^(\./)+~', '', $url), '/');
            if ($rel === '' || $rel[0] === '#' || strpos($rel, '..') !== false) return $m[0];
            $assetsRel = strpos($rel, 'assets/') === 0 ? substr($rel, 7) : (strpos($rel, '/') === false ? $rel : null);
            if ($assetsRel === null || $assetsRel === '') return $m[0];
            $tok = asset_token_url($CFG, $assetsRel);
            if ($tok === null) return $m[0];
            return '![' . $m[1] . '](' . site_origin() . $tok . $m[3] . ')';
        },
        $raw
    );
    $raw = preg_replace_callback(
        '/^([ \t]*)([\w\-. ]+\.(?:jpe?g|png|gif|webp|svg))([ \t]*)$/mi',
        function ($m) use ($CFG) {
            $tok = asset_token_url($CFG, trim($m[2]));
            return $tok === null ? $m[0] : $m[1] . site_origin() . $tok . $m[3];
        },
        $raw
    );
    return $raw;
}

function strip_secret_lines($raw) {
    $out = preg_replace_callback(
        '/\A((?:\xEF\xBB\xBF)?\s*---[ \t]*\r?\n)(.*?)(\r?\n---)/s',
        function ($m) {
            $head = preg_replace('/^[ \t]*(?:password_hash|password)[ \t]*:[^\n]*(?:\n|\z)/mi', '', $m[2]);
            return $m[1] . ($head === null ? $m[2] : $head) . $m[3];
        },
        (string)$raw,
        1
    );
    return $out === null ? (string)$raw : $out;
}

function pp_verify($input, $stored) {
    if (!is_string($input) || $input === '' || strlen($input) > 1024) return false;
    $stored = (string)$stored;
    if ($stored === '') return false;
    if (preg_match('/^\$(?:2[abxy]|argon2id?)\$/', $stored)) return password_verify($input, $stored);
    return hash_equals(hash_hmac('sha256', $stored, 'pp-verify'), hash_hmac('sha256', $input, 'pp-verify'));
}

function pp_config_secrets($CFG) {
    return (string)($CFG['protected_password'] ?? '') . "\x1f" . (string)($CFG['protected_list_password'] ?? '');
}

function pp_asset_key($CFG) {
    static $key = null;
    if ($key !== null) return $key;
    $dir = $CFG['posts_dir'] . '/.auth';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $path = $dir . '/img_key.bin';
    $raw = @file_get_contents($path);
    if ($raw !== false && strlen($raw) === 32) { $key = $raw; return $key; }
    $fresh = random_bytes(32);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $fresh, LOCK_EX) !== false) {
        @chmod($tmp, 0600);
        @rename($tmp, $path);
    }
    
    $raw2 = @file_get_contents($path);
    $key = ($raw2 !== false && strlen($raw2) === 32) ? $raw2 : $fresh;
    return $key;
}

function b64url_encode($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
function b64url_decode($str) {
    $str = strtr((string)$str, '-_', '+/');
    $pad = strlen($str) % 4;
    if ($pad) $str .= str_repeat('=', 4 - $pad);
    return base64_decode($str, true);
}

function asset_encode_token($CFG, $rel) {
    $key = pp_asset_key($CFG);
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($rel, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ct === false) return null;
    return b64url_encode($iv . $tag . $ct);
}

function asset_decode_token($CFG, $token) {
    $raw = b64url_decode($token);
    if ($raw === false || strlen($raw) < 12 + 16 + 1) return null;
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ct = substr($raw, 28);
    $rel = openssl_decrypt($ct, 'aes-256-gcm', pp_asset_key($CFG), OPENSSL_RAW_DATA, $iv, $tag);
    if ($rel === false || $rel === '' || strpos($rel, '..') !== false || $rel[0] === '/') return null;
    return $rel;
}

function asset_token_url($CFG, $rel) {
    $token = asset_encode_token($CFG, $rel);
    if ($token === null) return null;
    $dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (!preg_match('#^[A-Za-z0-9._%/\-]*$#', $dir)) $dir = '';
    return rtrim($dir, '/') . '/img/' . $token;
}

function pp_enc_derive_key($password, $saltHex) {
    $salt = hex2bin($saltHex);
    if ($salt === false) return false;
    return hash_hkdf('sha256', $password, 32, 'cipherscribe-post-encrypt-v1', $salt);
}

function pp_enc_is_sealed($raw) {
    return (bool)preg_match('/^[ \t]*encrypted[ \t]*:[ \t]*(1|true|yes|on)[ \t]*$/mi', fm_block($raw));
}

function fm_block($raw) {
    if (preg_match('/\A(?:\xEF\xBB\xBF)?\s*---[ \t]*\r?\n(.*?)\r?\n---/s', (string)$raw, $m)) return $m[1];
    return '';
}

function pp_enc_seal_body($plainBody, $password) {
    $saltHex = bin2hex(random_bytes(16));
    $key = pp_enc_derive_key($password, $saltHex);
    if ($key === false) return null;
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plainBody, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) return null;
    return [
        'salt' => $saltHex,
        'blob' => base64_encode($iv . $tag . $ciphertext),
    ];
}

function pp_enc_open_body($blobB64, $saltHex, $password) {
    $key = pp_enc_derive_key($password, $saltHex);
    if ($key === false) return null;
    $raw = base64_decode($blobB64, true);
    if ($raw === false || strlen($raw) < 12 + 16) return null;
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? null : $plain;
}

function pp_enc_maybe_seal_at_rest($path, $raw, $prot) {
    if (!$prot['protected'] || !$prot['has_own_secret'] || $prot['secret'] === '') return $raw;
    if (pp_enc_is_sealed($raw)) return $raw;
    if (preg_match('/^\$(?:2[abxy]|argon2id?)\$/', $prot['secret'])) return $raw; 
    if (!function_exists('openssl_encrypt')) return $raw;
    if (!preg_match('/\A((?:\xEF\xBB\xBF)?\s*---[ \t]*\r?\n)(.*?)(\r?\n---[ \t]*\r?\n)(.*)\z/s', $raw, $m)) return $raw;
    [, $open, $front, $close, $body] = $m;
    if (trim($body) === '') return $raw;
    $sealed = pp_enc_seal_body($body, $prot['secret']);
    if ($sealed === null) return $raw;
    $newFront = rtrim($front, "\r\n") . "\nencrypted: true\nenc_salt: " . $sealed['salt'] . "\n";
    $newRaw = $open . $newFront . $close . $sealed['blob'] . "\n";
    if (@file_put_contents($path, $newRaw, LOCK_EX) === false) return $raw;
    @touch($path); 
    return $newRaw;
}

function pp_enc_prepare_for_render($raw, $secret) {
    if (!pp_enc_is_sealed($raw)) return $raw;
    $front = fm_block($raw);
    if (!preg_match('/^[ \t]*enc_salt[ \t]*:[ \t]*([0-9a-f]{32})[ \t]*$/mi', $front, $sm)) return null;
    if (!preg_match('/\A((?:\xEF\xBB\xBF)?\s*---[ \t]*\r?\n.*?\r?\n---[ \t]*\r?\n)(.*)\z/s', $raw, $m)) return null;
    $blob = trim($m[2]);
    $plain = pp_enc_open_body($blob, $sm[1], $secret);
    if ($plain === null) return null;
    return $m[1] . $plain;
}

function pp_fingerprint($CFG, $slug, $secret) {
    return hash('sha256', 'pp2|' . $slug . '|' . $secret . '|' . pp_config_secrets($CFG));
}

function pp_is_unlocked($CFG, $slug, $secret) {
    if ($secret === '') return false;
    $all = $_SESSION['pp_unlocked'] ?? null;
    if (!is_array($all)) return false;
    $key = hash('sha256', $slug);
    $rec = $all[$key] ?? null;
    if (!is_array($rec) || !isset($rec['t'], $rec['f']) || !is_string($rec['f'])) return false;
    if ((int)$rec['t'] + (int)$CFG['protected_ttl'] < time()) {
        unset($_SESSION['pp_unlocked'][$key]);
        return false;
    }
    return hash_equals($rec['f'], pp_fingerprint($CFG, $slug, $secret));
}

function pp_client_ip($CFG) {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $h = (string)($CFG['trusted_ip_header'] ?? '');
    if ($h !== '' && !empty($_SERVER[$h])) {
        $parts = explode(',', (string)$_SERVER[$h]);
        $cand = trim($parts[0]);
        if (filter_var($cand, FILTER_VALIDATE_IP)) $ip = $cand;
    }
    $bin = @inet_pton($ip);
    if ($bin !== false && strlen($bin) === 16) {
        if (substr($bin, 0, 12) === str_repeat("\0", 10) . "\xFF\xFF") return (string)inet_ntop(substr($bin, 12));
        return 'v6:' . bin2hex(substr($bin, 0, 8));
    }
    return $ip;
}

function pp_rl_path($CFG, $key) {
    $dir = $CFG['posts_dir'] . '/.auth';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_dir($dir) || !is_writable($dir)) return null;
    return $dir . '/' . hash('sha256', $key) . '.json';
}

function pp_rl_op($CFG, $key, $op) {
    $window = (int)$CFG['protected_window'];
    $now = time();
    $path = pp_rl_path($CFG, $key);
    if ($path === null) return [];
    if ($op === 'clear') {
        if (is_file($path)) @unlink($path);
        return [];
    }
    if ($op === 'get' && !is_file($path)) return [];
    $fp = @fopen($path, 'c+');
    if (!$fp) return [];
    flock($fp, LOCK_EX);
    $data = json_decode((string)stream_get_contents($fp), true);
    $list = is_array($data) ? array_values(array_filter($data, function ($t) use ($now, $window) { return is_int($t) && $t > $now - $window; })) : [];
    if ($op === 'add') {
        $list[] = $now;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($list));
        fflush($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $list;
}

function pp_rl_wait($CFG, $limits) {
    $wait = 0;
    $now = time();
    $window = (int)$CFG['protected_window'];
    foreach ($limits as $key => $max) {
        $list = pp_rl_op($CFG, $key, 'get');
        if (count($list) >= $max) {
            $w = (min($list) + $window) - $now;
            if ($w < 1) $w = 1;
            if ($w > $wait) $wait = $w;
        }
    }
    return $wait;
}

function render_cache_gc($dir) {
    $cdir = render_cache_dir($dir);
    if (!is_dir($cdir)) return;
    $cut = time() - 86400 * 30;
    foreach ((array)@scandir($cdir) as $f) {
        $fs = (string)$f;
        $isEntry = substr($fs, -6) === '.cache';
        $isIndex = substr($fs, -4) === '.idx';
        if (!$isEntry && !$isIndex) continue;
        $p = $cdir . '/' . $fs;
        if (is_file($p) && @filemtime($p) < $cut) @unlink($p);
    }
}

function pp_rl_gc($CFG) {
    $dir = $CFG['posts_dir'] . '/.auth';
    if (!is_dir($dir)) return;
    $cut = time() - ((int)$CFG['protected_window'] * 2);
    foreach ((array)@scandir($dir) as $f) {
        if (substr((string)$f, -5) !== '.json') continue;
        $p = $dir . '/' . $f;
        if (is_file($p) && @filemtime($p) < $cut) @unlink($p);
    }
}

function pp_access_log($CFG, $event, $context = []) {
    $dir = $CFG['posts_dir'] . '/.auth';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $logPath = $dir . '/access.log';

    $ip      = pp_client_ip($CFG);
    $ua      = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $referer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 300);
    $ts      = date('Y-m-d H:i:s T');
    $sid     = substr(session_id(), 0, 12);

    $ctx = array_merge([
        'ip'      => $ip,
        'ua'      => $ua,
        'referer' => $referer,
        'session' => $sid,
    ], $context);

    $sanitize = function($v) {
        if (!is_string($v)) $v = (string)$v;
        return str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $v);
    };

    $parts = ['ts=' . $sanitize($ts), 'event=' . $sanitize($event)];
    foreach ($ctx as $k => $v) {
        $parts[] = $sanitize($k) . '=' . json_encode($sanitize($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line = implode(' | ', $parts) . "\n";

    $fp = @fopen($logPath, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        flock($fp, LOCK_UN);
        fclose($fp);
    } else {
        error_log('CipherScribe [access_log]: ' . rtrim($line));
    }

    if (@filesize($logPath) > 5 * 1024 * 1024) {
        @rename($logPath, $logPath . '.' . date('Ymd-His') . '.bak');
    }
}

function pp_list_rl_wait($CFG) {
    $ip = pp_client_ip($CFG);
    return pp_rl_wait($CFG, [
        'pplist|' . $ip => (int)$CFG['protected_max_fails'],
        'pplist_all'    => (int)$CFG['protected_post_max'],
    ]);
}
function pp_list_rl_add($CFG) {
    $ip = pp_client_ip($CFG);
    pp_rl_op($CFG, 'pplist|' . $ip, 'add');
    pp_rl_op($CFG, 'pplist_all', 'add');
}
function pp_list_rl_clear($CFG) {
    $ip = pp_client_ip($CFG);
    pp_rl_op($CFG, 'pplist|' . $ip, 'clear');
}

function pp_log_rl_wait($CFG) {
    $ip = pp_client_ip($CFG);
    return pp_rl_wait($CFG, [
        'pplog|' . $ip => (int)$CFG['protected_max_fails'],
        'pplog_all'    => (int)$CFG['protected_post_max'],
    ]);
}
function pp_log_rl_add($CFG) {
    $ip = pp_client_ip($CFG);
    pp_rl_op($CFG, 'pplog|' . $ip, 'add');
    pp_rl_op($CFG, 'pplog_all', 'add');
}
function pp_log_rl_clear($CFG) {
    $ip = pp_client_ip($CFG);
    pp_rl_op($CFG, 'pplog|' . $ip, 'clear');
}

function pp_log_is_unlocked($CFG) {
    if (empty($_SESSION[PP_LOG_SESSION_KEY])) return false;
    $rec = $_SESSION[PP_LOG_SESSION_KEY];
    if (!is_array($rec) || empty($rec['t']) || empty($rec['h'])) return false;
    if ((int)$rec['t'] + (int)$CFG['protected_ttl'] < time()) {
        unset($_SESSION[PP_LOG_SESSION_KEY]);
        return false;
    }
    $pw = (string)($CFG['access_log_password'] ?? '');
    if ($pw === '') return false;
    return hash_equals(hash_hmac('sha256', $pw, 'pplog'), (string)$rec['h']);
}

function pp_log_unlock($CFG) {
    $_SESSION[PP_LOG_SESSION_KEY] = [
        't' => time(),
        'h' => hash_hmac('sha256', (string)($CFG['access_log_password'] ?? ''), 'pplog'),
    ];
}

function pp_gate_accent_css() {
    return <<<'CSS'
[data-accent="indigo"]{--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"][data-accent="indigo"]{--accent:#8181FC;--accent-soft:#1C1D3D;--accent-hover:#9494FF;}
[data-accent="blue"]{--accent:#2E7CF6;--accent-soft:#EAF2FE;--accent-hover:#1E63D6;}
[data-theme="dark"][data-accent="blue"]{--accent:#5B9AFC;--accent-soft:#152238;--accent-hover:#7CAEFF;}
[data-accent="green"]{--accent:#0E9F6E;--accent-soft:#E5F8F1;--accent-hover:#0B7F58;}
[data-theme="dark"][data-accent="green"]{--accent:#34D399;--accent-soft:#0C2A20;--accent-hover:#5EE0AF;}
[data-accent="rose"]{--accent:#E23C6D;--accent-soft:#FDEBF1;--accent-hover:#C22857;}
[data-theme="dark"][data-accent="rose"]{--accent:#FB7096;--accent-soft:#331420;--accent-hover:#FF8CAA;}
[data-accent="amber"]{--accent:#C77700;--accent-soft:#FEF2DF;--accent-hover:#A66300;}
[data-theme="dark"][data-accent="amber"]{--accent:#F0A93E;--accent-soft:#2B1E06;--accent-hover:#FFC06B;}
CSS;
}

function pp_gate_theme_js() {
    return <<<'JS'
(function(){
  var root = document.documentElement;
  var theme = null, accent = null;
  try { theme = localStorage.getItem('theme'); } catch (e) {}
  try { accent = localStorage.getItem('accent'); } catch (e) {}
  if (!theme) {
    try { theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } catch (e) { theme = 'light'; }
  }
  root.setAttribute('data-theme', theme);
  root.setAttribute('data-accent', accent || root.getAttribute('data-accent') || 'indigo');
})();
JS;
}

function pp_gate_card_css() {
    return <<<'CSS'
*,*::before,*::after{box-sizing:border-box;}
html,body{min-height:100%;}
body{margin:0;padding:20px;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);color:var(--ink);font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;line-height:1.6;-webkit-font-smoothing:antialiased;}
@media (prefers-reduced-motion:no-preference){body{animation:pp-fade .35s ease;}}
@keyframes pp-fade{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}
.card{width:100%;max-width:400px;padding:36px 30px 30px;text-align:center;background:var(--surface);border:1px solid var(--border);border-radius:18px;box-shadow:0 12px 36px rgba(0,0,0,.08);}
.icon{width:56px;height:56px;margin:0 auto 20px;border-radius:15px;display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--accent);transition:background-color .35s ease,color .25s ease;}
.icon svg{width:26px;height:26px;}
h1{margin:0 0 6px;font-family:'Manrope','Inter',sans-serif;font-size:1.4rem;font-weight:800;letter-spacing:-.01em;color:var(--ink);}
p.lead{margin:0 0 22px;font-size:.95rem;color:var(--ink2);}
.field{position:relative;margin-bottom:14px;}
input[type=password],input[type=text]{width:100%;padding:13px 68px 13px 15px;border:1px solid var(--border);border-radius:11px;background:var(--surface2);color:var(--ink);font:inherit;font-size:1rem;outline:none;transition:border-color .15s ease,box-shadow .15s ease;}
input[type=password]:focus,input[type=text]:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);padding:7px 11px;border:0;border-radius:8px;background:none;color:var(--ink3);font:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.15s;}
.eye:hover{color:var(--accent);background:var(--accent-soft);}
.btn{width:100%;padding:13px 16px;border:0;border-radius:11px;background:var(--accent);color:#fff;font:inherit;font-size:.98rem;font-weight:700;cursor:pointer;transition:background-color .15s ease,transform .1s ease;}
.btn:hover{background:var(--accent-hover);}
.btn:active{transform:scale(.98);}
.err{display:flex;align-items:flex-start;gap:9px;margin:0 0 16px;padding:11px 13px;border:1px solid rgba(209,52,91,.3);border-radius:11px;background:rgba(209,52,91,.1);color:#D1345B;font-size:.87rem;text-align:left;line-height:1.5;}
.err svg{flex:0 0 15px;margin-top:2px;}
.note{display:flex;align-items:flex-start;gap:9px;margin:0 0 16px;padding:11px 13px;border:1px solid color-mix(in srgb, var(--accent) 28%, transparent);border-radius:11px;background:var(--accent-soft);color:var(--accent);font-size:.87rem;text-align:left;line-height:1.5;}
.note svg{flex:0 0 15px;margin-top:2px;}
.back{display:inline-flex;align-items:center;gap:5px;margin-top:20px;color:var(--ink3);font-size:.85rem;font-weight:600;text-decoration:none;transition:color .15s;}
.back:hover{color:var(--accent);}
.back svg{width:13px;height:13px;}
CSS;
}

function pp_gate_lock_icon() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
}

function pp_gate_eye_js() {
    return <<<'JS'
(function(){
  var pw = document.getElementById('pw');
  var eye = document.getElementById('eye');
  if (!pw || !eye) return;
  eye.addEventListener('click', function(){
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    eye.textContent = show ? 'Hide' : 'Show';
    pw.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    pw.focus();
  });
})();
JS;
}

function render_gate_page($CFG, $action, $csrf, $error, $status, $extraNote = '') {
    global $CSP_NONCE;
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('Referrer-Policy: no-referrer');
    $site = htmlspecialchars($CFG['site_name'], ENT_QUOTES, 'UTF-8');
    $act = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
    $tok = htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $acc = htmlspecialchars($CFG['accent'], ENT_QUOTES, 'UTF-8');
    $warnIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>';
    $infoIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/></svg>';
    $err = $error !== '' ? '<div class="err" role="alert">' . $warnIcon . '<span>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</span></div>' : '';
    $note = $extraNote !== '' ? '<div class="note" role="note">' . $infoIcon . '<span>' . htmlspecialchars($extraNote, ENT_QUOTES, 'UTF-8') . '</span></div>' : '';
    $palette = pp_gate_accent_css();
    $card = pp_gate_card_css();
    $css = <<<CSS
:root{--bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;--ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"]{--bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;--ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;}
{$palette}
{$card}
body{background:rgba(10,11,16,.6);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);animation:none;}
.card{box-shadow:0 24px 64px rgba(0,0,0,.35);}
CSS;
    $jsTheme = pp_gate_theme_js();
    $jsHello = console_hello_js(pp_accent_hex($CFG['accent'] ?? 'indigo'), doc_url('DOCUMENTATION'));
    $jsEye = pp_gate_eye_js();
    $lockIcon = pp_gate_lock_icon();
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="light" data-accent="{$acc}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title>Protected post &mdash; {$site}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>{$css}
.site-head{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;max-width:760px;margin:0 auto;}
.brand{font-family:'Manrope','Inter',sans-serif;font-weight:800;font-size:1.1rem;color:var(--ink);text-decoration:none;}
.gate-head-wrap{border-bottom:1px solid var(--border);}
.back-link{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--ink3);text-decoration:none;font-weight:600;transition:color .15s;}
.back-link:hover{color:var(--accent);}
.back-link svg{width:13px;height:13px;}
.gate-wrap{display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 49px);padding:24px;}
</style>
<script nonce="{$CSP_NONCE}">{$jsTheme}</script>
</head>
<body style="background:var(--bg);backdrop-filter:none;-webkit-backdrop-filter:none;">
<div class="gate-head-wrap">
  <header class="site-head">
    <a class="brand" href="{$home}">{$site}</a>
    <a class="back-link" href="{$home}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>All posts</a>
  </header>
</div>
<div class="gate-wrap">
<div class="card" role="dialog" aria-modal="true" aria-labelledby="ppPostModalTitle" style="box-shadow:0 8px 28px rgba(0,0,0,.08);">
  <div class="icon">{$lockIcon}</div>
  <h1 id="ppPostModalTitle">This post is protected</h1>
  <p class="lead">Enter the password to continue.</p>
  {$note}
  {$err}
  <form method="post" action="{$act}">
    <input type="hidden" name="pp_unlock" value="1">
    <input type="hidden" name="pp_csrf" value="{$tok}">
    <div class="field">
      <input id="pw" type="password" name="pp_password" placeholder="Password" required maxlength="1024" autofocus autocomplete="current-password" aria-label="Password">
      <button type="button" class="eye" id="eye" aria-label="Show password">Show</button>
    </div>
    <button type="submit" class="btn">Unlock</button>
  </form>
</div>
</div>
<script nonce="{$CSP_NONCE}">{$jsEye}</script>
<script nonce="{$CSP_NONCE}">{$jsHello}</script>
</body>
</html>
HTML;
    exit;
}

function pp_deny_export($CFG, $slug, $mode) {
    pp_access_log($CFG, 'EXPORT_BLOCKED', ['slug' => (string)$slug, 'mode' => (string)$mode, 'reason' => 'exports_disabled_for_protected']);
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    exit('Not found');
}

function pp_require($CFG, $slug, $acc, $mode) {
    if ($mode !== 'page') pp_deny_export($CFG, $slug, $mode);

    if (pp_is_unlocked($CFG, $slug, $acc['secret'])) {
        if ($mode === 'page') {
            pp_access_log($CFG, 'POST_VIEW', ['slug' => $slug, 'mode' => 'unlocked_session']);
        }
        return;
    }

    $viaList      = pp_list_is_unlocked($CFG);
    $hasOwnSecret = !empty($acc['has_own_secret']);

    if ($viaList && !$hasOwnSecret) {
        if ($mode === 'page') {
            pp_access_log($CFG, 'POST_VIEW', ['slug' => $slug, 'mode' => 'unlocked_via_list']);
        }
        return;
    }

    $pageUrl = doc_url(post_id($slug));

    if (!$hasOwnSecret) {
        pp_access_log($CFG, 'POST_GATE_REDIRECT_TO_LIST', ['slug' => $slug]);
        header('Location: ' . protected_list_url(post_id($slug)), true, 302);
        exit;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');

    $limiterOk = pp_rl_path($CFG, 'probe') !== null;

    $error  = '';
    $status = 200;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pp_unlock']) && !$limiterOk) {
        error_log('CipherScribe: posts/.auth is not writable, password attempts are refused.');
        pp_access_log($CFG, 'POST_UNLOCK_FAIL', ['slug' => $slug, 'reason' => 'rate_limiter_unavailable']);
        $error  = 'Password check is temporarily unavailable.';
        $status = 503;

    } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pp_unlock'])) {
        $given = $_POST['pp_csrf'] ?? '';
        $csrf  = $_SESSION['pp_csrf'] ?? '';

        if (!is_string($given) || !is_string($csrf) || $csrf === '' || !hash_equals($csrf, $given)) {
            pp_access_log($CFG, 'POST_UNLOCK_FAIL', ['slug' => $slug, 'reason' => 'csrf_mismatch']);
            $error  = 'Your session expired. Please try again.';
            $status = 403;
        } else {
            $ip    = pp_client_ip($CFG);
            $kPost = 'pp|' . $ip . '|' . hash('sha256', $slug);
            $kIp   = 'ip|' . $ip;
            $kAll  = 'all|' . hash('sha256', $slug);
            $wait  = pp_rl_wait($CFG, [$kPost => (int)$CFG['protected_max_fails'], $kIp => (int)$CFG['protected_ip_max'], $kAll => (int)$CFG['protected_post_max']]);

            if ($wait > 0) {
                $status = 429;
                header('Retry-After: ' . $wait);
                pp_access_log($CFG, 'POST_UNLOCK_RATELIMIT', ['slug' => $slug, 'wait_seconds' => $wait]);
                $error = 'Too many attempts. Try again in ' . max(1, (int)ceil($wait / 60)) . ' minute(s).';
            } else {
                $pw = $_POST['pp_password'] ?? '';
                if (pp_verify($pw, $acc['secret']) || pp_verify($pw, (string)($CFG['protected_password'] ?? ''))) {
                    pp_rl_op($CFG, $kPost, 'clear');
                    session_regenerate_id(true);
                    $u = $_SESSION['pp_unlocked'] ?? [];
                    if (!is_array($u)) $u = [];
                    foreach ($u as $k => $rec) {
                        if (!is_array($rec) || ((int)($rec['t'] ?? 0) + (int)$CFG['protected_ttl']) < time()) unset($u[$k]);
                    }
                    $u[hash('sha256', $slug)] = ['t' => time(), 'f' => pp_fingerprint($CFG, $slug, $acc['secret'])];
                    $_SESSION['pp_unlocked'] = $u;
                    $_SESSION['pp_csrf'] = bin2hex(random_bytes(32));
                    pp_access_log($CFG, 'POST_UNLOCK_SUCCESS', ['slug' => $slug, 'via_list' => $viaList]);
                    header('Location: ' . $pageUrl, true, 303);
                    exit;
                }
                pp_rl_op($CFG, $kPost, 'add');
                pp_rl_op($CFG, $kIp, 'add');
                pp_rl_op($CFG, $kAll, 'add');
                if (mt_rand(1, 40) === 1) pp_rl_gc($CFG);
                usleep(random_int(250000, 500000));
                pp_access_log($CFG, 'POST_UNLOCK_FAIL', ['slug' => $slug, 'reason' => 'wrong_password']);
                $error  = 'Incorrect password.';
                $status = 403;
            }
        }
    } else {
        pp_access_log($CFG, 'POST_GATE_SHOWN', ['slug' => $slug, 'via_list' => $viaList, 'has_own_secret' => $hasOwnSecret]);
    }

    if (empty($_SESSION['pp_csrf']) || !is_string($_SESSION['pp_csrf'])) $_SESSION['pp_csrf'] = bin2hex(random_bytes(32));

    $extraNote = ($viaList && $error === '')
        ? 'This post additionally requires its own specific password.'
        : '';

    render_gate_page($CFG, $pageUrl, $_SESSION['pp_csrf'], $error, $status, $extraNote);
}

function views_dir($dir) { return $dir . '/.stats'; }
function views_path($dir) { return views_dir($dir) . '/views.json'; }

function views_ensure_dir($dir) {
    $vdir = views_dir($dir);
    if (!is_dir($vdir)) {
        @mkdir($vdir, 0755, true);
        @file_put_contents($vdir . '/.htaccess', "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }
    return $vdir;
}

function views_get_all($dir) {
    $path = views_path($dir);
    if (!is_file($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function views_get($dir, $slug) {
    $all = views_get_all($dir);
    return (int)($all[$slug] ?? 0);
}

function views_record($CFG, $slug, $mode) {
    if ($mode !== 'page') return views_get($CFG['posts_dir'], $slug); 

    $seenKey = 'pp_viewed_' . date('Y-m-d');
    $seen = $_SESSION[$seenKey] ?? [];
    if (!is_array($seen)) $seen = [];
    if (in_array($slug, $seen, true)) return views_get($CFG['posts_dir'], $slug);

    views_ensure_dir($CFG['posts_dir']);
    $path = views_path($CFG['posts_dir']);
    $fp = @fopen($path, 'c+');
    $count = 0;
    if ($fp) {
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $data = $raw !== false && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($data)) $data = [];
        $data[$slug] = (int)($data[$slug] ?? 0) + 1;
        $count = $data[$slug];
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
        fclose($fp);
    } else {
        $count = views_get($CFG['posts_dir'], $slug);
    }

    $seen[] = $slug;
    
    if (count($seen) > 200) $seen = array_slice($seen, -200);
    $_SESSION[$seenKey] = $seen;
    
    foreach (array_keys($_SESSION) as $k) {
        if (is_string($k) && strpos($k, 'pp_viewed_') === 0 && $k !== $seenKey) unset($_SESSION[$k]);
    }

    return $count;
}

function views_format($n) {
    $n = (int)$n;
    if ($n < 1000) return (string)$n;
    if ($n < 1000000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
    return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
}

function open_post($CFG, $slug, $mode) {
    $acc = post_access($CFG, $slug);
    if (!$acc) return null;
    if ($acc['protected']) {
        pp_require($CFG, $slug, $acc, $mode);
        
        
        
        $raw = @file_get_contents($acc['path']);
        if ($raw !== false) pp_enc_maybe_seal_at_rest($acc['path'], $raw, $acc);
        return load_post($CFG['posts_dir'], $slug, true, $acc['secret']);
    }
    return load_post($CFG['posts_dir'], $slug, true);
}

function pp_accent_hex($accent) {
    $map = [
        'indigo' => '#5B5BF0',
        'blue'   => '#2E7CF6',
        'green'  => '#0E9F6E',
        'rose'   => '#E23C6D',
        'amber'  => '#C77700',
    ];
    return $map[$accent] ?? $map['indigo'];
}

function console_hello_js($accentHex = '#5B5BF0', $readmeUrl = '') {
    $accentHex = preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string)$accentHex) ? $accentHex : '#5B5BF0';
    $readmeUrl = is_string($readmeUrl) ? $readmeUrl : '';
    $safeUrl = json_encode($readmeUrl !== '' ? $readmeUrl : '/p/DOCUMENTATION', JSON_UNESCAPED_SLASHES);
    return <<<JS
(function(){
  var docUrl = {$safeUrl};
  var msg = 'Hi! This blog is by Vineet Pratap Singh — visit ' + docUrl + ' for the full documentation.';
  function currentAccent(){
    try {
      var v = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
      if (v) return v;
    } catch (e) {}
    return '{$accentHex}';
  }
  function hello(){
    try { console.log('%c' + msg, 'font:600 14px/1.6 system-ui,sans-serif;color:' + currentAccent() + ';'); } catch (e) {}
  }
  hello();
  try {
    if (typeof console.clear === 'function' && !console.clear.__hello) {
      var orig = console.clear;
      var wrapped = function(){
        var r = orig.apply(console, arguments);
        hello();
        return r;
      };
      wrapped.__hello = true;
      console.clear = wrapped;
    }
  } catch (e) {}
})();
JS;
}

function file_base($slug) {
    $slug = (string)$slug;
    $p = strrpos($slug, '/');
    $b = $p === false ? $slug : substr($slug, $p + 1);
    $b = preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/', '', $b);
    $b = trim((string)$b, " .");
    return $b === '' ? 'post' : $b;
}

function content_disposition($type, $slug, $ext) {
    $name = file_base($slug) . '.' . $ext;
    $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    if ($ascii === null || $ascii === '' || $ascii[0] === '.') $ascii = 'file.' . $ext;
    $h = $type . '; filename="' . $ascii . '"';
    if ($ascii !== $name) $h .= "; filename*=UTF-8''" . rawurlencode($name);
    return $h;
}

function render_cache_dir($dir) { return $dir . '/.cache'; }

const RENDER_CACHE_BLOB_PREFIX = 'S1';

function render_cache_key($path, $mtime) {
    return md5($path . '|' . $mtime . '|' . RENDER_CACHE_VERSION) . '.cache';
}

function render_cache_index_path($cdir, $path) {
    return $cdir . '/idx_' . md5($path) . '.idx';
}

function render_cache_ensure_dir($cdir) {
    if (!is_dir($cdir)) @mkdir($cdir, 0700, true);
    $ht = $cdir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }
}

function render_cache_get($dir, $path, $mtime) {
    $cdir = render_cache_dir($dir);
    render_cache_ensure_dir($cdir);
    $file = $cdir . '/' . render_cache_key($path, $mtime);
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false || substr($raw, 0, 2) !== RENDER_CACHE_BLOB_PREFIX) return null;
    $data = @unserialize(substr($raw, 2), ['allowed_classes' => false]);
    if (!is_array($data)) return null;
    return $data;
}

function render_cache_put($dir, $path, $mtime, $data) {
    $cdir = render_cache_dir($dir);
    render_cache_ensure_dir($cdir);
    $key = render_cache_key($path, $mtime);
    $file = $cdir . '/' . $key;
    @file_put_contents($file, RENDER_CACHE_BLOB_PREFIX . serialize($data), LOCK_EX);
    render_cache_forget_stale($cdir, $path, $key);
}

function render_cache_forget_stale($cdir, $path, $freshKey) {
    $idx = render_cache_index_path($cdir, $path);
    $oldKey = @file_get_contents($idx);
    if ($oldKey !== false) {
        $oldKey = trim($oldKey);
        if ($oldKey !== '' && $oldKey !== $freshKey) {
            @unlink($cdir . '/' . $oldKey);
        }
    }
    @file_put_contents($idx, $freshKey, LOCK_EX);
}

function load_post($dir, $slug, $allowProtected = false, $decryptSecret = null) {
    $path = post_path($dir, $slug);
    if ($path === null) return null;
    $mtime = @filemtime($path);

    if ($mtime) {
        $cached = render_cache_get($dir, $path, $mtime);
        if ($cached !== null) return $cached;
    }

    $raw = file_get_contents($path);
    if ($raw === false || is_hidden_source($raw)) return null;
    $prot = post_flags($raw);
    if ($prot['protected'] && !$allowProtected) return null;

    $sealedNoKey = false;
    if ($prot['protected'] && pp_enc_is_sealed($raw)) {
        if ($decryptSecret !== null) {
            $opened = pp_enc_prepare_for_render($raw, $decryptSecret);
            if ($opened === null) return null; 
            $raw = $opened;
        } else {
            
            
            
            $sealedNoKey = true;
            $front = fm_block($raw);
            $raw = "---\n" . $front . "\n---\n";
        }
    }

    $mdx = new MDX();
    $html = $mdx->toHTML(strip_secret_lines($raw));
    $meta = $mdx->meta;
    if (filter_var($meta['hidden'] ?? $meta['unlisted'] ?? $meta['exclude'] ?? false, FILTER_VALIDATE_BOOLEAN)) return null;
    if (!empty($meta['publish'])) {
        $pubTs = strtotime((string)$meta['publish']);
        if ($pubTs !== false && $pubTs > time()) return null;
    }
    $toc = $mdx->getTOC();

    $title = $meta['title'] ?? null;
    if (!$title && preg_match('/^#\s+(.+)$/m', $mdx->cleanSrc, $tm)) $title = trim($tm[1]);
    if (!$title) $title = $slug;

    $ts = false;
    if (isset($meta['created'])) $ts = strtotime($meta['created']);
    if (!$ts && isset($meta['date'])) $ts = strtotime($meta['date']);
    if (!$ts) $ts = filectime($path);

    $updatedTs = false;
    if (isset($meta['updated'])) $updatedTs = strtotime((string)$meta['updated']);
    if (!$updatedTs) {
        $mtime = @filemtime($path);
        if ($mtime && $mtime > $ts + 86400) $updatedTs = $mtime;
    }

    $summary = $meta['summary'] ?? null;
    if (!$summary) {
        $plain = trim(preg_replace('/^#.*$/m', '', $mdx->cleanSrc));
        $plain = preg_replace('/^```.*?^```/ms', '', $plain);
        $plain = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $plain);
        $plain = preg_replace('/^\s*(https?:\/\/|ftp:\/\/)\S+\s*$/m', '', $plain);
        $plain = preg_replace('/^\s*[\w\-. ]+\.(jpe?g|png|gif|webp|svg|mp4|webm|mov|ogg)\s*$/im', '', $plain);
        $plain = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $plain);
        $plain = preg_replace('/[#*`>_\-\[\]!]/', '', $plain);
        $plain = preg_replace('/\s+/', ' ', $plain);
        $summary = mb_substr(trim($plain), 0, 180) . (mb_strlen(trim($plain)) > 180 ? '…' : '');
    }

    $summary = strip_md($summary);
    $title = strip_md($title);
    $wordCount = str_word_count(strip_tags($html));
    $readMins = max(1, round($wordCount / 200));
    $plain = plain_text($html);

    $socialImage = resolve_cover($meta['cover'] ?? '');
    if (!$socialImage && preg_match('/<img[^>]+src="([^"]+)"/', $html, $cm)) {
        $socialImage = resolve_cover(html_entity_decode($cm[1], ENT_QUOTES, 'UTF-8'), false);
    }

    $html = md_inject_toc($html, $toc);
    $tags = post_tags($meta);
    $ctf = post_ctf($meta);

    $result = [
        'tags' => $tags, 'ctf' => $ctf, 'tags_text' => implode(' ', array_filter(array_merge(array_column($tags, 'name'), [$ctf['event'] ?? '', $ctf['category'] ?? '', $ctf['difficulty'] ?? '']))),
        'slug' => $slug, 'id' => post_id($slug), 'title' => $title, 'date' => $ts, 'updated' => $updatedTs ?: null,
        'month_key' => date('Y-m', $ts), 'month_label' => date('M, Y', $ts),
        'summary' => $summary, 'html' => $html, 'plain' => $plain, 'read_mins' => $readMins, 'social_image' => $socialImage,
        'protected' => $prot['protected'], 'raw_len' => strlen($raw), 'toc' => array_values(array_filter($toc, fn($h) => $h['level'] >= 2)),
        'hidden' => filter_var($meta['hidden'] ?? $meta['unlisted'] ?? $meta['exclude'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ];

    if (!$prot['protected'] && $mtime) render_cache_put($dir, $path, $mtime, $result);
    return $result;
}

function load_all_posts($dir) {
    $posts = [];
    foreach (post_files($dir) as $f) {
        $p = load_post($dir, slug_from_file($f));
        if ($p) $posts[] = $p;
    }
    usort($posts, fn($a, $b) => $b['date'] <=> $a['date']);
    return $posts;
}

function base_url() {
    global $CFG;
    $fixed = trim((string)($CFG['base_url'] ?? ''));
    if ($fixed !== '' && preg_match('#^https?://[A-Za-z0-9.\-:\[\]]+(?:/[A-Za-z0-9._%/\-]*)?$#', $fixed)) return rtrim($fixed, '/');
    $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('#^(?:[A-Za-z0-9.\-]+|\[[0-9A-Fa-f:.]+\])(?::[0-9]{1,5})?$#', $host)) {
        $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
        if (!preg_match('#^[A-Za-z0-9.\-]+$#', $host)) $host = 'localhost';
    }
    $dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (!preg_match('#^[A-Za-z0-9._%/\-]*$#', $dir)) $dir = '';
    return ($https ? 'https://' : 'http://') . $host . rtrim($dir, '/');
}

function site_origin() {
    global $CFG;
    $fixed = trim((string)($CFG['base_url'] ?? ''));
    if ($fixed !== '' && preg_match('#^(https?://[A-Za-z0-9.\-:\[\]]+)#', $fixed, $m)) return $m[1];
    $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('#^(?:[A-Za-z0-9.\-]+|\[[0-9A-Fa-f:.]+\])(?::[0-9]{1,5})?$#', $host)) {
        $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
        if (!preg_match('#^[A-Za-z0-9.\-]+$#', $host)) $host = 'localhost';
    }
    return ($https ? 'https://' : 'http://') . $host;
}

function post_url($p) {
    return doc_url($p['id']);
}

function doc_url($id) {
    $dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (!preg_match('#^[A-Za-z0-9._%/\-]*$#', $dir)) $dir = '';
    return rtrim($dir, '/') . '/p/' . rawurlencode(strtolower((string)$id));
}

function export_url($id, $format) {
    $fmt = ($format === 'html') ? 'html' : 'md';
    return pretty_dir() . '/export/' . rawurlencode(strtolower((string)$id)) . '/' . $fmt;
}

function view_md_url($id) {
    return pretty_dir() . '/md/' . rawurlencode(strtolower((string)$id));
}

function pretty_dir() {
    $dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (!preg_match('#^[A-Za-z0-9._%/\-]*$#', $dir)) $dir = '';
    return rtrim($dir, '/');
}

function home_url() {
    global $CFG;
    $raw = (string)($CFG['home_url'] ?? './');
    if (preg_match('~^(?:[a-z][a-z0-9+.\-]*:)?//~i', $raw)) return $raw;
    $dir = pretty_dir();
    return ($dir === '' ? '/' : $dir . '/');
}

function protected_list_url($next = null) {
    $url = pretty_dir() . '/protected';
    if ($next !== null && $next !== '') $url .= '?next=' . rawurlencode($next);
    return $url;
}

function access_log_url() {
    return pretty_dir() . '/access-log';
}

function rss_url() {
    return pretty_dir() . '/rss.xml';
}

function asset_url($path) {
    global $CFG;
    if (!is_string($path) || $path === '') return $path;
    if (preg_match('~^(?:[a-z][a-z0-9+.\-]*:)?//~i', $path)) return $path;
    if (strpos($path, 'assets/') !== 0) return $path;
    $rel = substr($path, strlen('assets/'));
    if ($rel === '' || strpos($rel, '..') !== false) return $path;
    $local = asset_token_url($CFG, $rel);
    if ($local === null) return $path; 
    $cdn = rtrim((string)($CFG['cdn_url'] ?? ''), '/');
    return $cdn !== '' ? $cdn . $local : $local;
}

function xml_esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8'); }

if (isset($_GET['robots'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    $base = base_url();
    echo "User-agent: *\n";
    echo "Disallow: /posts/\n";
    echo "Allow: /\n";
    echo 'Sitemap: ' . $base . '/sitemap.xml' . "\n";
    exit;
}

if (isset($_GET['sitemap'])) {
    header('Content-Type: application/xml; charset=UTF-8');
    $posts = load_all_posts($CFG['posts_dir']);
    $base = base_url();
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    echo '<url><loc>' . xml_esc($base . '/' . $CFG['self']) . "</loc></url>\n";
    foreach ($posts as $p) {
        $lastmod = !empty($p['updated']) && $p['updated'] > $p['date'] ? $p['updated'] : $p['date'];
        echo '<url><loc>' . xml_esc(site_origin() . post_url($p)) . '</loc><lastmod>' . date('c', $lastmod) . "</lastmod></url>\n";
    }
    echo "</urlset>";
    exit;
}

if (isset($_GET['rss'])) {
    $reqPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (substr($reqPath, -8) !== '/rss.xml' && $reqPath !== 'rss.xml') {
        header('Location: ' . rss_url(), true, 301);
        exit;
    }
    header('Content-Type: application/rss+xml; charset=UTF-8');
    $posts = load_all_posts($CFG['posts_dir']);
    $base = base_url();
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0"><channel><title>' . xml_esc($CFG['rss_title']) . '</title><link>' . xml_esc($base) . '</link><description>' . xml_esc($CFG['rss_desc']) . "</description><language>en-us</language>\n";
    foreach ($posts as $p) {
        $link = xml_esc(site_origin() . post_url($p));
        echo '<item><title>' . xml_esc($p['title']) . '</title><link>' . $link . '</link><description>' . xml_esc($p['summary']) . '</description><pubDate>' . date(DATE_RSS, $p['date']) . '</pubDate><guid>' . $link . "</guid></item>\n";
    }
    echo "</channel></rss>";
    exit;
}

function asset_mime($ext) {
    $map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'avif' => 'image/avif', 'ico' => 'image/x-icon',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', 'ogg' => 'video/ogg',
    ];
    return $map[strtolower($ext)] ?? null;
}

if (isset($_GET['img'])) {
    $rel = asset_decode_token($CFG, (string)$_GET['img']);
    $path = $rel !== null ? realpath($CFG['assets_dir'] . '/' . $rel) : false;
    $assetsReal = realpath($CFG['assets_dir']);
    if ($path === false || $assetsReal === false || strpos($path, $assetsReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Not found');
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = asset_mime($ext);
    if ($mime === null) { http_response_code(404); exit('Not found'); }

    $size = filesize($path);
    $mtime = filemtime($path);
    $etag = '"' . substr(hash('sha256', $path . '|' . $mtime . '|' . $size), 0, 32) . '"';

    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    if ($ext === 'svg') header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:");
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('ETag: ' . $etag);

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince  = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if (($ifNoneMatch !== '' && trim($ifNoneMatch) === $etag)
        || ($ifModSince !== '' && @strtotime($ifModSince) >= $mtime)) {
        http_response_code(304);
        exit;
    }

    if (ob_get_level() > 0) { @ob_end_clean(); } 

    $fh = @fopen($path, 'rb');
    if ($fh === false) { http_response_code(404); exit('Not found'); }

    $start = 0;
    $end = $size - 1;
    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if ($range !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $rm) && ($rm[1] !== '' || $rm[2] !== '')) {
        $reqStart = $rm[1] === '' ? null : (int)$rm[1];
        $reqEnd   = $rm[2] === '' ? null : (int)$rm[2];
        if ($reqStart === null) { $start = max(0, $size - $reqEnd); $end = $size - 1; }
        else { $start = $reqStart; $end = $reqEnd !== null ? min($reqEnd, $size - 1) : $size - 1; }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            fclose($fh);
            exit;
        }
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    header('Content-Length: ' . ($end - $start + 1));
    fseek($fh, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($fh)) {
        $chunk = fread($fh, min(65536, $remaining));
        if ($chunk === false) break;
        echo $chunk;
        $remaining -= strlen($chunk);
    }
    fclose($fh);
    exit;
}

function pp_list_is_unlocked($CFG) {
    if (empty($_SESSION[PP_LIST_SESSION_KEY])) return false;
    $rec = $_SESSION[PP_LIST_SESSION_KEY];
    if (!is_array($rec) || empty($rec['t']) || empty($rec['h'])) return false;
    if ((int)$rec['t'] + (int)$CFG['protected_ttl'] < time()) {
        unset($_SESSION[PP_LIST_SESSION_KEY]);
        return false;
    }
    $listPw = (string)($CFG['protected_list_password'] ?? '');
    if ($listPw === '') $listPw = (string)($CFG['protected_password'] ?? '');
    if ($listPw === '') return false;
    return hash_equals(hash_hmac('sha256', pp_config_secrets($CFG), 'pplist'), (string)$rec['h']);
}

function pp_list_unlock($CFG) {
    $_SESSION[PP_LIST_SESSION_KEY] = [
        't' => time(),
        'h' => hash_hmac('sha256', pp_config_secrets($CFG), 'pplist'),
    ];
}

function render_protected_list_gate($CFG, $error, $status, $next = '') {
    global $CSP_NONCE;
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    $site = htmlspecialchars($CFG['site_name'], ENT_QUOTES, 'UTF-8');
    $acc  = htmlspecialchars($CFG['accent'], ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $csrf = $_SESSION['pp_list_csrf'] ?? '';
    $tok  = htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8');
    $nextTok = preg_match('/^[A-Z0-9]{1,8}$/', (string)$next) ? htmlspecialchars($next, ENT_QUOTES, 'UTF-8') : '';
    $nextField = $nextTok !== '' ? '<input type="hidden" name="pp_list_next" value="' . $nextTok . '">' : '';
    $intro = $nextTok !== '' ? 'Enter the password to view this post.' : 'Enter the password to view the list of protected posts.';
    $warnIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>';
    $err  = $error !== '' ? '<div class="err" role="alert">' . $warnIcon . '<span>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</span></div>' : '';
    $palette = pp_gate_accent_css();
    $card = pp_gate_card_css();
    $css  = <<<CSS
:root{--bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;--ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"]{--bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;--ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;}
{$palette}
{$card}
body{background:rgba(10,11,16,.6);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);animation:none;}
.card{box-shadow:0 24px 64px rgba(0,0,0,.35);}
CSS;
    $jsTheme = pp_gate_theme_js();
    $jsEye = pp_gate_eye_js();
    $lockIcon = pp_gate_lock_icon();
    $listUrl = htmlspecialchars(protected_list_url(), ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="light" data-accent="{$acc}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Protected Posts &mdash; {$site}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>{$css}</style>
<script nonce="{$CSP_NONCE}">{$jsTheme}</script>
</head>
<body>
<div class="card" role="dialog" aria-modal="true" aria-labelledby="ppModalTitle">
  <div class="icon">{$lockIcon}</div>
  <h1 id="ppModalTitle">Protected Posts</h1>
  <p class="lead">{$intro}</p>
  {$err}
  <form method="post" action="{$listUrl}">
    <input type="hidden" name="pp_list_unlock" value="1">
    <input type="hidden" name="pp_list_csrf" value="{$tok}">
    {$nextField}
    <div class="field">
      <input id="pw" type="password" name="pp_list_password" placeholder="Password" required maxlength="1024" autofocus autocomplete="current-password" aria-label="Password">
      <button type="button" class="eye" id="eye" aria-label="Show password">Show</button>
    </div>
    <button type="submit" class="btn">View Protected Posts</button>
  </form>
  <a class="back" href="{$home}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>All posts</a>
</div>
<script nonce="{$CSP_NONCE}">{$jsEye}</script>
</body>
</html>
HTML;
    exit;
}

function render_protected_list_page($CFG, $protectedPosts) {
    global $CSP_NONCE;
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    $site = htmlspecialchars($CFG['site_name'], ENT_QUOTES, 'UTF-8');
    $acc  = htmlspecialchars($CFG['accent'], ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $items = '';
    foreach ($protectedPosts as $p) {
        $url   = htmlspecialchars(doc_url($p['id']), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8');
        $date  = htmlspecialchars(date('d M Y', $p['date']), ENT_QUOTES, 'UTF-8');
        $desc  = htmlspecialchars((string)($p['summary'] ?? ''), ENT_QUOTES, 'UTF-8');
        $descHtml = $desc !== '' ? '<div class="pcard-desc">' . $desc . '</div>' : '';
        $badge = !empty($p['extra']) ? '<span class="pcard-badge">Extra password</span>' : '';
        $items .= <<<HTML
<div class="pcard">
  <div class="pcard-meta">{$date}{$badge}</div>
  <a class="pcard-title" href="{$url}">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
    {$title}
  </a>
  {$descHtml}
</div>

HTML;
    }
    $emptyIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';
    if (!$items) $items = '<div class="empty">' . $emptyIcon . '<span>No protected posts found.</span></div>';
    $paletteList = pp_gate_accent_css();
    $jsThemeList = pp_gate_theme_js();
    $lockIconList = pp_gate_lock_icon();
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="light" data-accent="{$acc}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Protected Posts &mdash; {$site}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;--ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"]{--bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;--ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;}
{$paletteList}
*,*::before,*::after{box-sizing:border-box;}
html,body{min-height:100%;}
body{margin:0;background:var(--bg);color:var(--ink);font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;line-height:1.7;-webkit-font-smoothing:antialiased;}
.wrap{max-width:760px;margin:0 auto;padding:0 24px 60px;}
.site-head{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;max-width:760px;margin:0 auto;}
.brand{font-family:'Manrope','Inter',sans-serif;font-weight:800;font-size:1.1rem;color:var(--ink);text-decoration:none;}
.back-link{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--ink3);text-decoration:none;font-weight:600;transition:color .15s;}
.back-link:hover{color:var(--accent);}
.back-link svg{width:13px;height:13px;}
.hero-icon{width:48px;height:48px;margin:36px 0 14px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--accent);}
.hero-icon svg{width:22px;height:22px;}
h1{font-family:'Manrope','Inter',sans-serif;font-weight:800;font-size:1.8rem;color:var(--ink);margin:0 0 8px;letter-spacing:-.01em;}
.sub{color:var(--ink3);font-size:.9rem;margin:0 0 32px;}
.pcard{padding:18px 0;border-bottom:1px solid var(--border);transition:padding-left .15s ease;}
.pcard:first-child{border-top:1px solid var(--border);}
.pcard:hover{padding-left:4px;}
.pcard-meta{font-size:12px;color:var(--ink3);font-weight:600;margin-bottom:4px;}
.pcard-title{display:inline-flex;align-items:center;gap:9px;font-size:16px;font-weight:700;color:var(--ink);text-decoration:none;font-family:'Manrope','Inter',sans-serif;transition:color .15s;}
.pcard-title:hover{color:var(--accent);}
.pcard-title svg{color:var(--accent);flex-shrink:0;}
.pcard-desc{margin-top:6px;font-size:13.5px;color:var(--ink2);line-height:1.75;}
.pcard-badge{display:inline-block;margin-left:10px;padding:1px 8px;border-radius:20px;background:var(--accent-soft);color:var(--accent);font-size:10.5px;font-weight:700;letter-spacing:.03em;}
.empty{display:flex;flex-direction:column;align-items:center;gap:12px;color:var(--ink3);padding:56px 0;text-align:center;}
.empty svg{width:32px;height:32px;opacity:.5;}
</style>
<script nonce="{$CSP_NONCE}">{$jsThemeList}</script>
</head>
<body>
<div style="border-bottom:1px solid var(--border);margin-bottom:8px;">
  <header class="site-head">
    <a class="brand" href="{$home}">{$site}</a>
    <a class="back-link" href="{$home}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>All posts</a>
  </header>
</div>
<div class="wrap">
  <div class="hero-icon">{$lockIconList}</div>
  <h1>Protected Posts</h1>
  <p class="sub">You have access to view this list. Click any post to read it.</p>
  {$items}
</div>
</body>
</html>
HTML;
    exit;
}

function render_access_log_gate($CFG, $error, $status) {
    global $CSP_NONCE;
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    $site = htmlspecialchars($CFG['site_name'], ENT_QUOTES, 'UTF-8');
    $acc  = htmlspecialchars($CFG['accent'], ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $csrf = $_SESSION['pp_log_csrf'] ?? '';
    $tok  = htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8');
    $warnIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>';
    $err  = $error !== '' ? '<div class="err" role="alert">' . $warnIcon . '<span>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</span></div>' : '';
    $palette = pp_gate_accent_css();
    $card = pp_gate_card_css();
    $css  = <<<CSS
:root{--bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;--ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"]{--bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;--ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;}
{$palette}
{$card}
CSS;
    $jsTheme = pp_gate_theme_js();
    $jsEye = pp_gate_eye_js();
    $lockIcon = pp_gate_lock_icon();
    $logUrl = htmlspecialchars(access_log_url(), ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="light" data-accent="{$acc}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Access Log &mdash; {$site}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>{$css}</style>
<script nonce="{$CSP_NONCE}">{$jsTheme}</script>
</head>
<body>
<main class="card">
  <div class="icon">{$lockIcon}</div>
  <h1>Access Log</h1>
  <p class="lead">Enter the password to view recent access attempts.</p>
  {$err}
  <form method="post" action="{$logUrl}">
    <input type="hidden" name="pp_log_unlock" value="1">
    <input type="hidden" name="pp_log_csrf" value="{$tok}">
    <div class="field">
      <input id="pw" type="password" name="pp_log_password" placeholder="Password" required maxlength="1024" autofocus autocomplete="current-password" aria-label="Password">
      <button type="button" class="eye" id="eye" aria-label="Show password">Show</button>
    </div>
    <button type="submit" class="btn">View Log</button>
  </form>
  <a class="back" href="{$home}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>All posts</a>
</main>
<script nonce="{$CSP_NONCE}">{$jsEye}</script>
</body>
</html>
HTML;
    exit;
}

function render_access_log_page($CFG, $lines) {
    global $CSP_NONCE;
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    $site = htmlspecialchars($CFG['site_name'], ENT_QUOTES, 'UTF-8');
    $acc  = htmlspecialchars($CFG['accent'], ENT_QUOTES, 'UTF-8');
    $home = htmlspecialchars(home_url(), ENT_QUOTES, 'UTF-8');
    $jsThemeList = pp_gate_theme_js();

    $rowsHtml = '';
    foreach ($lines as $line) {
        $rowsHtml .= '<div class="logrow">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "</div>\n";
    }
    if ($rowsHtml === '') {
        $rowsHtml = '<div class="empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>No log entries yet.</div>';
    }

    $css = <<<CSS
:root{--bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;--ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"]{--bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;--ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;}
*{box-sizing:border-box;}
body{margin:0;background:var(--bg);color:var(--ink);font-family:'Inter',sans-serif;}
.site-head{display:flex;align-items:center;justify-content:space-between;max-width:900px;margin:0 auto;padding:20px 24px;}
.brand{font-family:'Manrope',sans-serif;font-weight:800;font-size:18px;color:var(--ink);text-decoration:none;}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--ink3);text-decoration:none;}
.back-link svg{width:14px;height:14px;}
.wrap{max-width:900px;margin:0 auto;padding:0 24px 60px;}
.wrap h1{font-family:'Manrope',sans-serif;font-size:24px;margin:8px 0 4px;}
.wrap .sub{color:var(--ink3);font-size:13.5px;margin:0 0 24px;}
.logbox{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:8px;max-height:70vh;overflow-y:auto;}
.logrow{font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--ink2);padding:8px 10px;border-bottom:1px solid var(--border);white-space:pre-wrap;word-break:break-word;}
.logrow:last-child{border-bottom:none;}
.logrow:nth-child(odd){background:var(--surface2);}
.empty{display:flex;flex-direction:column;align-items:center;gap:12px;color:var(--ink3);padding:56px 0;text-align:center;}
.empty svg{width:32px;height:32px;opacity:.5;}
CSS;

    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="light" data-accent="{$acc}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>Access Log &mdash; {$site}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>{$css}</style>
<script nonce="{$CSP_NONCE}">{$jsThemeList}</script>
</head>
<body>
<div style="border-bottom:1px solid var(--border);margin-bottom:8px;">
  <header class="site-head">
    <a class="brand" href="{$home}">{$site}</a>
    <a class="back-link" href="{$home}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>All posts</a>
  </header>
</div>
<div class="wrap">
  <h1>Access Log</h1>
  <p class="sub">Most recent attempts first &mdash; unlock/gate events for protected posts and the protected-posts list.</p>
  <div class="logbox">{$rowsHtml}</div>
</div>
</body>
</html>
HTML;
    exit;
}

if (isset($_GET['access_log'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $reqPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (substr($reqPath, -11) !== '/access-log' && $reqPath !== 'access-log') {
            header('Location: ' . access_log_url(), true, 301);
            exit;
        }
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    $logPw = (string)($CFG['access_log_password'] ?? '');
    if ($logPw === '') { http_response_code(404); exit('Not found'); }

    $limiterOk = pp_rl_path($CFG, 'probe') !== null;
    $error  = '';
    $status = 200;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pp_log_unlock'])) {
        if (!$limiterOk) {
            $error  = 'Password check is temporarily unavailable.';
            $status = 503;
        } else {
            $given = (string)($_POST['pp_log_csrf'] ?? '');
            $csrf  = (string)($_SESSION['pp_log_csrf'] ?? '');
            if ($csrf === '' || !hash_equals($csrf, $given)) {
                $error  = 'Session expired. Please try again.';
                $status = 403;
            } else {
                $wait = pp_log_rl_wait($CFG);
                if ($wait > 0) {
                    header('Retry-After: ' . $wait);
                    $error  = 'Too many attempts. Try again in ' . max(1, (int)ceil($wait / 60)) . ' minute(s).';
                    $status = 429;
                } else {
                    $inputPw = (string)($_POST['pp_log_password'] ?? '');
                    if (pp_verify($inputPw, $logPw)) {
                        pp_log_rl_clear($CFG);
                        session_regenerate_id(true);
                        pp_log_unlock($CFG);
                        $_SESSION['pp_log_csrf'] = bin2hex(random_bytes(32));
                        header('Location: ' . access_log_url(), true, 303);
                        exit;
                    } else {
                        usleep(random_int(250000, 500000));
                        pp_log_rl_add($CFG);
                        if (mt_rand(1, 40) === 1) pp_rl_gc($CFG);
                        $error  = 'Incorrect password.';
                        $status = 403;
                    }
                }
            }
        }
    }

    if (empty($_SESSION['pp_log_csrf']) || !is_string($_SESSION['pp_log_csrf'])) {
        $_SESSION['pp_log_csrf'] = bin2hex(random_bytes(32));
    }

    if (!pp_log_is_unlocked($CFG)) {
        render_access_log_gate($CFG, $error, $status);
        exit;
    }

    $logPath = $CFG['posts_dir'] . '/.auth/access.log';
    $lines = [];
    if (is_file($logPath)) {
        $raw = @file_get_contents($logPath);
        if ($raw !== false && $raw !== '') {
            $all = preg_split('/\r?\n/', trim($raw));
            $lines = array_reverse(array_slice($all, -500));
        }
    }
    render_access_log_page($CFG, $lines);
    exit;
}

if (isset($_GET['protected_list'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $reqPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (substr($reqPath, -10) !== '/protected' && $reqPath !== 'protected') {
            $nextQ = isset($_GET['next']) ? (string)$_GET['next'] : '';
            header('Location: ' . protected_list_url($nextQ !== '' ? $nextQ : null), true, 301);
            exit;
        }
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    $listPw  = (string)($CFG['protected_list_password'] ?? '');
    if ($listPw === '') $listPw = (string)($CFG['protected_password'] ?? '');
    $limiterOk = pp_rl_path($CFG, 'probe') !== null;
    $error   = '';
    $status  = 200;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pp_list_unlock'])) {
        if (!$limiterOk) {
            pp_access_log($CFG, 'LIST_UNLOCK_FAIL', ['reason' => 'rate_limiter_unavailable']);
            $error  = 'Password check is temporarily unavailable.';
            $status = 503;
        } else {
            $given = (string)($_POST['pp_list_csrf'] ?? '');
            $csrf  = (string)($_SESSION['pp_list_csrf'] ?? '');
            if ($csrf === '' || !hash_equals($csrf, $given)) {
                pp_access_log($CFG, 'LIST_UNLOCK_FAIL', ['reason' => 'csrf_mismatch']);
                $error  = 'Session expired. Please try again.';
                $status = 403;
            } else {
                $wait = pp_list_rl_wait($CFG);
                if ($wait > 0) {
                    header('Retry-After: ' . $wait);
                    pp_access_log($CFG, 'LIST_UNLOCK_RATELIMIT', ['wait_seconds' => $wait]);
                    $error  = 'Too many attempts. Try again in ' . max(1, (int)ceil($wait / 60)) . ' minute(s).';
                    $status = 429;
                } else {
                    $inputPw = (string)($_POST['pp_list_password'] ?? '');
                    if (pp_verify($inputPw, $listPw)) {
                        pp_list_rl_clear($CFG);
                        session_regenerate_id(true);
                        pp_list_unlock($CFG);
                        $_SESSION['pp_list_csrf'] = bin2hex(random_bytes(32));
                        pp_access_log($CFG, 'LIST_UNLOCK_SUCCESS', []);
                        $next = (string)($_POST['pp_list_next'] ?? '');
                        $dest = (preg_match('/^[A-Z0-9]{1,8}$/', $next) && resolve_post_id($CFG['posts_dir'], $next) !== null)
                            ? doc_url($next)
                            : protected_list_url();
                        header('Location: ' . $dest, true, 303);
                        exit;
                    } else {
                        usleep(random_int(250000, 500000));
                        pp_list_rl_add($CFG);
                        if (mt_rand(1, 40) === 1) pp_rl_gc($CFG);
                        pp_access_log($CFG, 'LIST_UNLOCK_FAIL', ['reason' => 'wrong_password']);
                        $error  = 'Incorrect password.';
                        $status = 403;
                    }
                }
            }
        }
    }

    if (empty($_SESSION['pp_list_csrf']) || !is_string($_SESSION['pp_list_csrf'])) {
        $_SESSION['pp_list_csrf'] = bin2hex(random_bytes(32));
    }

    if ($listPw === '') {
        
        
        http_response_code(404);
        exit('Not found');
    }

    if (!pp_list_is_unlocked($CFG)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            pp_access_log($CFG, 'LIST_GATE_SHOWN', []);
        }
        $nextIn = (string)($_POST['pp_list_next'] ?? $_GET['next'] ?? '');
        render_protected_list_gate($CFG, $error, $status, $nextIn);
        exit;
    }

    $nextIn = (string)($_GET['next'] ?? '');
    if (preg_match('/^[A-Z0-9]{1,8}$/', $nextIn) && resolve_post_id($CFG['posts_dir'], $nextIn) !== null) {
        header('Location: ' . doc_url($nextIn), true, 302);
        exit;
    }

    pp_access_log($CFG, 'LIST_VIEW', []);
    $protectedPosts = [];
    foreach (post_files($CFG['posts_dir']) as $f) {
        $sl   = slug_from_file($f);
        $path = post_path($CFG['posts_dir'], $sl);
        if (!$path) continue;
        $raw = @file_get_contents($path);
        if ($raw === false || is_hidden_source($raw)) continue;
        $fl = post_flags($raw);
        if (!$fl['protected']) continue;
        $lp = load_post($CFG['posts_dir'], $sl, true);
        if (!$lp) continue;
        $protectedPosts[] = [
            'id'      => $lp['id'],
            'title'   => $lp['title'],
            'date'    => (int)$lp['date'],
            'summary' => (string)$lp['summary'],
            'slug'    => $sl,
            'extra'   => $fl['secret'] !== '',
        ];
    }
    usort($protectedPosts, fn($a, $b) => $b['date'] <=> $a['date']);
    render_protected_list_page($CFG, $protectedPosts);
    exit;
}

$allPosts = load_all_posts($CFG['posts_dir']);
if (mt_rand(1, 60) === 1) render_cache_gc($CFG['posts_dir']);

function sanitize_slug($raw) {
    $s = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', (string)$raw);
    while (strpos($s, '..') !== false) $s = str_replace('..', '', $s);
    $s = trim($s, '/');
    if ($s === '' || strlen($s) > 200) return null;
    return $s;
}

function search_terms($raw) {
    if (!is_string($raw)) return [];
    $q = preg_replace('/\s+/u', ' ', (string)$raw);
    $q = $q === null ? '' : trim($q);
    if ($q === '') return [];
    if (mb_strlen($q) > 120) $q = mb_substr($q, 0, 120);
    $terms = [];
    foreach (explode(' ', $q) as $t) {
        $t = mb_strtolower($t);
        if ($t !== '' && !in_array($t, $terms, true)) $terms[] = $t;
        if (count($terms) >= 8) break;
    }
    return $terms;
}

function count_hits($haystack, $needle, $cap) {
    if ($haystack === '' || $needle === '') return 0;
    $n = 0;
    $off = 0;
    $len = mb_strlen($needle);
    while ($n < $cap) {
        $pos = mb_stripos($haystack, $needle, $off);
        if ($pos === false) break;
        $n++;
        $off = $pos + $len;
    }
    return $n;
}

function make_snippet($text, $pos) {
    $span = 180;
    $total = mb_strlen($text);
    $start = max(0, $pos - 60);
    $snip = mb_substr($text, $start, $span);
    if ($start > 0) {
        $sp = mb_strpos($snip, ' ');
        if ($sp !== false && $sp < 20) $snip = mb_substr($snip, $sp + 1);
        $snip = '…' . $snip;
    }
    if ($start + $span < $total) $snip .= '…';
    return $snip;
}

function search_posts($posts, $terms) {
    $phrase = implode(' ', $terms);
    $results = [];
    foreach ($posts as $p) {
        $score = 0;
        $ok = true;
        $bodyPos = false;
        foreach ($terms as $t) {
            $inTitle = count_hits($p['title'], $t, 5);
            $inSummary = count_hits($p['summary'], $t, 5);
            $inBody = count_hits($p['plain'], $t, 50);
            $inTag = count_hits($p['tags_text'] ?? '', $t, 5);
            if ($inTitle + $inSummary + $inBody + $inTag === 0) { $ok = false; break; }
            $score += $inTitle * 50 + $inSummary * 10 + $inBody + $inTag * 30;
            if ($inBody > 0) {
                $pos = mb_stripos($p['plain'], $t);
                if ($pos !== false && ($bodyPos === false || $pos < $bodyPos)) $bodyPos = $pos;
            }
        }
        if (!$ok) continue;
        if (count($terms) > 1) {
            if (count_hits($p['title'], $phrase, 1) > 0) $score += 100;
            $phrasePos = mb_stripos($p['plain'], $phrase);
            if ($phrasePos !== false) { $score += 40; $bodyPos = $phrasePos; }
        }
        if ($bodyPos !== false) $snippet = make_snippet($p['plain'], $bodyPos);
        else $snippet = '';
        $results[] = ['id' => $p['id'], 'title' => $p['title'], 'snippet' => $snippet, 'score' => $score, 'date' => $p['date']];
    }
    usort($results, function($a, $b) {
        if ($a['score'] !== $b['score']) return $b['score'] <=> $a['score'];
        return $b['date'] <=> $a['date'];
    });
    return array_slice($results, 0, 50);
}

if (isset($_GET['action']) && $_GET['action'] === 'search' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $terms = search_terms($_GET['q'] ?? '');
    $joined = implode(' ', $terms);
    if ($joined === '' || mb_strlen($joined) < 2) json_out(['ok' => true, 'terms' => $terms, 'results' => []]);
    $found = search_posts($allPosts, $terms);
    foreach ($found as &$r) unset($r['date']);
    unset($r);
    json_out(['ok' => true, 'terms' => $terms, 'results' => $found]);
}

function render_article_page($exPost, $CFG, $asDownload = true, $downloadName = null, $canonicalQuery = null) {
    if ($asDownload && (!is_array($exPost) || !empty($exPost['protected']))) {
        pp_deny_export($CFG, is_array($exPost) ? (string)($exPost['slug'] ?? '') : '', 'html');
    }
    $base = base_url() . '/';

    $themeIn  = ($_GET['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
    $accentIn = $_GET['accent'] ?? 'indigo';
    $accents  = [
        'indigo' => ['light' => ['#5B5BF0','#EEEEFE','#4444DC'], 'dark' => ['#8181FC','#1C1D3D','#9494FF']],
        'blue'   => ['light' => ['#2E7CF6','#EAF2FE','#1E63D6'], 'dark' => ['#5B9AFC','#152238','#7CAEFF']],
        'green'  => ['light' => ['#0E9F6E','#E5F8F1','#0B7F58'], 'dark' => ['#34D399','#0C2A20','#5EE0AF']],
        'rose'   => ['light' => ['#E23C6D','#FDEBF1','#C22857'], 'dark' => ['#FB7096','#331420','#FF8CAA']],
        'amber'  => ['light' => ['#C77700','#FEF2DF','#A66300'], 'dark' => ['#F0A93E','#2B1E06','#FFC06B']],
    ];
    if (!isset($accents[$accentIn])) $accentIn = 'indigo';
    list($accent, $accentSoft, $accentHover) = $accents[$accentIn][$themeIn];
    if ($themeIn === 'dark') {
        $bg = '#0D0E14'; $surface = '#15171F'; $surface2 = '#1B1E2A'; $border = '#282B3A';
        $ink = '#EDEEF6'; $ink2 = '#A9ABC0'; $ink3 = '#5C5F72';
        $codeBg = '#0D0F17'; $codeInk = '#E6E8F3';
    } else {
        $bg = '#F6F7FB'; $surface = '#FFFFFF'; $surface2 = '#EEF0F6'; $border = '#E1E3EC';
        $ink = '#111319'; $ink2 = '#40434E'; $ink3 = '#8A8DA0';
        $codeBg = '#0D0F17'; $codeInk = '#E6E8F3';
    }

    $content = $exPost['html'];

    $content = preg_replace_callback(
        '~<div class="md-embed md-embed-video"><iframe src="([^"]+)"[^>]*></iframe></div>~i',
        function($m) {
            $src = $m[1];
            if (preg_match('~youtube\.com/embed/([A-Za-z0-9_-]+)~i', $src, $vm)) {
                $vid = $vm[1];
                $thumb = "https://img.youtube.com/vi/{$vid}/hqdefault.jpg";
                $watch = "https://www.youtube.com/watch?v={$vid}";
                return '<a class="embed-card embed-video" href="' . $watch . '" target="_blank" rel="noopener noreferrer" style="background-image:url(\'' . $thumb . '\')"><span class="embed-play">&#9658;</span><span class="embed-label">Watch on YouTube</span></a>';
            }
            if (preg_match('~vimeo\.com/video/(\d+)~i', $src, $vm)) {
                $watch = "https://vimeo.com/{$vm[1]}";
                return '<a class="embed-card embed-video embed-video-plain" href="' . $watch . '" target="_blank" rel="noopener noreferrer"><span class="embed-play">&#9658;</span><span class="embed-label">Watch on Vimeo</span></a>';
            }
            return '<a class="embed-card embed-video embed-video-plain" href="' . htmlspecialchars($src, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer"><span class="embed-play">&#9658;</span><span class="embed-label">Watch video</span></a>';
        },
        $content
    );
    $content = preg_replace_callback(
        '~<div class="md-embed md-embed-link"><iframe[^>]*></iframe><a class="md-embed-fallback" href="([^"]+)"[^>]*>([^<]*)</a></div>~i',
        function($m) {
            return '<a class="embed-card embed-link" href="' . $m[1] . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($m[2], ENT_QUOTES) . '</a>';
        },
        $content
    );

    
    
    
    
    
    
    
    
    
    
    
    $assetsReal = realpath($CFG['assets_dir']);
    
    
    
    
    static $dataUriCache = [];
    $content = preg_replace_callback('/<img\b[^>]*\bsrc="([^"]+)"[^>]*>/i', function($m) use ($CFG, $assetsReal, &$dataUriCache) {
        $full = $m[0];
        $url = $m[1];
        if (strpos($url, '/img/') === false) return $full;
        $token = substr($url, strrpos($url, '/img/') + 5);
        $token = preg_replace('~[?#].*$~', '', $token);
        if (isset($dataUriCache[$token])) {
            return $dataUriCache[$token] === null ? $full : preg_replace('/\bsrc="[^"]+"/i', 'src="' . $dataUriCache[$token] . '"', $full, 1);
        }
        $rel = asset_decode_token($CFG, $token);
        if ($rel === null) { $dataUriCache[$token] = null; return $full; }
        $path = realpath($CFG['assets_dir'] . '/' . $rel);
        if ($path === false || $assetsReal === false || strpos($path, $assetsReal . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            $dataUriCache[$token] = null;
            return $full;
        }
        
        
        
        
        
        
        if (filesize($path) > 2 * 1024 * 1024) { $dataUriCache[$token] = null; return $full; }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = asset_mime($ext);
        if ($mime === null || strpos($mime, 'video/') === 0) { $dataUriCache[$token] = null; return $full; } 
        $bytes = @file_get_contents($path);
        if ($bytes === false) { $dataUriCache[$token] = null; return $full; }
        $dataUri = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        $dataUriCache[$token] = $dataUri;
        return preg_replace('/\bsrc="[^"]+"/i', 'src="' . $dataUri . '"', $full, 1);
    }, $content);

    $content = preg_replace_callback('/(src|href)="([^"]+)"/i', function($m) use ($base, $CFG) {
        $url = $m[2];
        if (preg_match('~^(https?:|data:|mailto:|tel:|#)~i', $url)) return $m[0];
        if ($url !== '' && $url[0] === '/') return $m[1] . '="' . site_origin() . $url . '"'; 
        if ($url !== '' && $url[0] === '?') return $m[1] . '="' . $base . $CFG['self'] . $url . '"'; 
        return $m[1] . '="' . $base . ltrim($url, '/') . '"';
    }, $content);

    $extraCss = md_extra_css();
    $exTitle = htmlspecialchars($exPost['title']);
    $exDate = htmlspecialchars(date('F j, Y', $exPost['date']));
    $exDesc = htmlspecialchars($exPost['summary']);
    $exSrcUrl = htmlspecialchars($canonicalQuery !== null ? site_origin() . $canonicalQuery : site_origin() . post_url($exPost));
    $exportedAt = htmlspecialchars(date('F j, Y \a\t g:i A T'));
    $siteName = htmlspecialchars($CFG['site_name']);
    $copyJs = <<<'JS'
(function(){
  function fallbackCopy(text){
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.top = '-1000px';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { ta.setSelectionRange(0, ta.value.length); } catch (e) {}
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    return ok;
  }
  function flash(btn, ok){
    if (!btn.getAttribute('data-label')) btn.setAttribute('data-label', btn.textContent);
    btn.textContent = ok ? 'Copied!' : 'Copy failed';
    clearTimeout(btn._t);
    btn._t = setTimeout(function(){ btn.textContent = btn.getAttribute('data-label'); }, 1400);
  }
  document.addEventListener('click', function(e){
    var btn = e.target && e.target.closest ? e.target.closest('[data-copy-code]') : null;
    if (!btn) return;
    var block = btn.closest('.code-block');
    var codeEl = block ? block.querySelector('code') : null;
    if (!codeEl) return;
    var text = codeEl.innerText || codeEl.textContent || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function(){ flash(btn, true); }, function(){ flash(btn, fallbackCopy(text)); });
    } else {
      flash(btn, fallbackCopy(text));
    }
  });
})();
JS;
    $htmlOut = <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="{$themeIn}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$exTitle}</title>
<meta name="description" content="{$exDesc}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:{$bg};--surface:{$surface};--surface2:{$surface2};--border:{$border};
  --ink:{$ink};--ink2:{$ink2};--ink3:{$ink3};
  --accent:{$accent};--accent-soft:{$accentSoft};--accent-hover:{$accentHover};
  --code-bg:{$codeBg};--code-ink:{$codeInk};
}
*,*::before,*::after{box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;overflow-x:hidden;}
body{margin:0;background:var(--bg);color:var(--ink2);font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;line-height:1.75;font-size:16px;overflow-x:hidden;word-break:break-word;overflow-wrap:anywhere;}
.page{max-width:780px;margin:0 auto;padding:0 20px;}
.hero{padding:56px 0 32px;border-bottom:1px solid var(--border);}
.src-note{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--ink3);margin-bottom:22px;padding:5px 12px;border:1px solid var(--border);border-radius:20px;background:var(--surface);max-width:100%;}
.src-note a{color:var(--accent);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:320px;}
.src-note a:hover{text-decoration:underline;}
h1.post-title{font-family:'Manrope',sans-serif;font-weight:800;font-size:clamp(1.7em,4.5vw,2.5em);line-height:1.2;color:var(--ink);margin:0 0 16px;letter-spacing:-.01em;}
.post-meta{display:flex;flex-wrap:wrap;align-items:center;gap:10px;color:var(--ink3);font-size:13.5px;font-weight:500;}
.post-meta .dot{width:3px;height:3px;border-radius:50%;background:var(--ink3);flex-shrink:0;}
.post-desc{margin:18px 0 0;color:var(--ink2);font-size:15.5px;line-height:1.6;}
article.markdown{max-width:100%;padding:40px 0 20px;}
article.markdown *{max-width:100%;}
article.markdown{min-width:0;overflow-wrap:anywhere;}
.table-wrap table{max-width:none;}
h1,h2,h3,h4{color:var(--ink);font-family:'Manrope',sans-serif;font-weight:700;line-height:1.3;margin:1.6em 0 .6em;}
h1{font-size:1.6em;} h2{font-size:1.35em;border-bottom:1px solid var(--border);padding-bottom:10px;} h3{font-size:1.15em;} h4{font-size:1em;}
h1:first-child,h2:first-child,h3:first-child,h4:first-child{margin-top:0;}
p{margin:0 0 1.2em;}
a{color:var(--accent);text-decoration:none;font-weight:500;}
a:hover{text-decoration:underline;}
strong{color:var(--ink);font-weight:700;}
ul,ol{padding-left:1.4em;margin:0 0 1.2em;}
li{margin:6px 0;}
img,video{max-width:100%;height:auto;border-radius:12px;display:block;}
.md-figure{margin:26px 0;text-align:center;}
.md-figure figcaption{font-size:12.5px;color:var(--ink3);margin-top:10px;font-style:italic;}
blockquote{margin:0 0 1.2em;padding:4px 20px;border-left:3px solid var(--accent);color:var(--ink2);background:var(--accent-soft);border-radius:0 10px 10px 0;}
blockquote p:last-child{margin-bottom:0;}
code{background:var(--surface2);color:var(--accent);padding:2px 7px;border-radius:6px;font-family:'JetBrains Mono',Consolas,monospace;font-size:.87em;overflow-wrap:break-word;word-break:break-word;}
.code-block{margin:22px 0;border-radius:12px;overflow:hidden;background:var(--code-bg);border:1px solid var(--border);max-width:100%;}
.code-bar{display:flex;justify-content:space-between;align-items:center;padding:9px 16px;background:rgba(255,255,255,.04);font-size:11px;color:#9198A8;font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.04em;}
.code-block pre{margin:0;padding:18px 16px;overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%;}
.code-block pre,.code-block code{white-space:pre-wrap;overflow-wrap:break-word;word-break:break-word;background:none;}
.code-block code{color:var(--code-ink);padding:0;font-size:13px;font-family:'JetBrains Mono',monospace;}
.tok-keyword{color:#C792EA;font-weight:600;}
.tok-string{color:#C3E88D;}
.tok-number{color:#F78C6C;}
.tok-comment{color:#6C7280;font-style:italic;}
.code-copy{background:none;border:1px solid rgba(255,255,255,.15);color:#9198A8;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-family:'Inter',sans-serif;text-transform:none;letter-spacing:0;transition:.15s;}
.code-copy:hover{color:#fff;border-color:#fff;}
.table-wrap{overflow-x:auto;margin:22px 0;border-radius:10px;border:1px solid var(--border);-webkit-overflow-scrolling:touch;}
table{border-collapse:collapse;width:100%;table-layout:fixed;}
.table-wrap table{min-width:calc(var(--cols,2) * 110px);}
th,td{border-bottom:1px solid var(--border);padding:10px 14px;text-align:left;vertical-align:top;font-size:14.5px;overflow-wrap:anywhere;word-break:break-word;}
th{background:var(--surface2);color:var(--ink);font-weight:700;}
tr:last-child td{border-bottom:none;}
.embed-card{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:180px;aspect-ratio:16/9;margin:1.4em 0;border-radius:12px;background-color:var(--surface2);background-size:cover;background-position:center;position:relative;color:#fff;font-family:'Manrope',sans-serif;font-weight:700;text-decoration:none;overflow:hidden;}
.embed-card.embed-video::before{content:"";position:absolute;inset:0;background:rgba(0,0,0,.4);}
.embed-card.embed-video-plain{background-color:var(--accent-soft);color:var(--accent);}
.embed-card .embed-play,.embed-card .embed-label{position:relative;z-index:1;}
.embed-card .embed-play{font-size:22px;}
.embed-card.embed-link{min-height:0;aspect-ratio:auto;padding:16px 18px;background:var(--surface2);color:var(--accent);justify-content:flex-start;font-weight:600;}
hr{border:0;border-top:1px solid var(--border);margin:2.4em 0;}
.export-footer-wrap{border-top:1px solid var(--border);padding:28px 0 48px;}
.export-meta{font-size:12.5px;color:var(--ink3);}
.credit{margin-top:10px;font-size:12.5px;color:var(--ink3);}
.credit a{color:var(--accent);}
{$extraCss}
@media(max-width:640px){
  .hero{padding:36px 0 24px;}
  article.markdown{padding:28px 0 12px;}
  .code-block pre{padding:14px 12px;}
  th,td{padding:8px 10px;font-size:13.5px;}
}
</style>
</head>
<body>
<div class="page">
  <div class="hero">
    <div class="src-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> <a href="{$exSrcUrl}" target="_blank" rel="noopener noreferrer">{$exSrcUrl}</a></div>
    <h1 class="post-title">{$exTitle}</h1>
    <div class="post-meta"><span>{$exDate}</span><span class="dot"></span><span>{$exPost['read_mins']} min read</span></div>
    <p class="post-desc">{$exDesc}</p>
  </div>
  <article class="markdown">{$content}</article>
  <div class="export-footer-wrap">
    <div class="export-meta">Exported from {$siteName} on {$exportedAt}.</div>
    <div class="credit">With <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block;vertical-align:-1px;color:#e0245e"><path d="M12 21s-7.5-4.6-10.2-9.1C.4 9.5 1 6.4 3.6 5A5.4 5.4 0 0 1 12 7.1 5.4 5.4 0 0 1 20.4 5c2.6 1.4 3.2 4.5 1.8 6.9C19.5 16.4 12 21 12 21z"/></svg> by <a href="https://psvineet.github.io" target="_blank" rel="noopener noreferrer">Vineet Pratap Singh</a></div>
  </div>
</div>
<script>{$copyJs}</script>
</body>
</html>
HTML;
    header('Content-Type: text/html; charset=UTF-8');
    if ($asDownload) {
        header('Content-Disposition: ' . content_disposition('attachment', $downloadName ?: $exPost['slug'], 'html'));
    }
    if (ob_get_level() > 0) { @ob_end_clean(); } 
    header('Cache-Control: no-store');
    $acceptsGzip = strpos((string)($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false;
    $gzOut = $acceptsGzip && function_exists('gzencode') ? @gzencode($htmlOut, 6) : false;
    if ($gzOut !== false) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . strlen($gzOut));
        echo $gzOut;
    } else {
        header('Content-Length: ' . strlen($htmlOut));
        echo $htmlOut;
    }
    exit;
}

function load_standalone_doc($path, $fallbackTitle, $fallbackSummary) {
    if (!is_file($path)) { http_response_code(404); exit('Not found'); }
    $raw = file_get_contents($path);
    $mdx = new MDX();
    $html = $mdx->toHTML($raw);
    $html = md_inject_toc($html, $mdx->getTOC());
    $meta = $mdx->meta;
    $title = $meta['title'] ?? (preg_match('/^#\s+(.+)$/m', $mdx->cleanSrc, $tm) ? trim($tm[1]) : $fallbackTitle);
    $summary = $meta['summary'] ?? $fallbackSummary;
    return [
        'slug' => pathinfo($path, PATHINFO_FILENAME),
        'id' => strtoupper(pathinfo($path, PATHINFO_FILENAME)),
        'title' => $title,
        'date' => filectime($path),
        'summary' => $summary,
        'html' => $html,
        'read_mins' => max(1, round(str_word_count(strip_tags($html)) / 200)),
        'toc' => array_values(array_filter($mdx->getTOC(), fn($h) => $h['level'] >= 2)),
        'social_image' => null,
        'hidden' => false,
        'standalone' => true,
    ];
}

function standalone_registry() {
    return [
        'DOCUMENTATION' => ['file' => __DIR__ . '/documentation.md', 'title' => 'Documentation', 'summary' => 'Documentation for this blog engine.', 'name' => 'documentation'],
        'SYNTAX' => ['file' => __DIR__ . '/syntax.md', 'title' => 'Syntax Guide', 'summary' => 'Every supported markdown syntax feature in one page.', 'name' => 'syntax'],
    ];
}

function standalone_entry($id) {
    $reg = standalone_registry();
    $id = strtoupper(trim((string)$id));
    return isset($reg[$id]) ? $reg[$id] + ['id' => $id] : null;
}

function standalone_by_id($id) {
    $e = standalone_entry($id);
    if (!$e || !is_file($e['file'])) return null;
    $doc = load_standalone_doc($e['file'], $e['title'], $e['summary']);
    $doc['id'] = $e['id'];
    $doc['slug'] = $e['name'];
    $doc['query'] = doc_url($e['id']);
    $doc['raw_url'] = view_md_url($e['id']);
    $doc['md_url'] = export_url($e['id'], 'md');
    $doc['html_url'] = export_url($e['id'], 'html');
    return $doc;
}

if (isset($_GET['readme']) || isset($_GET['demo'])) {
    header('Location: ' . doc_url(isset($_GET['readme']) ? 'DOCUMENTATION' : 'SYNTAX'), true, 301);
    exit;
}
if (isset($_GET['export']) && ($_GET['export'] === 'readme' || $_GET['export'] === 'demo')) {
    $_GET['id'] = $_GET['export'] === 'readme' ? 'DOCUMENTATION' : 'SYNTAX';
    $_GET['export'] = 'md';
}

function legacy_doc_id($id) {
    $map = ['README' => 'DOCUMENTATION', 'DEMO' => 'SYNTAX'];
    $up = strtoupper(trim((string)$id));
    return $map[$up] ?? $id;
}

if (isset($_GET['export']) && in_array($_GET['export'], ['html', 'md'], true)
    && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $eid = legacy_doc_id(strtoupper(trim((string)$_GET['id'])));
    if (preg_match('/^[A-Za-z0-9]{1,20}$/', $eid)) {
        $_GET['id'] = $eid;
        $isView = $_GET['export'] === 'md' && isset($_GET['view']);
        $canonical = $isView ? view_md_url($eid) : export_url($eid, $_GET['export']);
        $canonicalPath = (string)parse_url($canonical, PHP_URL_PATH);
        $reqPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($reqPath !== $canonicalPath) {
            header('Location: ' . $canonical, true, 301);
            exit;
        }
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'html') {
    if (isset($_GET['id'])) $_GET['id'] = legacy_doc_id($_GET['id']);
    if (isset($_GET['id']) && standalone_entry($_GET['id'])) {
        $docExport = standalone_by_id($_GET['id']);
        if (!$docExport) { http_response_code(404); exit('Not found'); }
        render_article_page($docExport, $CFG, true, $docExport['slug'], $docExport['query']);
        exit;
    }
    if (isset($_GET['id'])) {
        $exSlug = resolve_post_id($CFG['posts_dir'], strtoupper(trim((string)$_GET['id'])));
    } else {
        $exSlug = sanitize_slug($_GET['post'] ?? '');
    }
    $exAccH = $exSlug ? post_access($CFG, $exSlug) : null;
    if ($exAccH && $exAccH['protected']) pp_deny_export($CFG, $exSlug, 'html');
    $exPost = $exAccH ? open_post($CFG, $exSlug, 'export') : null;
    if (!$exPost) { http_response_code(404); exit('Not found'); }
    if (!empty($exPost['protected'])) pp_deny_export($CFG, $exSlug, 'html');
    render_article_page($exPost, $CFG, true, file_base($exSlug));
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'md') {
    if (isset($_GET['id'])) $_GET['id'] = legacy_doc_id($_GET['id']);
    $stEntry = isset($_GET['id']) ? standalone_entry($_GET['id']) : null;
    if ($stEntry) {
        if (!is_file($stEntry['file'])) { http_response_code(404); exit('Not found'); }
        if (isset($_GET['view'])) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: ' . content_disposition('inline', $stEntry['name'], 'md'));
        } else {
            header('Content-Type: text/markdown; charset=UTF-8');
            header('Content-Disposition: ' . content_disposition('attachment', $stEntry['name'], 'md'));
        }
        header('Content-Length: ' . filesize($stEntry['file']));
        header('Cache-Control: no-store');
        if (ob_get_level() > 0) { @ob_end_clean(); } 
        readfile($stEntry['file']);
        exit;
    }
    if (isset($_GET['id'])) {
        $exSlug = resolve_post_id($CFG['posts_dir'], strtoupper(trim((string)$_GET['id'])));
    } else {
        $exSlug = sanitize_slug($_GET['post'] ?? '');
    }
    $exAcc = $exSlug ? post_access($CFG, $exSlug) : null;
    if (!$exAcc) { http_response_code(404); exit('Not found'); }
    if ($exAcc['protected']) pp_deny_export($CFG, $exSlug, 'md');
    $exRaw = @file_get_contents($exAcc['path']);
    if ($exRaw === false) { http_response_code(404); exit('Not found'); }
    $exFlags = post_flags($exRaw);
    if ($exFlags['hidden']) { http_response_code(404); exit('Not found'); }
    if ($exFlags['protected']) {
        pp_deny_export($CFG, $exSlug, 'md');
    }
    $exRaw = strip_secret_lines($exRaw);
    $exRaw = md_redact_asset_paths($CFG, $exRaw);
    if (isset($_GET['view'])) {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: ' . content_disposition('inline', $exSlug, 'md'));
    } else {
        header('Content-Type: text/markdown; charset=UTF-8');
        header('Content-Disposition: ' . content_disposition('attachment', $exSlug, 'md'));
    }
    header('Content-Length: ' . strlen($exRaw));
    header('Cache-Control: no-store');
    if (ob_get_level() > 0) { @ob_end_clean(); } 
    echo $exRaw;
    exit;
}

$idParam = isset($_GET['id']) ? strtoupper(trim((string)$_GET['id'])) : null;
if ($idParam !== null && $idParam !== legacy_doc_id($idParam) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ' . doc_url(legacy_doc_id($idParam)), true, 301);
    exit;
}
if ($idParam && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $reqPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    
    
    
    
    $onCanonical = preg_match('#/p/([A-Za-z0-9]{1,20})$#', $reqPath, $pm) && strtoupper($pm[1]) === $idParam && $pm[1] === strtolower($pm[1]);
    if (!$onCanonical
        && (standalone_entry($idParam) || resolve_post_id($CFG['posts_dir'], $idParam) !== null)) {
        header('Location: ' . doc_url($idParam), true, 301);
        exit;
    }
}
$standalone = $idParam ? standalone_by_id($idParam) : null;
$slug = ($idParam && !$standalone) ? resolve_post_id($CFG['posts_dir'], $idParam) : null;

if ($slug) {
    $currentPost = open_post($CFG, $slug, 'page');
    if ($currentPost) $currentPost['views'] = views_record($CFG, $slug, 'page');
} else {
    $currentPost = null;
}
$notFound = $idParam && !$currentPost;
if ($standalone) {
    $currentPost = $standalone;
    $notFound = false;
}

$prevPost = $nextPost = null;
$relatedPosts = [];
if ($currentPost && empty($currentPost['standalone']) && empty($currentPost['protected'])) {
    foreach ($allPosts as $idx => $p) {
        if ($p['slug'] === $currentPost['slug']) {
            $nextPost = $allPosts[$idx - 1] ?? null;
            $prevPost = $allPosts[$idx + 1] ?? null;
            break;
        }
    }
    $curTags = array_column($currentPost['tags'] ?? [], 'slug');
    $scored = [];
    foreach ($allPosts as $p) {
        if ($p['slug'] === $currentPost['slug']) continue;
        $shared = count(array_intersect($curTags, array_column($p['tags'] ?? [], 'slug')));
        $sameMonth = $p['month_key'] === $currentPost['month_key'];
        if ($shared === 0 && !$sameMonth) continue;
        $scored[] = ['p' => $p, 's' => $shared * 10 + ($sameMonth ? 1 : 0)];
    }
    usort($scored, function ($a, $b) {
        if ($a['s'] !== $b['s']) return $b['s'] <=> $a['s'];
        return $b['p']['date'] <=> $a['p']['date'];
    });
    foreach (array_slice($scored, 0, 3) as $sc) $relatedPosts[] = $sc['p'];
}

$docLinks = null;
if ($currentPost && empty($currentPost['protected'])) {
    if (!empty($currentPost['standalone'])) {
        $docLinks = ['html' => $currentPost['html_url'], 'md' => $currentPost['md_url'], 'raw' => $currentPost['raw_url']];
    } else {
        $docLinks = ['html' => export_url($currentPost['id'], 'html'), 'md' => export_url($currentPost['id'], 'md'), 'raw' => view_md_url($currentPost['id'])];
    }
}

$pageTitle = $currentPost ? $currentPost['title'] . ' — ' . $CFG['site_name'] : $CFG['rss_title'];
$pageDesc = $currentPost ? $currentPost['summary'] : $CFG['rss_desc'];
$canonical = $currentPost
    ? (isset($currentPost['query']) ? site_origin() . $currentPost['query'] : site_origin() . post_url($currentPost))
    : base_url() . '/' . $CFG['self'];
$pageImage = null;
if (!empty($currentPost['social_image'])) {
    $image = $currentPost['social_image'];

    if (preg_match('#^https?://#i', $image)) {
        $pageImage = $image;
    } else {
        $img = asset_url(ltrim($image, '/'));
        if (preg_match('#^https?://#i', $img)) {
            $pageImage = $img;
        } else {
            $pageImage = rtrim(site_origin(), '/') . '/' . ltrim($img, '/');
        }
    }
}
$isProtected = $currentPost && !empty($currentPost['protected']);
$isHome = !$currentPost && !$notFound;
$filterLabels = ['category' => 'Category', 'difficulty' => 'Difficulty', 'event' => 'Event', 'tag' => 'Tag'];
$filters = [];
foreach ($filterLabels as $fk => $fl) {
    $filters[$fk] = ($isHome && isset($_GET[$fk]) && is_string($_GET[$fk])) ? tag_slug($_GET[$fk]) : '';
}
$activeTag = $filters['tag'];
$indexes = $isHome ? [
    'category' => ctf_facet($allPosts, 'category'),
    'difficulty' => ctf_facet($allPosts, 'difficulty'),
    'event' => ctf_facet($allPosts, 'event'),
    'tag' => tag_index($allPosts),
] : [];
$activeFilters = array_filter($filters, function ($v) { return $v !== ''; });
$listPosts = $allPosts;
if ($activeFilters) {
    $listPosts = array_values(array_filter($allPosts, function ($p) use ($activeFilters) {
        foreach ($activeFilters as $fk => $fv) {
            if (!post_matches_filter($p, $fk, $fv)) return false;
        }
        return true;
    }));
    $activeNames = [];
    foreach ($activeFilters as $fk => $fv) {
        $nm = isset($indexes[$fk][$fv]) ? $indexes[$fk][$fv]['name'] : $fv;
        $activeNames[$fk] = ($fk === 'tag' ? '#' : '') . $nm;
    }
    $pageTitle = implode(' · ', $activeNames) . ' — ' . $CFG['site_name'];
    $pageDesc = 'Posts filtered by ' . implode(', ', $activeNames) . ' on ' . $CFG['site_name'] . '.';
    $canonical = site_origin() . facet_url($activeFilters);
}
$perPage = (int)($CFG['per_page'] ?? 0);
$totalListPosts = count($listPosts);
$totalPages = $perPage > 0 ? max(1, (int)ceil($totalListPosts / $perPage)) : 1;
$curPage = $isHome && isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($curPage < 1) $curPage = 1;
if ($curPage > $totalPages) $curPage = $totalPages;
if ($perPage > 0 && $totalListPosts > $perPage) {
    $pagedPosts = array_slice($listPosts, ($curPage - 1) * $perPage, $perPage);
} else {
    $pagedPosts = $listPosts;
    $curPage = 1;
    $totalPages = 1;
}
function paged_url($page, $activeFilters) {
    $q = [];
    foreach (['category', 'difficulty', 'event', 'tag'] as $k) {
        if (!empty($activeFilters[$k])) $q[$k] = $activeFilters[$k];
    }
    if ($page > 1) $q['page'] = $page;
    return $q ? '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986) : home_url();
}

$docNav = [];
$docLabels = ['DOCUMENTATION' => 'Documentation', 'SYNTAX' => 'Syntax guide'];
$docCurrent = (!empty($currentPost['standalone']) && isset($currentPost['id'])) ? (string)$currentPost['id'] : '';
foreach (standalone_registry() as $docId => $docEntry) {
    if ($docId !== $docCurrent && is_file($docEntry['file'])) $docNav[$docId] = $docLabels[$docId] ?? $docEntry['title'];
}
$showProtectedLink = $isHome && (trim((string)($CFG['protected_list_password'] ?? '')) !== '' || trim((string)($CFG['protected_password'] ?? '')) !== '');
$showAccessLogLink = $isHome && trim((string)($CFG['access_log_password'] ?? '')) !== '';
if ($isProtected) {
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
} else {
    $etagSeed = $currentPost
        ? ($currentPost['slug'] . '|' . $currentPost['date'] . '|' . ($currentPost['updated'] ?? '') . '|' . RENDER_CACHE_VERSION)
        : ('home|' . $curPage . '|' . implode(',', $activeFilters) . '|' . ($allPosts ? max(array_column($allPosts, 'date')) : 0) . '|' . RENDER_CACHE_VERSION);
    $pageEtag = '"' . substr(hash('sha256', $etagSeed), 0, 32) . '"';
    header('ETag: ' . $pageEtag);
    header('Cache-Control: no-cache, private');
    $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    if ($ifNoneMatch !== '' && $ifNoneMatch === $pageEtag) {
        http_response_code(304);
        if (ob_get_level() > 0) { @ob_end_clean(); }
        exit;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<script nonce="<?= $CSP_NONCE ?>">
(function(){
  var root = document.documentElement;
  var theme = null, accent = null;
  try { theme = localStorage.getItem('theme'); } catch (e) {}
  try { accent = localStorage.getItem('accent'); } catch (e) {}
  if (!theme) {
    try { theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } catch (e) { theme = 'light'; }
  }
  root.setAttribute('data-theme', theme);
  root.setAttribute('data-accent', accent || '<?= htmlspecialchars($CFG['accent']) ?>');
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="'.$CFG['accent'].'"/><text x="16" y="22" font-family="Arial,Helvetica,sans-serif" font-size="17" font-weight="700" fill="#fff" text-anchor="middle">'.strtoupper(substr($CFG['site_name'],0,1)).'</text></svg>') ?>">
<title><?= htmlspecialchars($pageTitle) ?></title>
<?php if ($isProtected): ?><meta name="robots" content="noindex, nofollow, noarchive"><?php endif; ?>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta property="og:type" content="<?= $currentPost ? 'article' : 'website' ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($CFG['site_name']) ?>">
<?php if ($currentPost): ?><meta property="article:published_time" content="<?= date(DATE_ATOM, $currentPost['date']) ?>"><?php endif; ?>
<?php if ($pageImage): ?>
<meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars($pageImage) ?>">
<meta property="og:image:alt" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($pageImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($CFG['rss_title']) ?>" href="<?= htmlspecialchars(rss_url()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#F6F7FB;--surface:#FFFFFF;--surface2:#EEF0F6;--border:#E1E3EC;
  --ink:#111319;--ink2:#40434E;--ink3:#8A8DA0;
  --accent:#5B5BF0;--accent-rgb:91,91,240;--accent-soft:#EEEEFE;--accent-hover:#4444DC;
  
  --code-bg:#0D0F17;--code-ink:#E6E8F3;
}
[data-theme="dark"]{
  --bg:#0D0E14;--surface:#15171F;--surface2:#1B1E2A;--border:#282B3A;
  --ink:#EDEEF6;--ink2:#A9ABC0;--ink3:#5C5F72;
}
[data-accent="indigo"]{--accent:#5B5BF0;--accent-soft:#EEEEFE;--accent-hover:#4444DC;}
[data-theme="dark"][data-accent="indigo"]{--accent:#8181FC;--accent-soft:#1C1D3D;--accent-hover:#9494FF;}
[data-accent="blue"]{--accent:#2E7CF6;--accent-soft:#EAF2FE;--accent-hover:#1E63D6;}
[data-theme="dark"][data-accent="blue"]{--accent:#5B9AFC;--accent-soft:#152238;--accent-hover:#7CAEFF;}
[data-accent="green"]{--accent:#0E9F6E;--accent-soft:#E5F8F1;--accent-hover:#0B7F58;}
[data-theme="dark"][data-accent="green"]{--accent:#34D399;--accent-soft:#0C2A20;--accent-hover:#5EE0AF;}
[data-accent="rose"]{--accent:#E23C6D;--accent-soft:#FDEBF1;--accent-hover:#C22857;}
[data-theme="dark"][data-accent="rose"]{--accent:#FB7096;--accent-soft:#331420;--accent-hover:#FF8CAA;}
[data-accent="amber"]{--accent:#C77700;--accent-soft:#FEF2DF;--accent-hover:#A66300;}
[data-theme="dark"][data-accent="amber"]{--accent:#F0A93E;--accent-soft:#2B1E06;--accent-hover:#FFC06B;}

*{box-sizing:border-box;transition:background-color .35s ease,border-color .35s ease,color .25s ease,fill .25s ease,stroke .25s ease;}
html{scroll-behavior:smooth;font-size:15px;}
body{
  margin:0;font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink);
  line-height:1.7;-webkit-font-smoothing:antialiased;overflow-x:hidden;overflow-wrap:anywhere;
}
[hidden]{display:none!important;}
@media (prefers-reduced-motion:reduce){*{transition:none!important;}}

::view-transition-old(root),::view-transition-new(root){animation:none;mix-blend-mode:normal;}
::view-transition-old(root){z-index:1;}
::view-transition-new(root){z-index:2;}
@keyframes theme-reveal{from{clip-path:circle(0% at var(--tx,50%) var(--ty,50%));}to{clip-path:circle(150% at var(--tx,50%) var(--ty,50%));}}
.theme-animating::view-transition-new(root){animation:theme-reveal .6s cubic-bezier(.4,0,.2,1);}

.theme-icon-wrap{position:relative;width:16px;height:16px;display:inline-flex;flex-shrink:0;}
.theme-icon{position:absolute;inset:0;width:16px;height:16px;transition:opacity .35s ease,transform .5s cubic-bezier(.34,1.56,.64,1);}
.theme-icon-sun{opacity:0;transform:rotate(-90deg) scale(.4);}
.theme-icon-moon{opacity:1;transform:rotate(0) scale(1);}
[data-theme="dark"] .theme-icon-sun{opacity:1;transform:rotate(0) scale(1);}
[data-theme="dark"] .theme-icon-moon{opacity:0;transform:rotate(90deg) scale(.4);}

.menu-clock{cursor:default;color:var(--ink2);}
.menu-clock:hover{background:none;}
.menu-clock-tz{margin-left:auto;font-size:10.5px;font-weight:700;color:var(--ink3);letter-spacing:.04em;}
#menuClockTime{font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink);}
a{color:var(--accent);}
.wrap{max-width:760px;margin:0 auto;padding:0 24px 48px;}
.site-footer{text-align:center;padding:20px 24px;font-size:12px;color:var(--ink3);font-family:'Manrope','Inter',sans-serif;overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch;scrollbar-width:thin;}
.site-footer a{color:var(--accent);text-decoration:none;}
.site-footer-docs{font-weight:600;flex:0 0 auto;}
.site-footer-docs::after{content:"·";display:inline-block;margin:0 10px;color:var(--ink3);font-weight:400;}
.site-footer-docs a+a::before{content:"·";display:inline-block;margin:0 10px;color:var(--ink3);}
.progress-bar{position:fixed;top:0;left:0;height:3px;background:var(--accent);z-index:1000;width:0%;transition:width .1s linear;}

.site-head-bar{position:sticky;top:0;z-index:900;background:var(--bg);width:100%;margin-bottom:20px;box-sizing:border-box;}
.site-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:nowrap;padding:8px 24px;max-width:760px;margin:0 auto;box-sizing:border-box;width:100%;}
.site-head.is-post{max-width:1120px;}
.md-embed{position:relative;width:100%;aspect-ratio:16/9;margin:1.2em 0;border-radius:10px;overflow:hidden;background:var(--surface,#0000000d);}
.md-embed iframe{width:100%;height:100%;border:0;display:block;}
.md-embed-link{aspect-ratio:auto;height:420px;}
.md-embed-fallback{display:block;font-size:12px;padding:6px 10px;text-align:center;color:var(--muted,inherit);text-decoration:none;border-top:1px solid var(--border,#0002);}
.brand{display:flex;flex-direction:column;gap:3px;text-decoration:none;position:relative;min-width:0;overflow:hidden;}
.brand .name{
  font-family:'Manrope','Inter',sans-serif;font-weight:800;font-size:19px;letter-spacing:-.02em;
  background:linear-gradient(100deg,var(--accent) 30%,var(--ink) 55%,var(--accent) 80%);
  background-size:220% auto;background-position:0% center;-webkit-background-clip:text;background-clip:text;color:transparent;
  transition:background-position .6s ease;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;
}
.brand:hover .name{background-position:100% center;}
.head-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.icon-btn{
  width:34px;height:34px;border-radius:9px;border:1px solid var(--border);background:var(--surface);
  display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink2);transition:.15s;
}
.icon-btn:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-soft);}
.icon-btn svg{width:16px;height:16px;}
.settings-pop{position:relative;}
.settings-panel{
  position:absolute;top:42px;right:0;background:var(--surface);border:1px solid var(--border);border-radius:12px;
  padding:6px;box-shadow:0 8px 24px rgba(0,0,0,.12);display:none;z-index:50;min-width:200px;
}
.settings-panel.open{display:flex;flex-direction:column;}
.menu-divider{height:1px;background:var(--border);margin:6px 4px;}
.settings-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);font-weight:700;padding:6px 10px 6px;}
.accent-row{display:flex;gap:9px;flex-wrap:wrap;padding:0 10px 6px;}
.accent-dot{width:20px;height:20px;border-radius:50%;border:none;cursor:pointer;transition:.15s;padding:0;}
.accent-dot:hover{transform:scale(1.14);}
.accent-dot.active{box-shadow:0 0 0 2px var(--surface),0 0 0 4px currentColor;}

.filter-bar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;}
.filter-bar .filter-input{flex:1 1 200px;width:auto;}
.search-meta{min-height:18px;margin:0 0 16px;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--ink3);}
.blog-snippet{font-size:13.5px;color:var(--ink2);line-height:1.75;}
.blog-snippet mark{background:var(--accent-soft);color:var(--accent);font-weight:700;padding:0 3px;border-radius:4px;}
.filter-input{
  flex:1;min-width:0;width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);
  background:var(--surface);color:var(--ink);font-family:'Inter',sans-serif;font-size:13.5px;outline:none;transition:.15s;
  -webkit-appearance:none;-moz-appearance:none;appearance:none;
}
.filter-input::-webkit-search-cancel-button{-webkit-appearance:none;}
.filter-input:focus{border-color:var(--accent);}
.filter-select{
  padding:10px 32px 10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--surface);color:var(--ink2);
  font-size:13px;font-family:'Inter',sans-serif;cursor:pointer;outline:none;
  -webkit-appearance:none;-moz-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239198A8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M6 9l6 6 6-6'/></svg>");
  background-repeat:no-repeat;background-position:right 10px center;background-size:14px 14px;
}

.blog-list{display:flex;flex-direction:column;gap:0;}
.blog-card{padding:22px 0;border-bottom:1px solid var(--border);min-width:0;}
.blog-card:first-child{padding-top:0;}
.blog-date{font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--ink3);letter-spacing:.04em;}
.blog-title{font-family:'Manrope','Inter',sans-serif;font-size:18px;font-weight:700;color:var(--ink);text-decoration:none;display:block;margin:6px 0 8px;transition:color .15s;letter-spacing:-.01em;}
.blog-title:hover{color:var(--accent);}
.blog-desc{font-size:13.5px;color:var(--ink2);line-height:1.75;}
.blog-foot{display:flex;align-items:center;gap:14px;margin-top:12px;}
.blog-read{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:var(--accent);text-decoration:none;font-family:'Manrope','Inter',sans-serif;letter-spacing:-.01em;}
.blog-read:hover{opacity:.8;}
.read-time{font-size:11px;color:var(--ink3);font-family:'JetBrains Mono',monospace;}
.tag-row{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 14px;}
.tag-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;border:1px solid var(--border);background:var(--surface);color:var(--ink2);font-size:12px;font-weight:600;font-family:'Manrope','Inter',sans-serif;text-decoration:none;transition:.15s;line-height:1.5;}
.tag-chip span{font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--ink3);}
.tag-chip:hover{border-color:var(--accent);color:var(--accent);}
.tag-chip.active{background:var(--accent-soft);border-color:var(--accent);color:var(--accent);}
.tag-chip.small{padding:2px 9px;font-size:11px;}
.blog-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
.post-tags{display:flex;flex-wrap:wrap;gap:6px;margin:-12px 0 22px;}
.post-tags-wrap{margin:22px 0;}
.post-tags-heading{margin-bottom:8px;}
.post-tags-wrap .post-tags{margin:0;}
.post-meta-ctf{margin-top:4px;}
.tag-banner{font-size:13px;color:var(--ink2);margin:0 0 14px;}
.tag-banner a{margin-left:10px;color:var(--accent);text-decoration:none;font-weight:600;}
.facet-row{display:flex;align-items:flex-start;gap:10px;margin:0 0 10px;}
.facet-label{font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--ink3);text-transform:uppercase;letter-spacing:.06em;padding-top:7px;min-width:66px;flex-shrink:0;}
.facet-row .tag-row{margin:0;flex:1;min-width:0;}
.facet-more{flex-basis:100%;}
.facet-more summary{display:inline-flex;list-style:none;cursor:pointer;padding:4px 11px;border-radius:999px;border:1px dashed var(--border);color:var(--ink3);font-size:12px;font-weight:600;font-family:'Manrope','Inter',sans-serif;}
.facet-more summary::-webkit-details-marker{display:none;}
.facet-more[open] summary{margin-bottom:8px;}
.facet-pop-wrap{position:relative;max-width:100%;}
.facet-pop-toggle{display:inline-flex;align-items:center;gap:6px;cursor:pointer;background-image:none !important;padding-right:12px !important;}
.facet-pop-toggle svg{width:13px;height:13px;flex-shrink:0;transition:transform .15s;}
.facet-pop-toggle[aria-expanded="true"] svg{transform:rotate(180deg);}
.facet-pop{position:absolute;top:calc(100% + 6px);right:0;z-index:40;box-sizing:border-box;width:max-content;min-width:200px;max-width:min(300px,calc(100vw - 48px));max-height:60vh;overflow-y:auto;overflow-x:hidden;background:var(--surface);color:var(--ink2);border:1px solid var(--border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:6px;}
.facet-pop-group{border-bottom:1px solid var(--border);}
.facet-pop-group:last-of-type{border-bottom:none;}
.facet-pop-group>summary{display:flex;align-items:center;gap:7px;list-style:none;cursor:pointer;padding:7px 10px;user-select:none;}
.facet-pop-group>summary::-webkit-details-marker{display:none;}
.facet-pop-group>summary::marker{content:"";}
.facet-pop-caret{flex:0 0 16px;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:5px;background:var(--accent-soft);color:var(--accent);font-weight:800;font-size:12px;line-height:1;font-family:'JetBrains Mono',monospace;}
.facet-pop-caret::before{content:"+";}
.facet-pop-group[open]>summary .facet-pop-caret{background:var(--accent);color:#fff;}
.facet-pop-group[open]>summary .facet-pop-caret::before{content:"\2212";}
.facet-pop-label{font-family:'Manrope','Inter',sans-serif;font-size:10.5px;color:var(--ink3);text-transform:uppercase;letter-spacing:.06em;font-weight:700;}
.facet-pop-count{margin-left:auto;font-family:'Manrope','Inter',sans-serif;font-size:10.5px;color:var(--ink3);}
.facet-pop-body{padding:0 10px 8px;}
.facet-pop-row{display:flex;flex-wrap:nowrap;gap:6px;max-width:100%;overflow-x:auto;overflow-y:hidden;padding-bottom:8px;scrollbar-width:thin;scrollbar-color:var(--border) transparent;}
.facet-pop-row::-webkit-scrollbar{height:5px;}
.facet-pop-row::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.facet-pop-row::-webkit-scrollbar-track{background:transparent;}
.facet-pop-row .tag-chip{width:max-content;flex-shrink:0;}
.facet-pop-clear{display:block;text-align:center;text-decoration:none;font-size:12px;font-weight:600;color:var(--accent);padding:8px 10px 4px;margin-top:4px;border-top:1px solid var(--border);}
.pager{display:flex;align-items:center;justify-content:center;gap:16px;margin:28px 0 8px;font-family:'Manrope','Inter',sans-serif;}
.pager-btn{font-size:13px;font-weight:600;color:var(--ink);padding:8px 14px;border-radius:8px;border:1px solid var(--border);text-decoration:none;}
.pager-btn.disabled{pointer-events:none;opacity:.4;}
.pager-info{font-size:12px;color:var(--ink3);font-family:'JetBrains Mono',monospace;}
@media (max-width:520px){.facet-pop{max-width:min(260px,calc(100vw - 48px));}}
.ctf-badges{display:flex;flex-wrap:wrap;gap:6px;}
.blog-card .ctf-badges{margin-top:10px;}
.post-main .ctf-badges{margin:-12px 0 14px;}
.ctf-badges + .post-tags{margin-top:0;}
.ctf-badge{display:inline-flex;align-items:center;padding:2px 9px;border-radius:6px;font-size:11px;font-weight:700;font-family:'JetBrains Mono',monospace;letter-spacing:.02em;text-decoration:none;border:1px solid transparent;line-height:1.6;}
a.ctf-badge:hover{opacity:.8;}
.ctf-badge.cat{background:var(--accent-soft);color:var(--accent);}
.ctf-badge.evt{background:transparent;border-color:var(--border);color:var(--ink2);}
.ctf-badge.pts{background:var(--surface);border-color:var(--border);color:var(--ink);}
.ctf-badge.diff-easy{background:rgba(26,158,107,.13);color:#1a9e6b;}
.ctf-badge.diff-medium{background:rgba(201,138,6,.14);color:#c98a06;}
.ctf-badge.diff-hard{background:rgba(209,52,91,.13);color:#d1345b;}
.ctf-badge.diff-insane{background:rgba(130,80,223,.14);color:#8250df;}
.ctf-badge.diff-other{background:var(--surface);border-color:var(--border);color:var(--ink2);}
.no-results{display:none;text-align:center;padding:60px 0;color:var(--ink3);font-size:14px;}
.no-results.visible{display:block;}

.post-back{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--ink3);text-decoration:none;margin-bottom:22px;font-weight:600;}
.post-back:hover{color:var(--accent);}
.post-title{font-family:'Manrope','Inter',sans-serif;font-size:30px;font-weight:800;line-height:1.25;letter-spacing:-.02em;margin:6px 0 12px;color:var(--ink);}
.post-meta-scroll{overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;margin-bottom:26px;}
.post-meta-scroll::-webkit-scrollbar{height:4px;}
.post-meta{display:flex;align-items:center;gap:12px;flex-wrap:nowrap;white-space:nowrap;color:var(--ink3);font-size:12px;font-family:'JetBrains Mono',monospace;margin-bottom:0;}
.post-meta + .post-meta{margin-top:8px;}
.post-tags-bottom{margin:32px 0 0;padding-top:20px;border-top:1px solid var(--border);}
.post-grid{display:block;}
.post-main{min-width:0;max-width:100%;}
.post-side{margin-bottom:26px;}
.post-side .toc{margin:0;}
.toc-toggle-sep{display:none;}
.toc-toggle{display:none;align-items:center;gap:4px;background:none;border:none;padding:4px 2px;border-radius:6px;color:var(--accent);font:inherit;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;cursor:pointer;-webkit-tap-highlight-color:transparent;transition:opacity .15s;}
.toc-toggle:active{opacity:.65;}
.toc-toggle:focus-visible{outline:2px solid var(--accent);outline-offset:2px;}
.toc-toggle svg{width:12px;height:12px;transition:transform .15s;}
.toc-toggle.open svg{transform:rotate(180deg);}
@media(min-width:641px){
  .wrap{max-width:820px;}
  .wrap.is-post{max-width:1120px;}
  .site-head{max-width:820px;}
  .site-head.is-post{max-width:1120px;}
  .post-grid{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:56px;align-items:start;}
  .post-grid.no-side{grid-template-columns:minmax(0,1fr);}
  .post-side{position:sticky;top:88px;margin-bottom:0;}
  .post-side .toc{max-height:calc(100vh - 112px);overflow-y:auto;}
  .post-main{min-width:0;}
  .site-head.is-post{padding-right:340px;}
}
@media(min-width:1200px){
  .wrap.is-post{max-width:1240px;}
  .site-head.is-post{max-width:1240px;}
  .post-grid{grid-template-columns:minmax(0,1fr) 280px;gap:64px;}
  .site-head.is-post{padding-right:368px;}
}
.post-side .toc::-webkit-scrollbar{width:5px;}
.post-side .toc::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px;}
.post-side .toc{scrollbar-width:thin;scrollbar-color:var(--border) transparent;}

.markdown{font-size:15.5px;color:var(--ink2);min-width:0;max-width:100%;overflow-wrap:anywhere;}
.markdown iframe{max-width:100%;}
.markdown h1,.markdown h2,.markdown h3,.markdown h4,.markdown h5,.markdown h6{
  font-family:'Manrope','Inter',sans-serif;color:var(--ink);font-weight:700;letter-spacing:-.01em;
  margin:34px 0 14px;line-height:1.35;position:relative;scroll-margin-top:24px;
}
.markdown h1{font-size:26px;} .markdown h2{font-size:22px;border-bottom:1px solid var(--border);padding-bottom:8px;}
.markdown h3{font-size:18.5px;} .markdown h4{font-size:16px;} .markdown h5,.markdown h6{font-size:14px;color:var(--ink2);}
.heading-anchor{opacity:0;margin-left:8px;font-size:.8em;color:var(--ink3);text-decoration:none;transition:.15s;}
.markdown h1:hover .heading-anchor,.markdown h2:hover .heading-anchor,.markdown h3:hover .heading-anchor,
.markdown h4:hover .heading-anchor,.markdown h5:hover .heading-anchor,.markdown h6:hover .heading-anchor{opacity:1;}
.markdown p{margin:14px 0;}
.markdown a{color:var(--accent);text-decoration:none;}
.markdown a:hover{text-decoration:none;opacity:.8;}
.markdown strong{color:var(--ink);font-weight:700;}
.markdown mark{background:var(--accent-soft);color:var(--ink);padding:1px 4px;border-radius:4px;}
.markdown ul,.markdown ol{padding-left:1.4em;margin:14px 0;}
.markdown li{margin:6px 0;}
.markdown li.task-item{list-style:none;margin-left:-1.4em;padding-left:1.4em;}
.markdown li.task-item input{margin-right:8px;}
.markdown blockquote{
  background:var(--surface2);border-left:3px solid var(--accent);border-radius:0 10px 10px 0;
  margin:18px 0;padding:4px 18px;color:var(--ink2);
}
.markdown blockquote p{margin:10px 0;}
.markdown code{
  background:var(--surface2);color:var(--accent);padding:2px 6px;border-radius:5px;
  font-family:'JetBrains Mono',monospace;font-size:.87em;overflow-wrap:anywhere;
}
.code-block{margin:18px 0;border-radius:12px;overflow:hidden;background:var(--code-bg);border:1px solid var(--border);max-width:100%;min-width:0;}
.code-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.06);}
.code-lang{font-family:'JetBrains Mono',monospace;font-size:11px;color:#9198A8;text-transform:uppercase;letter-spacing:.05em;}
.code-copy{background:none;border:1px solid rgba(255,255,255,.15);color:#9198A8;font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;font-family:'Inter',sans-serif;transition:.15s;}
.code-copy:hover{color:#fff;border-color:#fff;}
.code-block pre{margin:0;padding:16px;overflow-x:auto;max-width:100%;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;tab-size:4;}
.code-block code{background:none;color:var(--code-ink);padding:0;font-size:13px;line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;}
.tok-keyword{color:#C792EA;font-weight:600;}
.tok-string{color:#C3E88D;}
.tok-number{color:#F78C6C;}
.tok-comment{color:#6C7280;font-style:italic;}
.markdown img{max-width:100%;height:auto;border-radius:10px;display:block;}
.markdown video{max-width:100%;height:auto;border-radius:10px;display:block;background:#000;}
.md-figure{margin:20px 0;text-align:center;}
.md-figure figcaption{font-size:12px;color:var(--ink3);margin-top:8px;font-style:italic;}
#pageLoader{position:fixed;inset:0;background:var(--bg);z-index:99999;display:flex;align-items:center;justify-content:center;transition:opacity .35s ease,visibility .35s ease;}
#pageLoader.hide{opacity:0;visibility:hidden;pointer-events:none;}
.pl-spinner{width:34px;height:34px;border-radius:50%;border:3px solid var(--border);border-top-color:var(--accent);animation:pl-spin .7s linear infinite;}
@keyframes pl-spin{to{transform:rotate(360deg);}}
.markdown img{background:var(--surface2);opacity:0;transform:scale(1.015);transition:opacity .4s ease,transform .4s ease;}
.markdown img:not(.img-loaded){
  min-height:140px;
  background-image:linear-gradient(100deg,var(--surface2) 20%,var(--surface) 40%,var(--accent-soft) 50%,var(--surface) 60%,var(--surface2) 80%);
  background-size:250% 100%;background-repeat:no-repeat;
  animation:img-shimmer 1.6s ease-in-out infinite;
}
@keyframes img-shimmer{0%{background-position:200% 0;}100%{background-position:-200% 0;}}
@media(prefers-reduced-motion:reduce){.markdown img:not(.img-loaded){animation:none;background-image:none;}}
.markdown img.img-loaded{opacity:1;transform:scale(1);animation:none;background-image:none;min-height:0;}
.md-figure img,.md-figure video{cursor:zoom-in;}
#lightboxOverlay{display:none;position:fixed;inset:0;background:rgba(8,8,12,.94);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;padding:80px 110px;}
#lightboxOverlay.open{display:flex;}
#lightboxInner{display:flex;align-items:center;justify-content:center;width:100%;height:100%;animation:lb-fade .18s ease;}
@keyframes lb-fade{from{opacity:0;transform:scale(.97);}to{opacity:1;transform:scale(1);}}
#lightboxOverlay img,#lightboxOverlay video{max-width:100%;max-height:100%;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.5);object-fit:contain;}
#lightboxClose{position:absolute;top:20px;right:24px;background:rgba(255,255,255,.08);border:0;color:#fff;font-size:22px;line-height:1;cursor:pointer;opacity:.9;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.15s;}
#lightboxClose:hover{opacity:1;background:rgba(255,255,255,.18);}
.lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.08);border:0;color:#fff;font-size:20px;line-height:1;cursor:pointer;opacity:.9;width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.15s;}
.lightbox-nav:hover{opacity:1;background:rgba(255,255,255,.18);transform:translateY(-50%) scale(1.06);}
#lightboxPrev{left:20px;}
#lightboxNext{right:20px;}
.lightbox-nav.hidden{display:none;}
#lightboxCounter{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.75);font-family:'Inter',sans-serif;font-size:12.5px;letter-spacing:.03em;background:rgba(255,255,255,.08);padding:5px 12px;border-radius:20px;display:none;}
#lightboxCounter.show{display:block;}
body.lb-lock{overflow:hidden;}
@media(max-width:640px){
  #lightboxOverlay{padding:32px 20px;}
  .lightbox-nav{width:42px;height:42px;font-size:17px;}
  #lightboxPrev{left:6px;}
  #lightboxNext{right:6px;}
  #lightboxClose{top:12px;right:12px;width:38px;height:38px;font-size:19px;}
}
.markdown hr{border:none;border-top:1px solid var(--border);margin:32px 0;}
.table-wrap{overflow-x:auto;max-width:100%;margin:18px 0;border:1px solid var(--border);border-radius:10px;-webkit-overflow-scrolling:touch;}
.markdown table{border-collapse:collapse;width:100%;table-layout:fixed;font-size:13.5px;}
.table-wrap table{min-width:calc(var(--cols,2) * 110px);}
.markdown th{background:var(--surface2);font-family:'Manrope','Inter',sans-serif;font-weight:700;text-align:left;vertical-align:top;padding:10px 14px;color:var(--ink);border-bottom:1px solid var(--border);overflow-wrap:anywhere;word-break:break-word;}
.markdown td{padding:10px 14px;vertical-align:top;border-bottom:1px solid var(--border);overflow-wrap:anywhere;word-break:break-word;}
.markdown tr:last-child td{border-bottom:none;}
.markdown dl{margin:16px 0;} .markdown dt{font-weight:700;color:var(--ink);} .markdown dd{margin:4px 0 12px 18px;color:var(--ink2);}
.toc{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin:20px 0;}
.toc-title{font-family:'Manrope','Inter',sans-serif;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);margin-bottom:6px;}
.toc-tree{list-style:none;padding:0;margin:0;}
.toc-tree .toc-tree{padding-left:16px;margin-top:2px;}
.toc-node{margin:1px 0;position:relative;}
.toc-branch{margin:0;}
.toc-branch-body{display:grid;grid-template-rows:0fr;transition:grid-template-rows .2s ease;}
.toc-branch[open]>.toc-branch-body{grid-template-rows:1fr;}
.toc-branch-body>.toc-tree{overflow:hidden;min-height:0;border-left:1px solid var(--border);margin-left:8px;}
.toc-branch>.toc-summary{display:flex;align-items:center;gap:7px;list-style:none;margin-inline-start:0;cursor:pointer;padding:3px 4px;border-radius:7px;transition:background-color .15s;-webkit-tap-highlight-color:transparent;}
.toc-branch>.toc-summary:hover{background:var(--accent-soft);}
.toc-branch>.toc-summary::-webkit-details-marker{display:none;}
.toc-branch>.toc-summary::marker{content:"";}
.toc-caret{flex:0 0 16px;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border-radius:5px;background:var(--accent-soft);color:var(--accent);font-weight:800;font-size:12px;line-height:1;font-family:'JetBrains Mono',monospace;user-select:none;transition:background-color .15s,color .15s;}
.toc-caret::before{content:"+";}
.toc-branch[open]>.toc-summary .toc-caret{background:var(--accent);color:#fff;}
.toc-branch[open]>.toc-summary .toc-caret::before{content:"\2212";}
.toc-link{font-size:13.5px;line-height:1.4;text-decoration:none;color:var(--ink2);display:block;flex:1;padding:4px 8px;border-radius:7px;transition:color .15s,background-color .15s;-webkit-tap-highlight-color:transparent;}
.toc-link:hover,.toc-link:focus-visible{color:var(--accent);background:var(--accent-soft);}
.toc-link:focus-visible{outline:2px solid var(--accent);outline-offset:-2px;}
.toc-link:active{background:var(--accent-soft);}
.toc-node:not(.has-children){padding-left:23px;}
.toc-lvl-2{color:var(--ink);font-weight:700;}
.toc-lvl-3,.toc-lvl-4,.toc-lvl-5,.toc-lvl-6{color:var(--ink3);font-weight:500;font-size:12.5px;}
.toc-link.active{position:relative;color:var(--accent);font-weight:700;background:var(--accent-soft);}
.toc-link.active::before{content:"";position:absolute;left:-4px;top:3px;bottom:3px;width:2.5px;border-radius:2px;background:var(--accent);}

.post-footer{display:flex;align-items:center;justify-content:space-between;margin-top:48px;padding-top:24px;border-top:1px solid var(--border);flex-wrap:wrap;gap:14px;}
.share-menu{position:relative;}
.share-dropdown{display:none;flex-direction:column;padding-left:6px;}
.share-dropdown.open{display:flex;}
.share-item{display:flex;align-items:center;gap:10px;width:100%;padding:9px 10px;border:none;border-radius:8px;background:none;color:var(--ink2);font-size:13px;font-family:inherit;font-weight:600;text-align:left;text-decoration:none;cursor:pointer;transition:.15s;}
.share-item:hover{background:var(--accent-soft);color:var(--accent);}
.share-item svg{width:15px;height:15px;flex-shrink:0;}

.post-nav{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;margin-top:24px;}
.post-nav a{min-width:0;border:1px solid var(--border);border-radius:12px;padding:14px 16px;text-decoration:none;transition:.15s;}
.post-nav a:hover{border-color:var(--accent);background:var(--accent-soft);}
.post-nav .pn-label{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;color:var(--ink3);text-transform:uppercase;letter-spacing:.05em;font-weight:700;}
.post-nav .pn-next .pn-label{justify-content:flex-end;}
.post-nav .pn-title{font-size:13.5px;color:var(--ink);font-weight:600;margin-top:4px;display:block;}
.post-nav .pn-next{text-align:right;grid-column:2;}

.related{margin-top:40px;}
.related-title{font-family:'Manrope','Inter',sans-serif;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink3);margin-bottom:14px;}
.related-grid{display:flex;flex-direction:column;gap:0;}

.notfound{text-align:center;padding:80px 0;}
.notfound h1{font-family:'Manrope','Inter',sans-serif;font-size:60px;margin:0;color:var(--accent);}
.notfound p{color:var(--ink3);margin:8px 0 24px;}
.btn-home{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;background:var(--accent);color:#fff;text-decoration:none;font-weight:600;font-size:13.5px;}

.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(10px);background:var(--ink);color:var(--bg);padding:10px 18px;border-radius:10px;font-size:12.5px;opacity:0;pointer-events:none;transition:.2s;z-index:1300;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

@media(max-width:640px){
  .wrap{padding:14px 16px 60px;}
  .post-title{font-size:24px;}
  .site-head{padding-left:16px;padding-right:16px;}
  .site-head-bar{margin-bottom:10px;}
  .post-nav{grid-template-columns:minmax(0,1fr);}
  .post-nav .pn-next{grid-column:1;text-align:left;}
  .toc-toggle{display:inline-flex;}
  .toc-toggle-sep{display:inline;}
  .post-side{display:none!important;margin:0 0 22px!important;}
  .post-side.open{display:block!important;}
  .post-side .toc{max-height:none;overflow:visible;-webkit-overflow-scrolling:auto;overscroll-behavior:auto;}
}
<?= md_extra_css() ?>
.markdown,.post-title,.post-desc,.post-meta{-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;}
.pp-shield{display:none;position:fixed;inset:0;z-index:2147483647;align-items:center;justify-content:center;padding:24px;text-align:center;background:var(--bg);color:var(--ink2);font-size:15px;font-weight:600;}
body.pp-locked .pp-shield{display:flex;}
body.pp-locked .wrap,body.pp-locked .site-head-bar{visibility:hidden;}
@media print{.markdown{display:none!important;}}
<?php if ($isProtected): ?>
.code-copy,[data-copy-code]{display:none!important;}
<?php endif; ?>
@media print{
  .site-head,.filter-bar,.post-meta ~ .post-footer,.post-footer,.related-wrap,.post-nav,.settings-panel,
  .share-dropdown,#pageLoader,.toc,#lightboxOverlay,.blog-foot,.head-actions{display:none !important;}
  body{background:#fff;color:#000;}
  .post-grid{display:block !important;}
  .markdown{color:#000;font-size:12.5pt;}
  .markdown a{color:#000;text-decoration:underline;}
  .code-block{border:1px solid #999;background:#f6f6f6 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .code-block code{color:#111 !important;}
  .code-block pre{white-space:pre-wrap;word-break:break-word;}
  .markdown img{max-width:100%;page-break-inside:avoid;}
  .post-title{font-size:22pt;}
  a[href]:after{content:"";}
}
</style>
</head>
<body>
<div id="pageLoader"><div class="pl-spinner"></div></div>
<div class="progress-bar" id="progressBar"></div>

<div class="site-head-bar">
  <header class="site-head<?= $currentPost ? ' is-post' : '' ?>">
    <a class="brand" href="<?= htmlspecialchars(home_url()) ?>">
      <span class="name"><?= htmlspecialchars($CFG['site_name']) ?></span>
    </a>
    <div class="head-actions">
<?php if ($docLinks && !$isProtected): ?>
      <a class="icon-btn" href="<?= htmlspecialchars($docLinks['raw']) ?>" target="_blank" rel="noopener" title="View raw markdown" aria-label="View raw markdown">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>
      </a>
<?php endif; ?>
      <div class="settings-pop">
        <button class="icon-btn" id="menuBtn" title="Menu" aria-label="Menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
        </button>
        <div class="settings-panel" id="menuPanel">
          <div class="share-item menu-clock" id="menuClock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span id="menuClockTime">--:--:--</span>
            <span class="menu-clock-tz">IST</span>
          </div>
          <div class="menu-divider"></div>
          <button class="share-item" id="themeBtn">
            <span class="theme-icon-wrap">
              <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 4V2M12 22v-2M4.9 4.9 3.5 3.5M20.5 20.5l-1.4-1.4M4 12H2M22 12h-2M4.9 19.1l-1.4 1.4M20.5 3.5l-1.4 1.4"/></svg>
              <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9Z"/></svg>
            </span>
            <span id="themeBtnLabel">Toggle theme</span>
          </button>
          <div class="settings-label">Accent</div>
          <div class="accent-row" id="accentRow"></div>
          <div class="menu-divider"></div>
          <a class="share-item" href="<?= htmlspecialchars(rss_url()) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
            RSS feed
          </a>
<?php if ($showProtectedLink): ?>
          <a class="share-item" href="<?= htmlspecialchars(protected_list_url()) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            Protected posts
          </a>
<?php endif; ?>
<?php if ($showAccessLogLink): ?>
          <a class="share-item" href="<?= htmlspecialchars(access_log_url()) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6M9 9h1"/></svg>
            Access log
          </a>
<?php endif; ?>
<?php if (!$isProtected): ?>
          <div class="share-menu">
            <button class="share-item" id="shareToggle">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.5l6.8-3.9M8.6 13.5l6.8 3.9"/></svg>
              Share
            </button>
            <div class="share-dropdown" id="shareDropdown">
              <button class="share-item" id="shareX"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23.3 22H16.9l-5-6.5-5.7 6.5H2.9l8.1-9.3L2 2h6.6l4.5 5.9L18.9 2zm-1.1 18h1.7L7 3.9H5.2l12.6 16.1z"/></svg> Share on X</button>
              <button class="share-item" id="shareLinkedin"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.58c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.15 1.45-2.15 2.94v5.68H9.34V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.55V9h3.57v11.45z"/></svg> Share on LinkedIn</button>
              <button class="share-item" id="shareWhatsapp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.5.1-.2 0-.4 0-.5C10 9 9.4 7.6 9.2 7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3z"/><path d="M12 2C6.5 2 2 6.5 2 12c0 1.9.5 3.7 1.5 5.3L2 22l4.8-1.5c1.5.8 3.3 1.3 5.2 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.7 0-3.3-.5-4.6-1.3l-.3-.2-3.4 1 1-3.3-.2-.3C3.5 14.6 3 13.3 3 12c0-5 4-9 9-9s9 4 9 9-4 9-9 9z"/></svg> Share on WhatsApp</button>
              <button class="share-item" id="shareCopy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.5-1.5"/></svg> Copy link</button>
              <?php if ($docLinks): ?>
              <button class="share-item" id="downloadHtmlBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 16 4-4-4-4M6 8l-4 4 4 4M14.5 4l-5 16"/></svg> Download HTML</button>
              <a class="share-item" href="<?= htmlspecialchars($docLinks['md']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 13 2 2 4-4"/></svg> Export as .md</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="menu-divider" id="backToTopDivider" style="display:none;"></div>
          <button class="share-item" id="backToTop" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
            Back to top
          </button>
        </div>
      </div>
    </div>
  </header>
</div>

<div class="wrap<?= $currentPost ? ' is-post' : '' ?>">

<?php if ($notFound): ?>
  <div class="notfound">
    <h1>404</h1>
    <p>That post doesn't exist (or was moved).</p>
    <a class="btn-home" href="<?= htmlspecialchars(home_url()) ?>"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back to all posts</a>
  </div>

<?php elseif ($currentPost): ?>
  <a class="post-back" href="<?= htmlspecialchars(home_url()) ?>">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    All posts
  </a>
  <div class="post-grid<?= empty($currentPost['toc']) ? ' no-side' : '' ?>">
    <div class="post-main">
      <h1 class="post-title"><?= htmlspecialchars($currentPost['title']) ?></h1>
      <div class="post-meta-scroll">
      <div class="post-meta">
<?php $wasUpdated = !empty($currentPost['updated']) && $currentPost['updated'] > $currentPost['date']; ?>
<?php if ($wasUpdated): ?>
        <span>Updated <?= date('d M Y', $currentPost['updated']) ?></span>
<?php else: ?>
        <span><?= date('d M Y', $currentPost['date']) ?></span>
<?php endif; ?>
        <span>·</span>
        <span><?= $currentPost['read_mins'] ?> min read</span>
<?php if (isset($currentPost['views'])): ?>
        <span>·</span>
        <span><?= htmlspecialchars(views_format($currentPost['views'])) ?> views</span>
<?php endif; ?>
<?php if ($isProtected): ?>
        <span>·</span>
        <span>Protected</span>
<?php endif; ?>
<?php if (!empty($currentPost['toc'])): ?>
        <span class="toc-toggle-sep">·</span>
        <button class="toc-toggle" id="tocToggle" type="button" aria-expanded="false" aria-controls="postSideToc">
          Contents
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
<?php endif; ?>
      </div>
<?php if (!$isProtected): $c = $currentPost['ctf'] ?? []; if ($c): ?>
      <div class="post-meta post-meta-ctf">
<?php $first = true; ?>
<?php if ($c['category'] !== ''): ?>
        <?= $first ? '' : '<span>·</span>' ?><span>Category: <?= htmlspecialchars($c['category']) ?></span>
<?php $first = false; endif; if ($c['difficulty'] !== ''): ?>
        <?= $first ? '' : '<span>·</span>' ?><span>Difficulty: <?= htmlspecialchars(ucfirst($c['difficulty'])) ?></span>
<?php $first = false; endif; if ($c['points'] !== null): ?>
        <?= $first ? '' : '<span>·</span>' ?><span>Points: <?= (int)$c['points'] ?></span>
<?php $first = false; endif; if ($c['event'] !== ''): ?>
        <?= $first ? '' : '<span>·</span>' ?><span>Event: <?= htmlspecialchars($c['event']) ?></span>
<?php $first = false; endif; ?>

      </div>
<?php endif; endif; ?>
      </div>
      <article class="markdown"><?= $currentPost['html'] ?></article>
<?php if (!$isProtected && !empty($currentPost['tags'])): ?>
      <div class="post-tags-wrap">
      <div class="facet-pop-label post-tags-heading">Tags</div>
      <div class="post-tags post-tags-bottom">
<?php foreach ($currentPost['tags'] as $tg): ?>
        <a class="tag-chip small" href="<?= htmlspecialchars(tag_url($tg['slug'])) ?>">#<?= htmlspecialchars($tg['name']) ?></a>
<?php endforeach; ?>
      </div>
      </div>
<?php endif; ?>
    </div>
    <?php if (!empty($currentPost['toc'])): ?>
    <aside class="post-side" id="postSideToc">
      <nav class="toc">
        <div class="toc-title">On this page</div>
        <?= toc_render_nodes(toc_build_tree($currentPost['toc'], 2), 0, false) ?>
      </nav>
    </aside>
    <?php endif; ?>
  </div>

  <div class="post-footer">
    <a class="post-back" href="<?= htmlspecialchars(home_url()) ?>" style="margin:0;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to posts
    </a>
  </div>

  <?php if ($prevPost || $nextPost): ?>
  <div class="post-nav">
    <?php if ($prevPost): ?><a class="pn-prev" href="<?= post_url($prevPost) ?>"><span class="pn-label"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg> Previous</span><span class="pn-title"><?= htmlspecialchars($prevPost['title']) ?></span></a><?php else: ?><span></span><?php endif; ?>
    <?php if ($nextPost): ?><a class="pn-next" href="<?= post_url($nextPost) ?>"><span class="pn-label">Next <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 18l6-6-6-6"/></svg></span><span class="pn-title"><?= htmlspecialchars($nextPost['title']) ?></span></a><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($relatedPosts)): ?>
  <div class="related">
    <div class="related-title">Related posts</div>
    <div class="related-grid">
      <?php foreach ($relatedPosts as $rp): ?>
      <div class="blog-card">
        <span class="blog-date"><?= date('d M Y', $rp['date']) ?></span>
        <a class="blog-title" href="<?= post_url($rp) ?>"><?= htmlspecialchars($rp['title']) ?></a>
        <div class="blog-desc"><?= htmlspecialchars($rp['summary']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

<?php else: ?>
  <?php

  ?>
  <?php if ($activeFilters): ?>
  <div class="tag-banner">Filtered by <strong><?= htmlspecialchars(implode(' · ', $activeNames)) ?></strong><a href="<?= htmlspecialchars(home_url()) ?>">Clear filters &times;</a></div>
  <?php endif; ?>
  <?php
  $anyFacets = false;
  foreach ($filterLabels as $fk => $fl) { if (!empty($indexes[$fk])) { $anyFacets = true; break; } }
  $activeFacetCount = count($activeFilters);
  $monthOptions = [];
  foreach ($listPosts as $p) { $monthOptions[$p['month_key']] = $p['month_label']; }
  krsort($monthOptions);
  ?>
  <div class="filter-bar">
    <input class="filter-input" id="blogSearch" type="search" placeholder="Search titles and post text…" aria-label="Search posts" autocomplete="off" spellcheck="false" maxlength="120" enterkeyhint="search">
    <?php if ($anyFacets): ?>
    <div class="facet-pop-wrap">
      <button type="button" class="filter-select facet-pop-toggle" id="facetPopToggle" aria-expanded="false" aria-controls="facetPop">
        Filter<?= $activeFacetCount ? ' (' . (int)$activeFacetCount . ')' : '' ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="facet-pop" id="facetPop" hidden>
        <?php if ($monthOptions): ?>
        <select id="blogDateFilter" hidden aria-hidden="true" tabindex="-1">
          <option value="">All dates</option>
          <?php foreach ($monthOptions as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
        <details class="facet-pop-group">
          <summary>
            <span class="facet-pop-caret" aria-hidden="true"></span>
            <span class="facet-pop-label">Date</span>
            <span class="facet-pop-count"><?= count($monthOptions) ?></span>
          </summary>
          <div class="facet-pop-body">
          <div class="facet-pop-row">
            <button type="button" class="tag-chip small date-opt active" data-value="">All dates</button>
            <?php foreach ($monthOptions as $key => $label): ?>
            <button type="button" class="tag-chip small date-opt" data-value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></button>
            <?php endforeach; ?>
          </div>
          </div>
        </details>
        <?php endif; ?>
        <?php foreach ($filterLabels as $fk => $fl): ?>
        <?php if (empty($indexes[$fk])) continue; ?>
        <?php $facetOpen = ($filters[$fk] ?? '') !== ''; ?>
        <details class="facet-pop-group"<?= $facetOpen ? ' open' : '' ?>>
          <summary>
            <span class="facet-pop-caret" aria-hidden="true"></span>
            <span class="facet-pop-label"><?= htmlspecialchars($fl) ?></span>
            <span class="facet-pop-count"><?= count($indexes[$fk]) ?></span>
          </summary>
          <div class="facet-pop-body">
          <div class="facet-pop-row">
            <?php foreach (array_values($indexes[$fk]) as $it): $on = $it['slug'] === ($filters[$fk] ?? ''); $params = $filters; $params[$fk] = $on ? '' : $it['slug']; ?>
            <a class="tag-chip small<?= $on ? ' active' : '' ?>" href="<?= htmlspecialchars(facet_url($params)) ?>"><?= $fk === 'tag' ? '#' : '' ?><?= htmlspecialchars($it['name']) ?> <span>(<?= (int)$it['count'] ?>)</span></a>
            <?php endforeach; ?>
          </div>
          </div>
        </details>
        <?php endforeach; ?>
        <?php if ($activeFacetCount): ?>
        <a class="facet-pop-clear" href="<?= htmlspecialchars($CFG['self']) ?>">Clear all filters</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="search-meta" id="searchMeta" role="status" aria-live="polite"></div>
  <div class="blog-list" id="blogList">
    <?php foreach ($pagedPosts as $i => $p): ?>
    <div class="blog-card" data-title="<?= htmlspecialchars(mb_strtolower($p['title'])) ?>" data-month="<?= htmlspecialchars($p['month_key']) ?>" data-pid="<?= htmlspecialchars($p['id']) ?>" data-order="<?= (int)$i ?>">
      <span class="blog-date"><?= date('d M Y', $p['date']) ?></span>
      <a class="blog-title" href="<?= post_url($p) ?>"><?= htmlspecialchars($p['title']) ?></a>
      <div class="blog-desc"><?= htmlspecialchars($p['summary']) ?></div>
      <div class="blog-snippet" hidden></div>
      <div class="blog-foot">
        <a class="blog-read" href="<?= post_url($p) ?>">Read full post <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg></a>
        <span class="read-time"><?= $p['read_mins'] ?> min read</span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="no-results" id="noResults">No posts match your filter.</div>
  <?php if (empty($allPosts)): ?>
    <div class="notfound" style="padding-top:0;"><p>No posts yet — drop <code>.md</code> files (or Joplin exports) into <code>/posts</code>.</p></div>
  <?php elseif (empty($listPosts)): ?>
    <div class="notfound" style="padding-top:0;"><p>No posts match <strong><?= htmlspecialchars(implode(' · ', $activeNames)) ?></strong>. <a href="<?= htmlspecialchars($CFG['self']) ?>">Show all posts</a></p></div>
  <?php elseif ($totalPages > 1): ?>
  <nav class="pager" id="blogPager" aria-label="Pagination">
    <a class="pager-btn<?= $curPage <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars(paged_url($curPage - 1, $activeFilters)) ?>" aria-disabled="<?= $curPage <= 1 ? 'true' : 'false' ?>">&larr; Prev</a>
    <span class="pager-info">Page <?= (int)$curPage ?> of <?= (int)$totalPages ?></span>
    <a class="pager-btn<?= $curPage >= $totalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars(paged_url($curPage + 1, $activeFilters)) ?>" aria-disabled="<?= $curPage >= $totalPages ? 'true' : 'false' ?>">Next &rarr;</a>
  </nav>
  <?php endif; ?>
<?php endif; ?>

</div>
<div class="toast" id="toast"></div>
<div class="pp-shield" id="ppShield" role="alert">Developer tools are not allowed on this site. Close them to continue.</div>

<div id="lightboxOverlay"><button id="lightboxPrev" class="lightbox-nav" aria-label="Previous">&#10094;</button><div id="lightboxInner"></div><button id="lightboxNext" class="lightbox-nav" aria-label="Next">&#10095;</button><button id="lightboxClose" aria-label="Close">&times;</button><div id="lightboxCounter"></div></div>

<script nonce="<?= $CSP_NONCE ?>">
<?= console_hello_js(pp_accent_hex($CFG['accent'] ?? 'indigo'), doc_url('DOCUMENTATION')) ?>
(function(){
  var root = document.documentElement;
  var theme = root.getAttribute('data-theme') || 'light';
  var accent = root.getAttribute('data-accent') || '<?= htmlspecialchars($CFG['accent']) ?>';

  var themeBtnLabel = document.getElementById('themeBtnLabel');
  function syncThemeIcon(){
    if(themeBtnLabel) themeBtnLabel.textContent = (theme === 'dark') ? 'Light mode' : 'Dark mode';
  }
  syncThemeIcon();

  var pageLoader = document.getElementById('pageLoader');
  function hidePageLoader(){
    if (pageLoader) pageLoader.classList.add('hide');
  }
  if (document.readyState === 'complete') {
    hidePageLoader();
  } else {
    window.addEventListener('load', hidePageLoader);
  }
  setTimeout(hidePageLoader, 4000);

  function wireImageFade(img){
    if (img.complete && img.naturalWidth > 0) {
      img.classList.add('img-loaded');
    } else {
      img.addEventListener('load', function(){ img.classList.add('img-loaded'); });
      img.addEventListener('error', function(){ img.classList.add('img-loaded'); });
    }
  }
  document.querySelectorAll('.markdown img').forEach(wireImageFade);

  var tocLinks = Array.prototype.slice.call(document.querySelectorAll('.post-side .toc .toc-link'));
  if (tocLinks.length) {
    var tocMap = {};
    var headingEls = [];
    tocLinks.forEach(function(link){
      var id = decodeURIComponent(link.getAttribute('href').slice(1));
      var el = document.getElementById(id);
      if (el) { tocMap[id] = link; headingEls.push(el); }
    });
    var allBranches = Array.prototype.slice.call(document.querySelectorAll('.post-side .toc .toc-branch'));
    function setActive(id){
      tocLinks.forEach(function(l){ l.classList.remove('active'); });
      var path = [];
      if (tocMap[id]) {
        tocMap[id].classList.add('active');
        var d = tocMap[id].closest('.toc-branch');
        while (d) { path.push(d); d = d.parentElement ? d.parentElement.closest('.toc-branch') : null; }
      }
      allBranches.forEach(function(b){ b.open = path.indexOf(b) !== -1; });
      if (tocMap[id] && window.matchMedia('(min-width:641px)').matches) {
        tocMap[id].scrollIntoView({ block: 'nearest' });
      }
    }
    if (headingEls.length) {
      var activeId = headingEls[0].id;
      var observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting) activeId = entry.target.id;
        });
        setActive(activeId);
      }, { rootMargin: '-96px 0px -70% 0px', threshold: 0 });
      headingEls.forEach(function(el){ observer.observe(el); });
      setActive(activeId);
    }
  }

  var themeBtn = document.getElementById('themeBtn');
  if(themeBtn) themeBtn.addEventListener('click', function(e){
    var next = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    var rect = themeBtn.getBoundingClientRect();
    var x = rect.left + rect.width / 2;
    var y = rect.top + rect.height / 2;
    root.style.setProperty('--tx', x + 'px');
    root.style.setProperty('--ty', y + 'px');

    function applyTheme(){
      theme = next;
      root.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
      syncThemeIcon();
      buildAccentDots();
    }

    if(document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
      root.classList.add('theme-animating');
      var vt = document.startViewTransition(applyTheme);
      vt.finished.finally(function(){ root.classList.remove('theme-animating'); });
    } else {
      applyTheme();
    }
  });

  var clockEl = document.getElementById('menuClockTime');
  function tickClock(){
    if(!clockEl) return;
    var now = new Date();
    clockEl.textContent = now.toLocaleTimeString('en-IN', { timeZone: 'Asia/Kolkata', hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
  tickClock();
  setInterval(tickClock, 1000);

  var accents = ['indigo','blue','green','rose','amber'];
  var accentHex = {
    indigo:{light:'#5B5BF0',dark:'#8181FC'}, blue:{light:'#2E7CF6',dark:'#5B9AFC'},
    green:{light:'#0E9F6E',dark:'#34D399'}, rose:{light:'#E23C6D',dark:'#FB7096'},
    amber:{light:'#C77700',dark:'#F0A93E'}
  };
  function buildAccentDots(){
    var row = document.getElementById('accentRow');
    if(!row) return;
    row.innerHTML = '';
    accents.forEach(function(key){
      var dot = document.createElement('button');
      dot.className = 'accent-dot' + (key === accent ? ' active' : '');
      var hex = accentHex[key][theme === 'dark' ? 'dark' : 'light'];
      dot.style.background = hex;
      dot.style.color = hex;
      dot.setAttribute('aria-label', key + ' accent');
      dot.addEventListener('click', function(){
        accent = key;
        root.setAttribute('data-accent', accent);
        localStorage.setItem('accent', accent);
        buildAccentDots();
      });
      row.appendChild(dot);
    });
  }
  buildAccentDots();

  var tocToggle = document.getElementById('tocToggle');
  var postSideToc = document.getElementById('postSideToc');
  if(postSideToc){
    var tocMeta = document.querySelector('.post-meta');
    var tocSlot = document.createElement('span');
    tocSlot.style.display = 'none';
    postSideToc.parentNode.insertBefore(tocSlot, postSideToc);
    var tocMq = window.matchMedia('(max-width:640px)');
    function placeTocPanel(){
      if(tocMq.matches && tocMeta){
        tocMeta.parentNode.insertBefore(postSideToc, tocMeta.nextSibling);
      } else {
        tocSlot.parentNode.insertBefore(postSideToc, tocSlot.nextSibling);
      }
    }
    placeTocPanel();
    if(tocMq.addEventListener) tocMq.addEventListener('change', placeTocPanel); else tocMq.addListener(placeTocPanel);
  }
  if(tocToggle && postSideToc){
    tocToggle.addEventListener('click', function(e){
      e.stopPropagation();
      var open = postSideToc.classList.toggle('open');
      tocToggle.classList.toggle('open', open);
      tocToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  var menuBtn = document.getElementById('menuBtn');
  var menuPanel = document.getElementById('menuPanel');
  if(menuBtn) menuBtn.addEventListener('click', function(e){
    e.stopPropagation();
    menuPanel.classList.toggle('open');
  });
  document.addEventListener('click', function(e){
    if(menuPanel && !menuPanel.contains(e.target) && e.target !== menuBtn) menuPanel.classList.remove('open');
  });

  var facetToggle = document.getElementById('facetPopToggle');
  var facetPop = document.getElementById('facetPop');
  if(facetToggle && facetPop){
    facetToggle.addEventListener('click', function(e){
      e.stopPropagation();
      var open = facetPop.hasAttribute('hidden') ? false : true;
      if(open){ facetPop.setAttribute('hidden',''); facetToggle.setAttribute('aria-expanded','false'); }
      else { facetPop.removeAttribute('hidden'); facetToggle.setAttribute('aria-expanded','true'); }
    });
    document.addEventListener('click', function(e){
      if(!facetPop.contains(e.target) && e.target !== facetToggle) { facetPop.setAttribute('hidden',''); facetToggle.setAttribute('aria-expanded','false'); }
    });
  }

  var dateHiddenSelect = document.getElementById('blogDateFilter');
  if(facetPop && dateHiddenSelect){
    Array.prototype.slice.call(facetPop.querySelectorAll('.date-opt')).forEach(function(opt){
      opt.addEventListener('click', function(){
        var val = opt.getAttribute('data-value') || '';
        dateHiddenSelect.value = val;
        dateHiddenSelect.dispatchEvent(new Event('change'));
        Array.prototype.slice.call(facetPop.querySelectorAll('.date-opt')).forEach(function(o){ o.classList.toggle('active', o === opt); });
      });
    });
  }

  var searchEl = document.getElementById('blogSearch');
  var dateEl = document.getElementById('blogDateFilter');
  var listEl = document.getElementById('blogList');
  var cards = listEl ? Array.prototype.slice.call(listEl.querySelectorAll('.blog-card')) : [];
  var noRes = document.getElementById('noResults');
  var metaEl = document.getElementById('searchMeta');
  var serverQ = '';
  var serverMap = null;
  var loading = false;
  var searchTimer = null;
  var searchCtl = null;

  function normQ(v){ return String(v || '').replace(/\s+/g, ' ').trim().toLowerCase(); }
  function termsOf(q){ return q ? q.split(' ').filter(Boolean).slice(0, 8) : []; }
  function escRe(s){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

  function setSnippet(el, text, terms){
    el.textContent = '';
    if(!text || !terms.length){ el.hidden = true; return; }
    var re = new RegExp('(' + terms.slice().sort(function(a, b){ return b.length - a.length; }).map(escRe).join('|') + ')', 'gi');
    var last = 0, m;
    while((m = re.exec(text)) !== null){
      if(m[0].length === 0){ re.lastIndex++; continue; }
      if(m.index > last) el.appendChild(document.createTextNode(text.slice(last, m.index)));
      var mk = document.createElement('mark');
      mk.textContent = m[0];
      el.appendChild(mk);
      last = m.index + m[0].length;
    }
    if(last < text.length) el.appendChild(document.createTextNode(text.slice(last)));
    el.hidden = false;
  }

  function applyFilter(){
    if(!listEl) return;
    var q = normQ(searchEl && searchEl.value);
    var month = dateEl ? dateEl.value : '';
    var terms = termsOf(q);
    var useServer = q.length >= 2 && serverMap !== null && serverQ === q;
    var pending = q.length >= 2 && loading && !useServer;
    var visible = 0;

    cards.forEach(function(c){
      var hit = null;
      var ok = true;
      if(q){
        if(useServer){
          hit = serverMap[c.dataset.pid] || null;
          ok = !!hit;
        } else {
          ok = terms.every(function(t){ return c.dataset.title.indexOf(t) !== -1; });
        }
      }
      if(ok && month && c.dataset.month !== month) ok = false;
      c.style.display = ok ? '' : 'none';
      var sn = c.querySelector('.blog-snippet');
      var desc = c.querySelector('.blog-desc');
      if(sn){
        if(ok && hit && hit.snippet){
          setSnippet(sn, hit.snippet, terms);
          if(desc) desc.hidden = true;
        } else {
          sn.hidden = true;
          if(desc) desc.hidden = false;
        }
      }
      if(ok) visible++;
    });

    cards.slice().sort(function(a, b){
      if(useServer){
        var sa = serverMap[a.dataset.pid], sb = serverMap[b.dataset.pid];
        var d = (sb ? sb.score : -1) - (sa ? sa.score : -1);
        if(d) return d;
      }
      return (+a.dataset.order) - (+b.dataset.order);
    }).forEach(function(c){ listEl.appendChild(c); });

    if(noRes){
      noRes.textContent = q ? 'No posts match \u201c' + q + '\u201d.' : 'No posts match your filter.';
      noRes.classList.toggle('visible', visible === 0 && !pending);
    }
    if(metaEl){
      if(!q) metaEl.textContent = '';
      else if(pending) metaEl.textContent = 'Searching\u2026';
      else metaEl.textContent = visible + (visible === 1 ? ' result' : ' results') + ' for \u201c' + q + '\u201d';
    }
    var pagerEl = document.getElementById('blogPager');
    if(pagerEl) pagerEl.style.display = (q || month) ? 'none' : '';
  }

  function runFullTextSearch(q){
    if(searchCtl){ searchCtl.abort(); searchCtl = null; }
    if(q.length < 2){ serverMap = null; serverQ = ''; loading = false; applyFilter(); return; }
    var opts = { headers: { 'Accept': 'application/json' } };
    if(window.AbortController){
      searchCtl = new AbortController();
      opts.signal = searchCtl.signal;
    }
    loading = true;
    applyFilter();
    fetch('?action=search&q=' + encodeURIComponent(q), opts)
      .then(function(r){
        if(!r.ok) throw new Error('search failed');
        return r.json();
      })
      .then(function(res){
        if(!res || !res.ok || normQ(searchEl.value) !== q) return;
        var map = Object.create(null);
        res.results.forEach(function(r){ map[r.id] = r; });
        serverMap = map;
        serverQ = q;
        loading = false;
        applyFilter();
      })
      .catch(function(err){
        if(err && err.name === 'AbortError') return;
        loading = false;
        applyFilter();
      });
  }

  if(searchEl){
    searchEl.addEventListener('input', function(){
      var q = normQ(searchEl.value);
      clearTimeout(searchTimer);
      if(q.length < 2){
        if(searchCtl){ searchCtl.abort(); searchCtl = null; }
        serverMap = null; serverQ = ''; loading = false;
        applyFilter();
        return;
      }
      loading = true;
      applyFilter();
      searchTimer = setTimeout(function(){ runFullTextSearch(q); }, 250);
    });
    searchEl.addEventListener('keydown', function(e){
      if(e.key === 'Escape' && searchEl.value){
        searchEl.value = '';
        searchEl.dispatchEvent(new Event('input'));
      }
    });
  }
  if(dateEl) dateEl.addEventListener('change', applyFilter);

  function toast(msg){
    var t = document.getElementById('toast');
    t.textContent = msg; t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 1800);
  }
  var url = window.location.href;
  var titleEl = document.querySelector('.post-title');
  var title = titleEl ? titleEl.textContent : document.title;
  var bX = document.getElementById('shareX');
  var bL = document.getElementById('shareLinkedin');
  var bW = document.getElementById('shareWhatsapp');
  var bC = document.getElementById('shareCopy');
  if(bX) bX.addEventListener('click', function(){ window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url), '_blank', 'noopener,noreferrer'); });
  if(bL) bL.addEventListener('click', function(){ window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url), '_blank', 'noopener,noreferrer'); });
  if(bW) bW.addEventListener('click', function(){ window.open('https://wa.me/?text=' + encodeURIComponent(title + ' ' + url), '_blank', 'noopener,noreferrer'); });
  if(bC) bC.addEventListener('click', function(){
    navigator.clipboard.writeText(url).then(function(){ toast('Link copied'); });
  });
  function downloadPostHtml(){
    var theme = document.documentElement.getAttribute('data-theme') || 'light';
    var accent = document.documentElement.getAttribute('data-accent') || 'indigo';
    var base = <?= json_encode($docLinks['html'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if(!base) return;
    var sep = base.indexOf('?') === -1 ? '?' : '&';
    window.location.href = base + sep + 'theme=' + encodeURIComponent(theme) + '&accent=' + encodeURIComponent(accent);
  }
  var bPdf = document.getElementById('downloadHtmlBtn');
  if(bPdf) bPdf.addEventListener('click', downloadPostHtml);

  var shareToggle = document.getElementById('shareToggle');
  var shareDropdown = document.getElementById('shareDropdown');
  if(shareToggle && shareDropdown){
    shareToggle.addEventListener('click', function(e){
      e.stopPropagation();
      shareDropdown.classList.toggle('open');
    });
    shareDropdown.addEventListener('click', function(){
      shareDropdown.classList.remove('open');
      if(menuPanel) menuPanel.classList.remove('open');
    });
    document.addEventListener('click', function(e){
      if(!shareDropdown.contains(e.target) && e.target !== shareToggle) shareDropdown.classList.remove('open');
    });
  }

  var backToTop = document.getElementById('backToTop');
  var backToTopDivider = document.getElementById('backToTopDivider');
  if(backToTop){
    backToTop.addEventListener('click', function(){
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if(menuPanel) menuPanel.classList.remove('open');
    });
    window.addEventListener('scroll', function(){
      var show = window.scrollY > 400;
      backToTop.style.display = show ? 'flex' : 'none';
      if(backToTopDivider) backToTopDivider.style.display = show ? 'block' : 'none';
    });
  }

  var article = document.querySelector('.markdown');
  var bar = document.getElementById('progressBar');
  if(article && bar){
    window.addEventListener('scroll', function(){
      var rect = article.getBoundingClientRect();
      var total = rect.height - window.innerHeight;
      var scrolled = Math.min(Math.max(-rect.top, 0), total);
      var pct = total > 0 ? (scrolled / total) * 100 : 0;
      bar.style.width = pct + '%';
    }, { passive: true });
  }
})();

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-copy-code]');
  if (!btn) return;
  var block = btn.closest('.code-block');
  var codeEl = block ? block.querySelector('code') : null;
  if (!codeEl) return;
  var code = codeEl.innerText;
  navigator.clipboard.writeText(code).then(function(){
    var old = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(function(){ btn.textContent = old; }, 1200);
  });
});

(function(){
  var lbOverlay = document.getElementById('lightboxOverlay');
  var lbInner = document.getElementById('lightboxInner');
  var lbClose = document.getElementById('lightboxClose');
  var lbPrev = document.getElementById('lightboxPrev');
  var lbNext = document.getElementById('lightboxNext');
  var lbCounter = document.getElementById('lightboxCounter');
  var lbGallery = [];
  var lbIndex = -1;
  var lbScrollY = 0;

  function renderLightbox(el){
    lbInner.innerHTML = '';
    var clone;
    if (el.tagName === 'IMG') {
      clone = document.createElement('img');
      clone.src = el.src; clone.alt = el.alt || '';
    } else if (el.tagName === 'VIDEO') {
      clone = document.createElement('video');
      clone.src = el.currentSrc || el.src;
      clone.controls = true; clone.autoplay = true; clone.playsInline = true;
    } else {
      return;
    }
    lbInner.innerHTML = '';
    lbInner.appendChild(clone);
    lbInner.style.animation = 'none';
    void lbInner.offsetWidth;
    lbInner.style.animation = '';
  }

  function updateNav(){
    var showNav = lbGallery.length > 1;
    lbPrev.classList.toggle('hidden', !showNav);
    lbNext.classList.toggle('hidden', !showNav);
    if (showNav) {
      lbCounter.textContent = (lbIndex + 1) + ' / ' + lbGallery.length;
      lbCounter.classList.add('show');
    } else {
      lbCounter.classList.remove('show');
    }
  }

  function lockScroll(){
    lbScrollY = window.scrollY || window.pageYOffset || 0;
    document.body.classList.add('lb-lock');
    document.body.style.position = 'fixed';
    document.body.style.top = (-lbScrollY) + 'px';
    document.body.style.width = '100%';
  }
  function unlockScroll(){
    document.body.classList.remove('lb-lock');
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    window.scrollTo(0, lbScrollY);
  }

  function openLightbox(el){
    if (el.tagName === 'IMG') {
      lbGallery = Array.prototype.slice.call(document.querySelectorAll('.markdown img'));
      lbIndex = lbGallery.indexOf(el);
    } else {
      lbGallery = [el];
      lbIndex = 0;
    }
    renderLightbox(lbGallery[lbIndex]);
    updateNav();
    lockScroll();
    lbOverlay.classList.add('open');
  }
  function closeLightbox(){
    lbOverlay.classList.remove('open');
    lbInner.innerHTML = '';
    lbGallery = []; lbIndex = -1;
    unlockScroll();
  }
  function navLightbox(dir){
    if (lbGallery.length < 2) return;
    lbIndex = (lbIndex + dir + lbGallery.length) % lbGallery.length;
    renderLightbox(lbGallery[lbIndex]);
    updateNav();
  }
  document.addEventListener('click', function(e){
    var el = e.target.closest('.md-figure img, .md-figure video');
    if (el) { e.preventDefault(); openLightbox(el); }
  });
  lbClose.addEventListener('click', closeLightbox);
  lbPrev.addEventListener('click', function(){ navLightbox(-1); });
  lbNext.addEventListener('click', function(){ navLightbox(1); });
  lbOverlay.addEventListener('click', function(e){ if (e.target === lbOverlay) closeLightbox(); });
  document.addEventListener('keydown', function(e){
    if (!lbOverlay.classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowLeft') navLightbox(-1);
    else if (e.key === 'ArrowRight') navLightbox(1);
  });
})();

(function(){
  document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
  document.documentElement.style.cssText += ';-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;';
  ['copy','cut','paste','dragstart'].forEach(function(ev){
    document.addEventListener(ev, function(e){ e.preventDefault(); });
  });
  document.addEventListener('keydown', function(e){
    var k = String(e.key || '').toLowerCase();
    var code = e.code || '';
    var mod = e.ctrlKey || e.metaKey;
    var block = false;
    if (e.key === 'F12' || code === 'F12') block = true;
    else if (mod && e.shiftKey && ['i','j','c','k','s'].indexOf(k) !== -1) block = true;
    else if (mod && !e.shiftKey && ['u','p','s','a'].indexOf(k) !== -1) block = true;
    else if (e.metaKey && e.altKey && ['KeyI','KeyJ','KeyC','KeyU'].indexOf(code) !== -1) block = true;
    if (block) { e.preventDefault(); e.stopPropagation(); }
  }, true);
  var locked = false;
  function probe(){
    var t0 = performance.now();
    debugger;
    var open = (performance.now() - t0) > 150;
    if (open !== locked) {
      locked = open;
      document.body.classList.toggle('pp-locked', open);
    }
  }
  probe();
  setInterval(probe, 500);
})();
</script>
<footer class="site-footer"><?php if ($docNav): ?><span class="site-footer-docs"><?php foreach ($docNav as $docId => $docLabel): ?><a href="<?= htmlspecialchars(doc_url($docId)) ?>"><?= htmlspecialchars($docLabel) ?></a><?php endforeach; ?></span><?php endif; ?>With <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block;vertical-align:-1px;color:#e0245e"><path d="M12 21s-7.5-4.6-10.2-9.1C.4 9.5 1 6.4 3.6 5A5.4 5.4 0 0 1 12 7.1 5.4 5.4 0 0 1 20.4 5c2.6 1.4 3.2 4.5 1.8 6.9C19.5 16.4 12 21 12 21z"/></svg> by <a href="https://psvineet.github.io" target="_blank" rel="noopener noreferrer">Vineet Pratap Singh</a></footer>
</body>
</html>