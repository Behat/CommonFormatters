<?php

namespace Behat\CommonFormatters;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Behat\Behat\Formatter\PrettyFormatter,
    Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Event\OutlineExampleEvent,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Exception\UndefinedException,
    Behat\Behat\Console\Formatter\OutputFormatter;

use Behat\Gherkin\Node\OutlineNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\TableNode;

use Behat\CommonFormatters\Util\FalseStepRecognizer;

/**
 * Pretty formatter with false steps.
 *
 * @author      Fabian Kiss <headrevision@gmail.com>
 */
class PrettyWithFalseStepsFormatter extends PrettyFormatter
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
        if (!$this->getParameter('expand')) {
            $color = $this->getResultOrExceptionColorCode($result, null);

            $this->printColorizedTableRow($examples->getRowAsString($iteration + 1), $color);
            $this->printOutlineExampleResultExceptions($examples, $this->delayedStepEvents);
        } else {
            $this->write('      ' . $examples->getKeyword() . ': ');
            $this->writeln('| ' . implode(' | ', $examples->getRow($iteration + 1)) . ' |');

            $this->stepIndent = '        ';
            foreach ($this->delayedStepEvents as $event) {
                $this->printStep(
                    $event->getStep(),
                    $event->getResult(),
                    $event->getDefinition(),
                    $event->getSnippet(),
                    $event->getException()
                );
            }
            $this->stepIndent = '    ';

            if ($iteration < count($examples->getRows()) - 2) {
                $this->writeln();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printOutlineExampleResultExceptions(TableNode $examples, array $events)
    {
        foreach ($events as $event) {
            $exception = $event->getException();
            if ($exception && !$exception instanceof UndefinedException) {
                $color = $this->getResultOrExceptionColorCode(StepEvent::FAILED, $exception);

                if ($this->parameters->get('verbose')) {
                    $error = (string) $exception;
                } else {
                    $error = $exception->getMessage();
                }
                $error = $this->relativizePathsInString($error);

                $this->writeln(
                    "        {+$color}" . strtr($error, array("\n" => "\n      ")) . "{-$color}"
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printStep(StepNode $step, $result, DefinitionInterface $definition = null,
                                 $snippet = null, \Exception $exception = null)
    {
        $color = $this->getResultOrExceptionColorCode($result, $exception);

        $this->printStepBlock($step, $definition, $color);

        if ($this->parameters->get('multiline_arguments')) {
            $this->printStepArguments($step->getArguments(), $color);
        }
        if (null !== $exception &&
            (!$exception instanceof UndefinedException || null === $snippet)) {
            $this->printStepException($exception, $color);
        }
        if (null !== $snippet && $this->getParameter('snippets')) {
            $this->printStepSnippet($snippet);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenariosSummary(LoggerDataCollector $logger)
    {
        $count  = $logger->getScenariosCount();
        $header = $this->translateChoice('scenarios_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesSummary($logger->getScenariosStatuses(), $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepsSummary(LoggerDataCollector $logger)
    {
        $count  = $logger->getStepsCount();
        $header = $this->translateChoice('steps_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesSummary($logger->getStepsStatuses(), $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function printStatusesSummary(array $statusesStatistics, LoggerDataCollector $logger)
    {
        $numberOfFalseSteps = count(FalseStepRecognizer::getFalseStepsEvents($logger));
        $statusesStatistics['false'] = $numberOfFalseSteps;

        $statuses = array();
        foreach ($statusesStatistics as $status => $count) {
            if ($count) {
                if ($status == 'false') {
                    $transStatus = sprintf("[%s false]", $count);
                    $statuses[] = array_pop($statuses) . " {+$status}$transStatus{-$status}";
                } else {
                    $transStatus = $this->translateChoice(
                        "{$status}_count", $count, array('%1%' => $count)
                    );
                    $statuses[] = "{+$status}$transStatus{-$status}";
                }
            }
        }
        $this->writeln(count($statuses) ? ' ' . sprintf('(%s)', implode(', ', $statuses)) : '');
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
}
