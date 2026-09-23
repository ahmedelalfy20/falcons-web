<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(private ImageOptimizer $images, private AuditLogger $audit) {}

    private function guard(): void
    {
        abort_unless(request()->user()->hasPermission(Permission::ContentManage), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.content.gallery', [
            'academy' => GalleryImage::collection('academy')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'collection' => ['required', 'in:academy'],
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['image', 'max:10240'],
        ]);
        $order = (int) GalleryImage::where('collection', $data['collection'])->max('sort_order');
        foreach ($request->file('photos') as $file) {
            $s = $this->images->store($file, 'gallery', 1600, 640);
            GalleryImage::create(['collection' => $data['collection'], 'path' => $s['path'], 'thumb_path' => $s['thumb'], 'width' => $s['width'], 'height' => $s['height'], 'sort_order' => ++$order]);
        }
        $this->audit->log('content.gallery_uploaded', null, ['collection' => $data['collection'], 'count' => count($request->file('photos'))]);

        return back()->with('success', __('Photos uploaded.'));
    }

    public function destroy(GalleryImage $image)
    {
        $this->guard();
        $this->images->delete($image->path);
        $this->images->delete($image->thumb_path);
        $this->audit->log('content.gallery_deleted', $image);
        $image->delete();

        return back()->with('success', __('Photo removed.'));
    }
}
