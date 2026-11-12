<?php

namespace Database\Seeders;

use App\Enums\ReactionType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use App\Support\FakeCpf;
use App\Support\SeedMedia;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CommunitySeeder extends Seeder
{
    private const TEST_ACCOUNT_KEY = 'u1';

    private const TEST_ACCOUNT_USERNAME = 'mari.cavalcanti';

    private const CPF_SEQUENCE_START = 100;

    private const BIRTH_DATE = '1995-06-15';

    private const PASSWORD = 'aprov123';

    public function run(): void
    {
        if (Review::query()->exists()) {
            return;
        }

        $seed = json_decode(File::get(database_path('data/community_seed.json')), true);

        DB::transaction(function () use ($seed) {
            $customers = $this->seedCustomers($seed['users']);
            $this->seedFollows($seed['follows'], $customers);

            foreach ($seed['reviews'] as $review) {
                $this->seedReview($review, $customers);
            }

            $testAccount = $customers[self::TEST_ACCOUNT_KEY];
            foreach ($seed['test_account_items'] as $state) {
                $testAccount->itemStates()->create($state);
            }
        });
    }

    private function seedCustomers(array $users): array
    {
        $customers = [];

        foreach ($users as $index => $data) {
            $customer = $data['key'] === self::TEST_ACCOUNT_KEY
                ? Customer::with('user')->where('username', self::TEST_ACCOUNT_USERNAME)->firstOrFail()
                : $this->createCustomer($data, $index);

            $customer->user->update(['avatar_path' => $data['avatar_path'] ? SeedMedia::publish($data['avatar_path']) : null]);
            $customer->forceFill([
                'bio' => $data['bio'],
                'level' => $data['level'],
                'xp' => $data['xp'],
                'created_at' => CarbonImmutable::parse($data['joined_at']),
            ])->save();

            $customers[$data['key']] = $customer;
        }

        return $customers;
    }

    private function createCustomer(array $data, int $index): Customer
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => "{$data['username']}@aprov.teste",
            'password' => self::PASSWORD,
            'role' => UserRole::Customer,
        ]);

        return $user->customer()->create([
            'username' => $data['username'],
            'cpf' => FakeCpf::fromSequence(self::CPF_SEQUENCE_START + $index),
            'birth_date' => self::BIRTH_DATE,
        ])->setRelation('user', $user);
    }

    private function seedFollows(array $follows, array $customers): void
    {
        foreach ($follows as $follow) {
            $customers[$follow['follower']]->following()->attach($customers[$follow['followed']]->id);
        }
    }

    private function seedReview(array $data, array $customers): void
    {
        $createdAt = CarbonImmutable::parse($data['created_at']);
        $ratings = [$data['delivery_rating'], $data['quality_rating'], $data['conformity_rating'], $data['value_rating']];

        $review = new Review([
            'customer_id' => $customers[$data['author']]->id,
            'item_id' => $data['item_id'],
            'delivery_rating' => $ratings[0],
            'quality_rating' => $ratings[1],
            'conformity_rating' => $ratings[2],
            'value_rating' => $ratings[3],
            'average_rating' => round(array_sum($ratings) / count($ratings), 1),
            'comment' => $data['comment'],
            'would_buy_again' => $data['would_buy_again'],
            'status' => Review::STATUS_PUBLISHED,
            'edited_at' => $data['edited_at'] ? CarbonImmutable::parse($data['edited_at']) : null,
        ]);
        $review->created_at = $createdAt;
        $review->updated_at = $createdAt;
        $review->save();

        $review->tags()->sync($data['tag_ids']);

        foreach ($data['media'] as $position => $file) {
            $review->media()->create([
                'type' => $file['type'],
                'path' => SeedMedia::publish($file['path']),
                'cover_path' => isset($file['cover_path']) ? SeedMedia::publish($file['cover_path']) : null,
                'width' => $file['width'] ?? null,
                'height' => $file['height'] ?? null,
                'duration_seconds' => $file['duration_seconds'] ?? null,
                'position' => $position,
            ]);
        }

        foreach ($data['versions'] as $version) {
            $versionRatings = [$version['delivery_rating'], $version['quality_rating'], $version['conformity_rating'], $version['value_rating']];
            $review->versions()->create([
                ...$version,
                'average_rating' => round(array_sum($versionRatings) / count($versionRatings), 1),
                'would_buy_again' => $data['would_buy_again'],
                'tag_ids' => $data['tag_ids'],
                'media_ids' => $review->media()->pluck('id')->all(),
                'published_at' => CarbonImmutable::parse($version['published_at']),
            ]);
        }

        $this->seedReactions($review->reactions(), $data, $customers);

        foreach ($data['comments'] as $comment) {
            $parent = $this->seedComment($review, $comment, $customers, null);

            foreach ($comment['replies'] as $reply) {
                $this->seedComment($review, $reply, $customers, $parent);
            }
        }
    }

    private function seedComment(Review $review, array $data, array $customers, ?ReviewComment $parent): ReviewComment
    {
        $createdAt = CarbonImmutable::parse($data['created_at']);

        $comment = new ReviewComment([
            'review_id' => $review->id,
            'user_id' => $customers[$data['author']]->user_id,
            'parent_id' => $parent?->id,
            'body' => $data['body'],
        ]);
        $comment->created_at = $createdAt;
        $comment->updated_at = $createdAt;
        $comment->save();

        $this->seedReactions($comment->reactions(), $data, $customers);

        return $comment;
    }

    private function seedReactions($relation, array $data, array $customers): void
    {
        foreach ([ReactionType::Agree->value => $data['agree'], ReactionType::Disagree->value => $data['disagree']] as $type => $voters) {
            foreach ($voters as $voter) {
                $relation->create(['user_id' => $customers[$voter]->user_id, 'type' => $type]);
            }
        }
    }
}
