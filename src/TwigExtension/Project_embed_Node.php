<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use \Twig_Compiler;
use \Twig_Node_Expression;
use \Twig_Node_Expression_Constant;

class Project_embed_Node extends Project_include_Node
{
  // we don't inject the module to avoid node visitors to traverse it twice (as it will be already visited in the main module)
  public function __construct($filename, $index, \Twig\Node\Expression\AbstractExpression $variables = null, $only = false, $ignoreMissing = false, $lineno, $tag = null) {
    parent::__construct(new \Twig\Node\Expression\ConstantExpression('not_used', $lineno), $variables, $only, $ignoreMissing, $lineno, $tag);
    $this
      ->setAttribute('filename', $filename);
    $this
      ->setAttribute('index', $index);
  }

  protected function addGetTemplate(\Twig\Compiler $compiler) {
    $compiler
      ->write('$this->loadTemplate(')
      ->string($this
      ->getAttribute('filename'))
      ->raw(', ')
      ->repr($compiler
      ->getFilename())
      ->raw(', ')
      ->repr($this
      ->getLine())
      ->raw(', ')
      ->string($this
      ->getAttribute('index'))
      ->raw(')');
  }
}