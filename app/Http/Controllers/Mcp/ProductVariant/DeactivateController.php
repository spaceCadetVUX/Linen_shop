<?php

namespace App\Http\Controllers\Mcp\ProductVariant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpProductVariantService;
use Illuminate\Http\JsonResponse;

class DeactivateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpProductVariantService $service) {}

    public function __invoke(string $slug, string $sku): JsonResponse
    {
        $result = $this->service->deactivate($slug, $sku);

        return $this->success(data: $result['data'], message: 'Variant deactivated.');
    }
}
