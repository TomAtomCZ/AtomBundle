<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;


class NodeAtomEntity extends Node implements NodeOutputInterface
{
    /**
     * @var string $entityName
     */
    public $entityName;

    /**
     * @var string $entityMethod
     */
    public $entityMethod;

    /**
     * @var integer $entityId
     */
    public $entityId;

    public function __construct($name, Node $body, $lineno, $tag = null, $entityName, $entityMethod, $entityId)
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
     * @param Compiler A Twig_Compiler instance
     */
    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write('$body = ob_get_clean();'."\n")
            ->write('$body = $this->checkAtomEntity("'.$this->entityName.'", "'.$this->entityMethod.'", "'.$this->entityId.'", $body);'."\n")
            ->write('echo $body;'."\n")
        ;
    }
}
