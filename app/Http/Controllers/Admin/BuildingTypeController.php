<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingType;
use Illuminate\Http\Request;

class BuildingTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view building-types');

        $query = BuildingType::withCount('activities')->orderBy('sort_order');
        if ($search = $request->get('q')) {
            $query->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
        }
        $buildingTypes = $query->paginate(15)->appends($request->only('q'));
        return view('admin.building-types.index', compact('buildingTypes'));
    }

    public function create()
    {
        $this->authorize('create building-types');

        return view('admin.building-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create building-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_fr'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'has_activities' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ]);
        $data['has_activities'] = $request->boolean('has_activities');
        BuildingType::create($data);
        return redirect()->route('admin.building-types.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(BuildingType $buildingType)
    {
        $this->authorize('edit building-types');

        return view('admin.building-types.edit', compact('buildingType'));
    }

    public function update(Request $request, BuildingType $buildingType)
    {
        $this->authorize('edit building-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_fr'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'has_activities' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ]);
        $data['has_activities'] = $request->boolean('has_activities');
        $buildingType->update($data);
        return redirect()->route('admin.building-types.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(BuildingType $buildingType)
    {
        $this->authorize('delete building-types');

        $buildingType->delete();
        return redirect()->route('admin.building-types.index')
            ->with('success', __('admin.ref_deleted'));
    }
}
