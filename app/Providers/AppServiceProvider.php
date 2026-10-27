<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoLandingPage;
use App\Models\User;
use App\Observers\ContentCacheObserver;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->role === User::ROLE_SUPER_ADMIN ? true : null;
        });

        foreach (config('permissions.abilities', []) as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasAbility($ability));
        }

        foreach ([Product::class, Category::class, Brand::class, Page::class, Article::class, HomepageSection::class, NavigationItem::class, SeoLandingPage::class] as $model) {
            $model::observe(ContentCacheObserver::class);
        }

        $this->configureRateLimiting();
        $this->composeStorefront();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('autocomplete', fn (Request $request) => Limit::perMinute(40)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->session()->getId()));
        RateLimiter::for('reviews', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('quote', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }

    private function composeStorefront(): void
    {
        View::composer(['layouts.storefront', 'storefront.partials.header', 'storefront.partials.footer'], function ($view) {
            $categories = Cache::remember('tajira.menu.header', 3600, function () {
                return Category::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->where('show_in_menu', true)
                    ->with(['activeChildren' => fn ($query) => $query->withCount(['publishedProducts'])])
                    ->withCount(['publishedProducts'])
                    ->orderBy('position')
                    ->get();
            });

            $brands = Cache::remember('tajira.brands.active', 3600, function () {
                return Brand::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'logo_path']);
            });

            $footerPages = Cache::remember('tajira.footer.pages', 3600, function () {
                return Page::query()
                    ->published()
                    ->where('show_in_footer', true)
                    ->orderBy('position')
                    ->get(['title', 'slug']);
            });

            $navHeader = Cache::remember('tajira.nav.header', 3600, function () {
                return NavigationItem::query()->where('location', 'header')->whereNull('parent_id')->where('is_active', true)->orderBy('position')->get();
            });

            $navFooter = Cache::remember('tajira.nav.footer', 3600, function () {
                return NavigationItem::query()->where('location', 'footer')->whereNull('parent_id')->where('is_active', true)->orderBy('position')->get();
            });

            $view->with([
                'menuCategories' => $categories,
                'menuBrands' => $brands,
                'footerPages' => $footerPages,
                'navHeader' => $navHeader,
                'navFooter' => $navFooter,
                'cartCount' => app(CartService::class)->count(),
            ]);
        });
    }
}
