<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Bach configuration for atoum.
 *
 * To launch the code coverage, please make sure you have pecl-xdebug installed,
 * and run:
 * $ php app/console atoum
 *
 * PHP version 5
 *
 * @category  Main
 * @package   Tests
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2013 Anaphore
 * @license   Unknown http://unknown.com
 * @link      http://anaphore.eu
 */

use \mageekguy\atoum;

$tests_dir = __DIR__ . '/tests-results/';
$coverage_dir = $tests_dir . 'code-coverage/';

if ( !file_exists($tests_dir) ) {
    mkdir($tests_dir);
    mkdir($tests_dir . 'code-coverage');
}

$coverageField = new atoum\report\fields\runner\coverage\html(
    'Bach',
    $coverage_dir
);
$coverageField->setRootUrl('file://' . realpath($coverage_dir));

$xunitWriter = new atoum\writers\file($tests_dir . '/atoum.xunit.xml');
$cloverWriter = new atoum\writers\file($tests_dir . '/clover.xml');

$coverageField->addSrcDirectory(
    __DIR__.'/src/Bach/HomeBundle/',
    function ($file, $key, $iterator) {
        // Pour continuer à descendre dans l'arborescence
        if ($file->isDir()) {
            return true;
        }

        if ($file->getExtension() === 'php') {
            return preg_match(
                "/(Extension|Bundle|CompilerPass|Configuration|Controller|Command|FeatureContext|Admin|Exception|ControllerTest)\.php/",
                $file->getFilename()
            ) < 1;
        }

        return false;
    }
);

$xunitReport = new atoum\reports\asynchronous\xunit();
$xunitReport->addWriter($xunitWriter);

$clover = new atoum\reports\asynchronous\clover();
$clover->addWriter($cloverWriter);

$runner->addReport($xunitReport);
$runner->addReport($clover);
$script
    ->addDefaultReport()
    ->addField($coverageField);

$script
    ->noCodeCoverageForNamespaces('Symfony')
    ->noCodeCoverageForNamespaces('Fp')
    ->noCodeCoverageForClasses('Twig_Extension');
