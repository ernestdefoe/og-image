<?php

namespace Ernestdefoe\OgImage\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Open Graph and Twitter Card tags written into the forum's HTML, which
 * is what link previews on Facebook, Slack, Discord and X read.
 */
class OgMetaTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Markdown, so a first post can carry an image.
        $this->extension('flarum-markdown', 'ernestdefoe-og-image');

        $this->setting('forum_title', 'Test Forum');
        $this->setting('forum_description', 'A place to talk');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Game "day" <thread>', 'slug' => 'game-day-thread', 'created_at' => Carbon::parse('2026-01-02 03:04:05'), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'No pictures', 'slug' => 'no-pictures', 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Staff only secret', 'slug' => 'staff-only-secret', 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'hidden_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<r><p>Kickoff at noon. <IMG src="https://img.example/field.jpg"><s>![field](</s>https://img.example/field.jpg<e>)</e></IMG></p></r>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>'.str_repeat('word ', 60).'</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>The secret plan</p></t>'],
            ],
        ]);
    }

    /** @return array<string, string[]> meta tag key => every content value it was given */
    private function meta(string $path, ?int $actor = null): array
    {
        $response = $this->send($this->request('GET', $path, $actor ? ['authenticatedAs' => $actor] : []));

        $this->assertSame(200, $response->getStatusCode());

        preg_match_all('/<meta (?:property|name)="([^"]+)" content="([^"]*)">/', (string) $response->getBody(), $matches, PREG_SET_ORDER);

        $tags = [];
        foreach ($matches as [, $key, $content]) {
            if (str_starts_with($key, 'og:') || str_starts_with($key, 'twitter:') || str_starts_with($key, 'article:') || str_starts_with($key, 'fb:')) {
                $tags[$key][] = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
            }
        }

        return $tags;
    }

    #[Test]
    public function a_discussion_is_described_as_an_article_with_its_first_image()
    {
        $tags = $this->meta('/d/1');

        $this->assertSame(['article'], $tags['og:type']);
        $this->assertSame(['Game "day" <thread>'], $tags['og:title']);
        $this->assertSame(['http://localhost/d/1-game-day-thread'], $tags['og:url']);
        $this->assertSame(['Test Forum'], $tags['og:site_name']);
        $this->assertSame(['2026-01-02T03:04:05+00:00'], $tags['article:published_time']);
        $this->assertSame(['Kickoff at noon.'], $tags['og:description']);
        $this->assertSame(['https://img.example/field.jpg'], $tags['og:image']);
        $this->assertSame(['summary_large_image'], $tags['twitter:card']);
        $this->assertSame(['https://img.example/field.jpg'], $tags['twitter:image']);
        $this->assertSame(['Game "day" <thread>'], $tags['twitter:title']);
    }

    #[Test]
    public function the_title_is_escaped_in_the_html()
    {
        $body = (string) $this->send($this->request('GET', '/d/1'))->getBody();

        $this->assertStringContainsString('<meta property="og:title" content="Game &quot;day&quot; &lt;thread&gt;">', $body);
    }

    #[Test]
    public function a_long_first_post_is_cut_to_two_hundred_characters()
    {
        $tags = $this->meta('/d/2');

        $description = $tags['og:description'][0];
        $this->assertSame(198, mb_strlen($description));
        $this->assertStringEndsWith('…', $description);
        $this->assertSame([$description], $tags['twitter:description']);
    }

    #[Test]
    public function without_an_image_or_a_default_the_card_is_a_summary()
    {
        $tags = $this->meta('/d/2');

        $this->assertArrayNotHasKey('og:image', $tags);
        $this->assertSame(['summary'], $tags['twitter:card']);
    }

    #[Test]
    public function the_default_image_stands_in_when_the_post_has_none()
    {
        $this->setting('ernestdefoe-og-image.default_image', 'https://img.example/default.png');

        $tags = $this->meta('/d/2');

        $this->assertSame(['https://img.example/default.png'], $tags['og:image']);
        $this->assertSame(['summary_large_image'], $tags['twitter:card']);
    }

    #[Test]
    public function another_page_whose_address_names_the_discussion_does_not_describe_it_to_a_visitor_who_cannot_see_it()
    {
        // Any 200 page whose path contains /d/{id} is read as that discussion.
        $this->extend((new Extend\Frontend('forum'))->route('/archive/d/{id}', 'test.archive'));

        $tags = $this->meta('/archive/d/3');

        $this->assertSame(['website'], $tags['og:type']);
        $this->assertSame(['Test Forum'], $tags['og:title']);
        $this->assertStringNotContainsString('secret', strtolower(json_encode($tags)));
    }

    #[Test]
    public function a_visitor_who_can_see_it_gets_the_real_preview()
    {
        $this->assertSame(['Staff only secret'], $this->meta('/d/3', 1)['og:title'], 'Its author, an admin');
    }

    #[Test]
    public function the_index_is_described_as_the_forum()
    {
        $this->setting('ernestdefoe-og-image.fb_app_id', '12345');

        $tags = $this->meta('/');

        $this->assertSame(['website'], $tags['og:type']);
        $this->assertSame(['Test Forum'], $tags['og:title']);
        $this->assertSame(['A place to talk'], $tags['og:description']);
        $this->assertSame(['12345'], $tags['fb:app_id']);
        $this->assertSame(['summary'], $tags['twitter:card']);
    }

    #[Test]
    public function a_page_another_extension_described_is_left_alone()
    {
        $this->extend(
            // Higher priority runs first, as an extension describing its own
            // pages would.
            (new Extend\Frontend('forum'))->content(function (Document $document) {
                $document->head[] = '<meta property="og:title" content="Described elsewhere">';
            }, 100)
        );

        $tags = $this->meta('/');

        $this->assertSame(['og:title' => ['Described elsewhere']], $tags);
    }
}
