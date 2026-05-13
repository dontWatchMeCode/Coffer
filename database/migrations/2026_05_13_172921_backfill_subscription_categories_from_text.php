<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $categories = DB::table('subscriptions')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->selectRaw('team_id, category, MIN(id) as min_id')
            ->groupBy('team_id', 'category')
            ->get();

        foreach ($categories as $category) {
            $slug = Str::slug($category->category);

            DB::table('subscription_categories')->upsert(
                [
                    'team_id' => $category->team_id,
                    'slug' => $slug,
                    'name' => $category->category,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['team_id', 'slug'],
                ['name', 'updated_at'],
            );

            $id = DB::table('subscription_categories')
                ->where('team_id', $category->team_id)
                ->where('slug', $slug)
                ->value('id');

            DB::table('subscriptions')
                ->where('team_id', $category->team_id)
                ->where('category', $category->category)
                ->update(['subscription_category_id' => $id]);
        }
    }

    public function down(): void
    {
        DB::table('subscriptions')->update(['subscription_category_id' => null]);
        DB::table('subscription_categories')->truncate();
    }
};
