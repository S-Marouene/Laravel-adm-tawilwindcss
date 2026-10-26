<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view governorates');
        $query = Governorate::withCount('delegations')->orderBy('id');
        if ($search = $request->get('q')) {
            $query->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
        }
        $governorates = $query->paginate(15)->appends($request->only('q'));
        return view('admin.governorates.index', compact('governorates'));
    }

    public function create()
    {
        $this->authorize('create governorates');
        return view('admin.governorates.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create governorates');
        $data = $request->validate([
            'id'      => ['required', 'integer', 'min:1', 'max:999', 'unique:governorates,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]);
        Governorate::create($data);
        return redirect()->route('admin.governorates.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(Governorate $governorate)
    {
        $this->authorize('edit governorates');
        return view('admin.governorates.edit', compact('governorate'));
    }

    public function update(Request $request, Governorate $governorate)
    {
        $this->authorize('edit governorates');
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_fr' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]);
        $governorate->update($data);
        return redirect()->route('admin.governorates.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(Governorate $governorate)
    {
        $this->authorize('delete governorates');
        $governorate->delete();
        return redirect()->route('admin.governorates.index')
            ->with('success', __('admin.ref_deleted'));
    }
}
