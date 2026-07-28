<?php

namespace App\Http\Controllers\Mcp\ProductVariant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpsertController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpProductVariantService $service) {}

    public function __invoke(Request $request, string $slug, string $sku): JsonResponse
    {
        $result = $this->service->upsert($slug, $sku, $request->all());

        return $this->success(data: $result['data'], message: 'Variant saved.');
    }
}
