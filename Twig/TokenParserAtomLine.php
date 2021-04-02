<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;


class TokenParserAtomLine extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token A Twig_Token instance
     *
     * @return NodeOutputInterface A Twig_NodeInterface instance
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::NAME_TYPE)->getValue();

        if ($stream->nextIf(Token::BLOCK_END_TYPE))
        {
            $body = $this->parser->subparse(array($this, 'decideAtomLineEnd'), true);
        }
        else
        {
            $body = new Node(array(
                new PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ));
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new NodeAtomLine($name, $body, $lineno);
    }

    public function decideAtomLineEnd(Token $token)
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
