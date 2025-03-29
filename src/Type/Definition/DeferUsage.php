<?php

namespace GraphQL\Type\Definition;

use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;

class DeferUsage
{
    public ?string $label;
    public ?DeferUsage $parent;
    
    /**
     * The selection set of the fragment with the @defer directive.
     */
    public ?SelectionSetNode $selectionSet;
    
    /**
     * The node (FragmentSpreadNode or InlineFragmentNode) that has the @defer directive.
     * 
     * @var FragmentSpreadNode|InlineFragmentNode|null
     */
    public $node;

    public function __construct(
        ?string $label,
        ?DeferUsage $parent,
        $node = null,
        ?SelectionSetNode $selectionSet = null
    ) {
        $this->label = $label;
        $this->parent = $parent;
        $this->node = $node;
        $this->selectionSet = $selectionSet;
    }
}
