<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Extractors\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebsiteController extends Controller
{
    public function __construct(private WebsiteService $websiteService) 
    {
    }

    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $result = $this->websiteService->extract($request->input('url'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract website metadata',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}