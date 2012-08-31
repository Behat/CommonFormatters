Feature: CSV statistics formatter
  In order to record long-time statistics about suite runs
  As a feature developer
  I need to have a CSV statistics formatter

  Background:
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features:   features
          bootstrap:   features/bootstrap
        formatter:
          classes:
            csv_statistics: 'Behat\CommonFormatters\CsvStatisticsFormatter'
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
      $steps->Given('/some precondition not fulfilled/', function() {
          assertTrue(false);
      });
      """
    And a file named "features/failures.feature" with:
      """
      Feature: Generate CSV

        Scenario: A scenario without failures
          Given some precondition

        Scenario: A scenario with failures
          Given some precondition not fulfilled
      """

Scenario: CSV-formatted output containing field names and one record
    When I run "behat -f csv_statistics --no-time"
    Then the output should contain:
      """
execution date,number of features,number of features with failures,number of scenarios,number of scenarios with failures,number of steps,number of failed steps
      """
     And the output should contain:
      """
1,1,2,1,2,1
     """
