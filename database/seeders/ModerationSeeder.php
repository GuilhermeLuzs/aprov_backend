<?php

namespace Database\Seeders;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModerationSeeder extends Seeder
{
    private const RANDOM_SEED = 20261117;

    private const PENDING_CASES = 9;

    private const RESOLVED_CASES = 4;

    private const TICKETS = [
        ['email' => 'mariana@aprov.com', 'subject' => 'Minhas moedas da Acer não apareceram', 'topic' => 'coins', 'status' => 'resolved', 'days' => 9,
            'messages' => ['Avaliei o monitor da Acer e não recebi as moedas.', 'Oi, Mariana! As moedas entram na hora em que a avaliação é publicada. Conferimos aqui e elas já estão no seu saldo.', 'Apareceram, obrigada!']],
        ['email' => 'mariana@aprov.com', 'subject' => 'Não consigo trocar a foto do perfil', 'topic' => 'account', 'status' => 'waiting', 'days' => 2,
            'messages' => ['Quando escolho a foto nova, o app volta para a antiga.', 'Oi! Pode nos dizer qual o modelo do seu celular e se a foto é do tipo HEIC?']],
        ['email' => null, 'subject' => 'Cupom de frete grátis recusado no site', 'topic' => 'coupons', 'status' => 'open', 'days' => 1,
            'messages' => ['Resgatei o cupom de frete grátis e o site da loja diz que o código é inválido.']],
        ['email' => null, 'subject' => 'Minha avaliação sumiu', 'topic' => 'review', 'status' => 'open', 'days' => 3,
            'messages' => ['Publiquei uma avaliação ontem e hoje ela não aparece mais no meu perfil.']],
        ['email' => null, 'subject' => 'Item errado no catálogo', 'topic' => 'catalog', 'status' => 'open', 'days' => 5,
            'messages' => ['A foto do curso de inglês é de outro curso.']],
        ['email' => 'empresa@aprov.com', 'subject' => 'Como mudar o valor da moeda?', 'topic' => 'coins', 'status' => 'resolved', 'days' => 14,
            'messages' => ['Queremos que a moeda valha mais. Onde mudamos?', 'Na tela Moedas, aba Configuração, no campo "Quanto vale uma moeda".']],
    ];

    public function run(): void
    {
        if (SupportTicket::query()->exists()) {
            return;
        }

        mt_srand(self::RANDOM_SEED);
        $now = CarbonImmutable::now();
        $master = User::query()->where('role', UserRole::Master)->first();
        $customers = User::query()->where('email', 'like', '%@historico.aprov.teste')->orderBy('id')->get();

        DB::transaction(function () use ($now, $master, $customers) {
            $reviews = Review::query()->published()->whereIn('customer_id', $customers->map->customer->filter()->pluck('id'))->inRandomOrder(self::RANDOM_SEED)->limit(self::PENDING_CASES + self::RESOLVED_CASES)->get();

            foreach ($reviews as $index => $review) {
                $resolved = $index >= self::PENDING_CASES;
                $reporters = $customers->where('id', '!=', $review->customer?->user_id)->shuffle(self::RANDOM_SEED + $index)->take(mt_rand(1, 4));

                foreach ($reporters as $reporter) {
                    $reason = ReportReason::cases()[mt_rand(0, count(ReportReason::cases()) - 1)];
                    $moment = $now->subHours(mt_rand(2, 240));
                    (new Report([
                        'reporter_id' => $reporter->id,
                        'reportable_type' => Report::TARGET_REVIEW,
                        'reportable_id' => $review->id,
                        'reason' => $reason,
                        'description' => $reason === ReportReason::Other ? 'Parece escrita por alguém da própria loja.' : null,
                        'status' => $resolved ? ReportStatus::Resolved : ReportStatus::Pending,
                        'decision' => $resolved ? 'kept' : null,
                        'resolution_note' => $resolved ? 'Opinião legítima do cliente, sem dado pessoal nem ofensa.' : null,
                        'resolved_by' => $resolved ? $master?->id : null,
                        'resolved_at' => $resolved ? $moment->addHours(6) : null,
                    ]))->forceFill(['created_at' => $moment, 'updated_at' => $moment])->save();
                }
            }

            foreach (self::TICKETS as $index => $seed) {
                $user = $seed['email'] ? User::query()->where('email', $seed['email'])->first() : $customers->get($index * 7);
                if (! $user) {
                    continue;
                }

                $start = $now->subDays($seed['days']);
                $ticket = (new SupportTicket(['user_id' => $user->id, 'subject' => $seed['subject'], 'topic' => $seed['topic'], 'status' => $seed['status']]))
                    ->forceFill(['created_at' => $start, 'updated_at' => $start->addHours(count($seed['messages']) * 5)]);
                $ticket->save();

                foreach ($seed['messages'] as $position => $body) {
                    $fromAdmin = $position % 2 === 1;
                    $moment = $start->addHours($position * 5);
                    (new SupportTicketMessage([
                        'support_ticket_id' => $ticket->id,
                        'user_id' => $fromAdmin ? $master?->id : $user->id,
                        'sender' => $fromAdmin ? SupportTicketMessage::FROM_ADMIN : SupportTicketMessage::FROM_REQUESTER,
                        'body' => $body,
                    ]))->forceFill(['created_at' => $moment, 'updated_at' => $moment])->save();
                }
            }
        });
    }
}
