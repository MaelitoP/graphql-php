<?php declare(strict_types=1);

namespace GraphQL\Executor;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;

/**
 * Returned after [query execution](executing-queries.md).
 * Represents both - result of successful execution and of a failed one
 * (with errors collected in `errors` prop).
 *
 * Could be converted to [spec-compliant](https://facebook.github.io/graphql/#sec-Response-Format)
 * serializable array using `toArray()`.
 *
 * @phpstan-type SerializableError array{
 *   message: string,
 *   locations?: array<int, array{line: int, column: int}>,
 *   path?: array<int, int|string>,
 *   extensions?: array<string, mixed>
 * }
 * @phpstan-type SerializableErrors array<int, SerializableError>
 * @phpstan-type PendingItem array{id: string, label?: string, path: array<string|int>}
 * @phpstan-type SerializableResult array{
 *     data?: array<string, mixed>,
 *     errors?: SerializableErrors,
 *     extensions?: array<string, mixed>,
 *     pending?: array<PendingItem>,
 *     hasNext?: bool
 * }
 * @phpstan-type ErrorFormatter callable(\Throwable): SerializableError
 * @phpstan-type ErrorsHandler callable(array<e> $errors, ErrorFormatter $formatter): SerializableErrors
 *
 * @see \GraphQL\Tests\Executor\ExecutionResultTest
 */
class ExecutionResult implements \JsonSerializable
{
    /**
     * Data collected from resolvers during query execution.
     *
     * @api
     *
     * @var array<string, mixed>|null
     */
    public ?array $data = null;

    /**
     * Errors registered during query execution.
     *
     * If an error was caused by exception thrown in resolver, $error->getPrevious() would
     * contain original exception.
     *
     * @api
     *
     * @var array<e>
     */
    public array $errors = [];

    /**
     * User-defined serializable array of extensions included in serialized result.
     *
     * @api
     *
     * @var array<string, mixed>|null
     */
    public ?array $extensions = null;

    /**
     * Items pending delivery in incremental execution.
     *
     * @api
     *
     * @var array<array{id: string, label?: string, path: array<string|int>}>|null
     */
    public ?array $pending = null;

    /**
     * Flag indicating whether there are more incremental payloads to come.
     *
     * @api
     */
    public bool $hasNext = false;

    /**
     * @var callable|null
     *
     * @phpstan-var ErrorFormatter|null
     */
    private $errorFormatter;

    /**
     * @var callable|null
     *
     * @phpstan-var ErrorsHandler|null
     */
    private $errorsHandler;

    /**
     * @param array<string, mixed>|null $data
     * @param array<e>              $errors
     * @param array<string, mixed>      $extensions
     * @param array<array{id: string, label?: string, path: array<string|int>}>|null $pending
     * @param bool $hasNext
     */
    public function __construct(
        array $data = null, 
        array $errors = [], 
        array $extensions = [],
        array $pending = null,
        bool $hasNext = false
    ) {
        $this->data = $data;
        $this->errors = $errors;
        $this->extensions = $extensions;
        $this->pending = $pending;
        $this->hasNext = $hasNext;
    }

    /**
     * Define custom error formatting (must conform to http://facebook.github.io/graphql/#sec-Errors).
     *
     * Expected signature is: function (GraphQL\Error\Error $error): array
     *
     * Default formatter is "GraphQL\Error\FormattedError::createFromException"
     *
     * Expected returned value must be an array:
     * array(
     *    'message' => 'errorMessage',
     *    // ... other keys
     * );
     *
     * @phpstan-param ErrorFormatter|null $errorFormatter
     *
     * @api
     */
    public function setErrorFormatter(?callable $errorFormatter): self
    {
        $this->errorFormatter = $errorFormatter;

        return $this;
    }

    /**
     * Define custom logic for error handling (filtering, logging, etc).
     *
     * Expected handler signature is:
     * fn (array $errors, callable $formatter): array
     *
     * Default handler is:
     * fn (array $errors, callable $formatter): array => array_map($formatter, $errors)
     *
     * @phpstan-param ErrorsHandler|null $errorsHandler
     *
     * @api
     */
    public function setErrorsHandler(?callable $errorsHandler): self
    {
        $this->errorsHandler = $errorsHandler;

        return $this;
    }

    /** @phpstan-return SerializableResult */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Converts GraphQL query result to spec-compliant serializable array using provided
     * errors handler and formatter.
     *
     * If debug argument is passed, output of error formatter is enriched which debugging information
     * ("debugMessage", "trace" keys depending on flags).
     *
     * $debug argument must sum of flags from @see \GraphQL\Error\DebugFlag
     *
     * @phpstan-return SerializableResult
     *
     * @api
     */
    public function toArray(int $debug = DebugFlag::NONE): array
    {
        $result = [];

        if ($this->errors !== []) {
            $errorsHandler = $this->errorsHandler
                ?? static fn (array $errors, callable $formatter): array => \array_map($formatter, $errors);

            $handledErrors = $errorsHandler(
                $this->errors,
                FormattedError::prepareFormatter($this->errorFormatter, $debug)
            );

            // While we know that there were errors initially, they might have been discarded
            if ($handledErrors !== []) {
                $result['errors'] = $handledErrors;
            }
        }

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        if ($this->extensions !== null && $this->extensions !== []) {
            $result['extensions'] = $this->extensions;
        }

        // Add incremental delivery fields if needed
        if ($this->pending !== null && count($this->pending) > 0) {
            $result['pending'] = $this->pending;
        }

        if ($this->hasNext) {
            $result['hasNext'] = true;
        }

        return $result;
    }
}
