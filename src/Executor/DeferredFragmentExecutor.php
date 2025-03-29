<?php declare(strict_types=1);

namespace GraphQL\Executor;

use GraphQL\Type\Definition\DeferUsage;
use GraphQL\Type\Definition\ObjectType;

/**
 * Handles execution of deferred fragments.
 *
 * This class is responsible for tracking and executing fragments
 * that were marked with the @defer directive.
 */
class DeferredFragmentExecutor
{
    /**
     * @var array<string, array{
     *   data: mixed,
     *   type: ObjectType,
     *   path: array<string|int>,
     *   label: string|null,
     *   usage: DeferUsage
     * }>
     */
    private array $deferredFragments = [];

    /**
     * @var array<string, bool>
     */
    private array $completedFragments = [];

    /**
     * Registers a fragment for deferred execution.
     *
     * @param string $id Unique ID for this fragment
     * @param mixed $data The data to execute the fragment against
     * @param ObjectType $type The type of the data
     * @param array<string|int> $path The path to the deferred field in the result
     * @param string|null $label Optional label for the deferred fragment
     * @param DeferUsage $usage The defer usage details
     */
    public function deferFragment(
        string $id,
        $data,
        ObjectType $type,
        array $path,
        ?string $label,
        DeferUsage $usage
    ): void {
        $this->deferredFragments[$id] = [
            'data' => $data,
            'type' => $type,
            'path' => $path,
            'label' => $label,
            'usage' => $usage,
        ];
    }

    /**
     * Marks a fragment as completed.
     */
    public function completeFragment(string $id): void
    {
        $this->completedFragments[$id] = true;
    }

    /**
     * Gets a pending fragment by ID.
     *
     * @return array{
     *   data: mixed,
     *   type: ObjectType,
     *   path: array<string|int>,
     *   label: string|null,
     *   usage: DeferUsage
     * }|null
     */
    public function getFragment(string $id): ?array
    {
        return $this->deferredFragments[$id] ?? null;
    }

    /**
     * Checks if a fragment is marked as completed.
     */
    public function isFragmentCompleted(string $id): bool
    {
        return isset($this->completedFragments[$id]);
    }

    /**
     * Gets all the pending fragment IDs that haven't been executed yet.
     *
     * @return array<string>
     */
    public function getPendingFragmentIds(): array
    {
        $pending = [];
        foreach (array_keys($this->deferredFragments) as $id) {
            if (!isset($this->completedFragments[$id])) {
                $pending[] = $id;
            }
        }
        return $pending;
    }

    /**
     * Generates the list of pending fragments for the initial response.
     *
     * @return array<array{id: string, label?: string, path: array<string|int>}>
     */
    public function generatePendingList(): array
    {
        $pending = [];
        foreach ($this->deferredFragments as $id => $fragment) {
            if (!isset($this->completedFragments[$id])) {
                $item = [
                    'id' => $id,
                    'path' => $fragment['path'],
                ];
                
                if ($fragment['label'] !== null) {
                    $item['label'] = $fragment['label'];
                }
                
                $pending[] = $item;
            }
        }
        return $pending;
    }

    /**
     * Checks if there are any pending fragments.
     */
    public function hasPendingFragments(): bool
    {
        return count($this->deferredFragments) > count($this->completedFragments);
    }
} 