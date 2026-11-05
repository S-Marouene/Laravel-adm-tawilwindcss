<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin management of notification message templates.
 * Only super-admin and admin roles can access this.
 */
class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        $templates = NotificationTemplate::orderBy('event_key')->get();

        return view('admin.notification-templates.index', compact('templates'));
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        return view('admin.notification-templates.edit', [
            'template' => $notificationTemplate,
        ]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title_fr' => ['required', 'string', 'max:255'],
            'body_fr'  => ['required', 'string', 'max:1000'],
            'title_ar' => ['required', 'string', 'max:255'],
            'body_ar'  => ['required', 'string', 'max:1000'],
            'level'    => ['required', 'in:info,success,warning,danger'],
        ]);

        $notificationTemplate->update($validated);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Modèle de notification mis à jour.');
    }

    public function reset(NotificationTemplate $notificationTemplate): RedirectResponse
    {
        // Re-seed just this template from the seeder defaults
        $seeder = new \Database\Seeders\NotificationTemplateSeeder();
        $seeder->run();

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'Tous les modèles ont été réinitialisés aux valeurs par défaut.');
    }
}
