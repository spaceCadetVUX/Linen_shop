<?php

namespace App\Http\Controllers\Mcp\Import;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FromSpecsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpImportService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->service->fromSpecs(
            slug: $request->string('slug')->toString(),
            manufacturerSlug: $request->input('manufacturer_slug'),
            categorySlug: $request->input('category_slug'),
            specsText: $request->string('specs_text')->toString(),
            locales: $request->input('locales', ['vi', 'en']),
        );

        return $this->success(data: $data, message: 'Specs parsed — review then call save_product.');
    }
}
