<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

class NodeAtomEntity extends Node implements NodeOutputInterface
{
    public string $entityName;

    public string $entityMethod;

    public int $entityId;

    public function __construct(string $name, Node $body, int $lineno, ?string $tag = null, AbstractExpression $entityName, AbstractExpression $entityMethod, AbstractExpression $entityId)
    {
        $this->entityName = $entityName;
        $this->entityMethod = $entityMethod->getAttribute('name');
        $this->entityId = $entityId->getAttribute('value');
        parent::__construct([
            'body' => $body
        ], [
            'name' => $name,
            'entityName' => $entityName,
            'entityMethod' => $this->entityMethod,
            'entityId' => $this->entityId
        ], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->raw("yield from (function () use (\$context, \$macros) {\n")
            ->indent()
            ->raw("return implode('', iterator_to_array((function () use (\$context, \$macros) {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->raw("return; yield '';\n")
            ->outdent()
            ->raw('})(), false));' . "\n")
            ->raw('$body = $this->checkAtomEntity("' . $this->entityName . '", "' . $this->entityMethod . '", "' . $this->entityId . '", $body);' . "\n")
            ->raw('yield $body;' . "\n")
            ->outdent()
            ->raw("})();\n");
    }
}
