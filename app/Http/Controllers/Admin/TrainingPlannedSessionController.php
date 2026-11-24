<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\TrainingPlannedSession;
use App\Models\TrainingRequest;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingPlannedSessionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $sessions = TrainingPlannedSession::forCentralizedTypes()
            ->withCount(['requests' => function ($q) {
                $q->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED]);
            }])
            ->orderBy('starts_at')
            ->get();

        // Wait-listed requests per centralized type (null session, not cancelled)
        $waitlist = TrainingRequest::whereIn('training_type', TrainingRequest::CENTRALIZED_TYPES)
            ->whereNull('planned_session_id')
            ->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED, TrainingRequest::STATUS_DRAFT])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->groupBy('training_type');

        return view('admin.training.planned.index', compact('sessions', 'waitlist'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? TrainingPlannedSession::STATUS_PLANNED;

        TrainingPlannedSession::create($data);

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_created'));
    }

    public function edit(TrainingPlannedSession $plannedSession): View
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        return view('admin.training.planned.edit', ['session' => $plannedSession]);
    }

    public function update(Request $request, TrainingPlannedSession $plannedSession): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $plannedSession->update($this->validated($request));

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_updated'));
    }

    public function destroy(TrainingPlannedSession $plannedSession): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        // Detach linked requests (they fall back to the wait list)
        TrainingRequest::where('planned_session_id', $plannedSession->id)->update(['planned_session_id' => null]);

        $plannedSession->delete();

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_deleted'));
    }

    /** Assign a wait-listed request to a session. */
    public function assign(Request $request): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $data = $request->validate([
            'training_request_id' => ['required', 'integer', 'exists:training_requests,id'],
            'planned_session_id'   => ['required', 'integer', 'exists:training_planned_sessions,id'],
        ]);

        $tr = TrainingRequest::findOrFail($data['training_request_id']);
        AdminScope::authorize($tr);

        /** @var TrainingPlannedSession $session */
        $session = TrainingPlannedSession::findOrFail($data['planned_session_id']);

        if ($tr->training_type !== $session->training_type) {
            return back()->withErrors(['planned_session_id' => __('training.planned_session_type_mismatch')]);
        }
        if ($session->isFull()) {
            return back()->withErrors(['planned_session_id' => __('training.planned_session_full')]);
        }

        $tr->update(['planned_session_id' => $session->id]);

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_assigned'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'training_type' => ['required', 'string', 'in:' . implode(',', TrainingRequest::CENTRALIZED_TYPES)],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['nullable', 'date', 'after:starts_at'],
            'capacity'      => ['nullable', 'integer', 'min:1'],
            'status'        => ['nullable', 'in:planned,started,completed,cancelled'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
