<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Status;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->validated('targetType');
        $targetId = (int) $request->validated('targetId');

        $authorUserId = $type === Report::TARGET_REVIEW
            ? Review::query()->published()->with('customer')->find($targetId)?->customer?->user_id
            : ReviewComment::query()->find($targetId)?->user_id;

        if ($authorUserId === null) {
            return $this->sendError('Conteúdo não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        if ($authorUserId === $user->id) {
            return $this->sendError('Você não pode denunciar o que você mesmo publicou.', [], Status::HTTP_UNPROCESSABLE_ENTITY);
        }

        $report = Report::query()->firstOrCreate(
            ['reporter_id' => $user->id, 'reportable_type' => $type, 'reportable_id' => $targetId],
            [
                'reason' => $request->validated('reason'),
                'description' => $request->validated('description'),
                'status' => ReportStatus::Pending,
            ],
        );

        return $this->sendResponse(['id' => $report->id], 'Denúncia enviada.', Status::HTTP_CREATED);
    }
}
