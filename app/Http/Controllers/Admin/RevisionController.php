<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContentRevision;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:product,page,article'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $model = match ($data['type']) {
            'product' => Product::query()->findOrFail($data['id']),
            'page' => Page::query()->findOrFail($data['id']),
            'article' => Article::query()->findOrFail($data['id']),
        };

        $this->authorize($data['type'] === 'product' ? 'catalogue.view' : 'content.view');

        $revisions = ContentRevision::query()
            ->where('revisionable_type', $model->getMorphClass())
            ->where('revisionable_id', $model->getKey())
            ->with('user:id,name')
            ->latest('id')
            ->paginate(20);

        return view('admin.revisions.index', compact('model', 'revisions', 'data'));
    }
}
