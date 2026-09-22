---
title: CipherScribe — Complete Documentation
summary: Full reference for CipherScribe (index.php) — setup, routing, front matter, protected posts, search, exports, the render cache internals, and security notes.
created: 2026-09-22
---

CipherScribe (`index.php`) is a single-file, database-free blog engine built for cybersecurity research, CTF writeups, technical notes and tech articles. Drop markdown files into a folder, refresh, and you get a themed blog with search, RSS, sitemap, dark mode, a table of contents, and per-post exports — no build step, no database, no admin panel.

> [!TIP]
> On any public post, the `<>` button in the header opens the raw markdown source so you can compare it with the rendered page.

[[TOC]]

## 1. Quick start

1. Upload the contents of this package to a PHP 7.4+ host (Apache with `.htaccess` support recommended; the `dom`, `mbstring` and `libxml` extensions must be enabled).
2. Make sure PHP can write to the site folder and to `posts/`. The engine writes `.htaccess-meta.json` and `posts/.auth/` on first use.
3. Add a file such as `posts/my-first-writeup.md`.
4. Open the site in the browser. Your post appears on the homepage.

Renaming the PHP file works too — every generated `.htaccess` and internal link reads the filename at runtime, so nothing is hardcoded to `index.php`.

## 2. Directory structure

```
your-site/
├── index.php                 # the engine, the only PHP file you need
├── .htaccess                 # protective rules, regenerated automatically when needed
├── .htaccess-meta.json       # install-state marker, created on first run (blocked from web access)
├── documentation.md          # this file, served live at /p/documentation
├── syntax.md                 # syntax guide, served live at /p/syntax
├── posts/                    # every .md file here becomes a post
│   ├── .htaccess             # denies direct web access to the folder
│   ├── .cache/                # rendered-HTML cache (see section 13) — blocked from the web
│   │   ├── .htaccess
│   │   ├── <hash>.cache       # one file per cached, rendered post
│   │   └── idx_<hash>.idx     # one small index file per source post, tracking its current cache key
│   ├── .auth/                 # rate-limit counters + access.log — blocked from the web
│   ├── my-first-writeup.md
│   ├── ctf/
│   │   └── web-challenge.md  # sub-folders work; slug becomes ctf/web-challenge
│   └── _draft.md             # leading _ or . means ignored entirely
└── assets/                   # images, screenshots and videos used by posts
    ├── .htaccess             # stops uploaded files from running as code
    └── screenshot.png
```

- `posts/`, `assets/` and `posts/.cache/` are created automatically as needed.
- Only `posts/` is scanned for posts. `documentation.md` and `syntax.md` in the project root never appear in the post list, sitemap, RSS or search — they're served only via their reserved `/p/documentation` and `/p/syntax` URLs (IDs `DOCUMENTATION` and `SYNTAX`).
- Any file or folder inside `posts/` whose name starts with `.` or `_` is skipped completely.
- Markdown is only ever served through `index.php`. The root `.htaccess` blocks `.md` and `.json` files, and `posts/.htaccess` blocks the whole `posts/` folder.

## 3. How it works

- Each `.md` file becomes one post. The filename without `.md` is the **slug**.
- On every request `index.php` scans `posts/`, and for each post either serves a cached render or parses the markdown fresh (see section 13 for exactly when).
- Edit, add or delete a file and refresh — the cache invalidates itself automatically because it is keyed to the file's own modified time.
- The engine suits a personal blog of tens to a few hundred posts.

### URL routing

| URL | What it shows |
|---|---|
| `index.php` | Post list (homepage) with search, month filter, and a Filter popover for tags/category/event/difficulty |
| `/p/xxxxxxxx` (or `?id=XXXXXXXX`) | A single post, looked up by its ID |
| `?tag=slug`, `?category=slug`, `?event=slug`, `?difficulty=slug` | Homepage filtered to matching posts; combine any of them |
| `?page=N` | Homepage, page N of the post list |
| `/sitemap.xml` (or `?sitemap=1`) | XML sitemap of post URLs only, with `lastmod` from each post's `updated` date |
| `/robots.txt` (or `?robots=1`) | Plain-text robots file pointing at the sitemap and disallowing `/posts/` |
| `/rss.xml` (or `?rss=1`) | RSS 2.0 feed of all listed posts |
| `/access-log` (or `?access_log=1`) | Password-gated recent access-log viewer. 404 unless `access_log_password` is set |
| `/protected` (or `?protected_list=1`) | Password-gated list of protected posts. 404 unless `protected_list_password` or `protected_password` is set |
| `/img/<token>` | An opaque image/video URL — never a real filename or path |
| `?action=search&q=term` | JSON search results, best match first |
| `/export/xxxxxxxx/html` (or `?export=html&id=XXXXXXXX`) | Standalone themed `.html` download, local images embedded |
| `/export/xxxxxxxx/md` (or `?export=md&id=XXXXXXXX`) | Downloads the raw markdown source |
| `/md/xxxxxxxx` (or `?export=md&id=XXXXXXXX&view=1`) | Shows the raw markdown as plain text |
| `/p/documentation` | This document, rendered exactly like a post |
| `/p/syntax` | The syntax guide, rendered exactly like a post |

`/p/<id>`, `/img/<token>`, `/sitemap.xml`, `/robots.txt`, `/rss.xml`, `/protected`, `/access-log`, `/export/<id>/html`, `/export/<id>/md` and `/md/<id>` are the canonical forms. Their `?id=`/`?sitemap=1`/`?export=…&id=…`/etc. query-string equivalents redirect (HTTP 301) to the pretty form. Pretty-form IDs are always lowercase in the URL — an uppercase `/p/ID` link still resolves and 301-redirects down to the lowercase canonical form, so old links keep working. `?action=search`, `?tag=`/`?category=`/`?event=`/`?difficulty=` and `?page=` still have no pretty equivalent and stay as query parameters, as does the `?export=…&post=slug` fallback (no ID to build a pretty URL from). `DOCUMENTATION` and `SYNTAX` are reserved IDs — longer than a post's 8-character hash, so their routes accept IDs up to 20 characters instead of 8. The older `README`/`DEMO` IDs (and the `?readme`/`?demo`/`?export=readme`/`?export=demo` shortcuts) still work and 301-redirect to the new ones, so old bookmarks and shared links keep working.

### Post IDs

```
ID = uppercase(base36(crc32b("pid:" + slug)))[0:8]
```

Recalculated from the filename on every request — never stored. Renaming a file changes its ID, and old `?id=` links to it break with no redirect. The ID is computed and matched case-insensitively; only the URL's display form is forced to lowercase.

## 4. Configuration

Settings live in the `$CFG` array near the top of `index.php`.

| Key | Default | Purpose |
|---|---|---|
| `site_name` | `CipherScribe` | Site title in the header, tab title and footer |
| `rss_title` / `rss_desc` | — | RSS channel title/description and homepage meta description |
| `posts_dir` | `posts` | Folder scanned for posts |
| `assets_dir` | `assets` | Folder for images and video |
| `home_url` | `./` | Where the site name in the header links to |
| `accent` | `indigo` | `indigo`, `blue`, `green`, `rose` or `amber` |
| `base_url` | empty | Fixed site address used for sitemap, RSS, canonical links and exports |
| `cdn_url` | empty | Base URL of an image CDN; local `assets/…` references are rewritten to it. Re-save posts once after enabling so cached entries pick it up |
| `version` | `2.0.0` | Bump to force `.htaccess` regeneration |
| `per_page` | `8` | Posts per homepage page |
| `access_log_password` | empty | Password for `/access-log`. Empty disables it (404) |
| `protected_ttl` | `1800` | Seconds an unlock stays valid (30 min), from the moment of unlocking |
| `protected_max_fails` | `5` | Wrong passwords allowed per IP+post inside the window |
| `protected_ip_max` | `20` | Wrong passwords allowed per IP across all posts inside the window |
| `protected_post_max` | `60` | Wrong passwords allowed per post from all visitors combined |
| `protected_window` | `900` | Lockout window in seconds (15 min) |
| `protected_password` | set your own | Fallback/second password for protected posts. Also unlocks `/protected` if no dedicated list password is set |
| `protected_list_password` | set your own | Unlocks the `/protected` list view. Falls back to `protected_password` when empty |
| `trusted_ip_header` | empty | e.g. `HTTP_CF_CONNECTING_IP` behind a proxy/CDN. Empty means `REMOTE_ADDR` |

> [!NOTE]
> `.htaccess` is only rewritten when `version` or the script filename changes.

## 5. Front matter

```markdown
---
title: My Post Title
summary: One or two sentences shown on the post-list card.
cover: cover-image.jpg
created: 2026-01-15
---
```

| Key | Purpose | Fallback when omitted |
|---|---|---|
| `title` | Post title | First `# Heading` in the body, else the slug |
| `summary` | Card text, RSS description, social meta | First ~180 characters of plain text |
| `cover` | Link-preview image only | First image in the post, else none |
| `created` / `date` | Publish date | The file's own timestamp |
| `updated` | Shown next to the date; sitemap `lastmod` | The file's mtime, if >1 day after `created` |
| `publish` | Future date — post stays hidden until then | Visible immediately |
| `hidden` | `true` makes the post unreachable everywhere | `false` |
| `protected` | `yes` locks the post behind a password | `false` |
| `password` | Plain text or `password_hash()` output | `protected_password` |
| `tags` | Comma-separated, e.g. `tags: web, crypto` | none |
| `event`, `category`, `difficulty`, `points` | CTF metadata | none |

`unlisted`/`exclude` alias `hidden`; `tag` aliases `tags`; `password_hash` aliases `password`.

### Cover image

Used only for Open Graph/Twitter card previews — never shown on the page, list, RSS or exports. Accepts a full URL or a path under `assets/`; anything with a disallowed scheme, `..`, spaces or quotes is ignored. With no valid `cover`, the first image in the post is used instead.

### Hiding versus ignoring

- `hidden: true` (also `yes`/`on`/`1`) is server-enforced from the file itself: blocked from the list, sitemap, RSS, search, `/p/<id>`, and every export route.
- A file (or folder) named starting with `.` or `_` is invisible everywhere, direct-link included.
- `hidden` always wins over `protected`.

## 6. Password-protected posts

```markdown
---
title: Private writeup
protected: yes
password: a-long-unique-passphrase
---
```

- `protected` accepts `yes`/`true`/`1`/`on`/`y`; any other non-empty value except an explicit "no" also protects it, so a typo never leaves a post public.
- A post with `password:` but no `protected:` line is still treated as protected.
- `password` can be plain text or a `password_hash()` hash.

### What a visitor sees

A protected post never appears in the list, search, RSS, sitemap, related posts, or prev/next. Its `/p/<id>` shows a password page revealing no content. Exports and the `<>` raw view always 404 for protected posts, unlocked or not.

### How it is protected

- Unlock lives in the session and expires exactly `protected_ttl` seconds after unlocking, regardless of activity.
- CSRF-protected form; session ID regenerates on success; constant-time / `password_verify()` comparison.
- Layered rate limiting: per IP+post, per IP across posts, and per post across all visitors, each with its own cap and a shared lockout window (HTTP 429 + `Retry-After`). IPv6 is bucketed per /64.
- Counters live in `posts/.auth/`, itself blocked from the web. If unwritable, attempts are refused (503) rather than left unlimited.
- Exports/raw-view are blocked at three separate points (route, shared guard, final serializer) so a race can't slip a response through.

### Protected list & shared-password posts

`/protected` lists title/summary/date for every protected post, gated by `protected_list_password` (falling back to `protected_password` if no dedicated list password is set). The header menu's **Protected posts** link appears whenever either password is configured — even before any post has actually been marked `protected`. Unlocking the list also opens every protected post that has **no password of its own**, since those rely entirely on the shared fallback. A post with its own `password`/`password_hash` always shows its own separate gate.

### Encrypted at rest

A protected post with its own plain-text `password:` is sealed on disk with AES-256-GCM the first time it's accessed (key via HKDF-SHA256 from the password + a per-post random salt stored in front matter). After sealing, front matter gains `encrypted: true` / `enc_salt: …` and the body becomes an opaque base64 blob — a leaked `.md` backup no longer discloses plaintext. Changing a sealed post's password without also re-encrypting the body will make it fail to open (safely — not garbled output). Sealed posts are never written to the render cache, encrypted or otherwise.

### Access log viewer

`/access-log` (behind `access_log_password`) shows the last 500 lines of `posts/.auth/access.log` — unlock attempts, wrong passwords, rate-limit hits, protected-post views. Read-only; never clears or edits the log.

## 7. Markdown syntax

See `syntax.md` (`/p/syntax`) for every feature with real, worked examples. Summary:

- **Headings:** ATX `#`–`######` with auto anchors, `{#custom-id}`, Setext `===`/`---`, `[[TOC]]`, and `---`/`***`/`___` rules.
- **Inline:** `**bold**`, `*italic*`, `***both***`, `~~strike~~`, `==highlight==`, `^superscript^`, `~subscript~`, `` `code` ``, `\*escaped\*`, `:emoji:` shortcodes, two trailing spaces for a forced break.
- **Links/footnotes:** inline, titled, autolinks, reference-style (explicit or implicit label), `[^1]` footnotes. Only `http`, `https`, `mailto`, `tel`, `ftp` schemes are kept — anything else (e.g. `javascript:`) becomes a harmless `#` link.
- **Images/embeds:** `![alt](path "title" =WxH)`, bare filenames on their own line, video-extension auto-detection, bare YouTube/Vimeo URLs, any other bare URL as a sandboxed iframe.
- **Lists:** `-`/`*`/`+` unordered (interchangeable within one list), ordered lists that keep a custom start number, nesting, task lists (`- [ ]` / `- [x]` / `- [X]`), list items containing code blocks.
- **Definition lists:** `Term` then `: definition`.
- **Blockquotes & callouts:** plain `>` quotes, and `> [!NOTE|TIP|IMPORTANT|WARNING|CAUTION]` coloured callouts.
- **Code:** triple-backtick or triple-tilde fences, language hint for highlighting (php, javascript, python, bash, sql, java, and more per the changelog), Copy button on every block.
- **Tables:** pipe tables with `:---`, `:---:`, `---:` alignment; wide tables scroll inside their own box.
- **Raw HTML:** DOM-sanitized allowlist (`strong`, `em`, `a`, `details`, `summary`, `div.custom-box`, …) — disallowed tags/attributes stripped.

## 8. Reading experience

Built in, no syntax required: collapsible table of contents with scroll tracking, reading-time estimate, reading-progress bar, live search with a month filter, long-content wrapping, image/video lightbox, previous/next + related posts (shared tags first, same month as fallback), light/dark theme with five accents, a tags/CTF meta row, a homepage Filter popover, pagination, a header menu (theme, accent, RSS, Protected posts, Access log, Share, Back to top), a footer with **Documentation**/**Syntax guide** links (labels come from `standalone_registry()`, always pointing at `/p/documentation` and `/p/syntax` and only shown when the underlying file exists — no broken links if one is ever removed), a Share menu, raw-source `<>` view, print-friendly styling, a console greeting, and Open Graph/Twitter social previews.

### Site-wide copy protection

Every page — not just protected posts — disables right-click, text selection, and copy/cut/paste/drag, and blocks the common devtools shortcuts (F12, Ctrl/Cmd+Shift+I/J/C/K, Ctrl/Cmd+U/P/S/A, and the Safari/macOS equivalents). A timing-based probe detects an open devtools panel and shows a blocking overlay until it's closed. None of this touches the Copy button on code blocks — it writes to the clipboard programmatically, independent of the browser's native copy event, so it keeps working everywhere. Protected posts additionally hide the code-block Copy button entirely, since nothing on that page should be extractable at all.

### Console greeting

Opening the browser console prints:

```
Hi! This blog is by Vineet Pratap Singh — visit /p/documentation for the full documentation.
```

The `/p/documentation` link is generated server-side from `doc_url('DOCUMENTATION')` — the same routing function that builds every other link on the site — so it always resolves to the real documentation page regardless of the folder the site is installed in or which page you're currently viewing. It's printed on load and again after `console.clear()` (a page can't intercept the console's own clear button or its keyboard shortcut, so those still wipe it until the next reload).

### Search

Searches title, summary and full body (code and table cells included) for every listed post. All words must match, in any order; exact-phrase matches rank higher; title > summary > body-match-count; ties go to the newer post. At least 2 characters, up to 8 words / 120 characters, up to 50 results, case-insensitive. Never returns hidden or `.`/`_`-prefixed posts. Endpoint: `?action=search&q=term` → `{ok, terms, results[{id, title, snippet, score}]}`.

### Long text and wide content

URLs, hashes, base64 blobs and minified code wrap instead of overflowing — in paragraphs, headings, lists, quotes, inline code, code blocks, table cells and HTML exports. Code keeps its indentation; wide tables scroll sideways in their own box.

## 9. Exports

| Export | How | Result |
|---|---|---|
| HTML | Share menu → Download HTML | Self-contained themed file, current theme/accent, working Copy buttons, local images <2 MB embedded so it opens fully offline |
| Markdown | Share menu → Export as .md, or `<>` | Raw source, downloaded or shown as plain text |
| RSS | `/rss.xml` | Feed of listed posts |
| Sitemap | `/sitemap.xml` | XML sitemap of listed posts |

Download names always come from the post's own filename, ignoring any parent folder. Protected posts cannot be exported or viewed raw under any circumstances.

## 10. Troubleshooting

See the quick answers below; most issues trace back to file permissions, `.htaccess` not being applied, or a post's own front matter.

- **A post link stopped working** → the file was renamed (IDs come from the filename).
- **Images don't show** → check the path is relative to `index.php`; filenames are case-sensitive.
- **`.md` files are downloadable directly** → `.htaccess` wasn't applied; on Nginx add `location ~ \.(md|json)$ { deny all; }` explicitly.
- **Search misses a post** → it may be `hidden` or `.`/`_`-prefixed; type ≥2 characters; every typed word must be present.
- **A post isn't listed** → check for a leading `.`/`_`, `hidden: true`, or `protected: yes` (protected posts are never listed).
- **Export/raw view 404s on a protected post** → intended; remove `protected` to allow it.
- **A protected post re-asks for the password** → the 30-minute `protected_ttl` window ended, or a relevant password/config value changed.
- **"Password check is temporarily unavailable"** → `posts/.auth/` isn't writable.
- **"Too many attempts"** → rate limit hit; wait, or clear files in `posts/.auth/`.
- **A `---` line turned a paragraph into a heading** → that's a Setext H2; leave a blank line before a literal horizontal rule.

## 11. Security notes

- All markdown output is escaped by default; raw HTML passes through a DOM-based sanitizer allowlist, not string replacement.
- Every link/image URL (markdown or raw HTML) is scheme-checked (`http`, `https`, `mailto`, `tel`, `ftp` only), with whitespace/control-character smuggling (`java&#9;script:`) stripped before the check.
- A per-request nonce (`script-src 'self' 'nonce-…'`) replaces a blanket `'unsafe-inline'` in the Content-Security-Policy. The nonce is `base64_encode(random_bytes(16))`, so it only ever contains `[A-Za-z0-9+/=]` — it cannot break out of the header value it's interpolated into.
- The render cache (`posts/.cache/`) is locked down like `posts/.auth/` (`0700` directory permission plus a denial `.htaccess` that's re-created if missing — see section 13) and only ever stores rendered HTML for already-public, non-protected posts.
- Protected-post bodies are sealed at rest with AES-256-GCM once they have their own plaintext password.
- `base_url`/`site_origin` are validated or explicitly fixed; all XML output is escaped.
- Share links open with `noopener,noreferrer`; the legacy `X-XSS-Protection` header is explicitly disabled (`0`) since it can itself introduce bugs in old browsers.
- Right-click, text selection, copy/cut/paste/drag and common devtools shortcuts are disabled site-wide, with a timing-based probe that shows a blocking overlay while devtools is open (see section 8). This deters casual copying but isn't a hard security boundary — view-source, browser extensions and the network tab remain unaffected, so it should never be relied on to keep genuinely sensitive content private; use `protected` posts for that.
- Post-file lookup resolves the real path and rejects anything outside `posts_dir`, blocking traversal.
- `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` are sent on every response; session cookies are HTTP-only, same-site strict.
- The root `.htaccess` denies dotfiles and `.md/.json/.log/.bak/.old/.orig/.sql/.ini/.inc`, disables directory listings. `assets/.htaccess` stops uploaded files executing as code. `posts/.htaccess` denies the whole folder. Nginx ignores `.htaccess`, so add the equivalent `location` blocks by hand there.
- `hidden` posts are refused by every route; `protected` posts are excluded from every listing/feed and cannot be exported.
- Download filenames are derived from the post filename only, with path/control characters stripped, so a name can't inject headers or paths.
- Search snippets and highlights are inserted as text/DOM nodes, never raw markup, so post content can't inject through search results.
- `cover` values are validated: only `http`, `https`, and safe relative paths are accepted.

## 12. Going live checklist

1. Set `protected_password` and `protected_list_password` to long, unique values — ideally `password_hash()` output.
2. Set `base_url` to your real address.
3. Set `trusted_ip_header` if you're behind a proxy/CDN.
4. Confirm PHP can write to the site folder and `posts/` (for `.htaccess-meta.json` and `posts/.auth/`).
5. On Nginx, add the `.md`/`.json` and `/posts/` deny rules by hand.
6. Request `posts/` and a post's `.md` directly and confirm 403/404.
7. Open a protected post's `/export/<id>/md` (or `?export=md&id=…`) and confirm 404.
8. Delete stray `_draft` files; back up `posts/` and `assets/` regularly.

## 13. Render cache internals

The render cache (`posts/.cache/`) stores the fully rendered HTML for every non-protected post, keyed to that post's file modification time, so an unchanged post is never re-parsed on every request.

**Cache key.** `md5(path . '|' . mtime . '|' . RENDER_CACHE_VERSION) . '.cache'` — editing a post changes its `mtime`, which changes the key, so the old rendered entry is automatically orphaned rather than served stale. `RENDER_CACHE_VERSION` is bumped whenever the on-disk cache format itself changes, invalidating every existing entry at once (safe — they just regenerate on next read).

**O(1) invalidation, not a full scan.** Each cached post also gets one tiny index file, `idx_<md5(path)>.idx`, holding just its current cache key. Writing a fresh render looks up that one index file, deletes the single old entry it names if the key changed, and overwrites the index — instead of reading every `.cache` file in the directory to find whichever one used to belong to this post. This is what makes cache turnover cheap on a folder with many posts.

**Storage format.** Each `.cache` file holds a 2-byte format tag (`S1`) followed by a plain PHP `serialize()` blob (not JSON) of the post's rendered data. `serialize()`/`unserialize()` avoids repeatedly UTF-8-validating and re-escaping a large HTML string on every read/write the way `json_encode()`/`json_decode()` does, and reads use `unserialize(..., ['allowed_classes' => false])` so a corrupted or tampered cache file can never be used to instantiate arbitrary PHP objects. The format tag means a future format change can be introduced without silently misreading old files — a mismatched tag is just treated as a cache miss.
No `_src`/source-path field is stored inside the blob at all anymore — the index file already tracks that relationship — so a read never has to load, then discard, that field.

**One stat, not two, on a hit.** Loading a post first takes the file's `mtime` (needed for the cache key regardless), then tries the cache *before* reading the file's contents. Only unprotected posts are ever cached, so a hit means the full source markdown is never read from disk at all for that request — no `file_get_contents()`, no re-parsing. A miss (new post, edited post, or a protected post, which is never cached) falls through to reading and rendering the file as before.

**Directory permissions.** The cache directory is created `0700` (owner-only), matching `posts/.auth/`, rather than the more permissive `0755` used for genuinely web-facing asset folders. Its lockdown `.htaccess` (`Require all denied` / `Deny from all`) is checked and re-created on *every* cache read or write if it's ever missing, instead of only being written once at directory-creation time — so restoring the directory from a backup that dropped dotfiles doesn't silently leave it open.

**30-day sweep is a backstop, not the primary mechanism.** `render_cache_gc()` still deletes any cache or index file older than 30 days, but this is deliberately based on file age, not content staleness — normal edits are already invalidated instantly and precisely by the mtime-keyed cache key above. The sweep only exists to clean up files that could be orphaned outside that normal write path (for example, a post deleted outright, where no future write ever comes along to trigger the index-based cleanup for its old entry).

**ETag/304 on page responses.** Ordinary (non-protected) page views — the homepage, a filtered/paginated list, or a single post — now send an `ETag` derived from the post's slug/date/updated-time (or, for a list view, the current page, active filters, and the newest post's date) plus the cache format version. If the browser's `If-None-Match` matches, the server answers `304 Not Modified` and exits before building any of the page HTML, instead of re-emitting the full page on every request. Protected pages are untouched by this and keep their existing `no-store` behaviour, since their content is visitor-specific and must never be cached by a shared cache or browser.

> [!NOTE]
> None of this changes what a post looks like or how front matter behaves — section 13 is purely about how fast and how safely the *same* rendered output is served on repeat requests.