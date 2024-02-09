<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\DirectiveLocation;

/**
 * @phpstan-import-type ArgumentListConfig from Argument
 *
 * @phpstan-type DirectiveConfig array{
 *   name: string,
 *   description?: string|null,
 *   args?: ArgumentListConfig|null,
 *   locations: array<string>,
 *   isRepeatable?: bool|null,
 *   astNode?: DirectiveDefinitionNode|null
 * }
 */
class Directive
{
    public const DEFAULT_DEPRECATION_REASON = 'No longer supported';

    public const DIRECTIVE_INCLUDE_NAME = 'include';
    public const DIRECTIVE_SKIP_NAME = 'skip';
    public const DIRECTIVE_DEPRECATED_NAME = 'deprecated';
    public const DIRECTIVE_DEFER_NAME = 'defer';

    public const IF_ARGUMENT_NAME = 'if';
    public const REASON_ARGUMENT_NAME = 'reason';
    public const LABEL_ARGUMENT_NAME = 'label';

    /**
     * Lazily initialized.
     *
     * @var array<self::DIRECTIVE_*, Directive>
     */
    protected static array $internalDirectives;

    public string $name;

    public ?string $description;

    /** @var array<int, Argument> */
    public array $args;

    public bool $isRepeatable;

    /** @var array<string> */
    public array $locations;

    public ?DirectiveDefinitionNode $astNode;

    /**
     * @var array<string, mixed>
     *
     * @phpstan-var DirectiveConfig
     */
    public array $config;

    /**
     * @param array<string, mixed> $config
     *
     * @phpstan-param DirectiveConfig $config
     */
    public function __construct(array $config)
    {
        $this->name = $config['name'];
        $this->description = $config['description'] ?? null;
        $this->args = isset($config['args'])
            ? Argument::listFromConfig($config['args'])
            : [];
        $this->isRepeatable = $config['isRepeatable'] ?? false;
        $this->locations = $config['locations'];
        $this->astNode = $config['astNode'] ?? null;

        $this->config = $config;
    }

    /**
     * @throws InvariantViolation
     *
     * @return array<self::DIRECTIVE_*, Directive>
     */
    public static function getInternalDirectives(): array
    {
        return self::$internalDirectives ??= [
            self::DIRECTIVE_INCLUDE_NAME => new self([
                'name' => self::DIRECTIVE_INCLUDE_NAME,
                'description' => 'Directs the executor to include this field or fragment only when the `if` argument is true.',
                'locations' => [
                    DirectiveLocation::FIELD,
                    DirectiveLocation::FRAGMENT_SPREAD,
                    DirectiveLocation::INLINE_FRAGMENT,
                ],
                'args' => [
                    self::IF_ARGUMENT_NAME => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Included when true.',
                    ],
                ],
            ]),
            self::DIRECTIVE_SKIP_NAME => new self([
                'name' => self::DIRECTIVE_SKIP_NAME,
                'description' => 'Directs the executor to skip this field or fragment when the `if` argument is true.',
                'locations' => [
                    DirectiveLocation::FIELD,
                    DirectiveLocation::FRAGMENT_SPREAD,
                    DirectiveLocation::INLINE_FRAGMENT,
                ],
                'args' => [
                    self::IF_ARGUMENT_NAME => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Skipped when true.',
                    ],
                ],
            ]),
            self::DIRECTIVE_DEPRECATED_NAME => new self([
                'name' => self::DIRECTIVE_DEPRECATED_NAME,
                'description' => 'Marks an element of a GraphQL schema as no longer supported.',
                'locations' => [
                    DirectiveLocation::FIELD_DEFINITION,
                    DirectiveLocation::ENUM_VALUE,
                    DirectiveLocation::ARGUMENT_DEFINITION,
                    DirectiveLocation::INPUT_FIELD_DEFINITION,
                ],
                'args' => [
                    self::REASON_ARGUMENT_NAME => [
                        'type' => Type::string(),
                        'description' => 'Explains why this element was deprecated, usually also including a suggestion for how to access supported similar data. Formatted using the Markdown syntax, as specified by [CommonMark](https://commonmark.org/).',
                        'defaultValue' => self::DEFAULT_DEPRECATION_REASON,
                    ],
                ],
            ]),
            self::DIRECTIVE_DEFER_NAME => new self([
                'name' => self::DIRECTIVE_DEFER_NAME,
                'description' => 'Directs the executor to defer this fragment when the `if` argument is true or undefined.',
                'locations' => [
                    DirectiveLocation::FRAGMENT_SPREAD,
                    DirectiveLocation::INLINE_FRAGMENT,
                ],
                'args' => [
                    self::LABEL_ARGUMENT_NAME => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'Unique name',
                    ],
                    self::IF_ARGUMENT_NAME => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Deferred when true or undefined.',
                        'defaultValue' => true,
                    ],
                ],
            ]),
        ];
    }

    /** @throws InvariantViolation */
    public static function includeDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal[self::DIRECTIVE_INCLUDE_NAME];
    }

    /** @throws InvariantViolation */
    public static function skipDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal[self::DIRECTIVE_SKIP_NAME];
    }

    /** @throws InvariantViolation */
    public static function deprecatedDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal[self::DIRECTIVE_DEPRECATED_NAME];
    }

    /** @throws InvariantViolation */
    public static function deferDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal[self::DIRECTIVE_DEFER_NAME];
    }

    /** @throws InvariantViolation */
    public static function isSpecifiedDirective(Directive $directive): bool
    {
        return \array_key_exists($directive->name, self::getInternalDirectives());
    }
}
