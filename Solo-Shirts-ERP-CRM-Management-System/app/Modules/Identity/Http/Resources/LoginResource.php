<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the login result: { token, token_type, user, abilities }.
 */
final class LoginResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{token: string, user: User, abilities: list<string>} $result */
        $result = $this->resource;

        return [
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => (new UserResource($result['user']))->toArray($request),
            'abilities' => $result['abilities'],
        ];
    }
}
