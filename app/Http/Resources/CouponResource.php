<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount' => $this->discount,
            'percent' => $this->percent,
            'expires_at' => $this->expires_at,
            'usage_limit' => $this->usage_limit,
            'used' => $this->used,
            'active' => $this->active,
        ];
    }
}