<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sector */
class SectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_primary' => $this->whenPivotLoaded('paper_sector', fn () => (bool) $this->pivot->is_primary),
            'published_papers_count' => $this->whenCounted('papers'),
        ];
    }
}
