<?php

namespace App\Http\Resources;

use App\Traits\HandlesUploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class GalleryItemResource extends JsonResource
{
    use HandlesUploads;

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'image'          => $this->uploadUrl($this->image),
            'caption'        => $this->caption,
            'category'       => $this->category,
            'category_label' => $this->category === 'before_after' ? 'Before / After' : Str::headline($this->category),
            'sort_order'     => $this->sort_order,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
