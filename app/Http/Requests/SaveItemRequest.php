<?php

namespace App\Http\Requests;

use App\Enums\Category;
use App\Enums\ItemKind;
use App\Enums\ReviewMediaType;
use App\Enums\ServiceModality;
use App\Enums\ServiceRecurrence;
use App\Models\Item;
use App\Models\ItemMedia;
use App\Support\Municipalities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveItemRequest extends FormRequest
{
    public const MAX_MEDIA = 6;

    public const MAX_DURATION_MINUTES = 7 * 24 * 60;

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['distinct', Rule::enum(Category::class)],
            'mediaIds' => ['required', 'array', 'min:1', 'max:'.self::MAX_MEDIA],
            'mediaIds.*' => ['integer', 'distinct'],
        ];

        if (! $this->editing()) {
            $rules['kind'] = ['required', Rule::enum(ItemKind::class)];
        }

        if ($this->kind() === ItemKind::Product) {
            return $rules + ['brand' => ['nullable', 'string', 'max:60']];
        }

        return $rules + [
            'durationMinutes' => ['required', 'integer', 'min:1', 'max:'.self::MAX_DURATION_MINUTES],
            'recurrence' => ['required', Rule::enum(ServiceRecurrence::class)],
            'modality' => ['required', Rule::enum(ServiceModality::class)],
            'cep' => [Rule::requiredIf($this->input('modality') === ServiceModality::OnSite->value), 'nullable', 'digits:8'],
            'cities' => [Rule::requiredIf($this->input('modality') === ServiceModality::AtHome->value), 'nullable', 'array'],
            'cities.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'kind.required' => 'Escolha se é produto ou serviço.',
            'title.required' => 'Informe o título.',
            'title.max' => 'Use até 120 caracteres.',
            'description.required' => 'Descreva o item.',
            'description.max' => 'Use até 1000 caracteres.',
            'categories.required' => 'Escolha pelo menos uma categoria.',
            'categories.min' => 'Escolha pelo menos uma categoria.',
            'categories.*' => 'Categoria inválida.',
            'brand.max' => 'Use até 60 caracteres.',
            'mediaIds.required' => 'Adicione pelo menos uma foto, que vira a capa do item.',
            'mediaIds.min' => 'Adicione pelo menos uma foto, que vira a capa do item.',
            'mediaIds.max' => 'Cada item pode ter no máximo '.self::MAX_MEDIA.' fotos ou vídeos.',
            'mediaIds.*' => 'Arquivo inválido. Envie de novo.',
            'durationMinutes.required' => 'Informe a duração.',
            'durationMinutes.integer' => 'Use um número inteiro maior que zero.',
            'durationMinutes.min' => 'Use um número inteiro maior que zero.',
            'durationMinutes.max' => 'A duração vai até 7 dias (168 horas).',
            'recurrence.required' => 'Escolha a recorrência.',
            'recurrence.enum' => 'Escolha a recorrência.',
            'modality.required' => 'Escolha a modalidade.',
            'modality.enum' => 'Escolha a modalidade.',
            'cep.required' => 'Informe o CEP do local, com 8 números.',
            'cep.digits' => 'Informe o CEP do local, com 8 números.',
            'cities.required' => 'Adicione pelo menos uma cidade atendida.',
            'cities.*' => 'Cidade inválida.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->checkMedia($validator);
                $this->checkCities($validator);
            },
        ];
    }

    public function editing(): ?Item
    {
        $item = $this->route('panelItem');

        return $item instanceof Item ? $item : null;
    }

    public function kind(): ItemKind
    {
        return $this->editing()?->kind ?? ItemKind::tryFrom((string) $this->input('kind')) ?? ItemKind::Product;
    }

    public function media(): array
    {
        $ids = array_map('intval', $this->validated('mediaIds'));
        $media = $this->ownedMedia($ids)->keyBy('id');

        return array_map(fn (int $id) => $media[$id], $ids);
    }

    public function cities(): array
    {
        if ($this->kind() !== ItemKind::Service || $this->validated('modality') !== ServiceModality::AtHome->value) {
            return [];
        }

        return array_map(fn ($code) => Municipalities::find((int) $code), $this->validated('cities'));
    }

    public function itemAttributes(): array
    {
        $data = $this->validated();
        $service = $this->kind() === ItemKind::Service;
        $modality = $service ? $data['modality'] : null;

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'categories' => array_values($data['categories']),
            'brand' => $service ? null : ($data['brand'] ?? null),
            'duration_minutes' => $service ? $data['durationMinutes'] : null,
            'recurrence' => $service ? $data['recurrence'] : null,
            'service_modality' => $modality,
            'cep' => $modality === ServiceModality::OnSite->value ? $data['cep'] : null,
        ];
    }

    private function ownedMedia(array $ids)
    {
        $item = $this->editing();

        return ItemMedia::query()
            ->whereIn('id', $ids)
            ->where('company_id', $this->user()->company->id)
            ->where(fn ($query) => $item
                ? $query->whereNull('item_id')->orWhere('item_id', $item->id)
                : $query->whereNull('item_id'))
            ->get();
    }

    private function checkMedia(Validator $validator): void
    {
        $ids = array_map('intval', $this->input('mediaIds'));
        $media = $this->ownedMedia($ids);

        if ($media->count() !== count($ids)) {
            $validator->errors()->add('mediaIds', 'Algum arquivo não foi encontrado. Envie de novo.');

            return;
        }

        if (! $media->contains(fn (ItemMedia $file) => $file->type === ReviewMediaType::Photo)) {
            $validator->errors()->add('mediaIds', 'Adicione pelo menos uma foto, que vira a capa do item.');
        }
    }

    private function checkCities(Validator $validator): void
    {
        if ($this->kind() !== ItemKind::Service || $this->input('modality') !== ServiceModality::AtHome->value) {
            return;
        }

        $codes = $this->input('cities', []);

        if ($codes === []) {
            $validator->errors()->add('cities', 'Adicione pelo menos uma cidade atendida.');

            return;
        }

        foreach ($codes as $code) {
            if (! Municipalities::find((int) $code)) {
                $validator->errors()->add('cities', 'Alguma cidade não está na lista do IBGE.');

                return;
            }
        }
    }
}
