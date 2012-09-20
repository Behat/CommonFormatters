Feature: JSON formatter
  In order to reuse a test run output as a human-readable data structure 
  As a behat developer
  I need to have a JSON formatter

  Background:
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features:   features
          bootstrap:   features/bootstrap
        formatter:
          parameters:
            debug: true
          classes:
            json: 'Behat\CommonFormatters\JsonFormatter'
      """
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php

      use Behat\Behat\Context\ClosuredContextInterface,
          Behat\Behat\Context\BehatContext,
          Behat\Behat\Exception\PendingException;
      use Behat\Gherkin\Node\PyStringNode,
          Behat\Gherkin\Node\TableNode;
      use Symfony\Component\Finder\Finder;

      if (file_exists(__DIR__ . '/../support/bootstrap.php')) {
          require_once __DIR__ . '/../support/bootstrap.php';
      }

      class FeatureContext extends BehatContext implements ClosuredContextInterface
      {
          public $parameters = array();

          public function __construct(array $parameters) {
              $this->parameters = $parameters;

              if (file_exists(__DIR__ . '/../support/env.php')) {
                  $world = $this;
                  require(__DIR__ . '/../support/env.php');
              }
          }

          public function getStepDefinitionResources() {
              if (file_exists(__DIR__ . '/../steps')) {
                  $finder = new Finder();
                  return $finder->files()->name('*.php')->in(__DIR__ . '/../steps');
              }
              return array();
          }

          public function getHookDefinitionResources() {
              if (file_exists(__DIR__ . '/../support/hooks.php')) {
                  return array(__DIR__ . '/../support/hooks.php');
              }
              return array();
          }

          public function __call($name, array $args) {
              if (isset($this->$name) && is_callable($this->$name)) {
                  return call_user_func_array($this->$name, $args);
              } else {
                  $trace = debug_backtrace();
                  trigger_error(
                      'Call to undefined method ' . get_class($this) . '::' . $name .
                      ' in ' . $trace[0]['file'] .
                      ' on line ' . $trace[0]['line'],
                      E_USER_ERROR
                  );
              }
          }
      }
      """
    And a file named "features/support/bootstrap.php" with:
      """
      <?php
      require_once 'PHPUnit/Autoload.php';
      require_once 'PHPUnit/Framework/Assert/Functions.php';
      """
    And a file named "features/steps/definitions.php" with:
      """
      <?php
      $steps->Given('/some precondition/', function() {
      });
      $steps->Given('/precondition 1/', function() {
      });
      $steps->Given('/precondition 2/', function() {
      });
      $steps->When('/some action/', function() {
      });
      $steps->When('/action 1/', function() {
      });
      $steps->When('/some action is pending/', function() {
          throw new PendingException();
      });
      $steps->When('/some action that fails/', function() {
          throw new \Exception();
      });
      $steps->Then('/some outcome/', function() {
      });
      """

Scenario: JSON-formatted output containing a feature with title only
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario: A scenario
        Given some precondition
    """
  When I run "behat -f json"
  Then the output should contain:
    """
    [
      {
        "title": "Generate JSON",
        "desc": null
    """

Scenario: JSON-formatted output containing a feature with title and description
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON
      In order to generate JSON

      Scenario: A scenario
        Given some precondition
    """
  When I run "behat -f json"
  Then the output should contain:
    """
    [
      {
        "title": "Generate JSON",
        "desc": "In order to generate JSON"
    """

Scenario: JSON-formatted output containing two features
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario: A scenario
        Given some precondition
    """
  And a file named "features/other_steps.feature" with:
    """
    Feature: Generate more JSON

      Scenario: A scenario
        Given some precondition
    """
  When I run "behat -f json"
  Then the output should contain:
    """
    [
      {
        "title": "Generate more JSON",
        "desc": null
    """
  And the output should contain:
    """
      {
        "title": "Generate JSON",
        "desc": null
    """

Scenario: JSON-formatted output containing a scenario
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario: A scenario
        Given some precondition
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": false,
                "result": "passed"
    """

Scenario: JSON-formatted output containing two scenarios
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario: A scenario
        Given some precondition

      Scenario: Another scenario
        Given some precondition
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": false,
                "result": "passed"
    """
  And the output should contain:
    """
          {
            "title": "Another scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": false,
                "result": "passed"
    """

Scenario: JSON-formatted output containing a background and one scenario
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Background:
        Given some precondition
        And some precondition

      Scenario: A scenario
        When some action
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": true,
                "result": "passed"
    """
  And the output should contain:
    """
              {
                "text": "some precondition",
                "type": "And",
                "background": true,
                "result": "passed"
    """
  And the output should contain:
    """
              {
                "text": "some action",
                "type": "When",
                "background": false,
                "result": "passed"
    """

Scenario: JSON-formatted output containing a background and two scenarios
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Background:
        Given some precondition

      Scenario: A scenario
        When some action

      Scenario: Another scenario
        Then some outcome
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": true,
                "result": "passed"
    """
  And the output should contain:
    """
              {
                "text": "some action",
                "type": "When",
                "background": false,
                "result": "passed"
    """
  And the output should contain:
    """
          {
            "title": "Another scenario",
            "class": "scenario",
            "result": "passed",
            "steps": [
              {
                "text": "some precondition",
                "type": "Given",
                "background": true,
                "result": "passed"
    """
  And the output should contain:
    """
              {
                "text": "some outcome",
                "type": "Then",
                "background": false,
                "result": "passed"
              }
            ]
          }
        ]
    """

Scenario: JSON-formatted output containing a scenario outline with one placeholder and two values
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario Outline: A scenario outline
        Given <precondition>

      Examples:
        | precondition   |
        | precondition 1 |
        | precondition 2 |
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario outline",
            "class": "outline",
            "result": "passed",
            "steps": [
              {
                "text": "<precondition>",
                "type": "Given"
    """
  And the output should contain:
    """
            "examples": [
              {
                "values": {
                  "precondition": "precondition 1"
                },
                "result": "passed"
              },
              {
                "values": {
                  "precondition": "precondition 2"
                },
                "result": "passed"
              }
            ]
    """

Scenario: JSON-formatted output containing a scenario outline with two placeholders and one value each
  Given a file named "features/steps.feature" with:
    """
    Feature: Generate JSON

      Scenario Outline: A scenario outline
        Given <precondition>
        When <action>

      Examples:
        | precondition   | action   |
        | precondition 1 | action 1 |
    """
  When I run "behat -f json"
  Then the output should contain:
    """
        "scenarios": [
          {
            "title": "A scenario outline",
            "class": "outline",
            "result": "passed",
            "steps": [
              {
                "text": "<precondition>",
                "type": "Given"
    """
  And the output should contain:
    """
              {
                "text": "<action>",
                "type": "When"
    """
  And the output should contain:
    """
            "examples": [
              {
                "values": {
                  "precondition": "precondition 1",
                  "action": "action 1"
                },
                "result": "passed"
              }
            ]
    """
