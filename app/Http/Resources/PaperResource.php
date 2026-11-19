<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Open-access representation of a PUBLISHED paper.
 * Deliberately excludes e-mail addresses, reviews, reviewer identities and editorial notes.
 *
 * @mixin \App\Models\Paper
 */
class PaperResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $committee = $this->relationLoaded('author') ? $this->author?->committee : null;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'abstract' => $this->abstract,
            'status' => $this->status->value,
            'version' => $this->version,
            'published_at' => $this->published_at?->toIso8601String(),
            'authors' => $this->relationLoaded('author') && $this->author
                ? [['name' => $this->author->name, 'organization' => $this->author->organization]]
                : [],
            'committee' => $committee ? (new CommitteeResource($committee))->resolve($request) : null,
            'sectors' => SectorResource::collection($this->whenLoaded('sectors')),
            'file' => [
                'size_bytes' => $this->file_size,
                'mime' => $this->file_mime ?? 'application/pdf',
                'download_url' => route('api.v1.papers.download', $this->id),
            ],
            'links' => [
                'self' => route('api.v1.papers.show', $this->id),
            ],
        ];
    }
}
