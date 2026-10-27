@extends('layouts.admin')

@section('title', $article->exists ? 'Edit article' : 'New article')
@section('heading', $article->exists ? 'Edit article' : 'New article')
@section('subheading', 'Separate editorial drafting from factual product verification.')

@section('content')
    <form method="post" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data" class="grid gap-4 xl:grid-cols-[1fr_340px]">
        @csrf
        @if($article->exists) @method('put') @endif

        <div class="space-y-4">
            <section class="card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label for="title" class="field-label">Title</label><input id="title" name="title" value="{{ old('title', $article->title) }}" required class="field-input"></div>
                    <div><label for="slug" class="field-label">Slug</label><input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" class="field-input"></div>
                    <div>
                        <label for="type" class="field-label">Type</label>
                        <select id="type" name="type" class="field-input">
                            @foreach(['guide' => 'Guide', 'buying' => 'Buying guide', 'comparison' => 'Comparison', 'setup' => 'Setup guide', 'compatibility' => 'Compatibility', 'news' => 'News'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $article->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="article_category_id" class="field-label">Category</label>
                        <select id="article_category_id" name="article_category_id" class="field-input">
                            <option value="">No category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('article_category_id', $article->article_category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="author_name" class="field-label">Author</label><input id="author_name" name="author_name" value="{{ old('author_name', $article->author_name) }}" class="field-input"></div>
                    <div class="sm:col-span-2"><label for="excerpt" class="field-label">Excerpt</label><textarea id="excerpt" name="excerpt" rows="2" class="field-input">{{ old('excerpt', $article->excerpt) }}</textarea></div>
                    <div class="sm:col-span-2">
                        <label for="body" class="field-label">Body (HTML, sanitised)</label>
                        <textarea id="body" name="body" rows="20" class="field-input font-mono text-xs">{{ old('body', $article->body) }}</textarea>
                        <p class="mt-1 text-xs text-ink-500">Use real HTML links for internal linking. AI-assisted drafts must be reviewed before publication; product facts require verification.</p>
                    </div>
                </div>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Mentioned products</h2>
                <p class="mt-1 text-xs text-ink-500">Selected products appear as related cards on the guide. They are not automatically endorsed.</p>
                <select name="products[]" multiple size="10" class="field-input mt-3">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(in_array($product->id, old('products', $selectedProducts)))>{{ $product->name }} — {{ $product->sku }}</option>
                    @endforeach
                </select>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Publication</h2>
                <div class="mt-3 space-y-3">
                    <div>
                        <label for="status" class="field-label">Status</label>
                        <select id="status" name="status" class="field-input">
                            @foreach(['draft', 'pending_review', 'published', 'archived'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $article->status) === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="published_at" class="field-label">Published at</label><input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="field-input"></div>
                    <div><label for="reading_minutes" class="field-label">Reading minutes</label><input id="reading_minutes" type="number" min="1" name="reading_minutes" value="{{ old('reading_minutes', $article->reading_minutes) }}" class="field-input" placeholder="Auto-estimated if blank"></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_indexable" value="1" class="h-4 w-4 rounded" @checked(old('is_indexable', $article->is_indexable))> Indexable</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded" @checked(old('is_featured', $article->is_featured))> Featured</label>
                </div>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Hero image</h2>
                @if($article->hero_image_path)
                    <img src="{{ asset('storage/'.$article->hero_image_path) }}" alt="" class="mt-2 h-32 w-full rounded object-cover">
                @endif
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="field-input mt-2">
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">SEO</h2>
                <div class="mt-3 space-y-3">
                    <div><label for="meta_title" class="field-label">Meta title</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" class="field-input"></div>
                    <div><label for="meta_description" class="field-label">Meta description</label><textarea id="meta_description" name="meta_description" rows="3" class="field-input">{{ old('meta_description', $article->meta_description) }}</textarea></div>
                    <div><label for="canonical_override" class="field-label">Canonical override</label><input id="canonical_override" name="canonical_override" value="{{ old('canonical_override', $article->canonical_override) }}" class="field-input"></div>
                    <div><label for="robots" class="field-label">Robots override</label><input id="robots" name="robots" value="{{ old('robots', $article->robots) }}" class="field-input"></div>
                </div>
            </section>

            <button class="btn btn-primary w-full">Save article</button>
            @if($article->exists)
                <a href="{{ route('admin.revisions.index', ['type' => 'article', 'id' => $article->id]) }}" class="btn btn-secondary w-full">Revision history</a>
            @endif
            <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary w-full">Cancel</a>
        </aside>
    </form>
@endsection
