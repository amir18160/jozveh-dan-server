<?php

namespace App\Http\Controllers\API;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ReportController extends BaseController
{

    public function index(Request $request)
    {
        $query = Report::with(['user', 'resource']);

        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->get('resource_id'));
        }

        $reports = $query->latest()->paginate(10);

        return $this->sendResponse($reports, 'Reports retrieved successfully.');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'resource_id' => 'nullable|exists:resources,id',
            'reason'      => 'required|string',
            'status'      => 'in:pending,resolved',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $report = Report::create($validator->validated());

        return $this->sendResponse($report->load(['user', 'resource']), 'Report created successfully.', 201);
    }


    public function show($id)
    {
        $report = Report::with(['user', 'resource'])->find($id);
        if (! $report) {
            return $this->sendError('Report not found.', [], 404);
        }

        return $this->sendResponse($report, 'Report retrieved successfully.');
    }


    public function update(Request $request, $id)
    {
        $report = Report::find($id);
        if (! $report) {
            return $this->sendError('Report not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'sometimes|required|string',
            'status' => 'in:pending,resolved',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $report->fill($validator->validated());
        $report->save();

        return $this->sendResponse($report->load(['user', 'resource']), 'Report updated successfully.');
    }


    public function destroy($id)
    {
        $report = Report::find($id);
        if (! $report) {
            return $this->sendError('Report not found.', [], 404);
        }

        $report->delete();

        return $this->sendResponse(null, 'Report deleted successfully.');
    }
}
