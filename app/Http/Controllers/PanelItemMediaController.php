<?php

namespace App\Http\Controllers;

use App\Enums\ReviewMediaType;
use App\Http\Requests\UploadItemMediaRequest;
use App\Http\Resources\PanelItemMediaResource;
use App\Models\ItemMedia;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Status;

class PanelItemMediaController extends Controller
{
    public function store(UploadItemMediaRequest $request): JsonResponse
    {
        $company = $request->user()->company;
        abort_unless($company, Status::HTTP_FORBIDDEN, 'Você não tem acesso a isso.');

        $pending = ItemMedia::query()->where('company_id', $company->id)->whereNull('item_id')->count();

        if ($pending >= config('aprov.max_pending_media')) {
            return $this->sendError('Há muitos arquivos enviados sem salvar. Salve o item ou espere um pouco.', [], Status::HTTP_TOO_MANY_REQUESTS);
        }

        $type = $request->type();
        $file = $request->file('file');
        $folder = "items/{$company->id}";
        $path = $file->store($folder, 'public');

        if ($type === ReviewMediaType::Video) {
            $attributes = [
                'cover_path' => $request->file('cover')->store($folder, 'public'),
                'width' => $request->integer('width'),
                'height' => $request->integer('height'),
                'duration_seconds' => $request->integer('durationSeconds'),
            ];
        } else {
            [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];
            $attributes = ['width' => $width, 'height' => $height];
        }

        $media = ItemMedia::create($attributes + [
            'company_id' => $company->id,
            'type' => $type,
            'path' => $path,
        ]);

        return $this->sendResponse(new PanelItemMediaResource($media), 'Arquivo enviado.', Status::HTTP_CREATED);
    }
}
