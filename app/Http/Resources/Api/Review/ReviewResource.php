<?php

namespace App\Http\Resources\Api\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])->all()),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
