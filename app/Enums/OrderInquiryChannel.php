<?php

namespace App\Enums;

enum OrderInquiryChannel: string
{
    case Zalo = 'zalo';
    case Phone = 'phone';
    case Email = 'email';
}
