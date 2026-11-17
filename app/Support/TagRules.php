<?php

namespace App\Support;

use App\Models\Item;
use App\Models\Tag;
use Illuminate\Validation\Validator;

class TagRules
{
    public static function check(Validator $validator, ?Item $item, array $tagIds): void
    {
        if (! $item || $tagIds === []) {
            return;
        }

        $tags = Tag::query()->whereIn('id', $tagIds)->get();

        if ($tags->contains(fn (Tag $tag) => ! TagCatalog::applies($tag, $item->kind->value, $item->categories ?? []))) {
            $validator->errors()->add('tagIds', 'Alguma tag não vale para este item.');

            return;
        }

        if ($tags->contains(fn (Tag $tag) => $tag->opposite_id && in_array($tag->opposite_id, $tagIds, false))) {
            $validator->errors()->add('tagIds', 'Não dá para marcar uma tag e a oposta dela juntas.');
        }
    }
}
