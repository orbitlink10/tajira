<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('settings.manage');
        $definitions = config('settings', []);
        $values = Settings::all();
        $groups = collect($definitions)->groupBy('group', true);

        return view('admin.settings.index', compact('groups', 'values'));
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $this->authorize('settings.manage');
        $old = Settings::all();

        foreach (array_keys(config('settings', [])) as $key) {
            $definition = config('settings.'.$key);
            $inputKey = 'settings.'.$key;

            $value = match ($definition['type'] ?? 'string') {
                'boolean' => $request->boolean($inputKey),
                'integer' => $request->filled($inputKey) ? (int) $request->input($inputKey) : null,
                'decimal' => $request->filled($inputKey) ? (float) $request->input($inputKey) : null,
                default => $request->input($inputKey),
            };

            Settings::set($key, $value, $request->user()->id);
        }

        $audit->log('settings.updated', null, $old, Settings::all());

        return back()->with('success', 'Business settings updated.');
    }
}
