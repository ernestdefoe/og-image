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

        $routeName   = (string) ($request->getAttribute('routeName') ?? '');
        $queryParams = $request->getQueryParams();

        if ($routeName === 'discussion' && !empty($queryParams['id'])) {
            try {
                $this->renderDiscussion($document, $request, (int) $queryParams['id'], $defaultImage);
            } catch (\Throwable) {
                // If discussion rendering fails for any reason, fall back to index tags
                $this->renderForumIndex($document, $request, $defaultImage);
            }
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

        try {
            $ogUrl = $this->url->to('forum')->route('discussion', [
                'id' => $discussion->id . ($discussion->slug ? '-' . $discussion->slug : ''),
            ]);
        } catch (\Throwable) {
            $ogUrl = (string) $request->getUri()->withQuery('')->withFragment('');
        }

        $this->addOg($document, 'og:type',  'article');
        $this->addOg($document, 'og:title', (string) ($discussion->title ?? ''));
        $this->addOg($document, 'og:url',   $ogUrl);

        if ($discussion->created_at) {
            $this->addOg($document, 'article:published_time', $discussion->created_at->toIso8601String());
        }

        $excerpt = '';
        $image   = null;

        $firstPost = $discussion->firstPost;
        if ($firstPost) {
            try {
                $html = $firstPost->formatContent($request);
            } catch (\Throwable) {
                try {
                    $html = $firstPost->formatContent();
                } catch (\Throwable) {
                    $html = (string) ($firstPost->content ?? '');
                }
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
            $this->addOg($document,  'og:image',       $finalImage);
            $this->addName($document, 'twitter:card',  'summary_large_image');
            $this->addName($document, 'twitter:image', $finalImage);
        } else {
            $this->addName($document, 'twitter:card', 'summary');
        }

        $this->addName($document, 'twitter:title', (string) ($discussion->title ?? ''));
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
            $this->addOg($document,  'og:description',       $forumDesc);
            $this->addName($document, 'twitter:description', $forumDesc);
        }

        if ($defaultImage !== '') {
            $this->addOg($document,  'og:image',          $defaultImage);
            $this->addName($document, 'twitter:card',     'summary_large_image');
            $this->addName($document, 'twitter:image',    $defaultImage);
        } else {
            $this->addName($document, 'twitter:card', 'summary');
        }

        $this->addName($document, 'twitter:title', $forumTitle);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function extractImage(string $html): ?string
    {
        if ($html === '') return null;

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*/i', $html, $matches)) {
            $src = $matches[1];
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
