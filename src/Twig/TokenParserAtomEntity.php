<?php

namespace TomAtom\AtomBundle\Twig;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\ConstantExpression;
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
     * @return NodeOutputInterface|NodeAtomEntity A Twig_NodeInterface instance
     * @throws SyntaxError
     */
    public function parse(Token $token): NodeOutputInterface|NodeAtomEntity
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $entityName = $stream->expect(Token::NAME_TYPE)->getValue();
        if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':')) {
            $entityName .= ':' . $stream->expect(Token::NAME_TYPE)->getValue();
            if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':')) {
                $entityName .= ':' . $stream->expect(Token::NAME_TYPE)->getValue();
            }
        }

        if (!str_contains($entityName, 'App\\Entity\\')) {
            $entityName = 'App\\Entity\\' . ucfirst($entityName);
        }

        $stream->expect(Token::PUNCTUATION_TYPE, ',');
        if ($stream->test(Token::NAME_TYPE)) {
            $methodName = $stream->expect(Token::NAME_TYPE)->getValue();
            $entityMethod = new ConstantExpression($methodName, $lineno);
        } else {
            $entityMethod = $this->parser->parseExpression();
        }

        $stream->expect(Token::PUNCTUATION_TYPE, ',');
        $entityId = $this->parser->parseExpression();

        if ($stream->nextIf(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse([$this, 'decideAtomEntityEnd'], true);
        } else {
            $body = new Node([
                new PrintNode($this->parser->parseExpression(), $lineno),
            ]);
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new NodeAtomEntity(null, $body, $lineno, 'App\\Entity\\' . ucfirst($entityName), $entityMethod, $entityId);
    }

    public function decideAtomEntityEnd(Token $token): bool
    {
        return $token->test('endatomentity');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag(): string
    {
        return 'atomentity';
    }
}
