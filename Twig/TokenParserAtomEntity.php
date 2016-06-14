<?php

namespace TomAtom\AtomBundle\Twig;


class TokenParserAtomEntity extends \Twig_TokenParser
{        
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     *
     * @return \Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $entityNamespace = $stream->expect(\Twig_Token::NAME_TYPE)->getValue();
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ':');
        $entityName = $stream->expect(\Twig_Token::NAME_TYPE)->getValue();
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',');
        $entityMethod = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',');
        $entityId = $this->parser->getExpressionParser()->parseExpression();

        if ($stream->nextIf(\Twig_Token::BLOCK_END_TYPE))
        {
            $body = $this->parser->subparse(array($this, 'decideAtomEntityEnd'), true);
        } 
        else 
        {
            $body = new \Twig_Node(array(
                new \Twig_Node_Print($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ));
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new NodeAtomEntity(null, $body, $lineno, $this->getTag(), $entityNamespace . ':' . $entityName, $entityMethod, $entityId);
    }

    public function decideAtomEntityEnd(\Twig_Token $token)
    {
        return $token->test('endatomentity');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'atomentity';
    }
}