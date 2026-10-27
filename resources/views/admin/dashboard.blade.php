@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Operations dashboard')
@section('subheading', 'Sales, stock and content signals across Tajira.')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Paid sales today</p>
            <p class="mt-1 text-2xl font-bold text-ink-900">{{ money((int) $revenueToday) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $ordersToday }} order{{ $ordersToday === 1 ? '' : 's' }} placed today</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Paid sales · 30 days</p>
            <p class="mt-1 text-2xl font-bold text-ink-900">{{ money((int) $revenue30) }}</p>
            <p class="mt-1 text-xs text-ink-500">Based on confirmed payments only</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Awaiting payment</p>
            <p class="mt-1 text-2xl font-bold text-ink-900">{{ $pendingPayment }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $needsAttention }} failed payment attempt(s) needing review</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Low or out of stock</p>
            <p class="mt-1 text-2xl font-bold text-ink-900">{{ $lowStock }}</p>
            <p class="mt-1 text-xs text-ink-500">Products at or below their threshold</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.quotations.index') }}" class="card p-4 hover:border-brand-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">New quotations</p>
            <p class="mt-1 text-xl font-bold text-ink-900">{{ $newQuotations }}</p>
        </a>
        <a href="{{ route('admin.reviews.index') }}" class="card p-4 hover:border-brand-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Reviews awaiting moderation</p>
            <p class="mt-1 text-xl font-bold text-ink-900">{{ $pendingReviews }}</p>
        </a>
        <a href="{{ route('admin.contact-messages.index') }}" class="card p-4 hover:border-brand-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">New contact messages</p>
            <p class="mt-1 text-xl font-bold text-ink-900">{{ $newMessages }}</p>
        </a>
        <a href="{{ route('admin.seo.index') }}" class="card p-4 hover:border-brand-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">SEO issues</p>
            <p class="mt-1 text-xl font-bold text-ink-900">{{ $seo['missing_meta_description'] + $seo['orphan_products'] + $seo['missing_images'] }}</p>
        </a>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3">
                <h2 class="text-sm font-bold text-ink-900">Recent orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">All orders</a>
            </div>
            @if($recentOrders->isEmpty())
                <p class="p-5 text-sm text-ink-500">No orders yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-admin">
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                <tr>
                                    <td class="font-semibold text-ink-900">{{ $order->number }}<span class="block text-xs font-normal text-ink-400">{{ $order->created_at->format('j M, H:i') }}</span></td>
                                    <td>{{ $order->customer_name }}<span class="block text-xs text-ink-400">{{ $order->county }}</span></td>
                                    <td><span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span></td>
                                    <td><span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ $order->paymentStatusLabel() }}</span></td>
                                    <td class="whitespace-nowrap">{{ $order->formattedTotal() }}</td>
                                    <td><a href="{{ route('admin.orders.show', $order->number) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card p-4">
            <h2 class="text-sm font-bold text-ink-900">Catalogue status</h2>
            <dl class="mt-3 space-y-2 text-sm">
                @foreach(['draft' => 'Draft', 'pending_review' => 'Pending review', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label)
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-600">{{ $label }}</dt>
                        <dd class="font-semibold text-ink-900">{{ $productCounts[$key] ?? 0 }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-4 border-t border-ink-100 pt-3">
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-500">Discovery staging</h3>
                <dl class="mt-2 space-y-1.5 text-sm">
                    @forelse($sourceCounts as $status => $count)
                        <div class="flex justify-between"><dt class="text-ink-600">{{ ucwords(str_replace('_', ' ', $status)) }}</dt><dd class="font-semibold">{{ $count }}</dd></div>
                    @empty
                        <p class="text-xs text-ink-500">No discovery records yet.</p>
                    @endforelse
                </dl>
                <a href="{{ route('admin.imports.index') }}" class="mt-2 inline-block text-xs font-semibold text-brand-700">Open reconciliation dashboard →</a>
            </div>
        </section>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3">
                <h2 class="text-sm font-bold text-ink-900">Recent quotations</h2>
                <a href="{{ route('admin.quotations.index') }}" class="text-xs font-semibold text-brand-700">View all</a>
            </div>
            @if($recentQuotations->isEmpty())
                <p class="p-5 text-sm text-ink-500">No quotation requests yet.</p>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach($recentQuotations as $quotation)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <a href="{{ route('admin.quotations.show', $quotation->number) }}" class="text-sm font-semibold text-ink-900 hover:text-brand-700">{{ $quotation->number }}</a>
                                <p class="text-xs text-ink-500">{{ $quotation->name }} · {{ $quotation->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="badge bg-ink-100 text-ink-700">{{ $quotation->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card p-4">
            <h2 class="text-sm font-bold text-ink-900">Import and SEO attention</h2>
            @if($activeImports->isEmpty())
                <p class="mt-2 text-sm text-ink-500">No import batches are currently running.</p>
            @else
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach($activeImports as $batch)
                        <li>
                            <a href="{{ route('admin.imports.show', $batch) }}" class="font-medium text-brand-700 hover:text-brand-900">{{ $batch->reference }}</a>
                            <span class="text-xs text-ink-500">{{ $batch->statusLabel() }} · {{ $batch->processed_rows }}/{{ $batch->total_rows }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['missing_meta_description'] }}</span><span class="text-xs text-ink-500">Missing meta descriptions</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['orphan_products'] }}</span><span class="text-xs text-ink-500">Orphan products</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['missing_images'] }}</span><span class="text-xs text-ink-500">Missing images</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['broken_internal_links'] }}</span><span class="text-xs text-ink-500">Broken links</span></a>
            </div>
            <p class="mt-3 text-xs text-ink-500">SEO checks are diagnostics, not guarantees of ranking or rich results.</p>
        </section>
    </div>
@endsection
