<?php

declare(strict_types=1);

namespace App\Integrations\Cloudflare\Resources;

use App\Integrations\Cloudflare\CloudflareConnector;
use App\Integrations\Cloudflare\Requests\Dns\CreateDnsRecord;
use App\Integrations\Cloudflare\Requests\Dns\DeleteDnsRecord;
use App\Integrations\Cloudflare\Requests\Dns\ListDnsRecords;
use Saloon\Http\Response;

class DnsResource
{
    public function __construct(
        protected CloudflareConnector $connector,
        protected string $zoneId,
    ) {}

    public function list(?string $type = null, ?string $name = null): Response
    {
        return $this->connector->send(new ListDnsRecords(
            $this->zoneId,
            $type,
            $name
        ));
    }

    public function create(
        string $type,
        string $name,
        string $content,
        bool $proxied = false,
        int $ttl = 1
    ): Response {
        return $this->connector->send(new CreateDnsRecord(
            $this->zoneId,
            $type,
            $name,
            $content,
            $proxied,
            $ttl
        ));
    }

    public function delete(string $recordId): Response
    {
        return $this->connector->send(new DeleteDnsRecord(
            $this->zoneId,
            $recordId
        ));
    }
}
