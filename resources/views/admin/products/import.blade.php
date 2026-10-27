@extends('layouts.admin')

@section('title', 'CSV import')
@section('heading', 'Product CSV import')
@section('subheading', 'Owner-supplied data with a dry run and row-level validation.')

@section('content')
    <div class="grid gap-4 xl:grid-cols-[1fr_340px]">
        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Upload CSV</h2>
            <form method="post" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <div>
                    <label for="file" class="field-label">CSV file</label>
                    <input id="file" type="file" name="file" accept=".csv,text/csv" required class="field-input">
                </div>
                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="dry_run" value="1" class="mt-0.5 h-4 w-4 rounded" checked>
                    <span>Dry run only — validate rows without creating products.</span>
                </label>
                <button class="btn btn-primary" type="submit">Run import</button>
            </form>

            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                <p class="font-semibold">Import rules</p>
                <ul class="mt-1 list-disc space-y-1 pl-4">
                    <li>Existing SKUs are never overwritten. They are reported as errors.</li>
                    <li>Products are created as drafts with no copied descriptions and no approved price unless a price is present in the CSV.</li>
                    <li>Brands must exist before import. Categories are matched by name.</li>
                    <li>Dry-run and import results are saved as an import batch for review.</li>
                </ul>
            </div>
        </section>

        <aside class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Required columns</h2>
            <p class="mt-2 text-sm text-ink-600">At minimum, include a <code>name</code> column. Recommended columns:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink-600">
                <li>name</li>
                <li>sku</li>
                <li>brand</li>
                <li>category</li>
                <li>model</li>
                <li>mpn</li>
                <li>price_kes</li>
                <li>stock</li>
            </ul>
            <a href="{{ route('admin.products.export') }}" class="btn btn-secondary mt-4 w-full">Download export template</a>
        </aside>
    </div>
@endsection
