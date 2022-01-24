<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use \Twig_Compiler;
use \Twig_Node_Include;

class Project_include_Node extends \Twig\Node\IncludeNode
{
    public function compile(\Twig\Compiler $compiler)
    {
        $expr = $this->getNode('expr');

        $name = null;
        if ($expr->hasAttribute('value')) {
            $name = $expr->getAttribute('value');
        }

        $compiler->addDebugInfo($this);

        if ($name) {
            $compiler
                ->write("echo '<!-- TWIG INCLUDE : " . $name . "\" -->';\n");
        }

        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->write("try {\n")
                ->indent()
            ;
        }

        $this->addGetTemplate($compiler);

        $compiler->raw('->display(');

        $this->addTemplateArguments($compiler);

        $compiler->raw(");\n");

        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->outdent()
                ->write("} catch (Twig_Error_Loader \$e) {\n")
                ->indent()
                ->write("// ignore missing template\n")
                ->outdent()
                ->write("}\n\n")
            ;
        }

        if ($name) {
            $compiler
                 ->write("echo '<!-- END TWIG INCLUDE : " . $name . "\" -->';\n");
        }
    }
}