<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view document-types');

        $query = DocumentType::orderBy('sort_order');
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

        return view('admin.document-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create document-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:500'],
            'name_fr'        => ['required', 'string', 'max:500'],
            'name_en'        => ['required', 'string', 'max:500'],
            'is_conditional' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ]);
        $data['is_conditional'] = $request->boolean('is_conditional');
        DocumentType::create($data);
        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_created'));
    }

    public function edit(DocumentType $documentType)
    {
        $this->authorize('edit document-types');

        return view('admin.document-types.edit', compact('documentType'));
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $this->authorize('edit document-types');

        $data = $request->validate([
            'name_ar'        => ['required', 'string', 'max:500'],
            'name_fr'        => ['required', 'string', 'max:500'],
            'name_en'        => ['required', 'string', 'max:500'],
            'is_conditional' => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ]);
        $data['is_conditional'] = $request->boolean('is_conditional');
        $documentType->update($data);
        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_updated'));
    }

    public function destroy(DocumentType $documentType)
    {
        $this->authorize('delete document-types');

        $documentType->delete();
        return redirect()->route('admin.document-types.index')
            ->with('success', __('admin.ref_deleted'));
    }
}
