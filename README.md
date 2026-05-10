# Flarum OG Image

Automatically adds Open Graph and Twitter Card meta tags to every page of your Flarum 2 forum so that Facebook, Twitter/X, and other platforms generate rich link previews when discussions are shared or auto-posted.

---

## What it does

| Page | Tags generated |
|---|---|
| Discussion | `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `article:published_time`, Twitter Card |
| Forum index / other pages | `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, Twitter Card |
| All pages | `og:site_name`, `fb:app_id` (if configured) |

**Image selection priority for discussions:**
1. First `<img>` found in the first post's rendered HTML
2. Default OG Image configured in Admin → Extensions → OG Image

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.1 |
| Flarum | ^2.0 |

---

## Installation

```bash
composer require ernestdefoe/og-image
php flarum cache:clear
```

---

## Configuration

1. Go to **Admin → Extensions → OG Image**
2. Set **Default OG Image URL** — an absolute URL to an image used when a discussion has no embedded images (e.g. your forum banner)
   - Facebook recommends **1200 × 630 px** minimum
   - Must be publicly accessible (no auth required)
3. Set **Facebook App ID** (optional but recommended) — removes the "missing fb:app_id" warning in the Facebook Sharing Debugger
   - Find your App ID at **developers.facebook.com → My Apps → your app → top-left of dashboard**
4. Click **Save Changes**

---

## How it works

The extension hooks into Flarum's server-side document rendering. Before the HTML is sent to the browser, it:

1. Checks the request route — discussions are detected via Flarum's `routeName` attribute or by matching `/d/{id}` in the URL path
2. Loads the discussion and its first post from the database
3. Renders the first post to HTML, strips tags for the description excerpt, and scans for the first `<img>` to use as the OG image
4. Writes `<meta property="og:*">` and `<meta name="twitter:*">` tags into the `<head>`

Because the tags are server-rendered, Facebook's scraper sees them immediately — no JavaScript execution required.

---

## Using with the Facebook Auto-Post extension

Install both extensions. When the Facebook Auto-Post extension posts a discussion URL to your Facebook Page, Facebook scrapes that URL and reads the OG tags this extension provides, producing a rich preview card with the discussion title, excerpt, and image.

---

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| No preview on Facebook | Facebook cached the page before OG tags were present — use the Sharing Debugger to force a re-scrape |
| Preview shows no image | Discussion has no `<img>` in the first post and no default image is configured |
| Default image not showing | URL is not absolute, not publicly accessible, or returns a non-image content type |
| Wrong description | The excerpt is taken from the first post only; BBcode/markdown is stripped |
| "Invalid App ID" warning | You entered your Facebook Page ID instead of your App ID — they are different numbers |
| Image not showing on auto-posted Facebook posts | See **HTTP 206 response** section below |

---

## HTTP 206 response — images not showing on Facebook posts

The Facebook Sharing Debugger reports a **Response Code** for every URL it scrapes. If yours shows **206** instead of **200**, your web server is returning partial content to Facebook's crawler. This means Facebook may not receive the full `<head>` section where OG tags live, causing the image (and other tags) to be missing from link previews.

**This is a web server configuration issue, not a Flarum or extension issue.**

### Nginx

Add to your server block:

```nginx
proxy_force_ranges off;
```

### Apache

Add to your `.htaccess` or virtual host config:

```apache
BrowserMatch "facebookexternalhit" no-gzip
```

### Cloudflare

If your site is proxied through Cloudflare, their edge cache may be serving partial responses. Add a Cache Rule to bypass caching for discussion pages:

1. Cloudflare dashboard → **Rules → Cache Rules**
2. Create a rule matching `fbsfb.com/d/*`
3. Set **Cache Status: Bypass**

After making any of these changes, go to **developers.facebook.com/tools/debug**, paste your discussion URL, and click **Scrape Again**. Confirm the Response Code changes to **200** before testing image previews.

---

## License

MIT © Ernestdefoe
