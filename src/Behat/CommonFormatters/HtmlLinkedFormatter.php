<?php

namespace Behat\CommonFormatters;

use \ReflectionMethod;

use Behat\Behat\Formatter\HtmlFormatter,
    Behat\Behat\Definition\DefinitionInterface;

use Behat\Gherkin\Node\StepNode;

/**
 * HTML formatter with links to the sources containing the step definitions.
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class HtmlLinkedFormatter extends HtmlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function printStepDefinitionPath(StepNode $step, DefinitionInterface $definition)
    {
        if ($this->getParameter('paths')) {
            $this->printPathLink($definition);
        }
    }

    /**
     * Prints path link, which links to the source containing the step definition.
     *
     * @param DefinitionInterface $definition
     */
    protected function printPathLink(DefinitionInterface $definition)
    {
        $path = $this->relativizePathsInString($definition->getPath());
        if ($this->hasParameter('remote_base_url')) {
            $url = $this->getParameter('remote_base_url') 
                . $this->relativizePathsInString($definition->getCallbackReflection()->getFileName());
            $this->writeln('<span class="path"><a href="' . $url . '">' . $path . '</a></span>');
        } else {
            $this->printPathComment($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getHtmlTemplateStyle()
    {
        return parent::getHtmlTemplateStyle() . '
        #behat .path a:link,
        #behat .path a:visited {
            color:#999;
        }
        #behat .path a:hover,
        #behat .path a:active {
            background-color:#000;
            color:#fff;
        }';
    }
}
