Feature: HTML formatter with links to the source containing step definitions
  In order to debug features
  As a feature writer
  I need to have a HTML formatter with links to the source

  Background:
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features:               %%BEHAT_CONFIG_PATH%%/features
          bootstrap:              %%BEHAT_CONFIG_PATH%%/features/bootstrap
        formatter:
          name:                   'html_linked'
          parameters:
            remote_base_url:      'http://localhost/'
          classes:
            html_linked:          'Behat\CommonFormatters\HtmlLinkedFormatter'

      annotations:
        paths:
          features:               %%BEHAT_CONFIG_PATH%%/features/annotations

      closures:
        paths:
          features:               %%BEHAT_CONFIG_PATH%%/features/closures
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
      """
    And a file named "features/steps.feature" with:
      """
      Feature: Step definition linking

        Scenario: A scenario comprising one step
          Given some precondition
      """

Scenario: HTML-formatted output with a link to the source containing the definition of the sole step in the one scenario of a feature
    When I run "behat -c behat.yml -f html_linked --no-time"
    Then the output should contain:
      """
      <div class="scenario">
      <h3>
      <span class="keyword">Scenario: </span>
      <span class="title">A scenario comprising one step</span>
      <span class="path">features/steps.feature:3</span>
      </h3>
      <ol>
      <li class="passed">
      <div class="step">
     <span class="keyword">Given </span>
     <span class="text">some precondition</span>
     <span class="path"><a href="http://localhost/features/steps/definitions.php">features/steps/definitions.php:2</a></span>
     </div>
     </li>
     </ol>
     </div>
     """
