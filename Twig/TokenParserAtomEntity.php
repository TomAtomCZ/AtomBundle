<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;


class TokenParserAtomEntity extends AbstractTokenParser
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
        $entityNamespace = $stream->expect(Token::NAME_TYPE)->getValue();
        $stream->expect(Token::PUNCTUATION_TYPE, ':');
        $entityName = $stream->expect(Token::NAME_TYPE)->getValue();
        $stream->expect(Token::PUNCTUATION_TYPE, ',');
        $entityMethod = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::PUNCTUATION_TYPE, ',');
        $entityId = $this->parser->getExpressionParser()->parseExpression();

        if ($stream->nextIf(Token::BLOCK_END_TYPE))
        {
            $body = $this->parser->subparse(array($this, 'decideAtomEntityEnd'), true);
        }
        else
        {
            $body = new Node(array(
                new PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ));
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new NodeAtomEntity(null, $body, $lineno, $this->getTag(), $entityNamespace . ':' . $entityName, $entityMethod, $entityId);
    }

    public function decideAtomEntityEnd(Token $token)
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
