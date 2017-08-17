<?php
namespace Codeception\Module;

use Codeception\Module as CodeceptionModule;
use Codeception\Test\Descriptor;
use RemoteWebDriver;

/**
 * Class VisualCeption
 *
 * @copyright Copyright (c) 2014 G+J Digital Products GmbH
 * @license MIT license, http://www.opensource.org/licenses/mit-license.php
 * @package Codeception\Module
 *
 * @author Nils Langner <langner.nils@guj.de>
 * @author Torsten Franz
 * @author Sebastian Neubert
 * @author Ray Romanov
 */
class VisualCeption extends CodeceptionModule
{
    protected $config = [
        'maximumDeviation' => 0,
        'saveCurrentImageIfFailure' => true,
        'referenceImageDir' => 'VisualCeption/',
        'currentImageDir' => 'debug/visual/',
        'report' => false,
        'module' => 'WebDriver'
    ];
    
    protected $saveCurrentImageIfFailure;
    private $referenceImageDir;

    /**
     * This var represents the directory where the taken images are stored
     * @var string
     */
    private $currentImageDir;

    private $maximumDeviation = 0;

    /**
     * @var RemoteWebDriver
     */
    private $webDriver = null;

    /**
     * @var WebDriver
     */
    private $webDriverModule = null;

    private $failed = array();
    private $logFile;
    private $templateVars = array();
    private $templateFile;

    public function _initialize()
    {
        $this->maximumDeviation = $this->config["maximumDeviation"];
        $this->saveCurrentImageIfFailure = (boolean)$this->config["saveCurrentImageIfFailure"];
        $this->referenceImageDir = (file_exists($this->config["referenceImageDir"]) ? "" : codecept_data_dir()) . $this->config["referenceImageDir"];

        if (!is_dir($this->referenceImageDir)) {
            $this->debug("Creating directory: $this->referenceImageDir");
            @mkdir($this->referenceImageDir, 0777, true);
        }
        $this->currentImageDir = codecept_output_dir() . $this->config["currentImageDir"];
        $this->_initVisualReport();
    }

    public function _afterSuite()
    {
        if (!$this->config['report']) {
            return;
        }
        $failedTests = $this->failed;
        $vars = $this->templateVars;
        $referenceImageDir = $this->referenceImageDir;
        $i = 0;

        ob_start();
        include_once $this->templateFile;
        $reportContent = ob_get_contents();
        ob_clean();

        $this->debug("Trying to store file (".$this->logFile.")");
        file_put_contents($this->logFile, $reportContent);
    }


    public function _failed(\Codeception\TestInterface $test, $fail)
    {
        if ($fail instanceof ImageDeviationException) {
            $this->failed[$test->getSignature() . '.' . $fail->getIdentifier()] = $fail;
        }
    }


    /**
     * Event hook before a test starts
     *
     * @param \Codeception\TestInterface $test
     * @throws \Exception
     */
    public function _before(\Codeception\TestInterface $test)
    {
        if (!$this->hasModule($this->config['module'])) {
            throw new \Codeception\Exception\ConfigurationException("VisualCeption uses the WebDriver. Please ensure that this module is activated.");
        }
        if (!class_exists('Imagick')) {
            throw new \Codeception\Exception\ConfigurationException("VisualCeption requires ImageMagick PHP Extension but it was not installed");
        }

        $this->webDriverModule = $this->getModule($this->config['module']);
        $this->webDriver = $this->webDriverModule->webDriver;

        $this->test = $test;
    }
    
    /**
     * Get value of the private property $referenceImageDir
     *
     * @return string Path to reference image dir
     */
    public function getReferenceImageDir()
	{
		return $this->referenceImageDir;
	}

    /**
     * Compare the reference image with a current screenshot, identified by their indentifier name
     * and their element ID.
     *
     * @param string $identifier Identifies your test object
     * @param string $elementID DOM ID of the element, which should be screenshotted
     * @param string|array $excludeElements Element name or array of Element names, which should not appear in the screenshot
     * @param float $deviation 
     */
    public function seeVisualChanges($identifier, $elementID = null, $excludeElements = array(), $deviation = null)
    {
        $excludeElements = (array)$excludeElements;

        $deviation = (!$deviation && !is_numeric($deviation)) ? $this->maximumDeviation : (float)$deviation;

        $deviationResult = $this->getDeviation($identifier, $elementID, $excludeElements);

        if (is_null($deviationResult["deviationImage"])) {
            return;
        }

        if ($deviationResult["deviation"] <= $deviation) {
            $compareScreenshotPath = $this->getDeviationScreenshotPath($identifier);
            $deviationResult["deviationImage"]->writeImage($compareScreenshotPath);

            throw new ImageDeviationException("The deviation of the taken screenshot is too low (" . $deviationResult["deviation"] . "%).\nSee $compareScreenshotPath for a deviation screenshot.",
                $identifier,
                $this->getExpectedScreenshotPath($identifier),
                $this->getScreenshotPath($identifier),
                $compareScreenshotPath);
        }

        // used for assertion counter in codeception / phpunit
        $this->assertTrue(true);

    }

    /**
     * Compare the reference image with a current screenshot, identified by their indentifier name
     * and their element ID.
     *
     * @param string $identifier identifies your test object
     * @param string $elementID DOM ID of the element, which should be screenshotted
     * @param string|array $excludeElements string of Element name or array of Element names, which should not appear in the screenshot
     * @param float $deviation 
     */
    public function dontSeeVisualChanges($identifier, $elementID = null, $excludeElements = array(), $deviation = null)
    {
        $excludeElements = (array)$excludeElements;

        $deviation = (!$deviation && !is_numeric($deviation)) ? $this->maximumDeviation : (float)$deviation;

        $deviationResult = $this->getDeviation($identifier, $elementID, $excludeElements);

        if (is_null($deviationResult["deviationImage"])) {
            return;
        }


        if ($deviationResult["deviation"] > $deviation) {
            $compareScreenshotPath = $this->getDeviationScreenshotPath($identifier);
            $deviationResult["deviationImage"]->writeImage($compareScreenshotPath);

            throw new ImageDeviationException("The deviation of the taken screenshot is too hight (" . $deviationResult["deviation"] . "%).\nSee $compareScreenshotPath for a deviation screenshot.",
                $identifier,
                $this->getExpectedScreenshotPath($identifier),
                $this->getScreenshotPath($identifier),
                $compareScreenshotPath);
        }
        // used for assertion counter in codeception / phpunit
        $this->assertTrue(true);
    }

    /**
     * Hide an element to set the visibility to hidden
     *
     * @param $elementSelector String of CSS Element selector, set visibility to hidden
     */
    private function hideElement($elementSelector)
    {
        $this->setVisibility($elementSelector, false);
    }

    /**
     * Show an element to set the visibility to visible
     *
     * @param $elementSelector String of CSS Element selector, set visibility to visible
     */
    private function showElement($elementSelector)
    {
        $this->setVisibility($elementSelector, true);
    }

    private function setVisibility($elementSelector, $isVisible)
    {
      $styleVisibility = $isVisible ? 'visible' : 'hidden';
      $this->webDriver->executeScript('
            var elements = [];
            elements = document.querySelectorAll("' . $elementSelector . '");
            if( elements.length > 0 ) {
                for (var i = 0; i < elements.length; i++) {
                    elements[i].style.visibility = "' . $styleVisibility . '";
                }
            }
        ');
        $this->debug("set visibility of element '$elementSelector' to '$styleVisibility'");
    }

    /**
     * Compares the two images and calculate the deviation between expected and actual image
     *
     * @param string $identifier Identifies your test object
     * @param string $elementID DOM ID of the element, which should be screenshotted
     * @param array $excludeElements Element names, which should not appear in the screenshot
     * @return array Includes the calculation of deviation in percent and the diff-image
     */
    private function getDeviation($identifier, $elementID, array $excludeElements = array())
    {
        $coords = $this->getCoordinates($elementID);
        $this->createScreenshot($identifier, $coords, $excludeElements);

        $compareResult = $this->compare($identifier);

        $deviation = $compareResult[1] * 100;

        $this->debug("The deviation between the images is ". $deviation . " percent");

        return array (
            "deviation" => $deviation,
            "deviationImage" => $compareResult[0],
            "currentImage" => $compareResult['currentImage'],
        );
    }

    /**
     * Initialize the module and read the config.
     * Throws a runtime exception, if the
     * reference image dir is not set in the config
     *
     * @throws \RuntimeException
     */

    /**
     * Find the position and proportion of a DOM element, specified by it's ID.
     * Used native JavaScript.
     * @param string $elementId DOM ID of the element, which should be screenshotted
     * @return array coordinates of the element
     */
    private function getCoordinates($elementId)
    {
        if (is_null($elementId)) {
            $elementId = 'body';
        }

        $imageCoords = array();

        $elementExists = (bool)$this->webDriver->executeScript('return document.querySelectorAll( "' . $elementId . '" ).length > 0;');

        if (!$elementExists) {
            throw new \Exception("The element you want to examine ('" . $elementId . "') was not found.");
        }

        $coords = $this->webDriver->executeScript('return document.querySelector( "' . $elementId . '" ).getBoundingClientRect();');

        $imageCoords['offset_x'] = $coords['left'];
        $imageCoords['offset_y'] = $coords['top'];
        $imageCoords['width'] = $coords['width'];
        $imageCoords['height'] = $coords['height'];

        return $imageCoords;
    }

    /**
     * Generates a screenshot image filename
     * it uses the testcase name and the given indentifier to generate a png image name
     *
     * @param string $identifier identifies your test object
     * @return string Name of the image file
     */
    private function getScreenshotName($identifier)
    {
        $signature = $this->test->getSignature();
        return str_replace(':',  '_', $signature). '.' . $identifier . '.png';
    }

    /**
     * Returns the temporary path including the filename where a the screenshot should be saved
     * If the path doesn't exist, the method generate it itself
     *
     * @param string $identifier identifies your test object
     * @return string Path an name of the image file
     * @throws \RuntimeException if debug dir could not create
     */
    private function getScreenshotPath($identifier)
    {
        $debugDir = $this->currentImageDir;
        if (!is_dir($debugDir)) {
            $created = @mkdir($debugDir, 0777, true);
            if ($created) {
                $this->debug("Creating directory: $debugDir");
            } else {
                throw new \RuntimeException("Unable to create temporary screenshot dir ($debugDir)");
            }
        }
        return $debugDir . $this->getScreenshotName($identifier);
    }

    /**
     * Returns the reference image path including the filename
     *
     * @param string $identifier identifies your test object
     * @return string Name of the reference image file
     */
    private function getExpectedScreenshotPath($identifier)
    {
        return $this->referenceImageDir . $this->getScreenshotName($identifier);
    }

    /**
     * Generate the screenshot of the dom element
     *
     * @param string $identifier identifies your test object
     * @param array $coords Coordinates where the DOM element is located
     * @param array $excludeElements List of elements, which should not appear in the screenshot
     * @return string Path of the current screenshot image
     */
    private function createScreenshot($identifier, array $coords, array $excludeElements = array())
    {
        $screenShotDir = \Codeception\Configuration::logDir() . 'debug/';

        if( !is_dir($screenShotDir)) {
            mkdir($screenShotDir, 0777, true);
        }

        $elementPath = $this->getScreenshotPath($identifier);

        $this->hideElementsForScreenshot($excludeElements);
        $screenshotBinary = $this->webDriver->takeScreenshot();
        $this->resetHideElementsForScreenshot($excludeElements);

        $screenShotImage = new \Imagick();
        $screenShotImage->readimageblob($screenshotBinary);
        $screenShotImage->cropImage($coords['width'], $coords['height'], $coords['offset_x'], $coords['offset_y']);
        $screenShotImage->writeImage($elementPath);

        return $elementPath;
    }

    /**
     * Hide the given elements with CSS visibility = hidden. Wait a second after hiding
     *
     * @param array $excludeElements Array of strings, which should be not visible
     */
    private function hideElementsForScreenshot(array $excludeElements)
    {
        foreach ($excludeElements as $element) {
            $this->hideElement($element);
        }
        if (!empty($excludeElements)) {
            $this->webDriverModule->waitForElementNotVisible(array_pop($excludeElements));
        }
    }

    /**
     * Reset hiding the given elements with CSS visibility = visible. Wait a second after reset hiding
     *
     * @param array $excludeElements array of strings, which should be visible again
     */
    private function resetHideElementsForScreenshot(array $excludeElements)
    {
        foreach ($excludeElements as $element) {
            $this->showElement($element);
        }
        if (!empty($excludeElements)) {
            $this->webDriverModule->waitForElementVisible(array_pop($excludeElements));
        }
    }

    /**
     * Returns the image path including the filename of a deviation image
     *
     * @param $identifier identifies your test object
     * @return string Path of the deviation image
     */
    private function getDeviationScreenshotPath ($identifier, $alternativePrefix = '')
    {
        $debugDir = \Codeception\Configuration::logDir() . 'debug/';
        $prefix = ( $alternativePrefix === '') ? 'compare' : $alternativePrefix;
        return $debugDir . $prefix . $this->getScreenshotName($identifier);
    }


    /**
     * Compare two images by its identifiers.
     * If the reference image doesn't exists
     * the image is copied to the reference path.
     *
     * @param $identifier identifies your test object
     * @return array Test result of image comparison
     */
    private function compare($identifier)
    {
        $expectedImagePath = $this->getExpectedScreenshotPath($identifier);
        $currentImagePath = $this->getScreenshotPath($identifier);

        if (!file_exists($expectedImagePath)) {
            $this->debug("Copying image (from $currentImagePath to $expectedImagePath");
            copy($currentImagePath, $expectedImagePath);
            return array (null, 0, 'currentImage' => null);
        } else {
            return $this->compareImages($expectedImagePath, $currentImagePath);
        }
    }

    /**
     * Compares to images by given file path
     *
     * @param $image1 Path to the exprected reference image
     * @param $image2 Path to the current image in the screenshot
     * @return array Result of the comparison
     */
    private function compareImages($image1, $image2)
    {
        $this->debug("Trying to compare $image1 with $image2");

        $imagick1 = new \Imagick($image1);
        $imagick2 = new \Imagick($image2);

        $imagick1Size = $imagick1->getImageGeometry();
        $imagick2Size = $imagick2->getImageGeometry();

        $maxWidth = max($imagick1Size['width'], $imagick2Size['width']);
        $maxHeight = max($imagick1Size['height'], $imagick2Size['height']);

        $imagick1->extentImage($maxWidth, $maxHeight, 0, 0);
        $imagick2->extentImage($maxWidth, $maxHeight, 0, 0);

        try {
            $result = $imagick1->compareImages($imagick2, \Imagick::METRIC_MEANSQUAREERROR);
            $result[0]->setImageFormat('png');
            $result['currentImage'] = clone $imagick2;
            $result['currentImage']->setImageFormat('png');
        }
        catch (\ImagickException $e) {
            $this->debug("IMagickException! could not campare image1 ($image1) and image2 ($image2).\nExceptionMessage: " . $e->getMessage());
            $this->fail($e->getMessage() . ", image1 $image1 and image2 $image2.");
        }
        return $result;
    }

    protected function _initVisualReport()
    {
        if (!$this->config['report']) {
            return;
        }
        $this->logFile = \Codeception\Configuration::logDir() . 'vcresult.html';

        if (array_key_exists('templateVars', $this->config)) {
            $this->templateVars = $this->config["templateVars"];
        }

        if (array_key_exists('templateFile', $this->config)) {
            $this->templateFile = (file_exists($this->config["templateFile"]) ? "" : __DIR__ ) . $this->config["templateFile"];
        } else {
            $this->templateFile = __DIR__ . "/report/template.php";
        }
        $this->debug( "VisualCeptionReporter: templateFile = " . $this->templateFile );
    }
}