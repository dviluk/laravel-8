<?php

namespace App\Http\Resources\V1;

use App\Utils\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Da formato al recurso.
     * 
     * @param \App\Models\User $resource 
     * @param array $options 
     * @return array 
     */
    public function formatter($resource, array $options): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'email' => $resource->email,
            'access' => 'admin',
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
            'deleted_at' => $resource->deleted_at,
        ];
    }
}
