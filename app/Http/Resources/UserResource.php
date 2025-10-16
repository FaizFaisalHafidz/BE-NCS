<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nama", type="string", example="Supervisor NCS"),
 *     @OA\Property(property="email", type="string", example="supervisor@ncs.com"),
 *     @OA\Property(property="nomor_telepon", type="string", example="081234567890"),
 *     @OA\Property(property="aktif", type="boolean", example=true),
 *     @OA\Property(property="role", type="string", example="supervisor"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2025-10-16T10:43:56.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2025-10-16T10:43:56.000000Z")
 * )
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'nomor_telepon' => $this->nomor_telepon,
            'aktif' => $this->aktif,
            'role' => $this->getRoleNames()->first(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
