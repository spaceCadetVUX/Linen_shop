<?php

namespace App\Http\Controllers\Mcp\FilterGroup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpFilterGroupService;
use Illuminate\Http\JsonResponse;

class DeactivateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpFilterGroupService $service) {}

    public function __invoke(string $slug): JsonResponse
    {
        $result = $this->service->deactivate($slug);

        return $this->success(data: $result['data'], message: 'Filter group deactivated.');
    }
}
