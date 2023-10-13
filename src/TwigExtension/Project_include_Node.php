<?php

namespace Drupal\ukd8_customizations\TwigExtension;

use Twig\Compiler;
use Twig\Node\IncludeNode;

class Project_include_Node extends IncludeNode {
    public function compile(Compiler $compiler): void
    {
        $expr = $this->getNode('expr');

        $name = null;
        if ($expr->hasAttribute('value')) {
            $name = $expr->getAttribute('value');
        }

        $compiler->addDebugInfo($this);

        if ($name) {
            $compiler->write("echo '<!-- TWIG INCLUDE : " . $name . "\" -->';\n");
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
                ->write("} catch (\\Twig\\Error\\LoaderError \$e) {\n")
                ->indent()
                ->write("// ignore missing template\n")
                ->outdent()
                ->write("}\n\n")
            ;
        }

        if ($name) {
            $compiler->write("echo '<!-- END TWIG INCLUDE : " . $name . "\" -->';\n");
        }
    }
}
