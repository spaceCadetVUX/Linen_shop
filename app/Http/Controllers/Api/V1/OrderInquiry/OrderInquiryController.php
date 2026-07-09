<?php

namespace App\Http\Controllers\Api\V1\OrderInquiry;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderInquiry\StoreOrderInquiryRequest;
use App\Http\Resources\Api\OrderInquiry\OrderInquiryResource;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\OrderInquiry\OrderInquiryService;
use Illuminate\Http\JsonResponse;

class OrderInquiryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderInquiryService $orderInquiryService,
    ) {}

    /**
     * POST /api/v1/order-inquiries
     * Stand-in for real checkout — see doc/todo.md. "Liên hệ đặt hàng"
     * popup on the cart page (Zalo / phone / email tabs) submits here.
     */
    public function store(StoreOrderInquiryRequest $request): JsonResponse
    {
        $inquiry = $this->orderInquiryService->submit($request, $request->validated());

        return $this->success(
            data: new OrderInquiryResource($inquiry),
            message: 'Đã ghi nhận yêu cầu đặt hàng',
            status: 201,
        );
    }
}
