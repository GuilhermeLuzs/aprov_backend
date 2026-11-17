<?php

namespace Database\Seeders;

use App\Enums\ItemKind;
use App\Enums\ReactionType;
use App\Enums\TagSentiment;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use App\Support\FakeCpf;
use App\Support\TagCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReviewHistorySeeder extends Seeder
{
    private const RANDOM_SEED = 20261115;

    private const EMAIL_DOMAIN = 'historico.aprov.teste';

    private const CUSTOMERS = 90;

    private const CPF_SEQUENCE_START = 500;

    private const HISTORY_DAYS = 180;

    private const FEATURED_COMPANY = 'c2';

    private const NO_REVIEW_TITLES = ['Espaguete ao sugo congelado 500g'];

    private const INACTIVE_SINCE_DAYS = 45;

    private const FIRST_NAMES = [
        'Ana', 'Beatriz', 'Bruno', 'Camila', 'Carlos', 'Daniela', 'Diego', 'Eduarda', 'Felipe', 'Fernanda',
        'Gabriel', 'Giovana', 'Gustavo', 'Helena', 'Igor', 'Isabela', 'João', 'Júlia', 'Kauã', 'Laura',
        'Leonardo', 'Letícia', 'Lucas', 'Luana', 'Marcelo', 'Marina', 'Mateus', 'Natália', 'Otávio', 'Patrícia',
        'Paulo', 'Rafaela', 'Renato', 'Sabrina', 'Samuel', 'Tatiane', 'Thiago', 'Vanessa', 'Vinícius', 'Yasmin',
    ];

    private const LAST_NAMES = [
        'Almeida', 'Andrade', 'Barbosa', 'Cardoso', 'Carvalho', 'Castro', 'Costa', 'Dias', 'Fernandes', 'Ferreira',
        'Gomes', 'Lima', 'Lopes', 'Machado', 'Martins', 'Melo', 'Moreira', 'Nascimento', 'Oliveira', 'Pereira',
        'Ribeiro', 'Rocha', 'Santos', 'Silva', 'Soares', 'Souza', 'Teixeira', 'Vieira',
    ];

    private const OPENINGS = [
        'good' => [
            'Gostei muito.', 'Superou o que eu esperava.', 'Recomendo sem medo.', 'Valeu cada centavo.',
            'Foi uma ótima experiência.', 'Já é a segunda vez e continua bom.',
        ],
        'fair' => [
            'Foi ok, nada de mais.', 'Tem pontos bons e pontos fracos.', 'Esperava um pouco mais.',
            'Cumpre o básico.', 'Dá para usar, mas não me impressionou.',
        ],
        'bad' => [
            'Ficou bem abaixo do que eu esperava.', 'Não recomendo.', 'Não foi o que eu esperava.', 'Me arrependi.',
            'Tive uma experiência ruim.',
        ],
    ];

    private const DETAILS = [
        ItemKind::Product->value => [
            'delivery' => ['good' => 'Chegou antes do prazo e bem embalado.', 'bad' => 'A entrega atrasou e ninguém avisou.'],
            'quality' => ['good' => 'A qualidade é muito boa, dá para sentir o cuidado.', 'bad' => 'A qualidade deixou a desejar.'],
            'conformity' => ['good' => 'Veio exatamente como na descrição.', 'bad' => 'Veio diferente do que estava na descrição.'],
            'value' => ['good' => 'O preço é justo pelo que entrega.', 'bad' => 'Achei caro pelo que é.'],
        ],
        ItemKind::Service->value => [
            'delivery' => ['good' => 'Foram pontuais e organizados.', 'bad' => 'Atrasaram e tive que remarcar.'],
            'quality' => ['good' => 'O serviço foi muito bem feito.', 'bad' => 'O serviço ficou mal feito em alguns pontos.'],
            'conformity' => ['good' => 'Fizeram tudo o que foi combinado.', 'bad' => 'Não fizeram tudo o que foi combinado.'],
            'value' => ['good' => 'O valor compensa.', 'bad' => 'O valor não compensa.'],
        ],
    ];

    private const CLOSINGS = [
        'good' => ['Vou comprar de novo.', 'Já indiquei para a família.', 'Nota alta merecida.', ''],
        'fair' => ['Talvez eu dê outra chance.', 'Se melhorar, volto.', ''],
        'bad' => ['Não volto a comprar.', 'Espero que melhorem.', ''],
    ];

    private const CHARACTERISTICS = [
        'delivery' => 'entrega',
        'quality' => 'qualidade',
        'conformity' => 'conformidade',
        'value' => 'custo_beneficio',
    ];

    public function run(): void
    {
        if (User::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->exists()) {
            return;
        }

        mt_srand(self::RANDOM_SEED);
        $now = CarbonImmutable::now();
        $catalog = require database_path('data/catalog_seed.php');
        $featured = collect($catalog['items'])->where('company_id', self::FEATURED_COMPANY)->pluck('title')->all();
        $tags = Tag::query()->get();

        DB::transaction(function () use ($now, $featured, $tags) {
            $customers = $this->seedCustomers($now);

            Item::query()->orderBy('id')->get()->each(function (Item $item) use ($customers, $now, $featured, $tags) {
                if (in_array($item->title, self::NO_REVIEW_TITLES, true)) {
                    return;
                }

                $isFeatured = in_array($item->title, $featured, true);
                $count = $isFeatured ? mt_rand(22, 45) : mt_rand(6, 18);
                $mean = mt_rand(26, 47) / 10;
                $latestDay = $item->is_active ? 0 : self::INACTIVE_SINCE_DAYS;

                foreach ($this->shuffled($customers)->take($count) as $customer) {
                    $this->seedReview($item, $customer, $customers, $mean, $this->randomMoment($now, $latestDay), $tags);
                }
            });
        });
    }

    private function seedCustomers(CarbonImmutable $now): Collection
    {
        $password = Hash::make(Str::password(32));
        $customers = collect();

        for ($index = 0; $index < self::CUSTOMERS; $index++) {
            $first = self::FIRST_NAMES[$index % count(self::FIRST_NAMES)];
            $last = self::LAST_NAMES[mt_rand(0, count(self::LAST_NAMES) - 1)];
            $username = Str::slug("{$first} {$last}", '.').'.'.($index + 1);

            $user = User::create([
                'name' => "{$first} {$last}",
                'email' => "{$username}@".self::EMAIL_DOMAIN,
                'password' => $password,
                'role' => UserRole::Customer,
            ]);

            $customer = $user->customer()->create([
                'username' => $username,
                'cpf' => FakeCpf::fromSequence(self::CPF_SEQUENCE_START + $index),
                'birth_date' => $now->subYears(mt_rand(18, 60))->subDays(mt_rand(0, 364))->toDateString(),
            ]);
            $customer->forceFill([
                'level' => mt_rand(1, 6),
                'xp' => mt_rand(40, 2400),
                'created_at' => $now->subDays(mt_rand(self::HISTORY_DAYS, self::HISTORY_DAYS + 240)),
            ])->save();

            $customers->push($customer->setRelation('user', $user));
        }

        return $customers;
    }

    private function seedReview(Item $item, Customer $customer, Collection $customers, float $mean, CarbonImmutable $moment, Collection $tags): void
    {
        $ratings = [];
        foreach (array_keys(self::CHARACTERISTICS) as $characteristic) {
            $ratings[$characteristic] = max(1, min(5, (int) round($mean + mt_rand(-12, 12) / 10)));
        }
        $average = array_sum($ratings) / count($ratings);
        $tone = $average >= 4 ? 'good' : ($average >= 3 ? 'fair' : 'bad');

        $review = new Review([
            'customer_id' => $customer->id,
            'item_id' => $item->id,
            'delivery_rating' => $ratings['delivery'],
            'quality_rating' => $ratings['quality'],
            'conformity_rating' => $ratings['conformity'],
            'value_rating' => $ratings['value'],
            'average_rating' => round($average, 1),
            'comment' => $this->comment($item->kind, $ratings, $tone),
            'would_buy_again' => $average >= 3.5,
            'status' => Review::STATUS_PUBLISHED,
        ]);
        $review->created_at = $moment;
        $review->updated_at = $moment;
        $review->save();

        $review->tags()->sync(TagCatalog::withoutOpposites($this->pickTags($item, $ratings, $tags), $review));
        $this->seedReactions($review, $customer, $customers, $tone, $moment);
    }

    private function comment(ItemKind $kind, array $ratings, string $tone): string
    {
        $parts = [$this->pick(self::OPENINGS[$tone])];

        foreach ($ratings as $characteristic => $rating) {
            if ($rating >= 4 && mt_rand(0, 1) === 1) {
                $parts[] = self::DETAILS[$kind->value][$characteristic]['good'];
            } elseif ($rating <= 2) {
                $parts[] = self::DETAILS[$kind->value][$characteristic]['bad'];
            }
        }

        $parts[] = $this->pick(self::CLOSINGS[$tone]);

        return implode(' ', array_filter($parts));
    }

    private function pickTags(Item $item, array $ratings, Collection $tags): array
    {
        $fits = $tags->filter(fn (Tag $tag) => TagCatalog::applies($tag, $item->kind->value, $item->categories ?? []));
        $chosen = [];

        foreach ($ratings as $characteristic => $rating) {
            $sentiment = $rating >= 4 ? TagSentiment::Positive : ($rating <= 2 ? TagSentiment::Negative : null);

            if (! $sentiment || mt_rand(0, 2) === 0) {
                continue;
            }

            $options = $fits->filter(fn (Tag $tag) => $tag->sentiment === $sentiment && $tag->characteristic->value === self::CHARACTERISTICS[$characteristic]);

            if ($options->isNotEmpty()) {
                $chosen[] = $options->values()[mt_rand(0, $options->count() - 1)]->id;
            }
        }

        if ($chosen === []) {
            $sentiment = array_sum($ratings) / count($ratings) >= 3 ? TagSentiment::Positive : TagSentiment::Negative;
            $options = $fits->filter(fn (Tag $tag) => $tag->sentiment === $sentiment)->values();
            $chosen[] = $options[mt_rand(0, $options->count() - 1)]->id;
        }

        return array_values(array_unique($chosen));
    }

    private function seedReactions(Review $review, Customer $author, Collection $customers, string $tone, CarbonImmutable $moment): void
    {
        $voters = $this->shuffled($customers->reject(fn (Customer $customer) => $customer->id === $author->id))->take(mt_rand(0, 8));
        $rows = $voters->map(fn (Customer $voter) => [
            'user_id' => $voter->user_id,
            'review_id' => $review->id,
            'type' => (mt_rand(1, 10) <= ($tone === 'bad' ? 6 : 8) ? ReactionType::Agree : ReactionType::Disagree)->value,
            'created_at' => $moment,
            'updated_at' => $moment,
        ])->all();

        if ($rows !== []) {
            DB::table('review_reactions')->insert($rows);
        }
    }

    private function randomMoment(CarbonImmutable $now, int $latestDay): CarbonImmutable
    {
        $span = self::HISTORY_DAYS - $latestDay;
        $daysAgo = $latestDay + (int) floor($span * (1 - sqrt(mt_rand() / mt_getrandmax())));
        $moment = $now->subDays($daysAgo)->setTimezone(config('aprov.panel_timezone'))->setTime(mt_rand(8, 22), mt_rand(0, 59));

        return $moment->greaterThan($now) ? $now->subMinutes(mt_rand(5, 300)) : $moment->utc();
    }

    private function shuffled(Collection $items): Collection
    {
        $list = $items->values()->all();

        for ($index = count($list) - 1; $index > 0; $index--) {
            $swap = mt_rand(0, $index);
            [$list[$index], $list[$swap]] = [$list[$swap], $list[$index]];
        }

        return collect($list);
    }

    private function pick(array $options): string
    {
        return $options[mt_rand(0, count($options) - 1)];
    }
}
