<?php

namespace Behat\CommonFormatters;

use Behat\Behat\Formatter\ConsoleFormatter,
    Behat\Behat\Event\EventInterface,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\BackgroundEvent,
    Behat\Behat\Event\OutlineEvent,
    Behat\Behat\Event\OutlineExampleEvent,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Exception\FormatterException;

use Behat\Gherkin\Node\StepNode;

use webignition\JsonPrettyPrinter\JsonPrettyPrinter;

use Json\SchemaException,
    Json\ValidationException,
    Json\Validator;

/**
 * Formatter that dumps the most important information about a suite run as JSON.
 *
 * @link   https://github.com/Behat/CommonFormatters/blob/master/resources/json_formatter_schema.json
 * @link   https://github.com/Behat/CommonFormatters/blob/master/features/json.feature
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class JsonFormatter extends ConsoleFormatter
{
    /**
     * @var array
     */
    protected $features;

    /**
     * @var array
     */
    protected $currentFeature;

    /**
     * @var bool
     */
    protected $currentlyBackgroundUnderway;

    /**
     * @var array
     */
    protected $currentScenarios;

    /**
     * @var array
     */
    protected $currentScenario;

    /**
     * @var array
     */
    protected $currentSteps;

    /**
     * @var array
     */
    protected $currentStep;

    /**
     * @var array
     */
    protected $currentOutlineExamples;

    /**
     * @var array
     */
    protected $currentOutlineExample;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeSuite', 'afterSuite', 'beforeFeature', 'afterFeature', 'beforeScenario',
            'afterScenario', 'beforeBackground', 'afterBackground', 'beforeOutline', 'afterOutline',
            'beforeOutlineExample', 'afterOutlineExample', 'beforeStep', 'afterStep'
        );

        return array_combine($events, $events);
    }

    /**
     * Listens to "suite.before" event.
     *
     * @param SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $this->features = array();
        $this->currentlyBackgroundUnderway = false;
    }

    /**
     * Listens to "suite.after" event.
     *
     * @param SuiteEvent $event
     */
    public function afterSuite(SuiteEvent $event)
    {
        $json = $this->buildJson();
        $json = $this->formatJson($json);
        $this->validateJson($json);
        $this->writeln($json);
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param FeatureEvent $event
     */
    public function beforeFeature(FeatureEvent $event)
    {
        $feature = $event->getFeature();

        $this->currentFeature = array(
            'title' => $feature->getTitle(),
            'desc' => $feature->getDescription(),
            'tags' => $feature->getTags()
        );
        $this->currentScenarios = array();
    }

    /**
     * Listens to "feature.after" event.
     *
     * @param FeatureEvent $event
     */
    public function afterFeature(FeatureEvent $event)
    {
        $this->currentFeature['result'] = $this->getResultAlias($event);
        $this->currentFeature['scenarios'] = $this->currentScenarios;
        $this->features[] = $this->currentFeature;
    }

    /**
     * Listens to "scenario.before" event.
     *
     * @param ScenarioEvent $event
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        $scenario = $event->getScenario();
        $this->currentScenario = array(
            'title' => $scenario->getTitle(),
            'isOutline' => false,
            'tags' => $scenario->getTags()
        );
        $this->currentSteps = array();
    }

    /**
     * Listens to "scenario.after" event.
     *
     * @param ScenarioEvent $event
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $this->currentScenario['result'] = $this->getResultAlias($event);
        $this->currentScenario['steps'] = $this->currentSteps;
        $this->currentScenarios[] = $this->currentScenario;
    }

    /**
     * Listens to "background.before" event.
     *
     * @param BackgroundEvent $event
     */
    public function beforeBackground(BackgroundEvent $event)
    {
        $this->currentlyBackgroundUnderway = true;
    }

    /**
     * Listens to "background.after" event.
     *
     * @param BackgroundEvent $event
     */
    public function afterBackground(BackgroundEvent $event)
    {
        $this->currentlyBackgroundUnderway = false;
    }

    /**
     * Listens to "outline.before" event.
     *
     * @param OutlineEvent $event
     */
    public function beforeOutline(OutlineEvent $event)
    {
        $this->currentScenario = array(
            'title' => $event->getOutline()->getTitle(),
            'isOutline' => true
        );
        $this->currentOutlineExamples = array();
    }

    /**
     * Listens to "outline.after" event.
     *
     * @param OutlineEvent $event
     */
    public function afterOutline(OutlineEvent $event)
    {
        $outlineSteps = array();
        foreach ($event->getOutline()->getSteps() as $step) {
            $outlineSteps[] = array(
                'text' => $step->getText(),
                'type' => $step->getType()
            );
        }

        $this->currentScenario['result'] = $this->getResultAlias($event);
        $this->currentScenario['steps'] = $outlineSteps;
        $this->currentScenario['examples'] = $this->currentOutlineExamples;
        $this->currentScenarios[] = $this->currentScenario;
    }

    /**
     * Listens to "outline.example.before" event.
     *
     * @param OutlineExampleEvent $event
     */
    public function beforeOutlineExample(OutlineExampleEvent $event)
    {
        $outlineExamples = $event->getOutline()->getExamples();
        $placeholders = $outlineExamples->getRow(0);
        $values = $outlineExamples->getRow($event->getIteration() + 1);

        $this->currentOutlineExample = array(
            'values' => array_combine($placeholders, $values)
        );
    }

    /**
     * Listens to "outline.example.after" event.
     *
     * @param OutlineExampleEvent $event
     */
    public function afterOutlineExample(OutlineExampleEvent $event)
    {
        $this->currentOutlineExample['result'] = $this->getResultAlias($event);
        $this->currentOutlineExamples[] = $this->currentOutlineExample;
    }

    /**
     * Listens to "step.before" event.
     *
     * @param StepEvent $event
     */
    public function beforeStep(StepEvent $event)
    {
        $step = $event->getStep();

        $this->currentStep = array(
            'text' => $this->getStepText($step),
            'type' => $step->getType(),
            'isBackground' => $this->currentlyBackgroundUnderway
        );
    }

    /**
     * Listens to "step.after" event.
     *
     * @param StepEvent $event
     */
    public function afterStep(StepEvent $event)
    {
        $this->currentStep['result'] = $this->getResultAlias($event);
        $this->currentSteps[] = $this->currentStep;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParameters()
    {
        return array(
            'expand' => true,
            'debug' => false
        );
    }

    /**
     * @param Behat\Gherkin\Node\StepNode $step
     *
     * @return string
     */
    protected function getStepText(StepNode $step)
    {
        $rawText = $step->getText();

        if ($step->hasArguments()) {
            foreach($step->getArguments() as $stepArgument) {
                $rawText .= "\n" . $stepArgument;
            }
        }

        return $rawText;
    }

    /**
     * @param Behat\Behat\Event\EventInterface $event
     *
     * @return string
     */
    protected function getResultAlias(EventInterface $event)
    {
        $eventClass = new \ReflectionClass('Behat\Behat\Event\StepEvent');
        $resultAliases = $eventClass->getConstants();

        $result = $event->getResult();
        if (is_null($result)) {
            return null;
        }

        $resultAlias = array_search($event->getResult(), $resultAliases);
        if ($resultAlias) {
            return strtolower($resultAlias);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    protected function buildJson()
    {
        $json = json_encode(array(
            'date' => date('o-m-d H:i:s'),
            'features' => $this->features
        ));

        return $json;
    }

    /**
     * @param string $json
     *
     * @return string
     */
    protected function formatJson($json)
    {
        $formattedJson = $json;

        if ($this->getParameter('debug')) {
            $jsonPrettyPrinter = new JsonPrettyPrinter();
            $formattedJson = $jsonPrettyPrinter->format($json);
        }

        return $formattedJson;
    }

    /**
     * @param string $json
     */
    protected function validateJson($json)
    {
        try {
            $validator = new Validator(__DIR__ . '/../../../resources/json_formatter_schema.json');
        } catch (SchemaException $e) {
            throw new FormatterException("JSON schema is invalid:\n\n" . $e->getMessage());
        }

        try {
            $validator->validate(json_decode($json));
        } catch (ValidationException $e) {
            throw new FormatterException("JSON is invalid:\n\n" . $e->getMessage());
        }
    }
}
