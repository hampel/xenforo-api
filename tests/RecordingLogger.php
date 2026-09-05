<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Psr\Log\AbstractLogger;

/**
 * @phpstan-type LogRecord array{level: string, message: string, context: array<mixed>}
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    public array $records = [];

    /**
     * @param  mixed  $level
     * @param  string|\Stringable  $message
     * @param  array<mixed>  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * @return list<array{level: string, message: string, context: array<mixed>}>
     */
    public function withLevel(string $level): array
    {
        return array_values(array_filter(
            $this->records,
            static fn (array $record): bool => $record['level'] === $level
        ));
    }

    public function hasMessageContaining(string $needle): bool
    {
        foreach ($this->records as $record) {
            if (str_contains($record['message'], $needle)) {
                return true;
            }
        }

        return false;
    }
}
