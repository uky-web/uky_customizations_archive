<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;

class Project_include_TokenParser extends IncludeTokenParser {
    public function parse(Token $token): \Twig\Node\Node
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $expr = Utilities::convertName($expr);

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        return new Project_include_Node($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'include';
    }
}
