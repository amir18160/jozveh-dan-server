<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Models\Resource;
use Illuminate\Support\Facades\Validator;

class AiSearchController extends BaseController
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }


    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:5|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $userQuery = $request->input('query');


        $keywords = $this->geminiService->extractKeywordsFromQuery($userQuery);

        if (empty($keywords)) {
            $keywords = explode(' ', $userQuery);
        }


        $queryBuilder = Resource::query();

        foreach ($keywords as $keyword) {
            $queryBuilder->orWhere('title', 'LIKE', "%{$keyword}%")
                ->orWhere('description', 'LIKE', "%{$keyword}%");
        }


        $resources = $queryBuilder
            ->with(['user:id,name,profile_image', 'categories:id,name'])
            ->withCount('reviews') // Get review count
            ->orderBy('view_count', 'desc') // Prioritize popular resources
            ->take(7)
            ->get();

        if ($resources->isEmpty()) {
            return $this->sendResponse([], 'No relevant resources found.');
        }


        $aiSummaries = $this->geminiService->generateSummariesForResources($resources);


        $results = $resources->map(function ($resource) use ($aiSummaries) {
            $resourceArray = $resource->toArray();
            $resourceArray['ai_summary'] = $aiSummaries[$resource->id] ?? 'AI summary could not be generated for this item.';
            return $resourceArray;
        });

        return $this->sendResponse($results, 'AI-powered search results retrieved successfully.');
    }
}
