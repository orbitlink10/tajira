@extends('layouts.admin')
@section('title', 'Pages')
@section('heading', 'Pages')
@section('content')
    <div class="tajira-pages-heading"><h2 class="tajira-page-title">Pages</h2><p>Manage site pages and published content.</p></div>
    <section class="card overflow-hidden tajira-pages-list" x-data="{ selectAll: false }">
        <div class="tajira-pages-list-head"><h2>Post List</h2>@can('content.manage')<a class="btn tajira-add-page" href="{{ route('admin.pages.create') }}">+ Add Page</a>@endcan</div>
        @can('content.manage')
            <form id="bulk-pages-form" class="tajira-pages-bulk" method="post" action="{{ route('admin.pages.bulk') }}" onsubmit="return confirm('Apply this action to the selected pages?');">
                @csrf
                <label class="sr-only" for="page-bulk-action">Bulk action</label>
                <select id="page-bulk-action" name="action" required><option value="">Bulk actions</option><option value="draft">Move to draft</option><option value="published">Publish</option><option value="archived">Archive</option><option value="delete">Delete</option></select>
                <button type="submit" class="btn btn-primary">Apply</button>
            </form>
        @endcan
        <div class="overflow-x-auto"><table class="table-admin tajira-pages-table">
            <thead><tr><th>@can('content.manage')<input type="checkbox" x-model="selectAll" aria-label="Select all pages on this page" @change="$root.querySelectorAll('[data-page-select]').forEach(box => box.checked = selectAll)">@endcan</th><th>No.</th><th>Image</th><th>Title</th><th>Alt Text</th><th>Type</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td>@can('content.manage')<input type="checkbox" data-page-select name="ids[]" value="{{ $page->id }}" form="bulk-pages-form" aria-label="Select {{ $page->title }}" @change="selectAll = [...$root.querySelectorAll('[data-page-select]')].every(box => box.checked)">@endcan</td>
                        <td>{{ $pages->firstItem() + $loop->index }}</td><td><span class="tajira-page-image">No Image</span></td>
                        <td>{{ $page->title }}<small>/pages/{{ $page->slug }}<br>{{ ucwords(str_replace('_', ' ', $page->status)) }} &middot; Updated {{ $page->updated_at->diffForHumans() }}<br>Footer: {{ $page->show_in_footer ? 'Yes' : 'No' }}</small></td>
                        <td>N/A</td><td>Page</td>
                        <td><div class="tajira-page-actions"><a class="preview" href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener">Preview</a>@can('content.manage')<a class="update" href="{{ route('admin.pages.edit', $page) }}">Update</a><form method="post" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?');">@csrf @method('delete')<button class="delete" type="submit">Delete</button></form>@endcan</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-ink-500">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
    <div class="mt-6">{{ $pages->links() }}</div>
@endsection
