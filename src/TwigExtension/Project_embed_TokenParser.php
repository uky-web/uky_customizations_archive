<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use \Twig_TokenParser_Embed;
use \Twig_Token;
use \Twig_Node_Embed;


class Project_embed_TokenParser extends \Twig\TokenParser\AbstractTokenParser {
    public function parse(\Twig\Token $token) 
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        $parent = Utilities::convertName($parent);

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        // inject a fake parent to make the parent() function work
        $stream->injectTokens(array(
        new \Twig\Token(\Twig\Token::BLOCK_START_TYPE, '', $token
            ->getLine()),
        new \Twig\Token(\Twig\Token::NAME_TYPE, 'extends', $token
            ->getLine()),
        new \Twig\Token(\Twig\Token::STRING_TYPE, '__parent__', $token
            ->getLine()),
        new \Twig\Token(\Twig\Token::BLOCK_END_TYPE, '', $token
            ->getLine()),
        ));

        $module = $this->parser->parse($stream, array($this, 'decideBlockEnd'), true);

        // override the parent with the correct one
        $module
            ->setNode('parent', $parent);
        $this->parser
            ->embedTemplate($module);
        $stream
            ->expect(\Twig\Token::BLOCK_END_TYPE);

        return new \Twig\Node\EmbedNode($module
            ->getTemplateName(), $module
            ->getAttribute('index'), $variables, $only, $ignoreMissing, $token
            ->getLine(), $this
            ->getTag());
    }

    public function getTag(){
        return 'embed';
    }

    public function decideBlockEnd(\Twig\Token $token)
    {
        return $token->test('endembed');
    }

    protected function parseArguments()
    {
        $stream = $this->parser->getStream();

        $ignoreMissing = false;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'ignore')) {
            $stream->expect(/* Token::NAME_TYPE */ 5, 'missing');

            $ignoreMissing = true;
        }

        $variables = null;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'only')) {
            $only = true;
        }

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return [$variables, $only, $ignoreMissing];
    }
    
}
