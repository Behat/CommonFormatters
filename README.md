Commonly used formatters for Behat
==================================

Formatters that distinguish between an error and a failure in terms of xUnit's notion
-------------------------------------------------------------------------------------

See https://github.com/Behat/Behat/issues/111

* A failed step is the equivalent of an error in xUnit.
* A false step is the equivalent of a failure in xUnit (colored magenta by the formatters).
* Actually a false step is a failed step too, it is just a fine-grained interpretation of a failed step (complementarily there can be non-false steps among failed steps).

### ProgressWithFalseStepsFormatter

https://github.com/Behat/CommonFormatters/blob/master/src/Behat/CommonFormatters/ProgressWithFalseStepsFormatter.php

![ProgressWithFalseStepsFormatter](https://github.com/Behat/CommonFormatters/raw/master/doc/progress_with_false_steps_formatter.png "ProgressWithFalseStepsFormatter")

### PrettyWithFalseStepsFormatter

https://github.com/Behat/CommonFormatters/blob/master/src/Behat/CommonFormatters/PrettyWithFalseStepsFormatter.php

![PrettyWithFalseStepsFormatter](https://github.com/Behat/CommonFormatters/raw/master/doc/pretty_with_false_steps_formatter.png "PrettyWithFalseStepsFormatter")

### HtmlWithFalseStepsFormatter

https://github.com/Behat/CommonFormatters/blob/master/src/Behat/CommonFormatters/HtmlWithFalseStepsFormatter.php

![HtmlWithFalseStepsFormatter](https://github.com/Behat/CommonFormatters/raw/master/doc/html_with_false_steps_formatter.png "HtmlWithFalseStepsFormatter")

Formatter that adds a new line to a CSV file for each suite run
---------------------------------------------------------------

### CsvStatisticsFormatter

https://github.com/Behat/CommonFormatters/blob/master/src/Behat/CommonFormatters/CsvStatisticsFormatter.php

    execution date,total execution time,number of features,number of features with failures,number of scenarios,number of scenarios with failures,number of steps,number of failed steps
    Fri 30 Mar 2012 11:36:57,2m44.987s,2,1,39,2,155,2
