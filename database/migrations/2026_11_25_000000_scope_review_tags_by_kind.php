<?php

use App\Support\TagCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const EQUIVALENT = [
        'Rápido' => 'Chegou antes do prazo',
        'Um pouco demorado' => 'Chegou com atraso',
        'Cumpriu o combinado' => 'Igual ao anúncio',
        'Faltou algo combinado' => 'Veio incompleto',
        'Sem cobrança extra' => 'Vale o preço',
        'Teve cobrança extra' => 'Preço salgado',
        'Acabamento impecável' => 'Ótima qualidade',
        'Acabamento simples' => 'Qualidade mediana',
        'Material resistente' => 'Ótima qualidade',
        'Pouco durável' => 'Qualidade mediana',
    ];

    public function up(): void
    {
        TagCatalog::sync();

        $tags = DB::table('tags')->orderBy('position')->get()->keyBy('id');
        $byName = $tags->keyBy('name');

        $replace = function (array $tagIds, string $kind, array $categories) use ($tags, $byName): array {
            $valid = array_values(array_filter($tagIds, fn ($id) => isset($tags[$id]) && TagCatalog::applies($tags[$id], $kind, $categories)));

            foreach ($tagIds as $id) {
                $tag = $tags[$id] ?? null;
                if (! $tag || in_array($id, $valid, false)) {
                    continue;
                }

                $equivalent = $byName->get(self::EQUIVALENT[$tag->name] ?? '');
                if ($equivalent && TagCatalog::applies($equivalent, $kind, $categories)) {
                    $valid[] = $equivalent->id;
                } elseif ($valid === []) {
                    $fallback = $this->sameMeaning($tags, $tag, $kind, $categories);
                    if ($fallback) {
                        $valid[] = $fallback->id;
                    }
                }
            }

            return array_values(array_unique($valid));
        };

        DB::table('reviews')
            ->join('items', 'items.id', '=', 'reviews.item_id')
            ->orderBy('reviews.id')
            ->select('reviews.*', 'items.kind as item_kind', 'items.categories as item_categories')
            ->each(function ($review) use ($replace) {
                $current = DB::table('review_tag')->where('review_id', $review->id)->pluck('tag_id')->all();
                $categories = json_decode($review->item_categories, true) ?? [];
                $next = TagCatalog::withoutOpposites($replace($current, $review->item_kind, $categories), $review);

                if ($this->sameIds($current, $next)) {
                    return;
                }

                DB::table('review_tag')->where('review_id', $review->id)->delete();
                DB::table('review_tag')->insert(array_map(fn ($id) => ['review_id' => $review->id, 'tag_id' => $id], $next));
            });

        DB::table('review_versions')
            ->join('reviews', 'reviews.id', '=', 'review_versions.review_id')
            ->join('items', 'items.id', '=', 'reviews.item_id')
            ->orderBy('review_versions.id')
            ->select('review_versions.id', 'review_versions.tag_ids', 'items.kind', 'items.categories')
            ->each(function ($version) use ($replace) {
                $current = array_map('intval', json_decode($version->tag_ids ?? '[]', true) ?? []);
                $next = $replace($current, $version->kind, json_decode($version->categories, true) ?? []);

                if (! $this->sameIds($current, $next)) {
                    DB::table('review_versions')->where('id', $version->id)->update(['tag_ids' => json_encode($next)]);
                }
            });
    }

    public function down(): void
    {
    }

    private function sameMeaning(Collection $tags, object $tag, string $kind, array $categories): ?object
    {
        return $tags->first(fn ($candidate) => $candidate->characteristic === $tag->characteristic
            && $candidate->sentiment === $tag->sentiment
            && TagCatalog::applies($candidate, $kind, $categories));
    }

    private function sameIds(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }
};
