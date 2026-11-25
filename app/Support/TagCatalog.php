<?php

namespace App\Support;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TagCatalog
{
    public const LEGACY_MAP = [
        'Chegou antes do prazo' => 'Chegou antes do prazo',
        'Bem embalado' => 'Bem embalado',
        'Material resistente' => 'Material resistente',
        'Acabamento impecável' => 'Acabamento impecável',
        'Funciona como descrito' => 'Igual ao anúncio',
        'Sabor caseiro de verdade' => 'Saboroso',
        'Veio completo' => 'Veio completo',
        'Igual às fotos' => 'Igual ao anúncio',
        'Profissional pontual' => 'Profissional pontual',
        'Custo-benefício justo' => 'Vale o preço',
        'Mais barato que a concorrência' => 'Vale o preço',
        'Rende bastante' => 'Rende bastante',
        'Atrasou muito' => 'Chegou com atraso',
        'Mal embalado' => 'Embalagem simples',
        'Quebrou rápido' => 'Pouco durável',
        'Diferente das fotos' => 'Diferente do anúncio',
        'Chegou frio ou passado' => 'Fora da temperatura',
        'Faltaram itens' => 'Veio incompleto',
        'Sem manual' => 'Faltou algo combinado',
        'Serviço malfeito' => 'Qualidade mediana',
        'Caro pelo que entrega' => 'Preço salgado',
        'Cobram taxa escondida' => 'Teve cobrança extra',
    ];

    public static function definitions(): array
    {
        return require database_path('data/tags_seed.php');
    }

    public static function sync(): void
    {
        $definitions = self::definitions();

        foreach ($definitions as $position => $definition) {
            DB::table('tags')->updateOrInsert(['name' => $definition['name']], [
                'sentiment' => $definition['sentiment'],
                'characteristic' => $definition['characteristic'],
                'kind' => $definition['kind'],
                'categories' => json_encode($definition['categories']),
                'position' => $position,
                'updated_at' => now(),
            ]);
        }

        $ids = DB::table('tags')->whereIn('name', array_column($definitions, 'name'))->pluck('id', 'name');
        foreach ($definitions as $definition) {
            DB::table('tags')->where('id', $ids[$definition['name']])->update(['opposite_id' => $ids[$definition['opposite']]]);
        }
    }

    public static function applies(object $tag, string $kind, array $categories): bool
    {
        $tagCategories = is_string($tag->categories) ? json_decode($tag->categories, true) : (array) $tag->categories;
        $tagKind = $tag->kind instanceof \BackedEnum ? $tag->kind->value : $tag->kind;

        return ($tagKind === null || $tagKind === $kind)
            && ($tagCategories === [] || array_intersect($tagCategories, $categories) !== []);
    }

    public static function forItem(string $kind, array $categories): Collection
    {
        return Tag::query()->orderBy('position')->get()
            ->filter(fn (Tag $tag) => self::applies($tag, $kind, $categories))
            ->values();
    }

    public const RATING_COLUMN = [
        'entrega' => 'delivery_rating',
        'qualidade' => 'quality_rating',
        'conformidade' => 'conformity_rating',
        'custo_beneficio' => 'value_rating',
    ];

    public static function withoutOpposites(array $tagIds, object $review): array
    {
        $tags = DB::table('tags')->whereIn('id', $tagIds)->get()->keyBy('id');

        return array_values(array_filter($tagIds, function ($id) use ($tags, $tagIds, $review) {
            $tag = $tags[$id] ?? null;
            if (! $tag || ! $tag->opposite_id || ! in_array($tag->opposite_id, $tagIds, false)) {
                return true;
            }
            $good = ($review->{self::RATING_COLUMN[$tag->characteristic]} ?? 3) >= 3;

            return $good === ($tag->sentiment === 'positive');
        }));
    }

    public static function fromLegacyIds(array $legacyIds, string $kind, array $categories): array
    {
        $names = array_keys(self::LEGACY_MAP);
        $tags = DB::table('tags')->get();

        return collect($legacyIds)
            ->map(fn ($id) => isset($names[$id - 1]) ? self::replacement($names[$id - 1], $kind, $categories, $tags)?->id : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function replacement(string $legacyName, string $kind, array $categories, Collection $tags): ?object
    {
        $tags = $tags->whereIn('name', array_column(self::definitions(), 'name'))->sortBy('position');
        $preferred = $tags->firstWhere('name', self::LEGACY_MAP[$legacyName] ?? null);
        if ($preferred && self::applies($preferred, $kind, $categories)) {
            return $preferred;
        }

        $reference = $preferred;
        if (! $reference) {
            return null;
        }

        return $tags->first(fn ($tag) => $tag->kind === null && json_decode($tag->categories, true) === []
            && $tag->sentiment === $reference->sentiment && $tag->characteristic === $reference->characteristic);
    }
}
