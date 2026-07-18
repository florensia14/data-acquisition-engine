<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Extractors\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
   public function __construct(private LocationService $locationService)
    {
    }

    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        try {
            $result = $this->locationService->extract($request->input('query'));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract location information',
                'error' => $e->getMessage(),
            ], 422);
        }
    }   
}