<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Seo;

/**
 * Seo
 *
 * Immutable value object describing the search-engine and social-share metadata
 * for a single page. Controllers build a page-specific instance (or start from
 * {@see Seo::default()}) and pass it to Inertia as the `seo` prop; the root Blade
 * view renders it server-side so every crawler and scraper receives complete,
 * correctly-escaped metadata on the initial page load.
 *
 * All mutators return a new instance, keeping the object immutable.
 *
 * @phpstan-type JsonLdBlock array<string, mixed>
 */
final readonly class Seo
{
    /**
     * @param  string  $title  Page title (rendered as <title> and og:title/twitter:title).
     * @param  string  $description  Meta description (~150–160 chars).
     * @param  string  $canonical  Absolute canonical URL for the page.
     * @param  string  $robots  Robots directive, e.g. "index,follow" or "noindex,follow".
     * @param  string  $image  Absolute URL to the social share image.
     * @param  string  $type  Open Graph object type, e.g. "website" or "article".
     * @param  list<JsonLdBlock>  $jsonLd  List of JSON-LD blocks (each an associative array).
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots = 'index,follow',
        public string $image = '',
        public string $type = 'website',
        public array $jsonLd = [],
    ) {}

    /**
     * Build the sitewide default metadata for the current request.
     *
     * The canonical and image URLs are derived from the configured application
     * URL (never the raw request Host header) to avoid host-header spoofing.
     */
    public static function default(): self
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $imageUrl = sprintf('%s/og-image.png', $baseUrl);

        return new self(
            title: (string) config('app.name'),
            description: 'Drink Safe is an anonymous community platform for viewing and reporting drink-spiking incidents at venues, helping people stay informed and safe on nights out.',
            canonical: self::canonicalForCurrentRequest($baseUrl),
            robots: 'index,follow',
            image: $imageUrl,
            type: 'website',
            jsonLd: [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => (string) config('app.name'),
                    'url' => $baseUrl,
                    'logo' => $imageUrl,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => (string) config('app.name'),
                    'url' => $baseUrl,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => sprintf('%s/map?search={search_term_string}', $baseUrl),
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        );
    }

    /**
     * Return a copy with a different title.
     */
    public function withTitle(string $title): self
    {
        return new self($title, $this->description, $this->canonical, $this->robots, $this->image, $this->type, $this->jsonLd);
    }

    /**
     * Return a copy with a different description.
     */
    public function withDescription(string $description): self
    {
        return new self($this->title, $description, $this->canonical, $this->robots, $this->image, $this->type, $this->jsonLd);
    }

    /**
     * Return a copy with a different canonical URL.
     */
    public function withCanonical(string $canonical): self
    {
        return new self($this->title, $this->description, $canonical, $this->robots, $this->image, $this->type, $this->jsonLd);
    }

    /**
     * Return a copy with a different robots directive.
     */
    public function withRobots(string $robots): self
    {
        return new self($this->title, $this->description, $this->canonical, $robots, $this->image, $this->type, $this->jsonLd);
    }

    /**
     * Return a copy with a different social share image.
     */
    public function withImage(string $image): self
    {
        return new self($this->title, $this->description, $this->canonical, $this->robots, $image, $this->type, $this->jsonLd);
    }

    /**
     * Return a copy with a different Open Graph type.
     */
    public function withType(string $type): self
    {
        return new self($this->title, $this->description, $this->canonical, $this->robots, $this->image, $type, $this->jsonLd);
    }

    /**
     * Return a copy with an additional JSON-LD block appended.
     *
     * @param  JsonLdBlock  $block
     */
    public function withJsonLd(array $block): self
    {
        return new self($this->title, $this->description, $this->canonical, $this->robots, $this->image, $this->type, [...$this->jsonLd, $block]);
    }

    /**
     * Return a copy marked as noindex (still followable).
     */
    public function noindex(): self
    {
        return $this->withRobots('noindex,follow');
    }

    /**
     * Serialise to the exact array shape consumed by the SEO Blade partial.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     robots: string,
     *     image: string,
     *     type: string,
     *     jsonLd: list<JsonLdBlock>
     * }
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'robots' => $this->robots,
            'image' => $this->image,
            'type' => $this->type,
            'jsonLd' => $this->jsonLd,
        ];
    }

    /**
     * Build the canonical URL for the current request from the config host.
     *
     * The trailing slash is trimmed so the home path resolves to the bare host.
     */
    private static function canonicalForCurrentRequest(string $baseUrl): string
    {
        return rtrim(sprintf('%s/%s', $baseUrl, ltrim(request()->path(), '/')), '/');
    }
}
