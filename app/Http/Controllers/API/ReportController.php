<?php

namespace App\Http\Controllers\API;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use Illuminate\Validation\Rule;

class ReportController extends BaseController
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permission to view reports.'], 403);
        }

        $query = Report::with(['user:id,name', 'resource:id,title']);

        if ($request->has('status') && in_array($request->status, ['pending', 'resolved'])) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate(20);

        return $this->sendResponse($reports, 'Reports retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required|exists:resources,id',
            'reason'      => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $report = Report::create([
            'user_id'     => Auth::id(),
            'resource_id' => $request->resource_id,
            'reason'      => $request->reason,
        ]);

        return $this->sendResponse($report, 'Resource reported successfully.', 201);
    }


    public function update(Request $request, Report $report)
    {

        if (Auth::user()->role !== 'admin') {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permission to update reports.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pending', 'resolved'])],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $report->update($validator->validated());

        return $this->sendResponse($report, 'Report status updated successfully.');
    }


    public function destroy(Report $report)
    {
        if (Auth::user()->role !== 'admin') {
            return $this->sendError('Forbidden.', ['error' => 'You do not have permission to delete reports.'], 403);
        }

        $report->delete();

        return $this->sendResponse(null, 'Report deleted successfully.');
    }
}
