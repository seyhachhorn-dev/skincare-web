<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'icon' => $this->icon && ! str_starts_with($this->icon, 'http')
                ? Storage::disk('public')->url($this->icon)
                : $this->icon,
        ];
    }
}
