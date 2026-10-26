<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\Governorate;
use Illuminate\Http\Request;

class DelegationController extends Controller
{
    private function gouvernoratsForSelect(): \Illuminate\Support\Collection
    {
        $col = match (app()->getLocale()) { 'fr' => 'name_fr', 'en' => 'name_en', default => 'name_ar' };
        return Governorate::orderBy('id')->pluck($col, 'id');
    }

    public function index(Request $request)
    {
        $this->authorize('view delegations');

        $query = Delegation::with('governorate')->orderBy('governorate_id')->orderBy('id');
        if ($search = $request->get('q')) {
            $query->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
        }
        if ($govId = $request->get('governorate_id')) {
            $query->where('governorate_id', $govId);
        }
        $delegations = $query->paginate(20)->appends($request->only('q', 'governorate_id'));
        $governorates = $this->gouvernoratsForSelect();
        return view('admin.delegations.index', compact('delegations', 'governorates'));
    }

    public function create()
    {
        $this->authorize('create delegations');

        $governorates = $this->gouvernoratsForSelect();
        return view('admin.delegations.create', compact('governorates'));
    }

    public function store(Request $request)
    {
        $this->authorize('create delegations');

        $data = $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_fr'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
        ]);
        Delegation::create($data);
        return redirect()->route('admin.delegations.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(Delegation $delegation)
    {
        $this->authorize('edit delegations');

        $governorates = $this->gouvernoratsForSelect();
        return view('admin.delegations.edit', compact('delegation', 'governorates'));
    }

    public function update(Request $request, Delegation $delegation)
    {
        $this->authorize('edit delegations');

        $data = $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_fr'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
        ]);
        $delegation->update($data);
        return redirect()->route('admin.delegations.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(Delegation $delegation)
    {
        $this->authorize('delete delegations');

        $delegation->delete();
        return redirect()->route('admin.delegations.index')
            ->with('success', __('admin.ref_deleted'));
    }
}
