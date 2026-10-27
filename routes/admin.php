<?php

use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SeoLandingPageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('mfa/challenge', [AuthController::class, 'showChallenge'])->name('mfa.challenge');
        Route::post('mfa/challenge', [AuthController::class, 'verifyChallenge'])->middleware('throttle:admin-login')->name('mfa.challenge.verify');
        Route::get('mfa/setup', [AuthController::class, 'showSetup'])->name('mfa.setup');
        Route::post('mfa/enable', [AuthController::class, 'enableMfa'])->middleware('throttle:admin-login')->name('mfa.enable');
        Route::post('mfa/disable', [AuthController::class, 'disableMfa'])->middleware('throttle:admin-login')->name('mfa.disable');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::middleware('admin.mfa')->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');

            Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
            Route::post('products/bulk-status', [ProductController::class, 'bulkStatus'])->name('products.bulk-status');
            Route::get('revisions', RevisionController::class)->name('revisions.index');
            Route::get('products/import', [ProductController::class, 'importForm'])->name('products.import.form');
            Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
            Route::get('products/{product}/preview', [ProductController::class, 'preview'])->name('products.preview');
            Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
            Route::post('products/{product}/unpublish', [ProductController::class, 'unpublish'])->name('products.unpublish');
            Route::post('products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
            Route::post('products/{product}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
            Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
            Route::post('products/{product}/documents', [ProductController::class, 'storeDocument'])->name('products.documents.store');
            Route::delete('products/{product}/documents/{document}', [ProductController::class, 'destroyDocument'])->name('products.documents.destroy');
            Route::resource('products', ProductController::class);
            Route::resource('products.variants', ProductVariantController::class)->except(['show']);

            Route::resource('categories', CategoryController::class)->except(['show']);
            Route::resource('brands', BrandController::class)->except(['show']);
            Route::resource('attributes', AttributeController::class)->except(['show']);

            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{order}/manual-payment', [OrderController::class, 'manualPayment'])->name('orders.manual-payment');
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
            Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');

            Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
            Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
            Route::post('quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
            Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');

            Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
            Route::post('contact-messages/{contactMessage}', [ContactMessageController::class, 'update'])->name('contact-messages.update');

            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
            Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

            Route::get('pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
            Route::resource('pages', PageController::class)->except(['show']);
            Route::get('articles/{article}/preview', [ArticleController::class, 'preview'])->name('articles.preview');
            Route::resource('articles', ArticleController::class)->except(['show']);
            Route::resource('article-categories', ArticleCategoryController::class)->except(['show']);
            Route::resource('navigation', NavigationController::class)->except(['show']);
            Route::get('homepage', [HomepageController::class, 'index'])->name('homepage.index');
            Route::post('homepage', [HomepageController::class, 'update'])->name('homepage.update');

            Route::resource('coupons', CouponController::class)->except(['show']);
            Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
            Route::post('shipping/zones', [ShippingController::class, 'storeZone'])->name('shipping.zones.store');
            Route::put('shipping/zones/{zone}', [ShippingController::class, 'updateZone'])->name('shipping.zones.update');
            Route::delete('shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('shipping.zones.destroy');
            Route::post('shipping/methods', [ShippingController::class, 'storeMethod'])->name('shipping.methods.store');
            Route::put('shipping/methods/{method}', [ShippingController::class, 'updateMethod'])->name('shipping.methods.update');
            Route::delete('shipping/methods/{method}', [ShippingController::class, 'destroyMethod'])->name('shipping.methods.destroy');

            Route::resource('redirects', RedirectController::class)->except(['show']);
            Route::get('seo', [SeoController::class, 'index'])->name('seo.index');
            Route::resource('seo/landing-pages', SeoLandingPageController::class)
                ->names('seo.landing-pages')
                ->parameters(['landing-pages' => 'landingPage'])
                ->except(['show']);
            Route::post('seo/audit-links', [SeoController::class, 'auditLinks'])->name('seo.audit-links');

            Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
            Route::post('imports/discovery', [ImportController::class, 'discovery'])->name('imports.discovery');
            Route::post('imports/csv', [ImportController::class, 'uploadCsv'])->name('imports.csv');
            Route::get('imports/{batch}', [ImportController::class, 'show'])->name('imports.show');
            Route::post('imports/{batch}/fetch', [ImportController::class, 'fetch'])->name('imports.fetch');
            Route::post('imports/{batch}/resume', [ImportController::class, 'resume'])->name('imports.resume');
            Route::post('imports/source/{sourceProduct}/approve', [ImportController::class, 'approve'])->name('imports.approve');
            Route::post('imports/source/{sourceProduct}/reject', [ImportController::class, 'reject'])->name('imports.reject');
            Route::post('imports/source/{sourceProduct}/retry', [ImportController::class, 'retry'])->name('imports.retry');

            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::resource('users', UserController::class)->except(['show']);
            Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');

            Route::get('audit', AuditController::class)->name('audit.index');
        });
    });
});
