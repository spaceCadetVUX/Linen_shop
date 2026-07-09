<?php

namespace App\Repositories\Eloquent;

use App\Models\OrderInquiry;

class OrderInquiryRepository extends BaseRepository
{
    protected function model(): string
    {
        return OrderInquiry::class;
    }
}
