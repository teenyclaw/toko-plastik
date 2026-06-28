<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $keys = config('toko-plastik.store_settings', []);
        $settings = StoreSetting::query()->pluck('value', 'key');

        $values = [];
        foreach ($keys as $key => $meta) {
            $values[$key] = $settings[$key] ?? ($meta['default'] ?? '');
        }

        return view('admin.settings.index', compact('keys', 'values'));
    }

    public function update(Request $request): RedirectResponse
    {
        $keys = array_keys(config('toko-plastik.store_settings', []));

        $rules = [];
        foreach ($keys as $key) {
            $rules[$key] = ['nullable', 'string', 'max:1000'];
        }

        $data = $request->validate($rules);

        foreach ($keys as $key) {
            StoreSetting::set($key, (string) ($data[$key] ?? ''));
        }

        return back()->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
