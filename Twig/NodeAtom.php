<?php

namespace TomAtom\AtomBundle\Twig;

class NodeAtom extends \Twig_Node implements \Twig_NodeOutputInterface
{
    public function __construct($name, \Twig_Node $body, $lineno, $tag = null)
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
            ->write("ob_start();\n")        
            ->subcompile($this->getNode('body'))
            ->write('$body = ob_get_clean();'."\n")        
            ->write('$body = $this->checkAtom("'.$this->getAttribute('name').'", $body);'."\n")            
            ->write('echo $body;'."\n")            
        ;
    }
}
