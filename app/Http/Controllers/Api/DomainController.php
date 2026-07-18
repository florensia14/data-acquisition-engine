<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Extractors\DomainService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    public function __construct(private DomainService $domainService)
    {
    }

    public function extract(Request $request): JsonResponse
    {
          $request->validate([
            'domain' => 'required|string',
        ]);

        try {
            $result = $this->domainService->extract($request->input('domain'));

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract domain information',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }
}