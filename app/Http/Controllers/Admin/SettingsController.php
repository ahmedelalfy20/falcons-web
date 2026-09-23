<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    /** Text fields per settings group (each also has an _ar twin where marked). */
    public const GROUPS = [
        'hero' => ['badge' => true, 'title' => true, 'subtitle' => true, 'primary_cta' => true, 'secondary_cta' => true],
        'founder' => ['name' => true, 'title' => true, 'quote' => true, 'bio_1' => true, 'bio_2' => true, 'mission_quote' => true],
        'about' => ['story_1' => true, 'story_2' => true, 'story_3' => true, 'quote' => true],
        'contact' => ['whatsapp' => false, 'whatsapp_message' => true, 'register_url' => false, 'email' => false],
        'app' => ['play_url' => false, 'appstore_url' => false],
        'social' => ['telegram' => false, 'facebook' => false, 'instagram' => false, 'tiktok' => false, 'youtube' => false],
        'stats' => ['active_members' => false, 'countries' => false, 'students_trained' => false, 'satisfaction_rate' => false, 'years_experience' => false],
    ];

    public function edit()
    {
        $this->authorize('manage-settings');

        return view('admin.settings', ['groups' => self::GROUPS]);
    }

    public function update(Request $request, AuditLogger $audit, ImageOptimizer $images)
    {
        $this->authorize('manage-settings');
        $rules = [
            'founder_image' => ['nullable', 'image', 'max:6144'],
            'free_courses_code' => ['nullable', 'string', 'min:4', 'max:64'],
            'free_courses_enabled' => ['sometimes', 'boolean'],
            'vision' => ['nullable', 'string', 'max:3000'], 'vision_ar' => ['nullable', 'string', 'max:3000'],
            'mission' => ['nullable', 'string', 'max:3000'], 'mission_ar' => ['nullable', 'string', 'max:3000'],
        ];
        foreach (self::GROUPS as $group => $fields) {
            foreach ($fields as $field => $translatable) {
                $rule = match (true) {
                    $group === 'stats' => ['nullable', 'integer', 'min:0', 'max:100000000'],
                    $group === 'social', $group === 'app', $field === 'register_url' => ['nullable', 'url:https,http', 'max:300'],
                    $field === 'email' => ['nullable', 'email', 'max:190'],
                    $field === 'whatsapp' => ['nullable', 'regex:/^\+?[0-9 ]{8,20}$/'],
                    default => ['nullable', 'string', 'max:3000'],
                };
                $rules["{$group}.{$field}"] = $rule;
                if ($translatable) {
                    $rules["{$group}.{$field}_ar"] = ['nullable', 'string', 'max:3000'];
                }
            }
        }
        $data = $request->validate($rules);

        foreach (self::GROUPS as $group => $fields) {
            $current = Setting::get($group, []);
            $incoming = $data[$group] ?? [];
            if ($group === 'stats') {
                $incoming = array_map(fn ($v) => $v === null ? null : (int) $v, $incoming);
            }
            Setting::put($group, array_merge($current, $incoming));
        }

        $lines = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $t))));
        Setting::put('vision', [
            'vision' => $lines($data['vision'] ?? ''), 'vision_ar' => $lines($data['vision_ar'] ?? ''),
            'mission' => $lines($data['mission'] ?? ''), 'mission_ar' => $lines($data['mission_ar'] ?? ''),
        ]);

        if ($request->hasFile('founder_image')) {
            $stored = $images->store($request->file('founder_image'), 'founder', 1200, null, 85);
            Setting::put('founder', array_merge(Setting::get('founder', []), ['image' => $stored['path']]));
        }

        $free = Setting::get('free_courses', []);
        $free['enabled'] = $request->boolean('free_courses_enabled');
        if (! empty($data['free_courses_code'])) {
            $free['invite_code_hash'] = Hash::make($data['free_courses_code']);
        }
        Setting::put('free_courses', $free);

        $audit->log('settings.updated', null, ['groups' => array_keys(self::GROUPS), 'invite_code_changed' => ! empty($data['free_courses_code'])]);

        return back()->with('success', __('Settings saved.'));
    }
}
