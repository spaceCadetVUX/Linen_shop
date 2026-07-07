<?php

namespace App\Enums;

enum VariantAvailability: string
{
    case Auto       = 'auto';
    case OutOfStock = 'out_of_stock';
    case PreOrder   = 'pre_order';
}
