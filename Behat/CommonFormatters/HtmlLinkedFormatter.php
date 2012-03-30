<?php

namespace Behat\CommonFormatters;

use \ReflectionMethod;

use Behat\Behat\Formatter\HtmlFormatter,
    Behat\Behat\Definition\DefinitionInterface;

use Behat\Gherkin\Node\StepNode;

/**
 * HTML formatter with links to the sources containing the step definitions.
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class HtmlLinkedFormatter extends HtmlFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function printStepDefinitionPath(StepNode $step, DefinitionInterface $definition)
    {
        if ($this->getParameter('paths')) {
            $this->printPathLink($definition);
        }
    }

    /**
     * Prints path link, which links to the source containing the step definition.
     *
     * @param DefinitionInterface $definition
     */
    protected function printPathLink(DefinitionInterface $definition)
    {
        $path = $this->relativizePathsInString($definition->getPath());
        if ($this->hasParameter('remote_base_url')) {
            $url = $this->getParameter('remote_base_url') 
                . $this->relativizePathsInString($definition->getCallbackReflection()->getFileName());
            $this->writeln('<span class="path"><a href="' . $url . '">' . $path . '</a></span>');
        } else {
            $this->printPathComment($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getHtmlTemplate()
    {
        $templatePath = $this->parameters->get('template_path')
                     ?: $this->parameters->get('support_path') . DIRECTORY_SEPARATOR . 'html.tpl';

        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }

        return <<<'HTMLTPL'
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
        #behat .path a:link,
        #behat .path a:visited {
            color:#999;
        }
        #behat .path a:hover,
        #behat .path a:active {
            background-color:#000;
            color:#fff;
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
        #behat .scenario .examples table .failed.exception td {
            border-left:5px solid #000;
            border-color:#C20000 !important;
            padding-left:0px;
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
            border-left:2px solid #C20000;
            background: #fff;
            margin-right:15px;
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
        {{content}}
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
                    var $scenario = $('.feature .scenario:not(:has(li.failed, li.pending))');
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
                    var $scenario = $('.feature .scenario:has(li.failed)');
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
HTMLTPL;
    }
}
