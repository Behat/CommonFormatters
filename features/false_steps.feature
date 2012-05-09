Feature: Formatters with failed steps
  In order to debug features
  As a feature writer
  I need to have formatters with failed steps

  Background:
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features:   features
          bootstrap:   features/bootstrap
        formatter:
          classes:
            Behat\CommonFormatters\ProgressWithFalseStepsFormatter:   'Behat\CommonFormatters\ProgressWithFalseStepsFormatter',
            Behat\CommonFormatters\PrettyWithFalseStepsFormatter:   'Behat\CommonFormatters\PrettyWithFalseStepsFormatter',
            Behat\CommonFormatters\HtmlWithFalseStepsFormatter:   'Behat\CommonFormatters\HtmlWithFalseStepsFormatter'
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
    And a file named "features/steps/failures.php" with:
      """
      <?php
      $steps->Given('/an unexpected error occurs/', function() {
          throw(new \RuntimeException('A runtime exception occured.'));
      });

      $steps->Given('/an assertion framework exception occurs/', function() {
          assertTrue(false);
      });
      """
    And a file named "features/false_step.feature" with:
      """
      Feature: False step recognition

        Scenario: Non-false step (a failed step due to an error not expected)
          Given an unexpected error occurs

        Scenario: False step (a failed step due to an assertion framework exception)
          Given an assertion framework exception occurs

        Scenario Outline: Non-false step and false step
          Given <failure> occurs
          Examples:
            | failure                          |
            | an unexpected error              |
            | an assertion framework exception |
      """

  Scenario: Progress formatter with false steps
    When I run "behat -f 'Behat\CommonFormatters\ProgressWithFalseStepsFormatter' --no-time"
    Then it should fail with:
      """
      FSFS

      (::) failed steps (::)

      01. A runtime exception occured.
          In step `Given an unexpected error occurs'.                                  # features/steps/failures.php:2
          From scenario `Non-false step (a failed step due to an error not expected)'. # features/false_step.feature:3

      02. A runtime exception occured.
          In step `Given an unexpected error occurs'.                                  # features/steps/failures.php:2
          From scenario `Non-false step and false step'.                               # features/false_step.feature:9

      (::) false steps (::)

      01. Failed asserting that false is true.
          In step `Given an assertion framework exception occurs'.                            # features/steps/failures.php:6
          From scenario `False step (a failed step due to an assertion framework exception)'. # features/false_step.feature:6

      02. Failed asserting that false is true.
          In step `Given an assertion framework exception occurs'.                            # features/steps/failures.php:6
          From scenario `Non-false step and false step'.                                      # features/false_step.feature:9

      4 scenarios (4 failed [2 false])
      4 steps (4 failed [2 false])
      """

  Scenario: Pretty formatter with false steps
    When I run "behat -f 'Behat\CommonFormatters\PrettyWithFalseStepsFormatter' --no-time"
    Then it should fail with:
      """
      Feature: False step recognition

        Scenario: Non-false step (a failed step due to an error not expected) # features/false_step.feature:3
          Given an unexpected error occurs                                    # features/steps/failures.php:2
            A runtime exception occured.

        Scenario: False step (a failed step due to an assertion framework exception) # features/false_step.feature:6
          Given an assertion framework exception occurs                              # features/steps/failures.php:6
            Failed asserting that false is true.

        Scenario Outline: Non-false step and false step                              # features/false_step.feature:9
          Given <failure> occurs                                                     # features/steps/failures.php:2

          Examples:
            | failure                          |
            | an unexpected error              |
              A runtime exception occured.
            | an assertion framework exception |
              Failed asserting that false is true.

      4 scenarios (4 failed [2 false])
      4 steps (4 failed [2 false])
      """

  Scenario: HTML formatter with false steps
    When I run "behat -f 'Behat\CommonFormatters\HtmlWithFalseStepsFormatter' --no-time"
    Then the output should contain:
      """
      <div class="feature">
      <h2>
      <span class="keyword">Feature: </span>
      <span class="title">False step recognition</span>
      </h2>
      
      <div class="scenario">
      <h3>
      <span class="keyword">Scenario: </span>
      <span class="title">Non-false step (a failed step due to an error not expected)</span>
      <span class="path">features/false_step.feature:3</span>
      </h3>
      <ol>
      <li class="failed">
      <div class="step">
      <span class="keyword">Given </span>
      <span class="text">an unexpected error occurs</span>
      <span class="path">features/steps/failures.php:2</span>
      </div>
      <pre class="backtrace">A runtime exception occured.</pre>
      </li>
      </ol>
      </div>
      <div class="scenario">
      <h3>
      <span class="keyword">Scenario: </span>
      <span class="title">False step (a failed step due to an assertion framework exception)</span>
      <span class="path">features/false_step.feature:6</span>
      </h3>
      <ol>
      <li class="false">
      <div class="step">
      <span class="keyword">Given </span>
      <span class="text">an assertion framework exception occurs</span>
      <span class="path">features/steps/failures.php:6</span>
      </div>
      <pre class="backtrace">Failed asserting that false is true.</pre>
      </li>
      </ol>
      </div>
      <div class="scenario outline">
      <h3>
      <span class="keyword">Scenario Outline: </span>
      <span class="title">Non-false step and false step</span>
      <span class="path">features/false_step.feature:9</span>
      </h3>
      <ol>
      <li class="skipped">
      <div class="step">
      <span class="keyword">Given </span>
      <span class="text"><strong class="skipped_param">&lt;failure&gt;</strong> occurs</span>
      <span class="path">features/steps/failures.php:2</span>
      </div>
      </li>
      </ol>
      <div class="examples">
      <h4>Examples</h4>
      <table>
      <thead>
      <tr class="skipped">
      <td>failure</td>
      </tr>
      </thead>
      <tbody>
      <tr class="failed">
      <td>an unexpected error</td>
      </tr>
      <tr class="failed exception">
      <td colspan="1">
      <pre class="backtrace">A runtime exception occured.</pre>
      </td>
      </tr>
      <tr class="failed">
      <td>an assertion framework exception</td>
      </tr>
      <tr class="false exception">
      <td colspan="1">
      <pre class="backtrace">Failed asserting that false is true.</pre>
      </td>
      </tr>
      </tbody>
      </table>
      </div>
      </div>
      </div>
      <div class="summary failed">
      <div class="counters">
      <p class="scenarios">
      4 scenarios (<strong class="failed">4 failed</strong> <strong class="false">[2 false]</strong>)
      </p>
      <p class="steps">
      4 steps (<strong class="failed">4 failed</strong> <strong class="false">[2 false]</strong>)
      </p>
      </div>
      <div class="switchers">
          <a href="javascript:void(0)" id="behat_show_all">[+] all</a>
          <a href="javascript:void(0)" id="behat_hide_all">[-] all</a>
      </div>
      </div>
      """
