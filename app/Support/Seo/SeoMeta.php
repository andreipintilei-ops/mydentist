<?php

namespace App\Support\Seo;

/**
 * Everything the <head> of an indexable page needs, in one place, so title /
 * description / canonical / robots / Open Graph / JSON-LD are rendered
 * identically by <x-seo-head> on every page instead of being hand-written per
 * template (where they drift and rot).
 */
final class SeoMeta
{
    /**
     * @param  array<int, array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index,follow',
        public string $ogType = 'website',
        public ?string $ogImage = null,
        public array $jsonLd = [],
    ) {}

    public static function make(string $title, ?string $description = null): static
    {
        return new self(title: $title, description: $description);
    }

    public function canonical(string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Mark the page noindex (still followable so it passes link equity through).
     * Used by /cauta and any faceted surface that must not enter the index.
     */
    public function noindex(): static
    {
        $this->robots = 'noindex,follow';

        return $this;
    }

    public function ogType(string $type): static
    {
        $this->ogType = $type;

        return $this;
    }

    public function ogImage(?string $url): static
    {
        $this->ogImage = $url;

        return $this;
    }

    /**
     * Append one JSON-LD graph (from a builder in App\Support\Seo\JsonLd).
     *
     * @param  array<string, mixed>  $graph
     */
    public function jsonLd(array $graph): static
    {
        if ($graph !== []) {
            $this->jsonLd[] = $graph;
        }

        return $this;
    }

    public function isIndexable(): bool
    {
        return ! str_contains($this->robots, 'noindex');
    }
}
