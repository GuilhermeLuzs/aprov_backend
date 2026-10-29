<?php

namespace Database\Seeders;

use App\Enums\CoinRuleTopic;
use App\Models\Company;
use App\Models\CompanyCoinRuleTopic;
use Illuminate\Database\Seeder;

class CompanyCoinRuleSeeder extends Seeder
{
    private const TOPICS = [
        ['topic' => CoinRuleTopic::CompletedReview, 'threshold' => null, 'coins' => 20, 'points' => 10, 'xp' => 15],
        ['topic' => CoinRuleTopic::MinTags, 'threshold' => 2, 'coins' => 10, 'points' => 5, 'xp' => 10],
        ['topic' => CoinRuleTopic::SentVideo, 'threshold' => null, 'coins' => 25, 'points' => 10, 'xp' => 20],
        ['topic' => CoinRuleTopic::MinValidPhotos, 'threshold' => 2, 'coins' => 15, 'points' => 5, 'xp' => 10],
        ['topic' => CoinRuleTopic::MinCommentLength, 'threshold' => 60, 'coins' => 10, 'points' => 0, 'xp' => 10],
        ['topic' => CoinRuleTopic::CoherentComment, 'threshold' => null, 'coins' => 20, 'points' => 0, 'xp' => 15],
    ];

    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            foreach (self::TOPICS as $topic) {
                CompanyCoinRuleTopic::create([...$topic, 'company_id' => $company->id]);
            }
        });
    }
}
