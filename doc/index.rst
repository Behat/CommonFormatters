CommonFormatters
================

An aggregation of extra formatters for Behat supposed to be used commonly.

Installation
------------

This extension requires:

* Behat 2.4+

Through Composer
~~~~~~~~~~~~~~~~

The easiest way to keep your suite updated is to use `Composer <http://getcomposer.org>`_:

1. Define dependencies in your ``composer.json``:

    Behat 2.x:

    .. code-block:: js

        {
            ...

            "require": {
                ...

                "behat/common-formatters":         "1.2.*",
                "webignition/json-pretty-print":   "@dev",
                "hasbridge/json-schema-validator": "@dev"
            }
        }

2. Install/update your vendors:

    .. code-block:: bash

        $ curl http://getcomposer.org/installer | php
        $ php composer.phar install

3. Activate extension by specifying its class in your ``behat.yml``:

    .. code-block:: yaml

        # behat.yml
        default:
          # ...
          extensions:
            Behat\CommonFormatters\Extension:
              formatters:
                progress_false: 'Behat\CommonFormatters\ProgressWithFalseStepsFormatter'
                pretty_false: 'Behat\CommonFormatters\PrettyWithFalseStepsFormatter'
                html_false: 'Behat\CommonFormatters\HtmlWithFalseStepsFormatter'
                csv_statistics: 'Behat\CommonFormatters\CsvStatisticsFormatter'
                json: 'Behat\CommonFormatters\JsonFormatter'

Usage
-----

See `Behat/CommonFormatters - Github <https://github.com/Behat/CommonFormatters/blob/master/README.md>`_.
