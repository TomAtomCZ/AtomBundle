<?php

namespace TomAtom\AtomBundle\Twig;


class TokenParserAtomLine extends \Twig_TokenParser
{        
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\Twig_Token::NAME_TYPE)->getValue();
        
        if ($stream->nextIf(\Twig_Token::BLOCK_END_TYPE)) 
        {
            $body = $this->parser->subparse(array($this, 'decideAtomLineEnd'), true);
        } 
        else 
        {
            $body = new \Twig_Node(array(
                new \Twig_Node_Print($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ));
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new NodeAtomLine($name, $body, $lineno);
    }

    public function decideAtomLineEnd(\Twig_Token $token)
    {
        return $token->test('endatomline');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'atomline';
    }
}