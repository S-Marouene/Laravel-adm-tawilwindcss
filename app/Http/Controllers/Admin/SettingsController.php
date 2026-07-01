<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Show the settings management page.
     */
    public function index()
    {
        $settings = Setting::getGrouped();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the application settings.
     */
    public function update(Request $request)
    {
        $settings = Setting::all()->keyBy('key');

        foreach ($settings as $setting) {
            $key = $setting->key;

            // Handle file uploads
            if ($setting->type === 'image' && $request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('settings', 'public');
                $setting->update(['value' => $path]);
                continue;
            }

            // Handle boolean checkboxes (they won't be present if unchecked)
            if ($setting->type === 'boolean') {
                $setting->update(['value' => $request->boolean($key) ? '1' : '0']);
                continue;
            }

            // Handle password fields - only update if a new value is provided
            if ($setting->type === 'password') {
                if ($request->filled($key)) {
                    $setting->update(['value' => $request->input($key)]);
                }
                continue;
            }

            // Handle regular fields
            if ($request->has($key)) {
                $setting->update(['value' => $request->input($key)]);
            }
        }

        // Clear cached settings if any cache driver is used
        cache()->forget('app_settings');

        ActivityLogger::updated('application settings', new Setting(), [
            'updated_keys' => $settings->pluck('key')->toArray(),
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', __('admin.settings_updated'));
    }

    /**
     * Remove an uploaded setting image.
     */
    public function removeImage(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        if ($setting->value) {
            Storage::disk('public')->delete($setting->value);
            $setting->update(['value' => '']);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', __('admin.image_removed'));
    }

    /**
     * Test the email configuration by sending a test email.
     */
    public function testMail(Request $request)
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        try {
            $toEmail = $request->input('test_email');
            $appName = setting('app_name', config('app.name'));

            \Illuminate\Support\Facades\Mail::raw(
                __('This is a test email from :app. Your email configuration is working correctly!', ['app' => $appName]),
                function ($message) use ($toEmail, $appName) {
                    $message->to($toEmail)
                        ->subject(__('Test Email - :app', ['app' => $appName]));
                }
            );

            return redirect()->route('admin.settings.index')
                ->with('success', __('admin.mail_test_success', ['email' => $toEmail]));
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', __('admin.mail_test_error') . ' ' . $e->getMessage());
        }
    }
}
