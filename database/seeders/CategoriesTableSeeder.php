<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Define the number of records to generate
        $numberOfRecords = 30;

        for ($i = 0; $i < $numberOfRecords; $i++) {
            $this->createCategory(null, $faker);
        }
    }

    private function createCategory($parent_id, $faker)
    {
        $category = [
            'name' => $faker->word,
            'parent_id' => $parent_id,
        ];

        $category_id = \DB::table('categories')->insertGetId($category);

        // Define the maximum number of subcategories per category
        $maxSubcategories = 1;

        // Decide randomly how many subcategories to create for this category
        $numberOfSubcategories = rand(0, $maxSubcategories);

        // Create subcategories recursively
        for ($i = 0; $i < $numberOfSubcategories; $i++) {
            $this->createCategory($category_id, $faker);
        }
    }
}
