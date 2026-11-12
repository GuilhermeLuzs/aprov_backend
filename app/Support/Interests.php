<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Interests
{
    private function __construct(
        private readonly ?int $customerId,
        private readonly array $categories,
        private readonly array $companyIds,
    ) {
    }

    public static function of(?Customer $customer): self
    {
        if (! $customer) {
            return new self(null, [], []);
        }

        return new self(
            $customer->id,
            $customer->preferred_categories ?? [],
            $customer->followedCompanies()->pluck('companies.id')->all(),
        );
    }

    public function isEmpty(): bool
    {
        return $this->categories === [] && $this->companyIds === [];
    }

    public function orderCompanies(Builder $query): Builder
    {
        if ($this->customerId !== null && $this->companyIds !== []) {
            $query->orderByDesc(
                DB::table('company_customer')
                    ->selectRaw('count(*)')
                    ->whereColumn('company_customer.company_id', 'companies.id')
                    ->where('company_customer.customer_id', $this->customerId),
            );
        }

        if ($this->categories !== []) {
            [$condition, $bindings] = $this->categoryCondition('items.categories');
            $query->orderByDesc(
                Item::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('items.company_id', 'companies.id')
                    ->where('is_active', true)
                    ->whereRaw($condition, $bindings),
            );
        }

        return $query;
    }

    public function orderItems(Builder $query): Builder
    {
        if ($this->categories === []) {
            return $query;
        }

        [$condition, $bindings] = $this->categoryCondition('categories');

        return $query->orderByRaw("{$condition} DESC", $bindings);
    }

    public function orderReviews(Builder $query): Builder
    {
        $conditions = [];
        $bindings = [];

        if ($this->categories !== []) {
            [$condition, $categoryBindings] = $this->categoryCondition('items.categories');
            $conditions[] = $condition;
            array_push($bindings, ...$categoryBindings);
        }

        if ($this->companyIds !== []) {
            $conditions[] = 'items.company_id in ('.implode(',', array_fill(0, count($this->companyIds), '?')).')';
            array_push($bindings, ...$this->companyIds);
        }

        if ($conditions === []) {
            return $query;
        }

        return $query->orderByDesc(
            Item::query()
                ->selectRaw('('.implode(' or ', $conditions).')', $bindings)
                ->whereColumn('items.id', 'reviews.item_id'),
        );
    }

    private function categoryCondition(string $column): array
    {
        $parts = array_fill(0, count($this->categories), "JSON_CONTAINS({$column}, ?)");

        return ['('.implode(' OR ', $parts).')', array_map('json_encode', $this->categories)];
    }
}
