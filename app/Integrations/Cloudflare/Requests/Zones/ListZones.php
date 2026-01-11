<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Requests\Zones;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListZones extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected ?string $name = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/zones';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'name' => $this->name,
        ]);
    }
}
