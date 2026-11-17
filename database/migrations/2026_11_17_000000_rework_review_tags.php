<?php

use App\Support\TagCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('kind', 10)->nullable()->after('characteristic');
            $table->foreignId('opposite_id')->nullable()->after('kind')->constrained('tags')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0)->after('opposite_id');
        });

        TagCatalog::sync();

        $current = array_column(TagCatalog::definitions(), 'name');
        $tags = DB::table('tags')->get()->keyBy('id');
        $legacy = $tags->whereNotIn('name', $current);
        $map = function (int $tagId, string $kind, array $categories) use ($tags, $legacy): ?int {
            $tag = $tags[$tagId] ?? null;
            if (! $tag) {
                return null;
            }
            if (! $legacy->has($tagId) && TagCatalog::applies($tag, $kind, $categories)) {
                return $tagId;
            }

            return TagCatalog::replacement($tag->name, $kind, $categories, $tags->values())?->id;
        };

        $links = DB::table('review_tag')
            ->join('reviews', 'reviews.id', '=', 'review_tag.review_id')
            ->join('items', 'items.id', '=', 'reviews.item_id')
            ->get(['review_tag.review_id', 'review_tag.tag_id', 'items.kind', 'items.categories']);

        foreach ($links as $link) {
            $new = $map($link->tag_id, $link->kind, json_decode($link->categories, true) ?? []);
            if ($new !== $link->tag_id) {
                DB::table('review_tag')->where('review_id', $link->review_id)->where('tag_id', $link->tag_id)->delete();
                if ($new) {
                    DB::table('review_tag')->insertOrIgnore(['review_id' => $link->review_id, 'tag_id' => $new]);
                }
            }
        }

        DB::table('reviews')->orderBy('id')->each(function ($review) {
            $ids = DB::table('review_tag')->where('review_id', $review->id)->pluck('tag_id')->all();
            $kept = TagCatalog::withoutOpposites($ids, $review);
            if (count($kept) !== count($ids)) {
                DB::table('review_tag')->where('review_id', $review->id)->whereNotIn('tag_id', $kept)->delete();
            }
        });

        DB::table('review_versions')
            ->join('reviews', 'reviews.id', '=', 'review_versions.review_id')
            ->join('items', 'items.id', '=', 'reviews.item_id')
            ->get(['review_versions.id', 'review_versions.tag_ids', 'items.kind', 'items.categories'])
            ->each(function ($version) use ($map) {
                $categories = json_decode($version->categories, true) ?? [];
                $mapped = collect(json_decode($version->tag_ids ?? '[]', true) ?? [])
                    ->map(fn ($id) => $map((int) $id, $version->kind, $categories))
                    ->filter()->unique()->values()->all();
                DB::table('review_versions')->where('id', $version->id)->update(['tag_ids' => json_encode($mapped)]);
            });

        DB::table('tags')->whereIn('id', $legacy->keys())->delete();
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opposite_id');
            $table->dropColumn(['kind', 'position']);
        });
    }
};
