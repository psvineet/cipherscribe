---
title: Markdown Syntax Guide
summary: Every markdown feature this renderer supports, shown with real working examples.
created: 2026-09-22
---

[[TOC]]

# Writing a Post

## Headings

# CTF Writeup: Pwning the Vault
## Recon
### Enumerating open ports {#recon-ports}
#### nmap output
##### Notes on filtered ports
###### Appendix

Setext-style headings also work:

Web Exploitation
================

SQL Injection
-------------

## Text formatting

This challenge was **surprisingly hard** and __took the whole weekend__.

The flag format is *lowercase, no spaces* — also written _lowercase, no spaces_.

***This is the most important line in the whole writeup.***

~~The bug was in the login form~~ — turns out it was in the password reset flow.

==Note to self: rotate this API key before publishing.==

The time complexity is O(n^2^) in the worst case.

The exploit sends H~2~O... no wait, it sends raw SQL: `' OR 1=1--`

Escaped characters so they render literally: \*not italic\*, \_not italic\_, \`not code\`

Quick reactions: :fire: this bug, :rocket: ship it, :tada: got the flag, :warning: careful with this payload,
:white_check_mark: patched, :bug: still broken, :lock: needs auth, :key: leaked credential, :shield: WAF blocked it,
:zap: fast exploit, :100: fully solved, :star: favorite challenge, :bulb: the insight that cracked it, :eyes: watch this

A line ending in two spaces forces a break:  
without starting a new paragraph.

## Links

Inline link: [OWASP Top 10](https://owasp.org/www-project-top-ten/)

Inline link with a tooltip: [nmap docs](https://nmap.org/book/man.html "nmap manual page")

Autolink, rendered as-is: <https://cve.mitre.org>

Reference-style link: [check the writeup index][writeups]

Reference-style link, implicit label (matches the link text itself): [the writeup index]

A claim worth a citation.[^1]

A second, independently numbered claim.[^cve-source]

[writeups]: https://example.com/writeups "All writeups"
[the writeup index]: https://example.com/writeups

[^1]: Verified against the vendor's own advisory, published the same week.
[^cve-source]: Footnotes can use named identifiers instead of plain numbers.

Dangerous schemes are neutralized rather than executed — this renders as a harmless `#` link, not a live one:
[click me](javascript:alert(1))

## Images

![Burp Suite intercepting the login request](assets/Screenshot_20260910_174416.png)

Same image, with a caption and an explicit size:

![Burp Suite intercepting the login request](assets/Screenshot_20260910_174416.png "Intercepted POST to /login" =600x340)

A video, auto-detected by its extension and given a player instead of a broken image tag:

![Proof-of-concept exploit running end to end](https://example.com/poc-demo.mp4)

A bare asset filename on its own line also resolves to an embed:

Screenshot_20260910_174416.png

## Auto-embeds

A bare YouTube URL on its own line becomes an inline video embed:

https://youtu.be/dQw4w9WgXcQ

A bare Vimeo URL does the same:

https://vimeo.com/347119375

Any other bare URL becomes a sandboxed preview iframe:

https://nmap.org

## Lists

Tools used, in the order I reached for them:

- Burp Suite for intercepting requests
- nmap for the initial port scan
  - `-sV` for service versions
  - `-p-` for a full port sweep
- sqlmap once I confirmed the injection point

Steps to reproduce:

1. Log in as a low-privilege user
2. Intercept the `PATCH /api/profile` request
3. Change `role` from `"user"` to `"admin"` before forwarding
   1. Confirm the response no longer strips the field
   2. Refresh the session to pick up the new claim
4. Access `/admin` directly

Mixed bullet markers all belong to the same list (`*`, `+`, `-` are interchangeable):

* Found via recon
+ Found via source review
- Found via fuzzing

Switching from bullets to numbers starts a fresh list:

- Bug #1: broken auth
- Bug #2: IDOR on invoices
1. Fix auth first
2. Fix IDOR second

An ordered list can pick up a start number and continue counting from there:

3. Third finding
4. Fourth finding

Nesting changes the bullet style per level automatically:

- Network layer
  - Transport layer
    - Application layer

Progress tracker for a multi-stage exploit chain:

- [x] Enumerate subdomains (lowercase x also counts)
- [X] Find the exposed `.git` directory (uppercase X too)
- [ ] Extract the leaked API key from history

A list item can contain its own code block:

- Reproduce locally:

  ```bash
  git clone https://example.com/vulnerable-app.git
  cd vulnerable-app && docker compose up
  ```

## Definition list

CSRF
: Cross-Site Request Forgery — tricking a logged-in user's browser into firing an unwanted request.

IDOR
: Insecure Direct Object Reference — accessing another user's data by just changing an ID in the URL.

## Blockquotes

> "The only truly secure system is one that is powered off, cast in a block
> of concrete." — Gene Spafford

A blockquote can hold other blocks, not just text:

> Findings from this section:
>
> - Missing rate limiting on `/login`
> - Session token doesn't rotate after privilege change
>
> ```
> 429 Too Many Requests  (never actually returned)
> ```

## Callout boxes

> [!NOTE]
> The staging environment uses different credentials than production — don't reuse them.

> [!TIP]
> Run `nmap` with `-Pn` first if the host seems to be dropping ping probes.

> [!WARNING]
> This payload will drop the `sessions` table. Only run it against a disposable environment.

> [!IMPORTANT]
> Rotate the leaked key before this post goes live.

> [!CAUTION]
> The exploit in this section is unpatched at time of writing — don't test it against systems you don't own.

## Code blocks

Plain, no syntax highlighting — useful for raw output:

```
HTTP/1.1 200 OK
Set-Cookie: session=eyJhbGciOiJIUzI1NiJ9...
```

PHP, the vulnerable handler:

```php
function authenticate($token) {
    if ($token == $_SESSION['expected']) {   // loose comparison bug
        return true;
    }
    return false;
}
```

JavaScript, the fetch that exposed the bug:

```javascript
fetch('/api/profile', {
  method: 'PATCH',
  body: JSON.stringify({ role: 'admin' })
});
```

Python, a quick PoC script:

```python
import requests

r = requests.post("https://target.example/login", data={"user": "admin", "pass": "' OR 1=1--"})
print(r.status_code, r.text[:200])
```

Bash, automating the recon:

```bash
#!/bin/bash
for host in $(cat targets.txt); do
  nmap -sV -p- "$host" -oN "scans/$host.txt"
done
```

SQL, the query that was actually vulnerable:

```sql
SELECT * FROM users WHERE username = '$input' AND password = '$pass';
```

Tilde fences are useful when the code itself contains backticks:

~~~text
Run it like this: `./exploit.sh --target 10.0.0.5`
~~~

## Tables

CVSS breakdown for this finding:

| Metric              | Value    | Impact          |
|:---------------------|:--------:|-----------------:|
| Attack Vector         | Network  | High             |
| Privileges Required   | None     | Critical         |
| Overall Score         | 9.8      | Critical         |

A plain table with no explicit alignment still works:

| Tool    | Purpose                |
|---------|-------------------------|
| Burp    | Intercepting proxy      |
| sqlmap  | Automated SQLi testing  |

## Horizontal rules

Three divider styles, chosen by which character you use — all render the same way, pick whichever you find easiest to type:

`---`

---

`***`

***

`___`

___

## Raw HTML

Sanitized before rendering — safe tags pass through, scripts and event handlers are stripped:

<div class="custom-box">
  <p>Full disclosure timeline: reported <strong>2026-08-01</strong>, patched
  <strong>2026-08-14</strong>, this post published after a 30-day embargo.
  See the <a href="https://example.com/advisory">vendor advisory</a> for details.</p>
</div>

Native collapsible section:

<details>
<summary>Click to reveal the full payload</summary>
' UNION SELECT username, password FROM users--
</details>

## Table of contents

Drop `[[TOC]]` anywhere in a post (it's used at the very top of this one) and it's replaced with a nested,
collapsible table of contents built from the post's own headings.

---

That's every block-level and inline feature this parser supports, shown the way you'd actually
use it in a real writeup rather than as placeholder text.
