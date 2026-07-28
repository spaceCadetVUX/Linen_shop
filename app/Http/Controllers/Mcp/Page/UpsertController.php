<?php

namespace App\Http\Controllers\Mcp\Page;

use App\Http\Controllers\Controller;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Mcp\McpPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpsertController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly McpPageService $service) {}

    public function __invoke(Request $request, string $pageKey): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()->id;
        $dryRun = $request->boolean('dry_run');

        $result = $this->service->upsert($pageKey, $request->all(), $tokenId, $dryRun);

        return $this->success(
            data: $result['data'],
            message: $dryRun ? 'Dry run — no changes written.' : 'Page saved.',
        );
    }
}
