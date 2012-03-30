<?php

namespace Behat\CommonFormatters;

use Behat\Behat\Formatter\ConsoleFormatter;

use Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Exception\FormatterException;

use Symfony\Component\Console\Output\StreamOutput;

/**
 * Formatter that adds a new line to a CSV file for each suite run.
 *
 * @author      Fabian Kiss <headrevision@gmail.com>
 */
class CsvStatisticsFormatter extends ConsoleFormatter
{
    /**
     * CSV file is empty so far?
     *
     * @var     boolean
     */
    protected $isEmptyFile;

    /**
     * {@inheritdoc}
     */
    public static function getDescription()
    {
        return "Updates CSV file.";
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParameters()
    {
        return array(
            'max_filesize' => 1024^3,
            'delimiter' => ","
        );
    }

    /**
     * @see     Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
     */
    public static function getSubscribedEvents()
    {
        $events = array('afterSuite');

        return array_combine($events, $events);
    }

    /**
     * Listens to "suite.after" event.
     *
     * @param   Behat\Behat\Event\SuiteEvent    $event
     *
     * @uses    printFieldNames()
     * @uses    printRecord()
     */
    public function afterSuite(SuiteEvent $event)
    {
        $logger = $event->getLogger();

        $this->printFieldNames();
        $this->printRecord($logger);
    }

    /**
     * Prints the name to each field.
     */
    protected function printFieldNames()
    {
        // trigger init of output stream to identify whether file is empty
        $this->getWritingConsole();

        if ($this->isEmptyFile) {
            $delimiter = $this->parameters->get('delimiter');
            $this->writeln(
                'execution date' . $delimiter .
                ($this->parameters->get('time') ? 'total execution time' . $delimiter : '') .
                'number of features' . $delimiter .
                'number of features with failures' . $delimiter .
                'number of scenarios' . $delimiter .
                'number of scenarios with failures' . $delimiter .
                'number of steps' . $delimiter .
                'number of failed steps'
            );
        $this->isEmptyFile = false;
        }
    }

    /**
     * Prints a record.
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger suite logger
     *
     * @uses    getNumberOfFailures()
     */
    protected function printRecord(LoggerDataCollector $logger)
    {
        $delimiter = $this->parameters->get('delimiter');
        $this->writeln(
            date('D d M Y H:i:s') . $delimiter .
            ($this->parameters->get('time') ? $this->getTimeSummary($logger) . $delimiter : '') .
            $logger->getFeaturesCount() . $delimiter .
            $this->getNumberOfFailures($logger->getFeaturesStatuses()) . $delimiter .
            $logger->getScenariosCount() . $delimiter .
            $this->getNumberOfFailures($logger->getScenariosStatuses()) . $delimiter .
            $logger->getStepsCount() . $delimiter .
            $this->getNumberOfFailures($logger->getStepsStatuses())
        );
    }


    /**
     * Retrieves suite run time information.
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger suite logger
     */
    protected function getTimeSummary(LoggerDataCollector $logger)
    {
        $time       = $logger->getTotalTime();
        $minutes    = floor($time / 60);
        $seconds    = round($time - ($minutes * 60), 3);

        return $minutes . 'm' . $seconds . 's';
    }

    /**
     * Retrieves the number of failures.
     *
     * @param   array   $statusesStatistics statuses statistic hash (status => count)
     */
    protected function getNumberOfFailures(array $statusesStatistics)
    {
        return $statusesStatistics['failed'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createOutputStream()
    {
        $outputPath = $this->parameters->get('output_path');

        if (null === $outputPath) {
            $this->isEmptyFile = true;
            $stream = fopen('php://stdout', 'w');
        } elseif (!is_dir($outputPath)) {
            if (file_exists($outputPath)) {
                $this->isEmptyFile = false;
                $this->archiveFile($outputPath);
            } else {
                $this->isEmptyFile = true;
            }
            $stream = fopen($outputPath, 'a');
        } else {
            throw new FormatterException(sprintf(
                'Filename expected as "output_path" parameter of "%s" formatter, but got: "%s"',
                basename(str_replace('\\', '/', get_class($this))), $outputPath
            ));
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    protected function createOutputConsole()
    {
        return new StreamOutput(
            $this->createOutputStream()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOutputConsole(StreamOutput $console)
    {
        // no configuration
    }

    /**
     * Archives file if maximimum filesize has been exceeded.
     *
     * @param   string   $outputPath file path
     */
    protected function archiveFile($outputPath)
    {
        if (filesize($outputPath) > $this->parameters->get('max_filesize')) {
            rename($outputPath, $outputPath . '.archive.' . date('Y.M.d'));
            $this->isEmptyFile = true;
        }
    }
}
