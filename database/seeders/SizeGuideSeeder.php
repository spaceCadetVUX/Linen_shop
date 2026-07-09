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

        $tshirtTableVi = <<<'HTML'
<p>Số đo tính bằng cm. Áo phông linen dáng rộng thoải mái — nếu bạn ở giữa hai size, chọn size nhỏ hơn để dáng gọn hơn.</p>
<table>
    <thead>
        <tr><th>Size</th><th>Vòng ngực</th><th>Dài áo</th><th>Ngang vai</th><th>Dài tay</th></tr>
    </thead>
    <tbody>
        <tr><td>S</td><td>96–100</td><td>66</td><td>42</td><td>20</td></tr>
        <tr><td>M</td><td>101–105</td><td>68</td><td>44</td><td>21</td></tr>
        <tr><td>L</td><td>106–110</td><td>70</td><td>46</td><td>22</td></tr>
        <tr><td>XL</td><td>111–115</td><td>72</td><td>48</td><td>23</td></tr>
    </tbody>
</table>
<p>Người mẫu cao 178 cm, mặc size M.</p>
HTML;

        $tshirtTableEn = <<<'HTML'
<p>All measurements in cm. Relaxed-fit linen tee — between two sizes? Size down for a slimmer fit.</p>
<table>
    <thead>
        <tr><th>Size</th><th>Chest</th><th>Length</th><th>Shoulder</th><th>Sleeve</th></tr>
    </thead>
    <tbody>
        <tr><td>S</td><td>96–100</td><td>66</td><td>42</td><td>20</td></tr>
        <tr><td>M</td><td>101–105</td><td>68</td><td>44</td><td>21</td></tr>
        <tr><td>L</td><td>106–110</td><td>70</td><td>46</td><td>22</td></tr>
        <tr><td>XL</td><td>111–115</td><td>72</td><td>48</td><td>23</td></tr>
    </tbody>
</table>
<p>Model is 178 cm tall and wears size M.</p>
HTML;

        $tshirtGuide = SizeGuide::updateOrCreate(
            ['key' => 'ao-phong'],
            ['is_active' => true, 'sort_order' => 1]
        );

        $tshirtGuide->translations()->updateOrCreate(
            ['locale' => 'vi'],
            ['name' => 'Áo phông', 'body' => $tshirtTableVi]
        );

        $tshirtGuide->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['name' => 'T-Shirts', 'body' => $tshirtTableEn]
        );

        $this->command->info('Sample size guide seeded: ao-nu, ao-phong (vi + en)');
    }
}
