<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomeBannerController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::query()
            ->orderBy('position')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.home-banners.index', compact('banners'));
    }

    public function create()
    {
        $banner = new HomeBanner([
            'position' => 'main',
            'background_color' => '#ffffff',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return view('admin.home-banners.form', compact('banner'));
    }

    public function store(Request $request)
    {
        $data = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('home-banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        HomeBanner::create($data);

        $this->clearHomeBannerCache();

        return redirect()
            ->route('admin.home-banners.index')
            ->with('success', 'Banner publicado correctamente.');
    }

    public function edit(HomeBanner $homeBanner)
    {
        $banner = $homeBanner;

        return view('admin.home-banners.form', compact('banner'));
    }

    public function update(Request $request, HomeBanner $homeBanner)
    {
        $data = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            if ($homeBanner->image_path) {
                Storage::disk('public')->delete($homeBanner->image_path);
            }

            $data['image_path'] = $request->file('image')->store('home-banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $homeBanner->update($data);

        $this->clearHomeBannerCache();

        return redirect()
            ->route('admin.home-banners.index')
            ->with('success', 'Banner actualizado correctamente.');
    }

    public function destroy(HomeBanner $homeBanner)
    {
        if ($homeBanner->image_path) {
            Storage::disk('public')->delete($homeBanner->image_path);
        }

        $homeBanner->delete();

        $this->clearHomeBannerCache();

        return redirect()
            ->route('admin.home-banners.index')
            ->with('success', 'Banner eliminado correctamente.');
    }

    private function validateBanner(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:40'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'position' => ['required', 'in:main,side'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function clearHomeBannerCache(): void
    {
        Cache::forget('home_banners_active');
    }
}