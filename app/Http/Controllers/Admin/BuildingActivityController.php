<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingActivity;
use App\Models\BuildingType;
use Illuminate\Http\Request;

class BuildingActivityController extends Controller
{
    private function typesForSelect(): \Illuminate\Support\Collection
    {
        $col = match (app()->getLocale()) { 'fr' => 'name_fr', 'en' => 'name_en', default => 'name_ar' };
        return BuildingType::orderBy('sort_order')->pluck($col, 'id');
    }

    public function index(Request $request)
    {
        $this->authorize('view building-activities');

        $query = BuildingActivity::with('buildingType')->orderBy('building_type_id')->orderBy('sort_order');
        if ($search = $request->get('q')) {
            $query->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
        }
        if ($typeId = $request->get('building_type_id')) {
            $query->where('building_type_id', $typeId);
        }
        $activities = $query->paginate(20)->appends($request->only('q', 'building_type_id'));
        $buildingTypes = $this->typesForSelect();
        return view('admin.building-activities.index', compact('activities', 'buildingTypes'));
    }

    public function create()
    {
        $this->authorize('create building-activities');

        $buildingTypes = $this->typesForSelect();
        return view('admin.building-activities.create', compact('buildingTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create building-activities');

        $data = $request->validate([
            'building_type_id' => ['required', 'integer', 'exists:building_types,id'],
            'name_ar'          => ['required', 'string', 'max:255'],
            'name_fr'          => ['required', 'string', 'max:255'],
            'name_en'          => ['required', 'string', 'max:255'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);
        BuildingActivity::create($data);
        return redirect()->route('admin.building-activities.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(BuildingActivity $buildingActivity)
    {
        $this->authorize('edit building-activities');

        $buildingTypes = $this->typesForSelect();
        return view('admin.building-activities.edit', compact('buildingActivity', 'buildingTypes'));
    }

    public function update(Request $request, BuildingActivity $buildingActivity)
    {
        $this->authorize('edit building-activities');

        $data = $request->validate([
            'building_type_id' => ['required', 'integer', 'exists:building_types,id'],
            'name_ar'          => ['required', 'string', 'max:255'],
            'name_fr'          => ['required', 'string', 'max:255'],
            'name_en'          => ['required', 'string', 'max:255'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);
        $buildingActivity->update($data);
        return redirect()->route('admin.building-activities.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(BuildingActivity $buildingActivity)
    {
        $this->authorize('delete building-activities');

        $buildingActivity->delete();
        return redirect()->route('admin.building-activities.index')
            ->with('success', __('admin.ref_deleted'));
    }
}
