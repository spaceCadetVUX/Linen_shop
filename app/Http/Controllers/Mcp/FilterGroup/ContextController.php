<?php

namespace App\Http\Controllers\Mcp\FilterGroup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpFilterGroupService;
use Illuminate\Http\JsonResponse;

class ContextController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpFilterGroupService $service) {}

    public function __invoke(string $slug): JsonResponse
    {
        return $this->success(
            data: $this->service->context($slug),
            message: 'Filter group context loaded.',
        );
    }
}
