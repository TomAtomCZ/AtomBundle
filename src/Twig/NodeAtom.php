<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

#[YieldReady]
class NodeAtom extends Node implements NodeOutputInterface
{
    public function __construct(string $name, Node $body, int $lineno, ?string $tag = null)
    {
        parent::__construct(array('body' => $body), array('name' => $name, 'default_locale' => null), $lineno, $tag);
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
            ->raw('$cacheKey = "' . $this->getAttribute('name') . '_" . $this->env->getRuntime(\'TomAtom\AtomBundle\Services\AtomRuntime\')->getRequestStack()->getCurrentRequest()->getLocale();' . "\n")
            ->raw("\$cached = \$this->env->getRuntime('Twig\Extra\Cache\CacheRuntime')->getCache()->get(\$cacheKey, function (\Symfony\Contracts\Cache\ItemInterface \$item) use (\$context, \$macros) {\n")
            ->indent()
            ->raw("return implode('', iterator_to_array((function () use (\$context, \$macros) {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->raw("return; yield '';\n")
            ->outdent()
            ->raw('})(), false));')
            ->outdent()
            ->raw("});\n")
            ->raw("if ('' !== \$cached) {\n")
            ->indent()
            ->raw("yield new Markup(\$cached, \$this->env->getCharset());\n")
            ->outdent()
            ->raw("}\n");
    }
}
