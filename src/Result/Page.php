<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

/**
 * One page of a list endpoint, and the pagination block that came with it.
 *
 * The shape is guaranteed across the whole API, core and add-on alike, because every
 * paginated endpoint builds it with the same
 * \XF\Api\Controller\AbstractController::getPaginationData() - so this class works
 * unchanged for an endpoint neither this package nor its author has ever seen.
 *
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    /**
     * @template TItem
     * @param  array<mixed>  $data  the decoded response body
     * @param  string  $key  the key holding the list, e.g. "users"
     * @param  callable(array<mixed>): TItem  $map  how to build one item
     * @return self<TItem>
     */
    public static function fromResponse(array $data, string $key, callable $map): self
    {
        $items = [];
        $raw = $data[$key] ?? [];

        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_array($item)) {
                    $items[] = $map($item);
                }
            }
        }

        $pagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : [];

        // A non-paginated endpoint answering with a plain list still produces a usable
        // single page here, rather than a division by zero further down.
        $perPage = self::int($pagination, 'per_page', max(1, count($items)));

        return new self(
            $items,
            self::int($pagination, 'current_page', 1),
            self::int($pagination, 'last_page', 1),
            $perPage,
            self::int($pagination, 'total', count($items)),
        );
    }

    /**
     * Whether another page exists.
     *
     * Asking for one past the end is an error rather than an empty result - XenForo's
     * assertValidApiPage() throws `invalid_page` - so "fetch until it comes back empty"
     * is not a workable loop against this API. This is the test to use instead.
     */
    public function hasMore(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @param  array<mixed>  $pagination
     */
    private static function int(array $pagination, string $key, int $fallback): int
    {
        $value = $pagination[$key] ?? null;

        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : $fallback;
    }
}
