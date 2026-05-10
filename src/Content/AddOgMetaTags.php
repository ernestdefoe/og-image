<?php

namespace Ernestdefoe\OgImage\Content;

use Flarum\Discussion\Discussion;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class AddOgMetaTags
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
    ) {}

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        $forumName    = (string) ($this->settings->get('forum_title') ?? '');
        $defaultImage = (string) ($this->settings->get('ernestdefoe-og-image.default_image') ?? '');

        $this->addOg($document, 'og:site_name', $forumName);

        $discussionId = $this->discussionIdFromRequest($request);

        if ($discussionId !== null) {
            $this->renderDiscussion($document, $request, $discussionId, $defaultImage);
        } else {
            $this->renderForumIndex($document, $request, $defaultImage);
        }
    }

    // ── Discussion page ───────────────────────────────────────────────────────

    private function renderDiscussion(
        Document $document,
        ServerRequestInterface $request,
        int $id,
        string $defaultImage
    ): void {
        $discussion = Discussion::with('firstPost')->find($id);

        if (!$discussion) {
            $this->renderForumIndex($document, $request, $defaultImage);
            return;
        }

        $ogUrl = $this->url->to('forum')->route('discussion', [
            'id' => $discussion->id . ($discussion->slug ? '-' . $discussion->slug : ''),
        ]);

        $this->addOg($document, 'og:type',  'article');
        $this->addOg($document, 'og:title', $discussion->title);
        $this->addOg($document, 'og:url',   $ogUrl);

        if ($discussion->created_at) {
            $this->addOg($document, 'article:published_time', $discussion->created_at->toIso8601String());
        }

        $excerpt = '';
        $image   = null;

        $firstPost = $discussion->firstPost;
        if ($firstPost) {
            try {
                $html = $firstPost->formatContent();
            } catch (\Throwable) {
                $html = (string) ($firstPost->content ?? '');
            }

            $text    = preg_replace('/\s+/', ' ', trim(strip_tags($html)));
            $excerpt = mb_strlen($text) > 200 ? mb_substr($text, 0, 197) . '…' : $text;

            $image = $this->extractImage($html);
        }

        if ($excerpt !== '') {
            $this->addOg($document, 'og:description', $excerpt);
        }

        $finalImage = $image ?: $defaultImage;

        if ($finalImage) {
            $this->addOg($document,  'og:image',        $finalImage);
            $this->addName($document, 'twitter:card',   'summary_large_image');
            $this->addName($document, 'twitter:image',  $finalImage);
        } else {
            $this->addName($document, 'twitter:card', 'summary');
        }

        $this->addName($document, 'twitter:title', $discussion->title);
        if ($excerpt !== '') {
            $this->addName($document, 'twitter:description', $excerpt);
        }
    }

    // ── Forum index / all other pages ─────────────────────────────────────────

    private function renderForumIndex(
        Document $document,
        ServerRequestInterface $request,
        string $defaultImage
    ): void {
        $forumTitle = (string) ($this->settings->get('forum_title') ?? '');
        $forumDesc  = (string) ($this->settings->get('forum_description') ?? '');
        $ogUrl      = (string) $request->getUri()->withQuery('')->withFragment('');

        $this->addOg($document, 'og:type',  'website');
        $this->addOg($document, 'og:title', $forumTitle);
        $this->addOg($document, 'og:url',   $ogUrl);

        if ($forumDesc !== '') {
            $this->addOg($document,  'og:description',      $forumDesc);
            $this->addName($document, 'twitter:description', $forumDesc);
        }

        if ($defaultImage !== '') {
            $this->addOg($document,  'og:image',           $defaultImage);
            $this->addName($document, 'twitter:card',      'summary_large_image');
            $this->addName($document, 'twitter:image',     $defaultImage);
        } else {
            $this->addName($document, 'twitter:card', 'summary');
        }

        $this->addName($document, 'twitter:title', $forumTitle);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function discussionIdFromRequest(ServerRequestInterface $request): ?int
    {
        $path = $request->getUri()->getPath();
        if (preg_match('#/d/(\d+)#', $path, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    private function extractImage(string $html): ?string
    {
        if ($html === '') return null;

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*/i', $html, $matches)) {
            $src = $matches[1];
            // Skip data URIs and tiny base64 placeholders
            if (!str_starts_with($src, 'data:')) {
                return $src;
            }
        }

        return null;
    }

    private function addOg(Document $document, string $property, string $content): void
    {
        if ($content === '') return;
        $esc = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $document->head[] = "<meta property=\"{$property}\" content=\"{$esc}\">";
    }

    private function addName(Document $document, string $name, string $content): void
    {
        if ($content === '') return;
        $esc = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $document->head[] = "<meta name=\"{$name}\" content=\"{$esc}\">";
    }
}
