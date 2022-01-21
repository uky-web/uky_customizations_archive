<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use \Twig_TokenParser_Include;
use \Twig_Token;
use \Twig_Node_Include;

class Project_include_TokenParser extends \Twig\TokenParser\IncludeTokenParser {
    public function parse(\Twig\Token $token) 
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $expr = Utilities::convertName($expr);

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        return new Project_include_Node($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'include';
    }
}