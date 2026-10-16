<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Model\LogEntry;
use StampMitra\Model\LogPage;

final class Logs extends AbstractResource
{
    /**
     * GET /v1/logs. Params: from, to, service, status (success|failure), limit (1-200), cursor.
     *
     * @param array<string, scalar|null> $params
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function list(array $params = [], RequestOptions|array|null $options = null): LogPage
    {
        $allowed = ['from', 'to', 'service', 'status', 'limit', 'cursor'];
        foreach (array_keys($params) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw ValidationError::forField((string) $key, "Unknown log filter '$key'; allowed: " . implode(', ', $allowed));
            }
        }
        if ($this->validateInput) {
            if (isset($params['status']) && !in_array($params['status'], ['success', 'failure'], true)) {
                throw ValidationError::forField('status', "status must be 'success' or 'failure'");
            }
            if (isset($params['limit']) && (!is_numeric($params['limit']) || (int) $params['limit'] < 1 || (int) $params['limit'] > 200)) {
                throw ValidationError::forField('limit', 'limit must be between 1 and 200');
            }
        }

        return $this->call(LogPage::class, 'GET', '/v1/logs', null, $params, $options);
    }

    /**
     * Auto-paginating iterator: follows nextCursor until exhausted.
     *
     * @param array<string, scalar|null> $params Same filters as list(); a starting cursor is honoured
     * @param RequestOptions|array<string, mixed>|null $options
     * @return \Generator<int, LogEntry>
     */
    public function iterate(array $params = [], RequestOptions|array|null $options = null): \Generator
    {
        $index = 0;
        do {
            $page = $this->list($params, $options);
            foreach ($page->items as $entry) {
                yield $index++ => $entry;
            }
            $cursor = $page->nextCursor;
            $params['cursor'] = $cursor;
        } while ($cursor !== null && $cursor !== '');
    }
}
