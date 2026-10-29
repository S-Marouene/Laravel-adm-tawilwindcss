<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingType;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view document-types');

        $query = DocumentType::with('buildingTypes')->orderBy('sort_order');
        if ($search = $request->get('q')) {
            $query->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_fr', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
        }
        $documentTypes = $query->paginate(15)->appends($request->only('q'));
        return view('admin.document-types.index', compact('documentTypes'));
    }

    public function create()
    {
        $this->authorize('create document-types');

        return view('admin.document-types.create', [
            'serviceOptions' => $this->serviceOptions(),
            'buildingTypes'  => BuildingType::forSelect(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create document-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:500'],
            'name_fr'        => ['required', 'string', 'max:500'],
            'name_en'        => ['required', 'string', 'max:500'],
            'service_keys'   => ['required', 'array', 'min:1'],
            'service_keys.*' => ['in:prevention,preventive-presence'],
            'is_conditional' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'template_file'  => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'building_types'   => ['nullable', 'array'],
            'building_types.*' => ['integer', 'exists:building_types,id'],
        ]);
        $data['is_conditional'] = $request->boolean('is_conditional');
        $data['services'] = $data['service_keys'];
        $data['service_key'] = $data['service_keys'][0]; // legacy single-service column (first value)
        unset($data['service_keys']);

        $documentType = DocumentType::create($data);

        $this->storeTemplate($documentType, $request);
        $this->syncBuildingTypes($documentType, $data['service_keys'], $request);

        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(DocumentType $documentType)
    {
        $this->authorize('edit document-types');

        $documentType->load('buildingTypes');

        return view('admin.document-types.edit', [
            'documentType'   => $documentType,
            'serviceOptions' => $this->serviceOptions(),
            'buildingTypes'  => BuildingType::forSelect(),
        ]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $this->authorize('edit document-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:500'],
            'name_fr'        => ['required', 'string', 'max:500'],
            'name_en'        => ['required', 'string', 'max:500'],
            'service_keys'   => ['required', 'array', 'min:1'],
            'service_keys.*' => ['in:prevention,preventive-presence'],
            'is_conditional' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'template_file'  => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'remove_template'  => ['boolean'],
            'building_types'   => ['nullable', 'array'],
            'building_types.*' => ['integer', 'exists:building_types,id'],
        ]);
        $data['is_conditional'] = $request->boolean('is_conditional');
        $data['services'] = $data['service_keys'];
        $data['service_key'] = $data['service_keys'][0]; // legacy single-service column (first value)
        unset($data['service_keys']);

        $documentType->update($data);

        // Replace the official form if a new one is uploaded, or delete it on request
        if ($request->boolean('remove_template')) {
            $this->deleteTemplateFile($documentType);
        }
        $this->storeTemplate($documentType, $request);

        $this->syncBuildingTypes($documentType, $data['services'], $request);

        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(DocumentType $documentType)
    {
        $this->authorize('delete document-types');

        $this->deleteTemplateFile($documentType);
        $documentType->delete();

        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_deleted'));
    }

    // =========================================================================
    // Official form (template) — file management helpers
    // =========================================================================

    private function storeTemplate(DocumentType $documentType, Request $request): void
    {
        if (! $request->hasFile('template_file')) {
            return;
        }

        $file = $request->file('template_file');
        if (! $file->isValid()) {
            return;
        }

        // Replace: delete the previous form first
        $this->deleteTemplateFile($documentType);

        $path = $file->store('document-templates', 'local');

        $documentType->forceFill([
            'template_path' => $path,
            'template_name' => $file->getClientOriginalName(),
        ])->save();
    }

    private function deleteTemplateFile(DocumentType $documentType): void
    {
        if ($documentType->template_path && Storage::disk('local')->exists($documentType->template_path)) {
            Storage::disk('local')->delete($documentType->template_path);
        }

        $documentType->forceFill([
            'template_path' => null,
            'template_name' => null,
        ])->save();
    }

    public function downloadTemplate(DocumentType $documentType)
    {
        $this->authorize('view document-types');

        if (! $documentType->template_path) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $documentType->template_path,
            $documentType->template_name ?: ($documentType->name . '.' . $documentType->templateExtension())
        );
    }

    /** Building-type scope only matters for the Prevention flow:
     *  visible when « Prevention » is checked, reset when it is not. */
    private function syncBuildingTypes(DocumentType $documentType, array $serviceKeys, Request $request): void
    {
        $ids = in_array(DocumentType::SERVICE_PREVENTION, $serviceKeys, true)
            ? ($request->input('building_types', []) ?? [])
            : [];

        $documentType->buildingTypes()->sync(array_map('intval', $ids));
    }

    /** [key => localized label] options for the service select. */
    private function serviceOptions(): array
    {
        $keys = [DocumentType::SERVICE_PREVENTION, DocumentType::SERVICE_PREVENTIVE_PRESENCE];
        $labels = __('admin.service_labels');
        $options = [];
        foreach ($keys as $key) {
            $options[$key] = $labels[$key] ?? $key;
        }

        return $options;
    }
}
