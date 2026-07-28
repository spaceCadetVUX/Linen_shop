<?php

namespace App\Http\Controllers\Mcp\Page;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpPageService;
use Illuminate\Http\JsonResponse;

class ListController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpPageService $service) {}

    public function __invoke(): JsonResponse
    {
        return $this->success(data: $this->service->list());
    }
}
