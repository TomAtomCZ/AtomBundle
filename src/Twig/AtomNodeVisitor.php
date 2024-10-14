<?php

namespace TomAtom\AtomBundle\Twig;

use TomAtom\AtomBundle\Services\NodeHelper;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\AbstractNodeVisitor;

class AtomNodeVisitor extends AbstractNodeVisitor
{
    protected NodeHelper $nodeHelper;

    public function __construct(NodeHelper $nh)
    {
        $this->nodeHelper = $nh;
    }

    public function doEnterNode(Node $node, Environment $env)
    {
        if ($node instanceof NodeAtom) {
            $atomName = $node->getAttribute('name');
            $defaultBody = $node->getNode('body')->getAttribute('data');
//            $body = $this->checkAtom($atomName, $defaultBody);
            $body = $this->nodeHelper->checkAtom($atomName, $defaultBody);
            $node->setNode('body', new BodyNode([
//                new TextNode('<div class="atom" id="' . $atomName . '">' . $body . '</div>', 1),
                new TextNode(!is_null($body) ? $body : '', 1),
//                new PrintNode(new NameExpression($name, 1), 1),
            ]));
            $node->setAttribute('default_locale', $this->nodeHelper->getDefaultLocale());
        }

        if ($node instanceof NodeAtomLine) {
            $atomName = $node->getAttribute('name');
            $defaultBody = $node->getNode('body')->getAttribute('data');
//            $body = $this->checkAtom($atomName, $defaultBody);
            $body = $this->nodeHelper->checkAtomLine($atomName, $defaultBody);
            $node->setNode('body', new BodyNode([
//                new TextNode('<div class="atom" id="' . $atomName . '">' . $body . '</div>', 1),
                new TextNode(!is_null($body) ? $body : '', 1),
//                new PrintNode(new NameExpression($name, 1), 1),
            ]));
        }
//        if ($node->getNodeTag() === 'atom') {
////            $name = $node->getAttribute('name');
//            $node->setNode('atom', new Node([
//                'NODE',
//                $node->getNode('atom'),
////                new BlockNode(Template::PROFILE_STAGE_END, Template::PROFILE_TYPE_BLOCK, $name),
//            ]));
//        }
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
