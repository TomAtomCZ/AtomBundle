<?php

namespace TomAtom\AtomBundle\Twig;

use TomAtom\AtomBundle\Services\NodeHelper;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\AbstractNodeVisitor;

// TODO resolve AbstractNodeVisitor deprecation
class AtomNodeVisitor extends AbstractNodeVisitor
{
    protected NodeHelper $nodeHelper;

    public function __construct(NodeHelper $nodeHelper)
    {
        $this->nodeHelper = $nodeHelper;
    }

    public function doEnterNode(Node $node, Environment $env)
    {
        if ($node instanceof NodeAtom) {
            $atomName = $node->getAttribute('name');
            $defaultBody = '';
            if ($node->getNode('body')->hasAttribute('data')) {
                $defaultBody = $node->getNode('body')->getAttribute('data');
            }
            $body = $this->nodeHelper->checkAtom($atomName, $defaultBody);
            $node->setNode('body', new BodyNode([
                new TextNode(!is_null($body) ? $body : '', 1),
            ]));
            $node->setAttribute('default_locale', $this->nodeHelper->getDefaultLocale());
        }

        if ($node instanceof NodeAtomLine) {
            $atomName = $node->getAttribute('name');
            $defaultBody = '';
            if ($node->getNode('body')->hasAttribute('data')) {
                $defaultBody = $node->getNode('body')->getAttribute('data');
            }
            $body = $this->nodeHelper->checkAtomLine($atomName, $defaultBody);
            $node->setNode('body', new BodyNode([
                new TextNode(!is_null($body) ? $body : '', 1),
            ]));
        }

        return $node;
    }

    public function doLeaveNode(Node $node, Environment $env): Node
    {
        // TODO: Implement doLeaveNode() method.
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
