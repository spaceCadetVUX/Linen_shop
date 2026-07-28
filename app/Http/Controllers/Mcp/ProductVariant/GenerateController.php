<?php

namespace App\Http\Controllers\Mcp\ProductVariant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpProductVariantService;
use Illuminate\Http\JsonResponse;

class GenerateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpProductVariantService $service) {}

    public function __invoke(string $slug): JsonResponse
    {
        return $this->success(
            data: $this->service->generateVariants($slug),
            message: 'Variants generated.',
        );
    }
}
