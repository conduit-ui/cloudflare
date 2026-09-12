<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\User;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class VerifyToken extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/user/tokens/verify';
    }
}
