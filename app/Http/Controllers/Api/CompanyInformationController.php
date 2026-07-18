<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompanyInformationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyInformationController extends Controller
{
    public function __construct(private CompanyInformationService $companyInformationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string',
        ]);

        $result = $this->companyInformationService->getCompanyInformation(
            $request->query('domain')
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}