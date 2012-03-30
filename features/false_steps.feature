Feature: Formatters with failed steps
  In order to debug features
  As a feature writer
  I need to have formatters with failed steps

  Background:
    Given a file named "behat.yml" with:
      """
      default:
        paths:
          features:               %%BEHAT_CONFIG_PATH%%/features
          bootstrap:              %%BEHAT_CONFIG_PATH%%/features/bootstrap
        formatter:
          name:                    'pretty_false'
          parameters:
            decorated:           true
            verbose:             false
            time:                true
            language:            'en'
            output_path:         null
            multiline_arguments: true
          classes:
            progress_false:      'Behat\CommonFormatters\ProgressWithFalseStepsFormatter',
            pretty_false:        'Behat\CommonFormatters\PrettyWithFalseStepsFormatter',
            html_false:          'Behat\CommonFormatters\HtmlWithFalseStepsFormatter'

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
    When I run "behat -c behat.yml -f progress_false --no-time"
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

      01. Failed asserting that <boolean:false> is true.
          In step `Given an assertion framework exception occurs'.                            # features/steps/failures.php:6
          From scenario `False step (a failed step due to an assertion framework exception)'. # features/false_step.feature:6

      02. Failed asserting that <boolean:false> is true.
          In step `Given an assertion framework exception occurs'.                            # features/steps/failures.php:6
          From scenario `Non-false step and false step'.                                      # features/false_step.feature:9

      4 scenarios (4 failed [2 false])
      4 steps (4 failed [2 false])
      """

  Scenario: Pretty formatter with false steps
    When I run "behat -c behat.yml -f pretty_false --no-time"
    Then it should fail with:
      """
      Feature: False step recognition

        Scenario: Non-false step (a failed step due to an error not expected) # features/false_step.feature:3
          Given an unexpected error occurs                                    # features/steps/failures.php:2
            A runtime exception occured.

        Scenario: False step (a failed step due to an assertion framework exception) # features/false_step.feature:6
          Given an assertion framework exception occurs                              # features/steps/failures.php:6
            Failed asserting that <boolean:false> is true.

        Scenario Outline: Non-false step and false step                              # features/false_step.feature:9
          Given <failure> occurs                                                     # features/steps/failures.php:2

          Examples:
            | failure                          |
            | an unexpected error              |
              A runtime exception occured.
            | an assertion framework exception |
              Failed asserting that <boolean:false> is true.

      4 scenarios (4 failed [2 false])
      4 steps (4 failed [2 false])
      """

  Scenario: HTML formatter with false steps
    When I run "behat -c behat.yml -f html_false --no-time"
    Then it should fail with:
      """
      <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns ="http://www.w3.org/1999/xhtml">
      <head>
          <meta content="text/html;charset=utf-8"/>
          <title>Behat Test Suite</title>
          <style type="text/css">
              body {
                  margin:0px;
                  padding:0px;
                  position:relative;
                  padding-top:75px;
              }
              #behat {
                  float:left;
                  font-family: Georgia, serif;
                  font-size:18px;
                  line-height:26px;
                  width:100%;
              }
              #behat .statistics {
                  float:left;
                  width:100%;
                  margin-bottom:15px;
              }
              #behat .statistics p {
                  text-align:right;
                  padding:5px 15px;
                  margin:0px;
                  border-right:10px solid #000;
              }
              #behat .statistics.failed p {
                  border-color:#C20000;
              }
              #behat .statistics.passed p {
                  border-color:#3D7700;
              }
              #behat .feature {
                  margin:15px;
              }
              #behat h2, #behat h3, #behat h4 {
                  margin:0px 0px 5px 0px;
                  padding:0px;
                  font-family:Georgia;
              }
              #behat h2 .title, #behat h3 .title, #behat h4 .title {
                  font-weight:normal;
              }
              #behat .path {
                  font-size:10px;
                  font-weight:normal;
                  font-family: 'Bitstream Vera Sans Mono', 'DejaVu Sans Mono', Monaco, Courier, monospace !important;
                  color:#999;
                  padding:0px 5px;
                  float:right;
              }
              #behat h3 .path {
                  margin-right:4%;
              }
              #behat ul.tags {
                  font-size:14px;
                  font-weight:bold;
                  color:#246AC1;
                  list-style:none;
                  margin:0px;
                  padding:0px;
              }
              #behat ul.tags li {
                  display:inline;
              }
              #behat ul.tags li:after {
                  content:' ';
              }
              #behat ul.tags li:last-child:after {
                  content:'';
              }
              #behat .feature > p {
                  margin-top:0px;
                  margin-left:20px;
              }
              #behat .scenario {
                  margin-left:20px;
                  margin-bottom:20px;
              }
              #behat .scenario > ol {
                  margin:0px;
                  list-style:none;
                  margin-left:20px;
                  padding:0px;
              }
              #behat .scenario > ol:after {
                  content:'';
                  display:block;
                  clear:both;
              }
              #behat .scenario > ol li {
                  float:left;
                  width:95%;
                  padding-left:5px;
                  border-left:5px solid;
                  margin-bottom:4px;
              }
              #behat .scenario > ol li .argument {
                  margin:10px 20px;
                  font-size:16px;
                  overflow:hidden;
              }
              #behat .scenario > ol li table.argument {
                  border:1px solid #d2d2d2;
              }
              #behat .scenario > ol li table.argument thead td {
                  font-weight: bold;
              }
              #behat .scenario > ol li table.argument td {
                  padding:5px 10px;
                  background:#f3f3f3;
              }
              #behat .scenario > ol li .keyword {
                  font-weight:bold;
              }
              #behat .scenario > ol li .path {
                  float:right;
              }
              #behat .scenario .examples {
                  margin-top:20px;
                  margin-left:40px;
              }
              #behat .scenario .examples table {
                  margin-left:20px;
              }
              #behat .scenario .examples table thead td {
                  font-weight:bold;
                  text-align:center;
              }
              #behat .scenario .examples table td {
                  padding:2px 10px;
                  font-size:16px;
              }
              #behat .scenario .examples table .exception td {
                  border-left:5px solid #000;
                  padding-left:0px;
              }
              #behat .scenario .examples table .failed.exception td {
                  border-color:#C20000 !important;
              }
              #behat .scenario .examples table .false.exception td {
                  border-color:#c200ee !important;
              }
              pre {
                  font-family:monospace;
              }
              .snippet {
                  font-size:14px;
                  color:#000;
                  margin-left:20px;
              }
              .backtrace {
                  font-size:12px;
                  line-height:18px;
                  color:#000;
                  overflow:hidden;
                  margin-left:20px;
                  padding:15px;
                  background: #fff;
                  margin-right:15px;
              }
              .failed .backtrace {
                  border-left:2px solid #C20000;
              }
              .false .backtrace {
                  border-left:2px solid #c200ee;
              }
              #behat .passed {
                  background:#DBFFB4;
                  border-color:#65C400 !important;
                  color:#3D7700;
              }
              #behat .failed {
                  background:#FFFBD3;
                  border-color:#C20000 !important;
                  color:#C20000;
              }
              #behat .false {
                  background:#ead9ee;
                  border-color:#c200ee !important;
                  color:#c200ee;
              }
              #behat .undefined, #behat .pending {
                  border-color:#FAF834 !important;
                  background:#FCFB98;
                  color:#000;
              }
              #behat .skipped {
                  background:lightCyan;
                  border-color:cyan !important;
                  color:#000;
              }
              #behat .summary {
                  position: absolute;
                  top: 0px;
                  left: 0px;
                  width:100%;
                  font-family: Arial, sans-serif;
                  font-size: 14px;
                  line-height: 18px;
              }
              #behat .summary .counters {
                  padding: 10px;
                  border-top: 0px;
                  border-bottom: 0px;
                  border-right: 0px;
                  border-left: 5px;
                  border-style: solid;
                  height: 52px;
                  overflow: hidden;
              }
              #behat .summary .switchers {
                  position: absolute;
                  right: 15px;
                  top: 25px;
              }
              #behat .summary .switcher {
                  text-decoration: underline;
                  cursor: pointer;
              }
              #behat .summary .switchers a {
                  margin-left: 10px;
                  color: #000;
              }
              #behat .summary .switchers a:hover {
                  text-decoration:none;
              }
              #behat .summary p {
                  margin:0px;
              }
              #behat .jq-toggle > .scenario,
              #behat .jq-toggle > ol {
                  display:none;
              }
              #behat .jq-toggle-opened > .scenario,
              #behat .jq-toggle-opened > ol {
                  display:block;
              }
              #behat .jq-toggle > h2,
              #behat .jq-toggle > h3 {
                  cursor:pointer;
              }
              #behat .jq-toggle > h2:after,
              #behat .jq-toggle > h3:after {
                  content:' |+';
                  font-weight:bold;
              }
              #behat .jq-toggle-opened > h2:after,
              #behat .jq-toggle-opened > h3:after {
                  content:' |-';
                  font-weight:bold;
              }
          </style>
      
          <style type="text/css" media="print">
              body {
                  padding:0px;
              }
      
              #behat {
                  font-size:11px;
              }
      
              #behat .jq-toggle > .scenario,
              #behat .jq-toggle > ol {
                  display:block;
              }
      
              #behat .summary {
                  position:relative;
              }
      
              #behat .summary .counters {
                  border:none;
              }
      
              #behat .summary .switchers {
                  display:none;
              }
      
              #behat .step .path {
                  display:none;
              }
      
              #behat .jq-toggle > h2:after,
              #behat .jq-toggle > h3:after {
                  content:'';
                  font-weight:bold;
              }
      
              #behat .jq-toggle-opened > h2:after,
              #behat .jq-toggle-opened > h3:after {
                  content:'';
                  font-weight:bold;
              }
      
              #behat .scenario > ol li {
                  border-left:none;
              }
          </style>
      </head>
      <body>
          <div id="behat">
      
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
      <pre class="backtrace">Failed asserting that &lt;boolean:false&gt; is true.</pre>
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
      <span class="text"><strong class="skipped_param"><failure></strong> occurs</span>
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
      <pre class="backtrace">Failed asserting that &lt;boolean:false&gt; is true.</pre>
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
      
          </div>
          <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.min.js"></script>
          <script type="text/javascript">
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
                          var $scenario = $('.feature .scenario:not(:has(li.failed, li.false, li.pending))');
                          var $feature  = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
      
                  $('#behat .summary .counters .steps .passed')
                      .addClass('switcher')
                      .click(function(){
                          var $scenario = $('.feature .scenario:has(li.passed)');
                          var $feature  = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
      
                  $('#behat .summary .counters .failed')
                      .addClass('switcher')
                      .click(function(){
                          var $scenario = $('.feature .scenario:has(li.failed, li.false)');
                          var $feature = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
      
                  $('#behat .summary .counters .false')
                      .addClass('switcher')
                      .click(function(){
                          var $scenario = $('.feature .scenario:has(li.false)');
                          var $feature = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
      
                  $('#behat .summary .counters .skipped')
                      .addClass('switcher')
                      .click(function(){
                          var $scenario = $('.feature .scenario:has(li.skipped)');
                          var $feature = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
      
                  $('#behat .summary .counters .pending')
                      .addClass('switcher')
                      .click(function(){
                          var $scenario = $('.feature .scenario:has(li.pending)');
                          var $feature = $scenario.parent();
      
                          $('#behat_hide_all').click();
      
                          $scenario.addClass('jq-toggle-opened');
                          $feature.addClass('jq-toggle-opened');
                      });
              });
          </script>
      </body>
      </html>
      """
