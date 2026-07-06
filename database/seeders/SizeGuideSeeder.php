<?php

namespace Database\Seeders;

use App\Models\SizeGuide;
use Illuminate\Database\Seeder;

class SizeGuideSeeder extends Seeder
{
    /**
     * One sample guide with a real measurement table so admins can
     * duplicate it in Filament (Content → Size Guides) and edit numbers,
     * instead of building the table markup from scratch.
     */
    public function run(): void
    {
        $tableVi = <<<'HTML'
<p>Số đo tính bằng cm. Nếu bạn ở giữa hai size, chọn size lớn hơn để dáng thoải mái đúng tinh thần linen.</p>
<table>
    <thead>
        <tr><th>Size</th><th>Vòng ngực</th><th>Vòng eo</th><th>Dài áo</th><th>Ngang vai</th></tr>
    </thead>
    <tbody>
        <tr><td>S</td><td>84–88</td><td>64–68</td><td>54</td><td>34</td></tr>
        <tr><td>M</td><td>89–93</td><td>69–73</td><td>56</td><td>36</td></tr>
        <tr><td>L</td><td>94–98</td><td>74–78</td><td>58</td><td>38</td></tr>
    </tbody>
</table>
<p>Người mẫu cao 175 cm, mặc size S.</p>
HTML;

        $tableEn = <<<'HTML'
<p>All measurements in cm. Between two sizes? Size up for the relaxed linen fit.</p>
<table>
    <thead>
        <tr><th>Size</th><th>Bust</th><th>Waist</th><th>Length</th><th>Shoulder</th></tr>
    </thead>
    <tbody>
        <tr><td>S</td><td>84–88</td><td>64–68</td><td>54</td><td>34</td></tr>
        <tr><td>M</td><td>89–93</td><td>69–73</td><td>56</td><td>36</td></tr>
        <tr><td>L</td><td>94–98</td><td>74–78</td><td>58</td><td>38</td></tr>
    </tbody>
</table>
<p>Model is 175 cm tall and wears size S.</p>
HTML;

        $guide = SizeGuide::updateOrCreate(
            ['key' => 'ao-nu'],
            ['is_active' => true, 'sort_order' => 0]
        );

        $guide->translations()->updateOrCreate(
            ['locale' => 'vi'],
            ['name' => 'Áo nữ', 'body' => $tableVi]
        );

        $guide->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['name' => "Women's Tops", 'body' => $tableEn]
        );

        $this->command->info('Sample size guide seeded: ao-nu (vi + en)');
    }
}
