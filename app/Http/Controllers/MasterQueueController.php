<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\ModerationAssignment;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterQueueController extends Controller
{
    private const RESOLVED_DAYS = 90;

    public function show(): JsonResponse
    {
        $reports = Report::query()
            ->where(fn ($query) => $query->where('status', ReportStatus::Pending)->orWhere('resolved_at', '>=', now()->subDays(self::RESOLVED_DAYS)))
            ->with('reporter')
            ->orderBy('id')
            ->get();

        $tickets = SupportTicket::query()->with(['user.customer', 'user.company', 'messages'])->latest('updated_at')->get();

        return $this->sendResponse([
            'reports' => $reports->map(fn (Report $report) => [
                'id' => (string) $report->id,
                'target' => $this->target($report),
                'reporterId' => (string) $report->reporter_id,
                'reporterName' => $report->reporter?->name ?? 'Conta removida',
                'reason' => $report->reason,
                'comment' => $report->description ?? '',
                'createdAt' => $report->created_at->toIso8601String(),
            ])->values(),
            'resolutions' => $reports->where('status', ReportStatus::Resolved)
                ->unique(fn (Report $report) => $report->reportable_type.':'.$report->reportable_id)
                ->map(fn (Report $report) => [
                    'target' => $this->target($report),
                    'decision' => $report->decision,
                    'note' => $report->resolution_note ?? '',
                    'byId' => (string) $report->resolved_by,
                    'at' => $report->resolved_at?->toIso8601String(),
                ])->values(),
            'assignments' => ModerationAssignment::query()->get()->map(fn (ModerationAssignment $assignment) => [
                'queue' => $assignment->queue,
                'key' => $assignment->target_key,
                'toId' => (string) $assignment->user_id,
            ]),
            'tickets' => $tickets->map(fn (SupportTicket $ticket) => $this->ticket($ticket))->values(),
            'targets' => $this->targets($reports),
            'admins' => User::query()->where('role', UserRole::Master)->pluck('name', 'id'),
        ]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'targetType' => ['required', Rule::in([Report::TARGET_REVIEW, Report::TARGET_COMMENT])],
            'targetId' => ['required', 'integer'],
            'decision' => ['required', Rule::in(['removed', 'kept'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = DB::transaction(function () use ($data, $request) {
            $updated = Report::query()
                ->where('reportable_type', $data['targetType'])
                ->where('reportable_id', $data['targetId'])
                ->where('status', ReportStatus::Pending)
                ->update([
                    'status' => ReportStatus::Resolved,
                    'decision' => $data['decision'],
                    'resolution_note' => $data['note'] ?? null,
                    'resolved_by' => $request->user()->id,
                    'resolved_at' => now(),
                ]);

            if ($updated && $data['decision'] === 'removed') {
                $data['targetType'] === Report::TARGET_REVIEW
                    ? Review::query()->whereKey($data['targetId'])->update(['status' => Review::STATUS_REMOVED])
                    : ReviewComment::query()->whereKey($data['targetId'])->delete();
            }

            return $updated;
        });

        abort_unless($updated, 404, 'Nenhuma denúncia pendente para isso.');

        return $this->sendResponse(null, $data['decision'] === 'removed' ? 'Conteúdo removido.' : 'Conteúdo mantido.');
    }

    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'queue' => ['required', Rule::in(['report', 'ticket'])],
            'key' => ['required', 'string', 'max:40'],
            'toId' => ['nullable', Rule::exists('users', 'id')->where('role', UserRole::Master->value)],
        ], ['toId.exists' => 'Escolha alguém da equipe da APROV.', '*' => 'Pedido inválido.']);

        if (empty($data['toId'])) {
            ModerationAssignment::query()->where('queue', $data['queue'])->where('target_key', $data['key'])->delete();
        } else {
            ModerationAssignment::query()->updateOrCreate(
                ['queue' => $data['queue'], 'target_key' => $data['key']],
                ['user_id' => $data['toId']],
            );
        }

        return $this->sendResponse(null, 'Responsável atualizado.');
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
        ]);

        DB::transaction(function () use ($ticket, $data, $request) {
            $ticket->messages()->create(['user_id' => $request->user()->id, 'sender' => SupportTicketMessage::FROM_ADMIN, 'body' => $data['text']]);
            $ticket->update(['status' => $data['status']]);
        });

        return $this->sendResponse($this->ticket($ticket->fresh(['user.customer', 'user.company', 'messages'])), 'Resposta enviada.');
    }

    public function status(Request $request, SupportTicket $ticket): JsonResponse
    {
        $ticket->update($request->validate(['status' => ['required', Rule::in(SupportTicket::STATUSES)]]));

        return $this->sendResponse($this->ticket($ticket->fresh(['user.customer', 'user.company', 'messages'])), 'Status atualizado.');
    }

    private function target(Report $report): array
    {
        return ['kind' => $report->reportable_type, 'id' => (string) $report->reportable_id];
    }

    private function ticket(SupportTicket $ticket): array
    {
        $company = $ticket->user?->company;

        return [
            'id' => (string) $ticket->id,
            'requester' => $company
                ? ['kind' => 'company', 'id' => (string) $company->id]
                : ['kind' => 'customer', 'id' => (string) $ticket->user?->customer?->id],
            'requesterName' => $company?->name ?? $ticket->user?->name ?? 'Conta removida',
            'subject' => $ticket->subject,
            'topic' => $ticket->topic,
            'status' => $ticket->status,
            'createdAt' => $ticket->created_at->toIso8601String(),
            'messages' => $ticket->messages->map(fn (SupportTicketMessage $message) => [
                'id' => (string) $message->id,
                'from' => $message->sender,
                'adminId' => $message->sender === SupportTicketMessage::FROM_ADMIN ? (string) $message->user_id : null,
                'text' => $message->body,
                'createdAt' => $message->created_at->toIso8601String(),
            ])->values(),
        ];
    }

    private function targets($reports): array
    {
        $ids = $reports->groupBy('reportable_type')->map(fn ($group) => $group->pluck('reportable_id')->unique()->all());
        $targets = [];

        Review::withTrashed()->with(['item.company', 'customer.user'])->whereIn('id', $ids[Report::TARGET_REVIEW] ?? [])->get()
            ->each(function (Review $review) use (&$targets) {
                $targets[Report::TARGET_REVIEW.':'.$review->id] = [
                    'title' => 'Avaliação de '.($review->item?->title ?? 'item removido'),
                    'text' => $review->comment,
                    'context' => collect([$review->item?->title, $review->item?->company?->name])->filter()->implode(' · '),
                    'owner' => $review->customer ? ['ref' => ['kind' => 'customer', 'id' => (string) $review->customer->id], 'name' => $review->customer->user?->name] : null,
                    'rating' => (float) $review->average_rating,
                    'removed' => $review->status === Review::STATUS_REMOVED || $review->trashed(),
                ];
            });

        ReviewComment::withTrashed()->with(['review.item', 'user.customer'])->whereIn('id', $ids[Report::TARGET_COMMENT] ?? [])->get()
            ->each(function (ReviewComment $comment) use (&$targets) {
                $customer = $comment->user?->customer;
                $targets[Report::TARGET_COMMENT.':'.$comment->id] = [
                    'title' => 'Comentário',
                    'text' => $comment->body,
                    'context' => 'Na avaliação de '.($comment->review?->item?->title ?? 'item removido'),
                    'owner' => $customer ? ['ref' => ['kind' => 'customer', 'id' => (string) $customer->id], 'name' => $comment->user->name] : null,
                    'rating' => null,
                    'removed' => $comment->trashed(),
                ];
            });

        return $targets;
    }
}
