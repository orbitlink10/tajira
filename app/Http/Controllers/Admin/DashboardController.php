<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ImportBatch;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Quotation;
use App\Models\SourceProduct;
use App\Services\SeoAuditService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(SeoAuditService $seoAudit)
    {
        $revenueToday = Order::query()->where('payment_status', 'paid')->whereDate('paid_at', today())->sum('total_minor');
        $revenue30 = Order::query()->where('payment_status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('total_minor');
        $ordersToday = Order::query()->whereDate('created_at', today())->count();
        $pendingPayment = Order::query()->where('payment_status', 'pending')->where('status', 'pending_payment')->count();
        $needsAttention = Order::query()->where('payment_status', 'failed')->where('status', '!=', 'cancelled')->count()
            + Order::query()->where('status', 'cancelled')->where('payment_status', 'paid')->count();
        $newQuotations = Quotation::query()->where('status', 'new')->count();
        $pendingReviews = ProductReview::query()->where('status', 'pending')->count();
        $newMessages = ContactMessage::query()->where('status', 'new')->count();

        $lowStock = Product::query()
            ->where('status', Product::STATUS_PUBLISHED)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('low_stock_threshold', '>', 0)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                        ->whereColumn('stock_quantity', '>', 'reserved_quantity');
                })->orWhere(function ($q) {
                    $q->whereColumn('stock_quantity', '<=', 'reserved_quantity')->where('product_type', 'simple');
                });
            })
            ->count();

        $recentOrders = Order::query()->latest()->limit(8)->get();
        $recentQuotations = Quotation::query()->latest()->limit(5)->get();
        $activeImports = ImportBatch::query()->whereIn('status', ['pending', 'running', 'paused'])->latest()->limit(5)->get();
        $sourceCounts = SourceProduct::query()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();
        $productCounts = Product::query()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();
        $seo = $seoAudit->metrics();

        return view('admin.dashboard', compact(
            'revenueToday', 'revenue30', 'ordersToday', 'pendingPayment', 'needsAttention',
            'newQuotations', 'pendingReviews', 'newMessages', 'lowStock', 'recentOrders',
            'recentQuotations', 'activeImports', 'sourceCounts', 'productCounts', 'seo'
        ));
    }
}
