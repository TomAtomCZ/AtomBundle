<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;


class NodeAtomLine extends Node implements NodeOutputInterface
{
    public function __construct($name, Node $body, $lineno, $tag = null)
    {
        parent::__construct(array('body' => $body), array('name' => $name), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler A Twig_Compiler instance
     */
    public function compile(Compiler $compiler)
    {

        $compiler
            ->addDebugInfo($this)
            ->write('$cacheKey = "'.$this->getAttribute('name').'_" . $this->env->getRuntime(\'TomAtom\AtomBundle\Services\AtomRuntime\')->getRequestStack()->getCurrentRequest()->getLocale();' . "\n")
            ->write("\$cached = \$this->env->getRuntime('Twig\Extra\Cache\CacheRuntime')->getCache()->get(\$cacheKey, function (\Symfony\Contracts\Cache\ItemInterface \$item) use (\$context, \$macros) {\n")
            ->indent()

            ->write("ob_start(function () { return ''; });\n")
            ->subcompile($this->getNode('body'))
            ->write("\n")
//            ->write('$body = ob_get_clean();'."\n")
//            ->write('$body = $this->checkAtomLine("'.$this->getAttribute('name').'", $body);'."\n")
            ->write("return ob_get_clean();\n")
            ->outdent()
            ->write("});\n")
            ->write("echo '' === \$cached ? '' : new Markup(\$cached, \$this->env->getCharset());\n")
        ;
    }
}
