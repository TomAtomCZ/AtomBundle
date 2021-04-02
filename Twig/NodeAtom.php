<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;


class NodeAtom extends Node implements NodeOutputInterface
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
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write('$body = ob_get_clean();'."\n")
//            ->write('$body = $this->checkAtom("'.$this->getAttribute('name').'", $body);'."\n")
//            ->write('echo $body;'."\n")
            ->write('echo $body;'."\n")
        ;
    }
}
