<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepairRequest\CreateRepairRequest;
use App\Http\Requests\RepairRequest\UpdateRepairRequest;
use App\Http\Responses\ApiResponse;
use App\Models\RepairRequest;

class RepairRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $repairRequests = RepairRequest::orderBy("id", "desc")->get();
        return ApiResponse::success(
            message: __('messages.repair_request.retrieved_list'),
            data: compact("repairRequests")
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRepairRequest $request)
    {
        $data = $request->validated();
        $repairRequest = RepairRequest::create($data);

        return ApiResponse::success(
            data: compact('repairRequest'),
            message: __('messages.repair_request.created')
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(RepairRequest $repairRequest)
    {
        return ApiResponse::success(
            data: compact('repairRequest'),
            message: __('messages.repair_request.retrieved')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRepairRequest $request, RepairRequest $repairRequest)
    {
        $data = $request->validated();
        $repairRequest->update($data);
        return ApiResponse::success(
            message: __('messages.repair_request.updated'),
            data: compact('repairRequest'),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RepairRequest $repairRequest)
    {
        $repairRequest->delete();
        return ApiResponse::success(message: __('messages.repair_request.deleted'));
    }
}