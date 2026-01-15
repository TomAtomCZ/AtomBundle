<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class TokenParserAtom extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token A Twig_Token instance
     *
     * @return NodeAtom A Twig_NodeInterface instance
     * @throws SyntaxError
     */
    public function parse(Token $token): NodeAtom
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::NAME_TYPE)->getValue();

        if ($stream->nextIf(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse([$this, 'decideAtomEnd'], true);
        } else {
            $body = new Node([
                new PrintNode($this->parser->parseExpression(), $lineno),
            ]);
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new NodeAtom($name, $body, $lineno);
    }

    public function decideAtomEnd(Token $token): bool
    {
        return $token->test('endatom');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag(): string
    {
        return 'atom';
    }
}
