<?php

namespace Drupal\ukd8_customizations\TwigExtension;

class Utilities {

  /**
   * convert an expression to the PL naming scheme
   * used for include and embed tags
   *
   * @param [type] $expr
   * @return void
   */
  public static function convertName($expr) {
    $name = '';
    if ($expr->hasAttribute('value')) {
        $name = $expr->getAttribute('value');
    }
    if ((strpos($name, '@')!==0) && (strpos($name, '.html.twig')===FALSE)) {
      // convert from patternlab syntax
      $parts = explode('-', $name);
      $type = array_shift($parts);
      $name = '@' . $type . '/' . implode('-', $parts) . '.html.twig';
      $expr->setAttribute('value', $name);
    }

    return $expr;
  }

}