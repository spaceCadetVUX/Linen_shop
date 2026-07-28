<?php

namespace App\Services\Mcp;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class McpPageService
{
    // ── Public API ─────────────────────────────────────────────────────────────

    public function context(string $pageKey): array
    {
        return $this->buildContextResponse($this->loadPage($pageKey));
    }

    public function list(): array
    {
        return Page::with('translations')
            ->get()
            ->map(fn (Page $page) => $this->buildContextResponse($page))
            ->values()
            ->all();
    }

    public function upsert(string $pageKey, array $data, int $tokenId, bool $dryRun): array
    {
        $preview = null;

        try {
            DB::transaction(function () use ($pageKey, $data, $tokenId, $dryRun, &$preview) {
                $overwrite = (bool) ($data['overwrite_existing'] ?? false);

                $page = Page::where('page_key', $pageKey)->first();

                if (! $page) {
                    $page = new Page([
                        'page_key' => $pageKey,
                        'is_active' => false,
                    ]);
                }

                // Never auto-activate via upsert — use the /activate endpoint so
                // the readiness gate always runs.
                if (isset($data['is_active']) && $data['is_active'] === false) {
                    $page->is_active = false;
                }

                $page->page_key = $pageKey;
                $page->mcp_drafted_at = now();
                $page->mcp_token_id = $tokenId;
                $page->save();

                if (! empty($data['translations'])) {
                    $this->writeTranslations($page, $data['translations'], $overwrite);
                }

                $page->refresh()->load('translations');
                $preview = $this->buildContextResponse($page);

                if ($dryRun) {
                    throw new \RuntimeException('__mcp_dry_run__');
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__mcp_dry_run__') {
                throw $e;
            }
        }

        return ['data' => $preview];
    }

    public function readiness(string $pageKey): array
    {
        return $this->computeReadiness($this->loadPage($pageKey));
    }

    public function activate(string $pageKey): array
    {
        $page = $this->loadPage($pageKey);
        $readiness = $this->computeReadiness($page);

        if (! empty($readiness['blocking_issues'])) {
            abort(422, 'Page chưa sẵn sàng để activate: '.implode('; ', $readiness['blocking_issues']));
        }

        $page->update([
            'is_active' => true,
            'mcp_drafted_at' => null,
            'mcp_token_id' => null,
        ]);

        $page->refresh()->load('translations');

        return ['data' => $this->buildContextResponse($page)];
    }

    public function deactivate(string $pageKey): array
    {
        $page = $this->loadPage($pageKey);

        $page->update(['is_active' => false]);

        $page->refresh()->load('translations');

        return ['data' => $this->buildContextResponse($page)];
    }

    // ── Private: load ────────────────────────────────────────────────────────────

    private function loadPage(string $pageKey): Page
    {
        $page = Page::with('translations')->where('page_key', $pageKey)->first();

        if (! $page) {
            abort(404, "Page '{$pageKey}' not found.");
        }

        return $page;
    }

    // ── Private: write helpers ───────────────────────────────────────────────────

    private function writeTranslations(Page $page, array $translations, bool $overwrite): void
    {
        foreach ($translations as $locale => $data) {
            if (! in_array($locale, ['vi', 'en'], true)) {
                continue;
            }

            $translation = PageTranslation::firstOrNew([
                'page_id' => $page->id,
                'locale' => $locale,
            ]);

            $isNew = ! $translation->exists;

            $writeable = ['title', 'body', 'meta_title', 'meta_description'];
            foreach ($writeable as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }
                if (! $overwrite && ! $isNew && filled($translation->{$field})) {
                    continue;
                }
                $translation->{$field} = $data[$field];
            }

            // Slug is never AI-settable — always derived from title, same rule
            // as Product/Category/BlogPost translations.
            if (filled($data['title'] ?? null) && ($overwrite || ! filled($translation->slug))) {
                $translation->slug = Str::slug($data['title']);
            }

            $translation->page_id = $page->id;
            $translation->locale = $locale;
            $translation->save();
        }
    }

    // ── Private: readiness ────────────────────────────────────────────────────────

    private function computeReadiness(Page $page): array
    {
        $checks = [];
        $blocking = [];
        $warnings = [];
        $score = 0;
        $total = 0;

        foreach (['vi', 'en'] as $locale) {
            $translation = $page->translations->firstWhere('locale', $locale);

            $hasTitle = filled($translation?->title);
            $hasBody = filled($translation?->body);
            $hasMetaTitle = filled($translation?->meta_title);
            $hasMetaDesc = filled($translation?->meta_description);

            $checks[$locale] = [
                'has_title' => ['pass' => $hasTitle],
                'has_body' => ['pass' => $hasBody],
                'has_meta_title' => ['pass' => $hasMetaTitle],
                'has_meta_description' => ['pass' => $hasMetaDesc],
            ];

            $total += 4;
            $score += (int) $hasTitle + (int) $hasBody + (int) $hasMetaTitle + (int) $hasMetaDesc;

            // vi is the primary site locale — blocking. en lags behind on a
            // bilingual site often enough that it's a warning, not a blocker
            // (same posture as Brand's readiness for seo_en.*).
            foreach ([
                'title' => $hasTitle,
                'body' => $hasBody,
                'meta_title' => $hasMetaTitle,
                'meta_description' => $hasMetaDesc,
            ] as $field => $pass) {
                if ($pass) {
                    continue;
                }
                if ($locale === 'vi') {
                    $blocking[] = "vi.{$field} missing";
                } else {
                    $warnings[] = "en.{$field} missing";
                }
            }
        }

        $scorePercent = $total > 0 ? (int) round(($score / $total) * 100) : 0;

        return [
            'page_key' => $page->page_key,
            'score' => $scorePercent,
            'ready' => empty($blocking),
            'checks' => $checks,
            'blocking_issues' => $blocking,
            'warnings' => $warnings,
        ];
    }

    // ── Private: response builder ─────────────────────────────────────────────────

    private function buildContextResponse(Page $page): array
    {
        $translationsOut = [];
        foreach ($page->translations as $t) {
            $translationsOut[$t->locale] = [
                'title' => $t->title,
                'slug' => $t->slug,
                'body' => $t->body,
                'meta_title' => $t->meta_title,
                'meta_description' => $t->meta_description,
            ];
        }

        return [
            'page_key' => $page->page_key,
            'is_active' => (bool) $page->is_active,
            'mcp_drafted_at' => $page->mcp_drafted_at?->toIso8601String(),
            'translations' => $translationsOut,
        ];
    }
}
