<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu test tối thiểu để kiểm thử Scout/Meilisearch (song ngữ vi/en).
 * Chạy riêng: php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'product' => [
                    'name'              => 'Áo Tank Cashmere Lurex',
                    'slug'              => 'ao-tank-cashmere-lurex',
                    'sku'               => 'LN-TOP-0001',
                    'short_description' => 'Áo tank dệt từ cashmere Mông Cổ Grade A kết hợp lurex ánh kim, dáng fitted.',
                    'description'       => 'Áo Tank Cashmere Lurex LINNÉ được dệt từ 90% cashmere Mông Cổ Grade A và 10% sợi lurex ánh kim, mang lại vẻ sang trọng tinh tế cho mọi trang phục. Dáng fitted tôn dáng, chất liệu mềm mại, nhẹ và ấm.',
                    'price'             => 2890000,
                    'currency'          => 'VND',
                    'stock_quantity'    => 25,
                    'is_active'         => true,
                ],
                'trans_en' => [
                    'locale'             => 'en',
                    'name'               => 'Cashmere Lurex Tank Top',
                    'slug'               => 'cashmere-lurex-tank-top',
                    'short_description'  => 'Grade A Mongolian cashmere tank top with metallic lurex thread, fitted silhouette.',
                    'description'        => 'The LINNÉ Cashmere Lurex Tank Top is woven from 90% Grade A Mongolian cashmere and 10% metallic lurex yarn, delivering subtle luxury for any outfit. Fitted silhouette, soft, lightweight and warm.',
                    'price'              => null,
                    'currency'           => null,
                ],
            ],
            [
                'product' => [
                    'name'              => 'Áo Len Cashmere Cổ Tròn',
                    'slug'              => 'ao-len-cashmere-co-tron',
                    'sku'               => 'LN-TOP-0002',
                    'short_description' => 'Áo len cổ tròn 100% cashmere, form basic dễ phối đồ.',
                    'description'       => 'Áo Len Cashmere Cổ Tròn LINNÉ dệt 100% cashmere nguyên chất, form basic thanh lịch, dễ dàng phối cùng quần âu hoặc chân váy. Giữ ấm tốt, thoáng khí, không gây bí da.',
                    'price'             => 3490000,
                    'currency'          => 'VND',
                    'stock_quantity'    => 18,
                    'is_active'         => true,
                ],
                'trans_en' => [
                    'locale'             => 'en',
                    'name'               => 'Crewneck Cashmere Sweater',
                    'slug'               => 'crewneck-cashmere-sweater',
                    'short_description'  => '100% pure cashmere crewneck sweater, basic silhouette, easy to style.',
                    'description'        => 'The LINNÉ Crewneck Cashmere Sweater is woven from 100% pure cashmere in an elegant basic silhouette, easy to pair with trousers or skirts. Warm, breathable, and gentle on skin.',
                    'price'              => null,
                    'currency'           => null,
                ],
            ],
            [
                'product' => [
                    'name'              => 'Khăn Choàng Cashmere',
                    'slug'              => 'khan-choang-cashmere',
                    'sku'               => 'LN-ACC-0001',
                    'short_description' => 'Khăn choàng cashmere mềm mại, kích thước 70x200cm, nhiều màu.',
                    'description'       => 'Khăn Choàng Cashmere LINNÉ dệt từ sợi cashmere cao cấp, kích thước 70x200cm, mềm mại và ấm áp, phù hợp làm phụ kiện cho cả nam và nữ trong mùa lạnh.',
                    'price'             => 1590000,
                    'currency'          => 'VND',
                    'stock_quantity'    => 40,
                    'is_active'         => true,
                ],
                'trans_en' => [
                    'locale'             => 'en',
                    'name'               => 'Cashmere Scarf',
                    'slug'               => 'cashmere-scarf',
                    'short_description'  => 'Soft cashmere scarf, 70x200cm, available in multiple colors.',
                    'description'        => 'The LINNÉ Cashmere Scarf is woven from premium cashmere yarn, 70x200cm, soft and warm — a versatile accessory for both men and women in cold weather.',
                    'price'              => null,
                    'currency'           => null,
                ],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['product']['sku']],
                $data['product'],
            );

            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'locale' => 'vi'],
                array_merge(
                    ['product_id' => $product->id, 'locale' => 'vi'],
                    array_intersect_key($data['product'], array_flip(['name', 'slug', 'short_description', 'description', 'price', 'currency'])),
                ),
            );

            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'locale' => 'en'],
                array_merge(['product_id' => $product->id], $data['trans_en']),
            );
        }
    }
}
