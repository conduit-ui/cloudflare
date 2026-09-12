<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

/**
 * Agent-first JSON envelope for CLI commands.
 *
 * Contract (when --json is set) — both envelopes go to stdout:
 * - Success → {"ok":true,"data":...}
 * - Failure → {"ok":false,"error":"..."}
 *
 * Always check the process exit code (0 = SUCCESS, 1 = FAILURE).
 * Human (non-JSON) mode is unchanged: tables/info on stdout, errors via $this->error().
 *
 * @see docs/agent-output.md
 */
trait OutputsJson
{
    protected function wantsJson(): bool
    {
        return (bool) $this->option('json');
    }

    /**
     * Emit a success envelope to stdout and return SUCCESS.
     */
    protected function jsonSuccess(mixed $data): int
    {
        $this->line($this->encodeEnvelope([
            'ok' => true,
            'data' => $data,
        ]));

        return self::SUCCESS;
    }

    /**
     * Emit a failure envelope to stdout when --json, else human error; return FAILURE.
     */
    protected function jsonFail(string $message): int
    {
        if ($this->wantsJson()) {
            $this->line($this->encodeEnvelope([
                'ok' => false,
                'error' => $message,
            ]));

            return self::FAILURE;
        }

        $this->error($message);

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function encodeEnvelope(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{"ok":false,"error":"Failed to encode JSON"}';
    }
}
