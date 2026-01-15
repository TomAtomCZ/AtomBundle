<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

#[YieldReady]
class NodeAtomEntity extends Node implements NodeOutputInterface
{
    public function __construct(?string $name, Node $body, int $lineno, string $entityName, AbstractExpression $entityMethod, AbstractExpression $entityId)
    {
        parent::__construct([
            'body' => $body,
            'entityMethod' => $entityMethod,
            'entityId' => $entityId,
        ], [
            'name' => $name,
            'entityName' => $entityName,
            'default_locale' => null
        ], $lineno);
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
            ->raw('$isAdmin = $this->env->getExtension(\'TomAtom\AtomBundle\Twig\TomAtomExtension\')->getAuthorizationChecker()->isGranted(\'ROLE_ATOM_EDIT\');' . "\n")
            ->raw('$entityMethod = ')
            ->subcompile($this->getNode('entityMethod'))
            ->raw(";\n")
            ->raw('$entityId = ')
            ->subcompile($this->getNode('entityId'))
            ->raw(";\n")
            ->raw("yield from (function () use (\$context, \$macros, \$isAdmin, \$entityMethod, \$entityId) {\n")
            ->indent()
            ->raw('$body = implode(\'\', iterator_to_array((function () use ($context, $macros) {' . "\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->raw("return; yield '';\n")
            ->outdent()
            ->raw('})(), false));' . "\n")
            ->raw('$body = $this->env->getExtension(\'TomAtom\AtomBundle\Twig\TomAtomExtension\')->getTranslationNodeVisitor()->getHelper()->checkAtomEntity("' . $this->getAttribute('entityName') . '", $entityMethod, $entityId, $body, $isAdmin);' . "\n")
            ->raw('if ($body !== null) { yield new \Twig\Markup($body, $this->env->getCharset()); }' . "\n")
            ->outdent()
            ->raw("})();\n");
    }
}
