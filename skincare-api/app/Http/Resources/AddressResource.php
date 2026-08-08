<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'province' => $this->province,
            'district' => $this->district,
            'commune' => $this->commune,
            'house_no' => $this->house_no,
            'pickup_point' => $this->pickup_point,
            'location' => $this->location,
            'type' => $this->type,
            'is_default' => $this->is_default,
        ];
    }
}
