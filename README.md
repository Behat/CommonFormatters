Commonly used formatters for Behat
==================================

Formatter that creates output with links to the source containing the step definitions
--------------------------------------------------------------------------------------

### HtmlLinkedFormatter

Parameter `remote_base_url` specifies the URL of the repository the paths to the context classes are relative to.

https://github.com/headrevision/CommonFormatters/blob/master/Behat/CommonFormatters/HtmlLinkedFormatter.php

![HtmlLinkedFormatter](https://github.com/headrevision/CommonFormatters/raw/master/doc/html_linked_formatter.png "HtmlLinkedFormatter")

Formatters that distinguish between an error and a failure in terms of xUnit's notion
-------------------------------------------------------------------------------------

See https://github.com/Behat/Behat/issues/111

* A failed step is the equivalent of an error in xUnit.
* A false step is the equivalent of a failure in xUnit (colored magenta by the formatters).
* Actually a false step is a failed step too, it is just a fine-grained interpretation of a failed step (complementarily there can be non-false steps among failed steps).

### ProgressWithFalseStepsFormatter

https://github.com/headrevision/CommonFormatters/blob/master/Behat/CommonFormatters/ProgressWithFalseStepsFormatter.php

![ProgressWithFalseStepsFormatter](https://github.com/headrevision/CommonFormatters/raw/master/doc/progress_with_false_steps_formatter.png "ProgressWithFalseStepsFormatter")

### PrettyWithFalseStepsFormatter

https://github.com/headrevision/CommonFormatters/blob/master/Behat/CommonFormatters/PrettyWithFalseStepsFormatter.php

![PrettyWithFalseStepsFormatter](https://github.com/headrevision/CommonFormatters/raw/master/doc/pretty_with_false_steps_formatter.png "PrettyWithFalseStepsFormatter")

### HtmlWithFalseStepsFormatter

https://github.com/headrevision/CommonFormatters/blob/master/Behat/CommonFormatters/HtmlWithFalseStepsFormatter.php

![HtmlWithFalseStepsFormatter](https://github.com/headrevision/CommonFormatters/raw/master/doc/html_with_false_steps_formatter.png "HtmlWithFalseStepsFormatter")

Formatter that adds a new line to a CSV file for each suite run
---------------------------------------------------------------

### CsvStatisticsFormatter

https://github.com/headrevision/CommonFormatters/blob/master/Behat/CommonFormatters/CsvStatisticsFormatter.php

    execution date,total execution time,number of features,number of features with failures,number of scenarios,number of scenarios with failures,number of steps,number of failed steps
    Fri 30 Mar 2012 11:36:57,2m44.987s,2,1,39,2,155,2
