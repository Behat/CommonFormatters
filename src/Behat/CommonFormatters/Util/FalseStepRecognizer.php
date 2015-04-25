<?php

namespace Behat\CommonFormatters\Util;

use Behat\Behat\DataCollector\LoggerDataCollector;

/**
 * Util class for recognizing false steps.
 *
 * @author      Fabian Kiss <headrevision@gmail.com>
 */
class FalseStepRecognizer
{ 

    /*
     * recognition works for PHPUnit & Mink only!
     */
    protected static $frameworkExceptions = array(
        'PHPUnit_Framework_ExpectationFailedException',
        'Behat\Mink\Exception\ExpectationException',
    );

    /**
     * @param   Exception   $exception   exception to a step failed
     *
     * @return  boolean
     */
    public static function isAnAssertionFrameworkException($exception)
    {
        foreach (self::$frameworkExceptions as $className) {
            if ($exception instanceof $className) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns array of failed steps events without non-false steps among those
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger suite logger
     *
     * @return  array
     */
    public static function getFalseStepsEvents(LoggerDataCollector $logger)
    {
        return self::getFalseOrNonFalseStepsEvents($logger, true);
    }

    /**
     * Returns array of failed steps events without false steps among those
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger suite logger
     *
     * @return  array
     */
    public static function getNonFalseStepsEvents(LoggerDataCollector $logger)
    {
        return self::getFalseOrNonFalseStepsEvents($logger, false);
    }

    /**
     * Returns array containing a subset of failed steps events - either all 
     * false steps or all non-false steps among those.
     *
     * @param   Behat\Behat\DataCollector\LoggerDataCollector   $logger       suite logger
     * @param   boolean                                         $falseSteps   false steps only?
     *
     * @return  array
     */
    protected static function getFalseOrNonFalseStepsEvents(LoggerDataCollector $logger, $falseSteps)
    {
        $falseStepsEvents = array();
        $failedStepsEvents = $logger->getFailedStepsEvents();
        foreach ($failedStepsEvents as $failedStepEvent) {
            $failedStepException = $failedStepEvent->getException();
            if ($falseSteps && self::isAnAssertionFrameworkException($failedStepException) ||
                !$falseSteps && !self::isAnAssertionFrameworkException($failedStepException)) {
                $falseStepsEvents[] = $failedStepEvent;
            }
        }
        return $falseStepsEvents;
    }
}
