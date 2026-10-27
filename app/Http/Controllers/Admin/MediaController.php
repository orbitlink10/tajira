<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\AuditLogger;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index()
    {
        $this->authorize('media.manage');
        $media = Media::query()->latest()->paginate(30);

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request, ImageService $images, AuditLogger $audit)
    {
        $this->authorize('media.manage');
        $request->validate([
            'image' => ['required', 'image', 'max:8192', 'mimes:jpg,jpeg,png,webp'],
            'alt' => ['required', 'string', 'max:250'],
            'title' => ['nullable', 'string', 'max:250'],
        ]);

        $file = $request->file('image');
        $images->assertSafe($file);
        $directory = 'media/'.now()->format('Y/m');
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6));
        $result = $images->convertToWebp($file->getRealPath(), $directory, $basename);

        $medium = Media::query()->create([
            'disk' => 'public',
            'path' => $result['path'],
            'filename' => $basename.'.webp',
            'mime' => $result['mime'],
            'size' => $result['size'],
            'width' => $result['width'],
            'height' => $result['height'],
            'alt' => $request->input('alt'),
            'title' => $request->input('title'),
            'uploaded_by' => $request->user()->id,
        ]);

        $audit->log('media.uploaded', $medium);

        return back()->with('success', 'Image optimised and uploaded.');
    }

    public function destroy(Media $medium, AuditLogger $audit)
    {
        $this->authorize('media.manage');

        if ($medium->disk === 'public' && Storage::disk('public')->exists($medium->path)) {
            Storage::disk('public')->delete($medium->path);
        }

        $audit->log('media.deleted', $medium);
        $medium->delete();

        return back()->with('success', 'Media deleted. Check products or content that referenced this path.');
    }
}
