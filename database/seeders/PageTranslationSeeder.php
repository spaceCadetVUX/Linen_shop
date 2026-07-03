<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'privacy-policy' => [
                'vi' => [
                    'title'             => 'Chính sách bảo mật',
                    'slug'              => 'privacy-policy',
                    'body'              => '<p>Nội dung đang được cập nhật — vui lòng chỉnh sửa trang này trong Filament (Content → Pages).</p>',
                    'meta_title'        => 'Chính sách bảo mật — LINNÉ',
                    'meta_description'  => 'Chính sách bảo mật của LINNÉ.',
                ],
                'en' => [
                    'title'             => 'Privacy Policy',
                    'slug'              => 'privacy-policy',
                    'body'              => '<p>Content pending — please edit this page in Filament (Content → Pages).</p>',
                    'meta_title'        => 'Privacy Policy — LINNÉ',
                    'meta_description'  => "LINNÉ's privacy policy.",
                ],
            ],
            'terms' => [
                'vi' => [
                    'title'             => 'Điều khoản dịch vụ',
                    'slug'              => 'terms',
                    'body'              => '<p>Nội dung đang được cập nhật — vui lòng chỉnh sửa trang này trong Filament (Content → Pages).</p>',
                    'meta_title'        => 'Điều khoản dịch vụ — LINNÉ',
                    'meta_description'  => 'Điều khoản dịch vụ của LINNÉ.',
                ],
                'en' => [
                    'title'             => 'Terms of Service',
                    'slug'              => 'terms',
                    'body'              => '<p>Content pending — please edit this page in Filament (Content → Pages).</p>',
                    'meta_title'        => 'Terms of Service — LINNÉ',
                    'meta_description'  => "LINNÉ's terms of service.",
                ],
            ],
        ];

        foreach ($pages as $pageKey => $translations) {
            $page = Page::updateOrCreate(
                ['page_key' => $pageKey],
                ['is_active' => true]
            );

            foreach ($translations as $locale => $data) {
                $page->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $data
                );
            }
        }

        $this->command->info('Placeholder pages seeded: privacy-policy, terms (vi + en)');
    }
}
