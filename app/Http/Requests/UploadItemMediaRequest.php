<?php

namespace App\Http\Requests;

use App\Enums\ReviewMediaType;
use Illuminate\Foundation\Http\FormRequest;

class UploadItemMediaRequest extends FormRequest
{
    private const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private const VIDEO_TYPES = ['video/mp4', 'video/webm'];

    private const PHOTO_MAX_KB = 5 * 1024;

    private const VIDEO_MAX_KB = 50 * 1024;

    private const COVER_MAX_KB = 2 * 1024;

    private const MAX_DIMENSION = 10000;

    private const MAX_VIDEO_SECONDS = 600;

    public function rules(): array
    {
        if ($this->type() === ReviewMediaType::Video) {
            return [
                'file' => ['bail', 'required', 'file', 'mimetypes:'.implode(',', self::VIDEO_TYPES), 'max:'.self::VIDEO_MAX_KB],
                'cover' => ['required', 'file', 'mimetypes:image/jpeg', 'max:'.self::COVER_MAX_KB],
                'width' => ['required', 'integer', 'min:1', 'max:'.self::MAX_DIMENSION],
                'height' => ['required', 'integer', 'min:1', 'max:'.self::MAX_DIMENSION],
                'durationSeconds' => ['required', 'integer', 'min:1', 'max:'.self::MAX_VIDEO_SECONDS],
            ];
        }

        return [
            'file' => ['bail', 'required', 'file', 'mimetypes:'.implode(',', self::PHOTO_TYPES), 'max:'.self::PHOTO_MAX_KB, 'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Escolha um arquivo.',
            'file.uploaded' => 'O arquivo não chegou inteiro. Tente de novo.',
            'file.mimetypes' => 'Formato não aceito. Use foto JPG, PNG ou WebP, ou vídeo MP4 ou WebM.',
            'file.max' => $this->type() === ReviewMediaType::Video ? 'O vídeo passa de 50 MB.' : 'A foto passa de 5 MB.',
            'file.dimensions' => 'A foto é grande demais.',
            'cover.*' => 'Não deu para ler o vídeo. Tente outro arquivo.',
            'width.*' => 'Não deu para ler o vídeo. Tente outro arquivo.',
            'height.*' => 'Não deu para ler o vídeo. Tente outro arquivo.',
            'durationSeconds.max' => 'O vídeo pode ter até 10 minutos.',
            'durationSeconds.*' => 'Não deu para ler o vídeo. Tente outro arquivo.',
        ];
    }

    public function type(): ReviewMediaType
    {
        $file = $this->file('file');
        $mime = $file?->isValid() ? (string) $file->getMimeType() : '';

        return in_array($mime, self::VIDEO_TYPES, true) ? ReviewMediaType::Video : ReviewMediaType::Photo;
    }
}
