<?php

namespace Behat\CommonFormatters;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Behat\Behat\Formatter\ProgressFormatter,
    Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Console\Formatter\OutputFormatter;

use Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\StepNode;

use Behat\CommonFormatters\Util\FalseStepRecognizer;

/**
 * Progress formatter with false steps.
 *
 * @link   https://github.com/Behat/CommonFormatters/blob/master/features/false_steps.feature
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class ProgressWithFalseStepsFormatter extends ProgressFormatter
{
    /**
     * Listens to "suite.after" event.
     *
     * @param   Behat\Behat\Event\SuiteEvent    $event
     *
     * @uses    printFailedSteps()
     * @uses    printFalseSteps()
     * @uses    printPendingSteps()
     * @uses    printSummary()
     * @uses    printUndefinedStepsSnippets()
     */
    public function afterSuite(SuiteEvent $event)
    {
        $logger = $event->getLogger();

        $this->writeln("\n");
        $this->printFailedSteps($logger);
        $this->printFalseSteps($logger);
        $this->printPendingSteps($logger);
        $this->printSummary($logger);
        $this->printUndefinedStepsSnippets($logger);
    }

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
    protected function printStep(StepNode $step, $result, DefinitionInterface $definition = null,
                                 $snippet = null, \Exception $exception = null)
    {
        if (FalseStepRecognizer::isAnAssertionFrameworkException($exception)) {
            $this->write('{+false}S{-false}');
        } else {
            parent::printStep($step, $result, $definition, $snippet, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printFailedSteps(LoggerDataCollector $logger)
    {
        if (count(FalseStepRecognizer::getNonFalseStepsEvents($logger))) {
            $header = $this->translate('failed_steps_title');
            $this->writeln("{+failed}(::) $header (::){-failed}\n");
            $this->printExceptionEvents(FalseStepRecognizer::getNonFalseStepsEvents($logger));
        }
    }

    /**
     * Prints all false steps info.
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger suite logger
     */
    protected function printFalseSteps(LoggerDataCollector $logger)
    {
        if (count(FalseStepRecognizer::getFalseStepsEvents($logger))) {
            $header = 'false steps';
            $this->writeln("{+false}(::) $header (::){-false}\n");
            $this->printExceptionEvents(FalseStepRecognizer::getFalseStepsEvents($logger));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printExceptionEvents(array $events)
    {
        foreach ($events as $number => $event) {
            $exception = $event->getException();

            if (null !== $exception) {
                $color = $this->getExceptionColorCode($exception);

                if ($this->parameters->get('verbose')) {
                    $error = (string) $exception;
                } else {
                    $error = $exception->getMessage();
                }
                $error = sprintf("%s. %s",
                    str_pad((string) ($number + 1), 2, '0', STR_PAD_LEFT),
                    strtr($error, array("\n" => "\n    "))
                );
                $error = $this->relativizePathsInString($error);

                $this->writeln("{+$color}$error{-$color}");
            }

            $this->printStepPath($event->getStep(), $event->getDefinition(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepPath(StepNode $step, DefinitionInterface $definition = null,
                                     \Exception $exception = null)
    {
        $color = $this->getExceptionColorCode($exception);

        $type       = $step->getType();
        $text       = $step->getText();
        $stepPath   = "In step `$type $text'.";
        $stepPathLn = mb_strlen($stepPath);

        $node = $step->getParent();
        if ($node instanceof BackgroundNode) {
            $scenarioPath   = "From scenario background.";
        } else {
            $title          = $node->getTitle();
            $title          = $title ? "`$title'" : '***';
            $scenarioPath   = "From scenario $title.";
        }
        $scenarioPathLn     = mb_strlen($scenarioPath);

        $this->maxLineLength = max($this->maxLineLength, $stepPathLn);
        $this->maxLineLength = max($this->maxLineLength, $scenarioPathLn);

        $this->write("    {+$color}$stepPath{-$color}");
        if (null !== $definition) {
            $indentCount = $this->maxLineLength - $stepPathLn;
            $this->printPathComment(
                $this->relativizePathsInString($definition->getPath()), $indentCount
            );
        } else {
            $this->writeln();
        }

        $this->write("    {+$color}$scenarioPath{-$color}");
        $indentCount = $this->maxLineLength - $scenarioPathLn;
        $this->printPathComment(
            $this->relativizePathsInString($node->getFile()) . ':' . $node->getLine(), $indentCount
        );
        $this->writeln();
    }

    /**
     * {@inheritdoc}
     */
    protected function printScenariosSummary(LoggerDataCollector $logger)
    {
        $count  = $logger->getScenariosCount();
        $header = $this->translateChoice('scenarios_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesExtendedSummary($logger->getScenariosStatuses(), $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function printStepsSummary(LoggerDataCollector $logger)
    {
        $count  = $logger->getStepsCount();
        $header = $this->translateChoice('steps_count', $count, array('%1%' => $count));
        $this->write($header);
        $this->printStatusesExtendedSummary($logger->getStepsStatuses(), $logger);
    }

    /**
     * @see printStatusesSummary()
     */
    protected function printStatusesExtendedSummary(array $statusesStatistics, LoggerDataCollector $logger)
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
     * Returns color code from exception.
     *
     * @param   Exception   $exception   exception from step failed
     *
     * @return  string          pending|failed|false
     */
    protected function getExceptionColorCode($exception)
    {
        if ($exception instanceof PendingException) {
            return 'pending';
        } elseif (FalseStepRecognizer::isAnAssertionFrameworkException($exception)) {
            return 'false';
        } else {
            return 'failed';
        }
    }
}
