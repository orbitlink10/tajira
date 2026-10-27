<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request, SeoService $seo)
    {
        $category = null;

        if ($request->filled('category')) {
            $category = ArticleCategory::query()->where('slug', $request->query('category'))->first();
        }

        $articles = Article::query()
            ->published()
            ->when($category, fn ($query) => $query->where('article_category_id', $category->id))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = ArticleCategory::query()->orderBy('position')->get();
        $meta = $seo->build(
            'Buying guides and setup guides | '.setting('business.name', 'Tajira'),
            'Practical networking, fibre, security and telephony buying guides from Tajira.',
            route('guides.index')
        );

        return view('storefront.guides.index', compact('articles', 'categories', 'category', 'meta'));
    }

    public function show(Article $article, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($article->isPublished(), 404);

        $article->load(['category', 'products' => fn ($query) => $query->published()->with(['brand:id,name', 'primaryImage'])->limit(6)]);

        $related = Article::query()
            ->published()
            ->whereKeyNot($article->id)
            ->when($article->article_category_id, fn ($query) => $query->where('article_category_id', $article->article_category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $crumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Guides', 'url' => route('guides.index')],
            ['name' => $article->title, 'url' => route('guides.show', $article->slug)],
        ];

        $meta = $seo->forArticle($article);
        $jsonLd = [$structured->article($article), $structured->breadcrumbs($crumbs)];

        return view('storefront.guides.show', compact('article', 'related', 'crumbs', 'meta', 'jsonLd'));
    }
}
