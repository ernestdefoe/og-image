# Flarum OG Image

Automatically adds Open Graph and Twitter Card meta tags to every page of your Flarum 2 forum so that Facebook, Twitter/X, and other platforms generate rich link previews when discussions are shared or auto-posted.

---

## What it does

| Page | Tags generated |
|---|---|
| Discussion | `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `article:published_time`, Twitter Card |
| Forum index / other pages | `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, Twitter Card |
| All pages | `og:site_name` |

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
3. Click **Save Changes**

---

## How it works

The extension hooks into Flarum's server-side document rendering. Before the HTML is sent to the browser, it:

1. Checks the request path — `/d/{id}` signals a discussion page
2. Loads the discussion and its first post from the database
3. Renders the first post to HTML, strips tags for the description excerpt, and scans for the first `<img>` to use as the OG image
4. Writes `<meta property="og:*">` and `<meta name="twitter:*">` tags into the `<head>`

Because the tags are server-rendered, Facebook's scraper sees them immediately — no JavaScript execution required.

---

## Using with the Facebook Auto-Post extension

Install both extensions. When the Facebook Auto-Post extension posts a plain-text message containing your discussion URL, Facebook's scraper visits that URL and reads the OG tags this extension provides. The result is a rich preview card with the discussion title, excerpt, and image appearing on your Facebook Page.

To force Facebook to re-scrape a URL (useful after installing this extension on an existing forum):

1. Go to **developers.facebook.com/tools/debug**
2. Paste the discussion URL
3. Click **Debug** then **Scrape Again**

---

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| No preview on Facebook | Facebook cached the page before OG tags were present — use the Sharing Debugger to force a re-scrape |
| Preview shows no image | Discussion has no `<img>` in the first post and no default image is configured |
| Default image not showing | URL is not absolute, not publicly accessible, or returns a non-image content type |
| Wrong description | The excerpt is taken from the first post only; BBcode/markdown is stripped |

---

## License

MIT © Ernestdefoe
