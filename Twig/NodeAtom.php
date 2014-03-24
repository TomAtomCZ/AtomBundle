<?php

namespace TomAtom\AtomBundle\Twig;

class NodeAtom extends \Twig_Node implements \Twig_NodeOutputInterface
{
    public function __construct($name, \Twig_NodeInterface $body, $lineno, $tag = null)
    {
        parent::__construct(array('body' => $body), array('name' => $name), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("\$this->displayAtom('%s', '%s', \$context);\n", $this->getAttribute('name'), $this->getNode('body')->getAttribute('data')))
        ;
        
//        $compiler
//            ->addDebugInfo($this)
//            ->write("echo '<div class=\"atom\">';\n")    
//            ->subcompile($this->getNode('body'))
//            ->write("echo '</div>';\n")    
//        ;
    }
}
