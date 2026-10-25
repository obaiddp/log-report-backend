<?php

namespace App\Http\Controllers;

use App\Models\AssetInspection;
use App\Notifications\InspectionCompleted;
use App\Notifications\InspectionNeedsSignature;
use App\Notifications\InspectionReturned;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AssetInspectionController
{
    private const RELATIONS = [
        'department',
        'itemType',
        'preparedBy',
        'adminUser',
    ];

    private const STATUSES = [
        'handover_for_repairing',
        'recieved_after_repairing',
        'new_purchase',
        'others',
    ];

    public function getAssetInspections(Request $request): JsonResponse
    {
        $query = AssetInspection::with(self::RELATIONS)->latest();

        if (! $this->seesAll($request)) {
            $id = $request->user()->id;
            $query->where(fn ($q) => $q->where('prepared_by', $id)->orWhere('admin_user', $id));
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function getAssetInspectionById(Request $request, AssetInspection $assetInspection): JsonResponse
    {
        $this->assertCanView($request, $assetInspection);

        return response()->json([
            'status' => 'success',
            'data'   => $assetInspection->load(self::RELATIONS),
        ]);
    }

    // From ICT department
    public function postAssetInspections(Request $request): JsonResponse
    {
        logger("am i reaching here");
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'cell' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'exists:departments,id'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::in(self::STATUSES)], // item status

            'item_type_id' => ['required', 'exists:item_types,id'],
            'model' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'item_tag' => ['nullable', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:50'],
            'generation' => ['nullable', 'string', 'max:50'],
            'hard_drive' => ['nullable', 'string', 'max:50'],

            'item_problem' => ['nullable', 'string'],
            'it_remarks' => ['nullable', 'string'],

            // The approver the ICT person assigns. Cannot be the creator.
            'admin_user' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($q) => $q->whereIn('department_id', DB::table('departments')->where('code', 'admin-log')->pluck('id'))
                ),
                Rule::notIn([$request->user()->id]),
            ],

            // Must be true: the ICT person confirms the user acknowledged the form.
            'user_digital_signature' => ['accepted'],
        ]);

        logger("This is what is validated: ", $validated);

        $asset = AssetInspection::create([
            ...$validated,
            'prepared_by' => $request->user()->id,
            'prepared_by_digital_signature' => true,
            'prepared_signed_at' => now(),
            'user_digital_signature' => true,
            'user_signed_at' => now(),
            'admin_digital_signature' => false,
            'approval_status' => AssetInspection::APPROVAL_PENDING_ADMIN,
        ]);

        $asset->load(self::RELATIONS);
        $asset->adminUser->notify(new InspectionNeedsSignature($asset));

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection created and sent to the admin for signature',
            'data'   => $asset,
        ], 201);
    }

    // From ICT department. Allowed while pending or after being returned.
    public function updateAssetInspectionById(Request $request, AssetInspection $assetInspection): JsonResponse
    {
        abort_unless(
            (int) $assetInspection->prepared_by === $request->user()->id,
            403,
            'Only the person who prepared this inspection can edit it.'
        );
        abort_if(
            $assetInspection->isCompleted(),
            409,
            'A completed inspection cannot be edited.'
        );

        $validated = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'cell' => ['nullable', 'string', 'max:20'],
            'department_id' => ['sometimes', 'required', 'exists:departments,id'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::in(self::STATUSES)],

            'item_type_id' => ['sometimes', 'required', 'exists:item_types,id'],
            'model' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['sometimes', 'required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'item_tag' => ['nullable', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:50'],
            'generation' => ['nullable', 'string', 'max:50'],
            'hard_drive' => ['nullable', 'string', 'max:50'],

            'item_problem' => ['nullable', 'string'],
            'it_remarks' => ['nullable', 'string'],

            'admin_user' => [
                'sometimes',
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($q) => $q->whereIn('department_id', DB::table('departments')->where('code', 'admin-log')->pluck('id'))
                ),
                Rule::notIn([$request->user()->id]),
            ],
        ]);

        $wasReturned = $assetInspection->approval_status === AssetInspection::APPROVAL_RETURNED;
        $previousAdminId = (int) $assetInspection->admin_user;

        // Editing a returned form resubmits it to the admin.
        if ($wasReturned) {
            $validated['approval_status'] = AssetInspection::APPROVAL_PENDING_ADMIN;
            $validated['return_reason'] = null;
        }

        $assetInspection->update($validated);
        $assetInspection->load(self::RELATIONS);

        $adminChanged = (int) $assetInspection->admin_user !== $previousAdminId;

        if ($wasReturned || $adminChanged) {
            $assetInspection->adminUser->notify(new InspectionNeedsSignature($assetInspection));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection updated successfully',
            'data'   => $assetInspection,
        ]);
    }

    // From Admin department: sign and complete.
    public function signAsAdmin(Request $request, AssetInspection $assetInspection): JsonResponse
    {
        $this->assertAssignedAdmin($request, $assetInspection);
        $this->assertPendingAdmin($assetInspection);

        $validated = $request->validate([
            'admin_remarks' => ['nullable', 'string'],
        ]);

        $assetInspection->update([
            ...$validated,
            'admin_digital_signature' => true,
            'admin_signed_at' => now(),
            'approval_status' => AssetInspection::APPROVAL_COMPLETED,
        ]);

        $assetInspection->load(self::RELATIONS);
        $assetInspection->preparedBy->notify(new InspectionCompleted($assetInspection));

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection signed successfully',
            'data' => $assetInspection,
        ]);
    }

    // From Admin department: send back to ICT with a reason.
    public function returnToIt(Request $request, AssetInspection $assetInspection): JsonResponse
    {
        $this->assertAssignedAdmin($request, $assetInspection);
        $this->assertPendingAdmin($assetInspection);

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:2000'],
            'admin_remarks' => ['nullable', 'string'],
        ]);

        $assetInspection->update([
            ...$validated,
            'approval_status' => AssetInspection::APPROVAL_RETURNED,
            'admin_digital_signature' => false,
            'admin_signed_at' => null,
        ]);

        $assetInspection->load(self::RELATIONS);
        $assetInspection->preparedBy->notify(new InspectionReturned($assetInspection));

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection returned to IT',
            'data' => $assetInspection,
        ]);
    }

    public function deleteAssetInspectionById(Request $request, AssetInspection $assetInspection): JsonResponse
    {
        abort_unless(
            (int) $assetInspection->prepared_by === $request->user()->id,
            403,
            'Only the person who prepared this inspection can delete it.'
        );
        abort_if(
            $assetInspection->isCompleted(),
            409,
            'A completed inspection cannot be deleted.'
        );

        $assetInspection->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection deleted successfully',
        ]);
    }

    private function assertAssignedAdmin(Request $request, AssetInspection $assetInspection): void
    {
        abort_unless(
            (int) $assetInspection->admin_user === $request->user()->id,
            403,
            'You are not the assigned approver for this inspection.'
        );
    }

    private function assertPendingAdmin(AssetInspection $assetInspection): void
    {
        abort_unless(
            $assetInspection->approval_status === AssetInspection::APPROVAL_PENDING_ADMIN,
            422,
            'This inspection is not awaiting admin approval.'
        );
    }

    private function seesAll(Request $request): bool
    {
        return in_array($request->user()->role?->name, ['super_admin', 'ict_manager'], true);
    }

    private function assertCanView(Request $request, AssetInspection $a): void
    {
        $id = $request->user()->id;
        abort_unless(
            $this->seesAll($request) || (int) $a->prepared_by === $id || (int) $a->admin_user === $id,
            403,
            'You cannot view this inspection.'
        );
    }
}