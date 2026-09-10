<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Resources;

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\User\VerifyToken;
use Saloon\Http\Response;

class UserResource
{
    public function __construct(
        protected CloudflareConnector $connector,
    ) {}

    public function verifyToken(): Response
    {
        return $this->connector->send(new VerifyToken);
    }
}
