<?php declare(strict_types=1);

namespace GraphQL\Executor;

/**
 * Represents an incremental result for @defer directive.
 * Used to deliver deferred fragments data.
 *
 * @see \GraphQL\Type\Definition\Directive::deferDirective()
 */
class IncrementalResult implements \JsonSerializable
{
    /**
     * @var array<array{id: string, data: array<string, mixed>|null}>
     */
    private array $incremental = [];

    /**
     * @var array<array{id: string}>
     */
    private array $completed = [];

    /**
     * @var bool
     */
    private bool $hasNext = false;

    /**
     * Adds data for a deferred fragment.
     *
     * @param string              $id   The unique ID of the deferred fragment
     * @param array<string, mixed>|null $data The data for the deferred fragment
     */
    public function addData(string $id, ?array $data): void
    {
        $this->incremental[] = [
            'id' => $id, 
            'data' => $data,
        ];
    }

    /**
     * Marks a deferred fragment as completed.
     *
     * @param string $id The unique ID of the completed fragment
     */
    public function markCompleted(string $id): void
    {
        $this->completed[] = ['id' => $id];
    }

    /**
     * Sets whether there are more incremental payloads to come.
     */
    public function setHasNext(bool $hasNext): void
    {
        $this->hasNext = $hasNext;
    }

    /**
     * Converts the incremental result to an array.
     */
    public function toArray(): array
    {
        $result = [];

        if (count($this->incremental) > 0) {
            $result['incremental'] = $this->incremental;
        }

        if (count($this->completed) > 0) {
            $result['completed'] = $this->completed;
        }

        $result['hasNext'] = $this->hasNext;

        return $result;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
} 