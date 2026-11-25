<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\TrainingPlannedSession;
use App\Models\TrainingRequest;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingPlannedSessionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize(TrainingPlannedSession::PERM_VIEW);
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $sessions       = TrainingPlannedSession::forCentralizedTypes()->orderBy('starts_at')->get();
        $sessionsByType = $sessions->groupBy('training_type');

        // Wait-listed requests per centralized type (null session, not cancelled).
        // Regional managers only see requests from their own territories.
        $waitlist = AdminScope::apply(
            TrainingRequest::query()
                ->whereIn('training_type', TrainingRequest::CENTRALIZED_TYPES)
                ->whereNull('planned_session_id')
                ->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED, TrainingRequest::STATUS_DRAFT])
                ->with(['governorate', 'delegation', 'participants'])
                ->latest(),
        )
            ->get()
            ->groupBy('training_type');

        // A company request represents one person per participant row.
        $waitlistPeopleCount = $waitlist->map(fn ($requests) => $requests->sum(
            fn (TrainingRequest $tr) => $tr->isCompany() ? max(1, $tr->participants->count()) : 1
        ));

        $governorates         = Governorate::forSelect();
        $defaultGovernorateId = Governorate::benArousId();

        return view('admin.training.planned.index', compact(
            'sessions', 'sessionsByType', 'waitlist', 'waitlistPeopleCount',
            'governorates', 'defaultGovernorateId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize(TrainingPlannedSession::PERM_CREATE);
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $data = $this->normalizeDates($this->validated($request));
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? TrainingPlannedSession::STATUS_PLANNED;
        $data['governorate_id'] = $data['governorate_id'] ?? Governorate::benArousId();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        // Wait-listed requests of the same type are attached at creation time.
        $assignIds = $this->validatedWaitlistIds($request, $data['training_type']);

        DB::transaction(function () use ($data, $assignIds) {
            $session = TrainingPlannedSession::create($data);

            if ($assignIds) {
                TrainingRequest::whereIn('id', $assignIds)
                    ->update(['planned_session_id' => $session->id]);
            }
        });

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_created'));
    }

    public function edit(TrainingPlannedSession $plannedSession): View
    {
        $this->authorize(TrainingPlannedSession::PERM_EDIT);
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        return view('admin.training.planned.edit', [
            'session'              => $plannedSession,
            'governorates'         => Governorate::forSelect(),
            'defaultGovernorateId' => Governorate::benArousId(),
        ]);
    }

    public function update(Request $request, TrainingPlannedSession $plannedSession): RedirectResponse
    {
        $this->authorize(TrainingPlannedSession::PERM_EDIT);
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $data = $this->normalizeDates($this->validated($request));
        $data['governorate_id'] = $data['governorate_id'] ?? Governorate::benArousId();
        // An unchecked checkbox is absent from the payload; keep the current
        // value when no explicit field was submitted (e.g. API-style updates).
        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $plannedSession->is_active;

        $plannedSession->update($data);

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_updated'));
    }

    public function destroy(TrainingPlannedSession $plannedSession): RedirectResponse
    {
        $this->authorize(TrainingPlannedSession::PERM_DELETE);
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
        $this->authorize(TrainingPlannedSession::PERM_EDIT);
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $data = $request->validate([
            'training_request_id' => ['required', 'integer', 'exists:training_requests,id'],
            'planned_session_id'   => ['required', 'integer', 'exists:training_planned_sessions,id'],
        ]);

        $tr = TrainingRequest::findOrFail($data['training_request_id']);
        AdminScope::authorize($tr);

        if (! $tr->isCentralized() || in_array($tr->status, [TrainingRequest::STATUS_DRAFT, TrainingRequest::STATUS_CANCELLED], true)) {
            return back()->withErrors(['training_request_id' => __('training.planned_session_not_waitlisted')]);
        }

        /** @var TrainingPlannedSession $session */
        $session = TrainingPlannedSession::findOrFail($data['planned_session_id']);

        if ($tr->training_type !== $session->training_type) {
            return back()->withErrors(['planned_session_id' => __('training.planned_session_type_mismatch')]);
        }
        // Over-capacity and closed sessions stay assignable: the centrale picks
        // the final participants from the roster after submissions close.
        if (! $session->isAssignable()) {
            return back()->withErrors(['planned_session_id' => __('training.planned_session_unavailable')]);
        }

        $tr->update(['planned_session_id' => $session->id]);

        return redirect()->route('admin.training.planned.index')
            ->with('success', __('training.planned_assigned'));
    }

    /** Planned sessions are date-only: no time is collected or displayed. */
    private function normalizeDates(array $data): array
    {
        $data['starts_at'] = Carbon::parse($data['starts_at'])->startOfDay();

        if (! empty($data['ends_at'])) {
            $data['ends_at'] = Carbon::parse($data['ends_at'])->startOfDay();
        }

        return $data;
    }

    /**
     * Requests checked in the "create" wait-list panel must still be eligible
     * at submit time: same type, no session yet, submitted and in scope.
     *
     * @return array<int, int>
     */
    private function validatedWaitlistIds(Request $request, string $trainingType): array
    {
        $validated = $request->validate([
            'assign_requests'    => ['nullable', 'array'],
            'assign_requests.*'  => ['integer', 'distinct'],
        ]);

        $ids = array_values(array_unique(
            array_map('intval', $validated['assign_requests'] ?? [])
        ));

        if (! $ids) {
            return [];
        }

        $eligible = AdminScope::apply(
            TrainingRequest::query()
                ->whereIn('id', $ids)
                ->where('training_type', $trainingType)
                ->whereNull('planned_session_id')
                ->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED, TrainingRequest::STATUS_DRAFT])
        )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($eligible) !== count($ids)) {
            throw ValidationException::withMessages([
                'assign_requests' => __('training.planned_waitlist_invalid'),
            ]);
        }

        return $ids;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'training_type'  => ['required', 'string', 'in:' . implode(',', TrainingRequest::CENTRALIZED_TYPES)],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'starts_at'      => ['required', 'date'],
            'ends_at'        => ['nullable', 'date', 'after_or_equal:starts_at'],
            'capacity'       => ['nullable', 'integer', 'min:1'],
            'status'         => ['nullable', 'in:planned,started,completed,cancelled'],
            'is_active'      => ['nullable', 'boolean'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
