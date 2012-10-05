<?php

namespace Behat\CommonFormatters;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Behat\Behat\Formatter\HtmlFormatter,
    Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Exception\UndefinedException,
    Behat\Behat\Console\Formatter\OutputFormatter;

use Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\TableNode;

use Behat\CommonFormatters\Util\FalseStepRecognizer;

/**
 * HTML formatter with false steps.
 *
 * @link   https://github.com/Behat/CommonFormatters/blob/master/features/false_steps.feature
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class HtmlWithFalseStepsFormatter extends HtmlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function createOutputConsole()
    {
        $streamOutput = parent::createOutputConsole();
        $format = new OutputFormatter(null, array(
            'false'         => new OutputFormatterStyle('magenta'),
            'false_param'   => new OutputFormatterStyle('magenta', null, array('bold'))
        ));
        $streamOutput->setFormatter($format);

        return $streamOutput;
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExampleResult(TableNode $examples, $iteration, $result, $isSkipped)
    {
        $color  = $this->getResultOrExceptionColorCode($result, null);

        $this->printColorizedTableRow($examples->getRow($iteration + 1), $color);
        $this->printOutlineExampleResultExceptions($examples, $this->delayedStepEvents);
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExampleResultExceptions(TableNode $examples, array $events)
    {
        $colCount = count($examples->getRow(0));

        foreach ($events as $event) {
            $exception = $event->getException();
            if ($exception && !$exception instanceof UndefinedException) {
                $error = $this->relativizePathsInString($exception->getMessage());

                $color = $this->getResultOrExceptionColorCode(StepEvent::FAILED, $exception);

                $this->writeln('<tr class="' . $color . ' exception">');
                $this->writeln('<td colspan="' . $colCount . '">');
                $this->writeln('<pre class="backtrace">' . htmlspecialchars($error) . '</pre>');
                $this->writeln('</td>');
                $this->writeln('</tr>');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printStep(StepNode $step, $result, DefinitionInterface $definition = null,
                                 $snippet = null, \Exception $exception = null)
    {
        $this->writeln('<li class="' . $this->getResultOrExceptionColorCode($result, $exception) . '">');

        $color = $this->getResultOrExceptionColorCode($result, $exception);

        $this->printStepBlock($step, $definition, $color);

        if ($this->parameters->get('multiline_arguments')) {
            $this->printStepArguments($step->getArguments(), $color);
        }
        if (null !== $exception &&
            (!$exception instanceof UndefinedException || null === $snippet)) {
            $this->printStepException($exception, $color);
        }
        if (null !== $snippet) {
            $this->printStepSnippet($snippet);
        }

        $this->writeln('</li>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenariosSummary(LoggerDataCollector $logger)
    {
        $this->writeln('<p class="scenarios">');

        $count  = $logger->getScenariosCount();
        $header = $this->translateChoice('scenarios_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesExtendedSummary($logger->getScenariosStatuses(), $logger);

        $this->writeln('</p>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepsSummary(LoggerDataCollector $logger)
    {
        $this->writeln('<p class="steps">');

        $count  = $logger->getStepsCount();
        $header = $this->translateChoice('steps_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesExtendedSummary($logger->getStepsStatuses(), $logger);

        $this->writeln('</p>');
    }

    /**
     * @see printStatusesSummary()
     */
    protected function printStatusesExtendedSummary(array $statusesStatistics, LoggerDataCollector $logger) {
        $numberOfFalseSteps = count(FalseStepRecognizer::getFalseStepsEvents($logger));
        $statusesStatistics['false'] = $numberOfFalseSteps;

        $statuses = array();
        $statusTpl = '<strong class="%s">%s</strong>';
        foreach ($statusesStatistics as $status => $count) {
            if ($count) {
                if ($status == 'false') {
                    $transStatus = sprintf("[%s false]", $count);
                    $statuses[] = array_pop($statuses) . " " . sprintf($statusTpl, $status, $transStatus);
                } else {
                    $transStatus = $this->translateChoice(
                        "{$status}_count", $count, array('%1%' => $count)
                    );
                    $statuses[] = sprintf($statusTpl, $status, $transStatus);
                }
            }
        }
        if (count($statuses)) {
            $this->writeln(' ('.implode(', ', $statuses).')');
        }
    }

    /**
     * Returns color code from tester result status code and exception.
     *
     * @param   integer     $result      tester result status code
     * @param   Exception   $exception   exception (if step is failed)
     *
     * @return  string          passed|pending|skipped|undefined|failed|false
     */
    protected function getResultOrExceptionColorCode($result, $exception)
    {
        if (FalseStepRecognizer::isAnAssertionFrameworkException($exception)) {
            return 'false';
        } else {
            return $this->getResultColorCode($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getHtmlTemplateStyle()
    {
        return parent::getHtmlTemplateStyle() . '
        #behat .scenario .examples table .exception td {
            border-left:5px solid #000;
            padding-left:0px;
        }
        #behat .scenario .examples table .false.exception td {
            border-color:#c200ee !important;
        }
        .failed .backtrace {
            border-left:2px solid #C20000;
        }
        .false .backtrace {
            border-left:2px solid #c200ee;
        }
        #behat .false {
            background:#ead9ee;
            border-color:#c200ee !important;
            color:#c200ee;
        }';
    }

    /**
     * {@inheritdoc}
     */
    protected function getHtmlTemplateScript()
    {
        return <<<'HTMLTPL'
        $(document).ready(function(){
            $('#behat .feature h2').click(function(){
                $(this).parent().toggleClass('jq-toggle-opened');
            }).parent().addClass('jq-toggle');

            $('#behat .scenario h3').click(function(){
                $(this).parent().toggleClass('jq-toggle-opened');
            }).parent().addClass('jq-toggle');

            $('#behat_show_all').click(function(){
                $('#behat .feature').addClass('jq-toggle-opened');
                $('#behat .scenario').addClass('jq-toggle-opened');
            });

            $('#behat_hide_all').click(function(){
                $('#behat .feature').removeClass('jq-toggle-opened');
                $('#behat .scenario').removeClass('jq-toggle-opened');
            });

            $('#behat .summary .counters .scenarios .passed')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:not(:has(.failed, .false, .pending))');
                    var $feature  = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });

            $('#behat .summary .counters .steps .passed')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:has(.passed)');
                    var $feature  = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });

            $('#behat .summary .counters .failed')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:has(.failed, .false)');
                    var $feature = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });

            $('#behat .summary .counters .false')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:has(.false)');
                    var $feature = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });

            $('#behat .summary .counters .skipped')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:has(.skipped)');
                    var $feature = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });

            $('#behat .summary .counters .pending')
                .addClass('switcher')
                .click(function(){
                    var $scenario = $('.feature .scenario:has(.pending)');
                    var $feature = $scenario.parent();

                    $('#behat_hide_all').click();

                    $scenario.addClass('jq-toggle-opened');
                    $feature.addClass('jq-toggle-opened');
                });
        });
HTMLTPL;
    }
}
