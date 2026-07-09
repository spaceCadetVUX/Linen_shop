<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\AuthorRepository;
use App\Repositories\Eloquent\BlogPostRepository;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private AuthorRepository $authorRepository,
    ) {}

    public function show(string $locale, string $slug): View
    {
        $author = $this->authorRepository->findActiveBySlugOrFail($slug);

        $posts = $this->blogPostRepository->paginateByAuthorIdDecorated($locale, $author->id, 12);

        $breadcrumbItems = [
            ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
            ['label' => 'Blog', 'url' => route($locale.'.blog.index')],
            ['label' => $author->name, 'url' => null],
        ];

        view()->share('alternateUrls', [
            'vi' => route('vi.author.show', $author->slug),
            'en' => route('en.author.show', $author->slug),
        ]);

        $baseUrl    = rtrim((string) config('app.url'), '/');
        $authorUrl  = $baseUrl . ($locale === 'vi' ? '/vi/tac-gia/' : '/en/authors/') . $author->slug;

        $personSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            '@id'      => $baseUrl . '/authors/' . $author->slug . '#person',
            'name'     => $author->name,
            'url'      => $authorUrl,
        ];

        if (filled($author->title))    $personSchema['jobTitle']    = $author->title;
        if (filled($author->bio))      $personSchema['description'] = $author->bio;
        if ($avatarUrl = $author->avatar_url) $personSchema['image'] = $avatarUrl;

        $sameAs = $author->same_as;
        if (! empty($sameAs)) {
            $personSchema['sameAs'] = count($sameAs) === 1 ? $sameAs[0] : $sameAs;
        }

        $expertise = array_values(array_filter((array) ($author->expertise ?? [])));
        if (! empty($expertise)) {
            $personSchema['knowsAbout'] = $expertise;
        }

        $fallbackTitle = $locale === 'vi'
            ? 'Tác giả: ' . $author->name
            : 'Author: ' . $author->name;
        $fallbackDescription = $author->bio
            ? \Str::limit(strip_tags($author->bio), 160)
            : $fallbackTitle;

        return view('pages.blog.author', compact(
            'locale', 'author', 'posts', 'breadcrumbItems',
            'fallbackTitle', 'fallbackDescription'
        ) + [
            'seoMeta'       => null,
            'fallbackImage' => $author->avatar_url,
            'ogType'        => 'profile',
            'jsonldSchemas' => [$personSchema],
        ]);
    }
}
