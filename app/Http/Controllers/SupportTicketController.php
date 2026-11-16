<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as Status;

class SupportTicketController extends Controller
{
    private const MESSAGE_MAX = 2000;

    public function index(Request $request): JsonResponse
    {
        $tickets = $request->user()->supportTickets()->with('messages')->latest('updated_at')->get();

        return $this->sendResponse($tickets->map(fn (SupportTicket $ticket) => $this->ticket($ticket))->values());
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, Status::HTTP_NOT_FOUND, 'Ticket não encontrado.');

        return $this->sendResponse($this->ticket($ticket->load('messages')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'topic' => ['required', Rule::in(SupportTicket::TOPICS)],
            'message' => ['required', 'string', 'max:'.self::MESSAGE_MAX],
        ], [
            'subject.required' => 'Diga em poucas palavras do que se trata.',
            'subject.max' => 'Use até 120 caracteres.',
            'topic.*' => 'Escolha o assunto.',
            'message.required' => 'Conte o que aconteceu.',
            'message.max' => 'Use até '.self::MESSAGE_MAX.' caracteres.',
        ]);

        $ticket = DB::transaction(function () use ($request, $data) {
            $ticket = $request->user()->supportTickets()->create(['subject' => $data['subject'], 'topic' => $data['topic'], 'status' => 'open']);
            $ticket->messages()->create(['user_id' => $request->user()->id, 'sender' => SupportTicketMessage::FROM_REQUESTER, 'body' => $data['message']]);

            return $ticket;
        });

        return $this->sendResponse($this->ticket($ticket->load('messages')), 'Pedido enviado. A equipe da APROV responde por aqui.', Status::HTTP_CREATED);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, Status::HTTP_NOT_FOUND, 'Ticket não encontrado.');
        $data = $request->validate(['text' => ['required', 'string', 'max:'.self::MESSAGE_MAX]], ['text.*' => 'Escreva a mensagem.']);

        DB::transaction(function () use ($ticket, $data, $request) {
            $ticket->messages()->create(['user_id' => $request->user()->id, 'sender' => SupportTicketMessage::FROM_REQUESTER, 'body' => $data['text']]);
            $ticket->update(['status' => 'open']);
        });

        return $this->sendResponse($this->ticket($ticket->fresh('messages')), 'Mensagem enviada.');
    }

    private function ticket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'topic' => $ticket->topic,
            'status' => $ticket->status,
            'createdAt' => $ticket->created_at->toIso8601String(),
            'updatedAt' => $ticket->updated_at->toIso8601String(),
            'messages' => $ticket->messages->map(fn (SupportTicketMessage $message) => [
                'id' => $message->id,
                'fromSupport' => $message->sender === SupportTicketMessage::FROM_ADMIN,
                'text' => $message->body,
                'createdAt' => $message->created_at->toIso8601String(),
            ])->values(),
        ];
    }
}
