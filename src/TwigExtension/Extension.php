<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use Drupal\Core\Render\Element;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;
use \Twig_Environment;

class Extension extends \Twig\Extension\AbstractExtension {

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'ukd8.twig_extensions';
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      // remove HTML comments from markup
      new \Twig\TwigFilter('nocomment', [$this, 'removeHtmlComments']),
      // an alternate image style (from https://www.drupal.org/files/issues/twig_image_style-2361299-31.patch)
      new \Twig\TwigFilter('resize', [$this, 'getImageFieldWithStyle']),
      // smart truncate
      new \Twig\TwigFilter('smarttrim', [$this, 'smartTrim']),
      // get an alias for an entity
      new \Twig\TwigFilter('alias', [$this, 'entityAlias']),
      // check if a view has any content
      new \Twig\TwigFilter('has_rows', [$this, 'viewHasRows']),
      // remove empty items from an array
      new \Twig\TwigFilter('array_filter', 'array_filter'),
      // run the builder on an entity
      new \Twig\TwigFilter('entity_view', [$this, 'entityView']),
      // html_decode_entities
      new \Twig\TwigFilter('unescape', [$this, 'unescape']),
      // child elements
      new \Twig\TwigFilter('children', [$this, 'children']),
      // social media matcher
      new \Twig\TwigFilter('matchsocial', [$this, 'socialMatcher']),
      // inject a class in a render array
      new \Twig\TwigFilter('injectclass', [$this, 'injectClass']),
      // just wrap PHP uniqid()
      new \Twig\TwigFilter('uniqid', [$this, 'uniqid']),
      // attribution array -> key=value set
      new \Twig\TwigFilter('attr_list', [$this, 'attrList']),
      // try to fix a time timezone
      new \Twig\TwigFilter('tz_adjust', [$this, 'tzAdjust']),
    ];
  }

  /**
   * Generates a list of all Twig functions that this extension defines.
   */
  public function getFunctions()
  {
    return [
        // just wrap PHP uniqid()
        new \Twig\TwigFunction('uniqid', [$this, 'uniqid']),
        // svg injection
        new \Twig\TwigFunction('svg', [$this, 'svg'], ['is_safe' => ['html']]),
        // xdebug breakpoint (based on https://github.com/ajgarlag/AjglBreakpointTwigExtension)
        new \Twig\TwigFunction('xdebug', [$this, 'setBreakpoint'], ['needs_environment' => true, 'needs_context' => true]),
        // uri -> url
        new \Twig\TwigFunction('uritourl', [$this, 'uriToUrl']),
        // load a term from a tid
        new \Twig\TwigFunction('term_lookup', [$this, 'termLookup']),
        // return a rendered term based on a field in the term
        new \Twig\TwigFunction('render_term_lookup', [$this, 'renderTermLookup'], ['is_safe' => ['html']]),
        // return an alias for a language
        new \Twig\TwigFunction('lang_alias', [$this, 'langAlias']),
        // simplify a date range with ranger
        new \Twig\TwigFunction('ranger', [$this, 'simplifyDateRange']),
    ];
  }

  public function getTokenParsers()
  {
      return [
        new Project_include_TokenParser(),
        new Project_embed_TokenParser(),
      ];
  }


  /**
   * Remove HTML comments (from e.g., a field with Twig debug output on)
   */
  public function removeHtmlComments($mixed) {
    if (is_array($mixed)) {
      // ugh, probably a render array
      return $mixed;
    }
    else {
      $output = trim(preg_replace('/<!--(.|\s)*?-->/', '', $mixed));
    }
    return $output;
  }


   /**
   * Function that returns a renderable array of an image field with a given
   * image style.
   *
   * @param $field
   *   Renderable array of a field or maybe a URL
   * @param $style
   *   an image style.
   *
   * @return mixed
   *   a renderable array or NULL if there is no valid input.
   */
  public function getImageFieldWithStyle($field, $style) {
    if (isset($field['#field_type']) && $field['#field_type'] == 'image') {
      $element_children = Element::children($field, TRUE);

      if(!empty($element_children)) {
        foreach ($element_children as $delta => $key) {
          $field[$key]['#image_style'] = $style;
        }
      }
      return $field;
    }

    if (is_string($field)) {
      // assume it's a URL and try to resize
      $stylerenderer = ImageStyle::load($style);
      return $stylerenderer->buildUrl($field);
    }

    return null;
  }


  /**
   * Smart trim, word count
   * assumes a string that's had tags stripped out
   *   e.g., field|render|striptags|smarttrim(n)
   * @param  arr $field
   * @return int        $word_count
   */
  public function smartTrim($field, $word_count = 10) {
    $output = strtok($field, " \n");

    while(--$word_count > 0) {
      $word = strtok(" \n");
      $output .= " " . $word;
    }
    // add a few more to get to the end of the sentence
    while (($word !== false) && (strpos($word, '.') !== strlen($word)-1)) {
      $word = strtok(" \n");
      $output .= " " . $word;
    }

    return $output;
  }

  /**
   * Return an alias for an entity
   * @param  obj $entity
   * @return str        $alias
   */
  public function entityAlias($entity) {
    $url = '';
    if ($entity) {
      $url = $entity->toUrl()->toString();
    }
    return $url;
  }

  /**
   * check if a rendered view has rows (really, just looks for divs)
   * @return bool
   */
  public function viewHasRows($view, $class = 'views-row') {
    $view = $this->removeHtmlComments($view);
    $dom = new \DOMDocument();

    // load HTML but suppress warnings
    $libxml_previous_state = libxml_use_internal_errors(true);
    $dom->loadHTML($view);
    libxml_clear_errors();
    libxml_use_internal_errors($libxml_previous_state);

    $finder = new \DomXPath($dom);
    $rows = $finder->query("//*[contains(@class, '$class')]");
    return ($rows->length >= 1);
  }

  /**
   * build a render array for an entity
   * @return a render array
   */
  public function entityView($entity, $entity_type, $view_mode = 'full') {
    $builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    return $builder->build($builder->view($entity, $view_mode));
  }

  /**
   * wrap html_entity_decode
   * @return str 
   */
  public function unescape($value) {
    return html_entity_decode($value);
  }

  /**
   * wrap PHP uniqid
   * @return str hash
   */
  public function uniqid($prefix = '') {
    return $prefix . '-' . uniqid();
  }
  
  /**
   * inject an svg from a theme
   * @return str html
   */
  public function svg($filename = null, $opts = array()) {
    if (is_null($filename)) {
        return "No SVG specified.";
    }
    // figure out the current theme path
    $theme_dir = \Drupal::theme()->getActiveTheme()->getPath();
    
    // svg dir defined relative to theme dir
    $dir = isset($opts['dir'])? $opts['dir'] : 'svg';
    $svg_dir = realpath($theme_dir . '/' . $dir);
    if ($svg_dir === FALSE) {
      return "SVG directory not found.";
    }
    if (strpos($filename, '.svg')===FALSE) {
      $filename .= '.svg';
    }
    $filename = basename($filename);
    $fn = $svg_dir . '/' . $filename;
    if (!file_exists($fn)) {
      return "SVG file " . $filename . " not found.";
    }
    
    $xml = simplexml_load_file($fn);
    if ($xml === FALSE) {
      return "Unable to read SVG";
    }
    
    $dom = dom_import_simplexml($xml);
    if (!$dom) {
      return "Unable to convert XML to DOM";
    }
    
    // manipulate the output
    foreach ($opts as $k => $v) {
      $dom->setAttribute($k, $v);
    }
    
    // spit out the svg tag
    $output = new \DOMDocument();
    $cloned = $dom->cloneNode(TRUE);
    $output->appendChild($output->importNode($cloned, TRUE));
    
    return $output->saveHTML();
  }

  /**
   * set an xdebug breakpoint (if the extension is available)
   */
  public function setBreakpoint(\Twig\Environment $environment, $context)
  {
    if (function_exists('xdebug_break')) {
      $arguments = array_slice(func_get_args(), 2);
      xdebug_break();
    }
  }

  /**
   * convert a URI into a URL
   */
  public function uriToUrl($uri) {
    $url = \Drupal\Core\Url::fromUri($uri);
    return $url->toString();
  }

  
  /**
   * return a full term entity from a tid
   */
  public function termLookup($tid) {
    if (is_array($tid) && count($tid) == 1) {
      $tid = array_shift($tid);
    }
    if (!is_numeric($tid)) {
      // look up by label
      $query = \Drupal::service('entity.query')
        ->get('taxonomy_term')
        ->condition('name', $tid);
      $tids = $query->execute();
      if (count($tids) > 0) {
        $tid = array_shift($tids);
      }
      else {
        $tid = null;
      }
    }
    return Term::load($tid);
  }
  
  /**
   * lookup and render a term in $taxonomy
   * by matching $needle against $field values
   */
  public function renderTermLookup($taxonomy, $field, $needle) {
    $query = \Drupal::service('entity.query')
        ->get('taxonomy_term')
        ->condition('vid', $taxonomy)
        ->condition($field, $needle);
    $tids = $query->execute();
    if ($tids) {
        $term = $this->termLookup($tids);
        $view_builder = \Drupal::service('entity_type.manager')->getViewBuilder('taxonomy_term');
        return $view_builder->view($term);
    }
  }

  /** 
   * from a system path 
   * return an alias for a particular language
   */
   public function langAlias($system_path, $lang = 'en') {
     return \Drupal::service('path.alias_manager')->getAliasByPath($system_path, $lang);
   }

   /**
   * Get the render children of a field
   */
  public function children($variable) {
    if (is_array($variable)){
      return array_filter($variable, function($k) { return (is_numeric($k) || (strpos($k, '#')!==0)); }, ARRAY_FILTER_USE_KEY);
    }
    return array();
  }

  /** 
   * Figure out a social media label from a URL
   */
  public function socialMatcher($url) {
    $components = parse_url($url);
    $host_pieces = explode('.', $components['host']);
    return $host_pieces[count($host_pieces) - 2];
  } 

  /** 
   * Add a class to a render array
   */
  public function injectClass($build, $class) {
    if (is_array($build)) {
      if (is_string($class)) {
        $build['#attributes']['class'][] = $class;
      }
      if (is_array($class)) {
        $build['#attributes']['class'] += $class;
      }
    }
    return $build;
  } 

  /** 
   * helper function: return an enumerated constant for the format
   */
  private function getIntlDateFormat($format) {
    switch ($format) {
      case 'none':
        return \IntlDateFormatter::NONE;
      case 'full':
        return \IntlDateFormatter::FULL;
      case 'long':
        return \IntlDateFormatter::LONG;
      case 'medium':
        return \IntlDateFormatter::MEDIUM;
      case 'short':
        return \IntlDateFormatter::SHORT;
    }
    return \IntlDateFormatter::MEDIUM;
  }

  /**
   * Simplify a date range
   */
  public function simplifyDateRange($start, $end, $date_format = 'medium', $time_format = 'short', $range_separator = null, $date_time_separator = null) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
    $date_format = $this->getIntlDateFormat($date_format);
    $time_format = $this->getIntlDateFormat($time_format);
  
    $ranger = new \OpenPsa\Ranger\Ranger('en');
    $ranger
      ->setDateType($date_format)
      ->setTimeType($time_format);
    if (!is_null($date_time_separator)) {
      $ranger->setDateTimeSeparator($date_time_separator);
    }
    if (!is_null($range_separator)) {
      $ranger->setRangeSeparator($range_separator);
    }
    if (is_numeric($start)) {
      $start = date('c', $start);
    }
    if (is_numeric($end)) {
      $end = date('c', $end);
    }
    if (empty($end)) {
      $end = $start;
    }
    return $ranger->format($start, $end);
  }

  public function attrList($arr) {
    $str = '';
    if (is_array($arr)) {
        $attributes = [];
        foreach($arr as $key => $value) {
            $key = str_replace('_','-',$key);
            $attributes[] = $key . '=' . $value;
        }
        if (!empty($attributes)) {
            $str = join(' ',$attributes);
        }
    }
    return $str;
  }

  public function tzAdjust($datetime) {
    if (empty($datetime)) return '';
    if (is_numeric($datetime)) {
      $datetime = date('c', $datetime);
    }
    $date = new \DateTime($datetime, new \DateTimeZone('UTC'));
    $tz = date_default_timezone_get();
    $date->setTimezone(new \DateTimeZone($tz));
    return $date->format('Y-m-d g:i a');
  }

}
