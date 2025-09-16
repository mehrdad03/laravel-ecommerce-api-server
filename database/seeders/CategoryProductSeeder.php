<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategoryProductSeeder extends Seeder
{
    /** ساخت اسلاگ امن برای فارسی */
    private function makeSlug(string $text, string $prefix = 'item'): string
    {
        $slug = Str::slug($text, '-');
        if ($slug === '') {
            $slug = $prefix . '-' . substr(md5($text), 6, 10);
        }
        return $slug;
    }

    private function thumbUrl(string $seed): string
    {
        // 600x600
        return "https://picsum.photos/seed/".urlencode($seed)."/600/600";
    }

    private function galleryUrls(string $seed): array
    {
        // سه تصویر 800x800
        return [
            "https://picsum.photos/seed/".urlencode($seed.'-g1')."/800/800",
            "https://picsum.photos/seed/".urlencode($seed.'-g2')."/800/800",
            "https://picsum.photos/seed/".urlencode($seed.'-g3')."/800/800",
        ];
    }

    public function run(): void
    {
        // ---------- پاکسازی امن برای توسعه ----------
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // اگر این جداول را دارید، به ترتیب زیر خالی کنید تا FK ها گیر ندهند
        foreach (['cart_items', 'order_items', 'product_images', 'products', 'categories'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // ---------- پایان پاکسازی ----------

        // 4 دستهٔ اصلی + 5 دستهٔ فرعی برای هرکدام (فلت، چون parent_id نداریم)
        $structure = [
            'موبایل' => ['سامسونگ', 'اپل', 'شیائومی', 'هواوی', 'نوکیا'],
            'لپ‌تاپ' => ['ایسوس', 'لنوو', 'اچ‌پی', 'دل', 'مک‌بوک'],
            'لوازم جانبی موبایل' => ['قاب گوشی', 'گلس و محافظ صفحه', 'شارژر', 'کابل و مبدل', 'پاوربانک'],
            'صوتی و هدفون' => ['هدفون بی‌سیم', 'هدفون سیمی', 'اسپیکر بلوتوث', 'ایرفون', 'ساندبار'],
        ];

        DB::transaction(function () use ($structure) {

            // 1) ساخت همهٔ دسته‌ها (اصلی و فرعی) و نگه‌داشتن id ها
            $categoryIds = []; // [name => id]
            foreach ($structure as $parentName => $children) {
                // دستهٔ اصلی
                $parentSlug = $this->makeSlug($parentName, 'cat');
                $parentId = DB::table('categories')->insertGetId([
                    'name'       => $parentName,
                    'slug'       => $parentSlug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoryIds[$parentName] = $parentId;

                // دسته‌های فرعی (به‌صورت فلت)
                foreach ($children as $childName) {
                    $childSlug = $this->makeSlug($parentName.'-'.$childName, 'cat');
                    $childId = DB::table('categories')->insertGetId([
                        'name'       => $childName,
                        'slug'       => $childSlug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $categoryIds[$childName] = $childId;
                }
            }

            // 2) ساخت محصولات برای هر دسته (اصلی و فرعی) + thumbnail + گالری
            // سیاست: برای هر «دستهٔ اصلی» 3 محصول، برای هر «دستهٔ فرعی» 5 محصول
            foreach ($structure as $parentName => $children) {

                // محصولات دستهٔ اصلی
                $parentId = $categoryIds[$parentName];
                for ($i = 1; $i <= 3; $i++) {
                    $pName = "{$parentName} محصول والد {$i}";
                    $slug  = $this->makeSlug($parentName . "-parent-$i", 'prd');

                    $productId = DB::table('products')->insertGetId([
                        'category_id' => $parentId,
                        'name'        => $pName,
                        'slug'        => $slug,
                        'description' => "توضیحات {$pName}",
                        // دقت: decimal در اسکیما داری؛ عدد را به صورت صحیح/دو رقم اعشار می‌ریزیم
                        'price'       => number_format((rand(300, 2000) * 1000) / 100, 2, '.', ''),
                        'stock'       => rand(5, 50),
                        'thumbnail'   => $this->thumbUrl($slug),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    foreach ($this->galleryUrls($slug) as $imgUrl) {
                        DB::table('product_images')->insert([
                            'product_id' => $productId,
                            'path'       => $imgUrl,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // محصولات دسته‌های فرعی
                foreach ($children as $childName) {
                    $childId = $categoryIds[$childName];
                    for ($j = 1; $j <= 5; $j++) {
                        $pName = "{$childName} محصول {$j}";
                        $slug  = $this->makeSlug($childName . "-$j", 'prd');

                        $productId = DB::table('products')->insertGetId([
                            'category_id' => $childId,
                            'name'        => $pName,
                            'slug'        => $slug,
                            'description' => "توضیحات {$pName}",
                            'price'       => number_format((rand(100, 1500) * 1000) / 100, 2, '.', ''),
                            'stock'       => rand(1, 40),
                            'thumbnail'   => $this->thumbUrl($slug),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);

                        foreach ($this->galleryUrls($slug) as $imgUrl) {
                            DB::table('product_images')->insert([
                                'product_id' => $productId,
                                'path'       => $imgUrl,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }
}
