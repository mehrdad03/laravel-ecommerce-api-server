<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{

    protected function makeSlug(string $name): string
    {
        $slug = Str::slug($name, '-');
        if ($slug === '') {
            $slug = 'cat-' . substr(md5($name), 6, 8);
        }
        return $slug;
    }

    public function run(): void
    {

        $data = [
            'موبایل' => ['سامسونگ', 'اپل', 'شیائومی', 'هواوی', 'نوکیا'],
            'لپ‌تاپ' => ['ایسوس', 'لنوو', 'اچ‌پی', 'دل', 'مک‌بوک'],
            'لوازم جانبی موبایل' => ['قاب گوشی', 'گلس و محافظ صفحه', 'شارژر', 'کابل و مبدل', 'پاوربانک'],
            'صوتی و هدفون' => ['هدفون بی‌سیم', 'هدفون سیمی', 'اسپیکر بلوتوث', 'ایرفون', 'ساندبار'],
        ];

        foreach ($data as $parentName => $children) {
            // ایجاد دستهٔ ریشه‌ای
            $parent = Category::create([
                'name'      => $parentName,
                'slug'      => $this->makeSlug($parentName),
                'parent_id' => null,
            ]);

            // ایجاد 5 زیرشاخه
            foreach ($children as $childName) {
                Category::create([
                    'name'      => $childName,
                    'slug'      => $this->makeSlug($parentName.'-'.$childName), // یکتاتر
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
