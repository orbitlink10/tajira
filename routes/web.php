<?php

use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\ArticleController;
use App\Http\Controllers\Storefront\AuthController;
use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\CompareController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\MerchantFeedController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\QuotationController;
use App\Http\Controllers\Storefront\RedirectController;
use App\Http\Controllers\Storefront\RobotsController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

require __DIR__.'/admin.php';

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemaps.index');
Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages'])->name('sitemaps.pages');
Route::get('/sitemaps/categories.xml', [SitemapController::class, 'categories'])->name('sitemaps.categories');
Route::get('/sitemaps/brands.xml', [SitemapController::class, 'brands'])->name('sitemaps.brands');
Route::get('/sitemaps/products.xml', [SitemapController::class, 'products'])->name('sitemaps.products');
Route::get('/sitemaps/articles.xml', [SitemapController::class, 'articles'])->name('sitemaps.articles');
Route::get('/sitemaps/collections.xml', [SitemapController::class, 'collections'])->name('sitemaps.collections');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/feeds/google-merchant.xml', MerchantFeedController::class)->name('feeds.merchant');

Route::get('/search', [ProductController::class, 'search'])->middleware('throttle:search')->name('search');
Route::get('/search/autocomplete', [ProductController::class, 'autocomplete'])->middleware('throttle:autocomplete')->name('search.autocomplete');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product:slug}/reviews', [ProductController::class, 'storeReview'])->middleware('throttle:reviews')->name('products.reviews.store');

Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/collections/{collection:slug}', CollectionController::class)->name('collections.show');

Route::get('/guides', [ArticleController::class, 'index'])->name('guides.index');
Route::get('/guides/{article:slug}', [ArticleController::class, 'show'])->name('guides.show');

Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:contact')->name('contact.send');

Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/add', [CompareController::class, 'add'])->name('compare.add');
Route::post('/compare/remove/{product:slug}', [CompareController::class, 'remove'])->name('compare.remove');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');

Route::get('/quote', [QuotationController::class, 'create'])->name('quotes.create');
Route::post('/quote', [QuotationController::class, 'store'])->middleware('throttle:quote')->name('quotes.store');
Route::get('/quote/{quotation}', [QuotationController::class, 'show'])->name('quotes.show');
Route::get('/quote/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotes.pdf');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::post('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::get('/checkout/shipping', [CheckoutController::class, 'shipping'])->name('checkout.shipping');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

Route::get('/track', [OrderController::class, 'track'])->name('orders.track');
Route::post('/track', [OrderController::class, 'find'])->middleware('throttle:login')->name('orders.find');
Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('order.access')->name('orders.show');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('order.access')->name('orders.cancel');
Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->middleware('order.access')->name('orders.invoice');

Route::get('/orders/{order}/payment', [PaymentController::class, 'show'])->middleware('order.access')->name('payments.show');
Route::post('/orders/{order}/payment', [PaymentController::class, 'pay'])->middleware(['order.access', 'throttle:payments'])->name('payments.pay');
Route::get('/orders/{order}/payment/status', [PaymentController::class, 'status'])->middleware('order.access')->name('payments.status');
Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback'])->name('payments.mpesa.callback');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:login')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:login')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('addresses.delete');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
    Route::get('/reviews', [AccountController::class, 'reviews'])->name('reviews');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::get('/privacy', [AccountController::class, 'privacy'])->name('privacy');
    Route::get('/privacy/export', [AccountController::class, 'export'])->name('privacy.export');
    Route::post('/privacy/deletion', [AccountController::class, 'requestDeletion'])->name('privacy.deletion');
});

Route::middleware('auth')->group(function () {
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{product:slug}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

Route::fallback(RedirectController::class);
