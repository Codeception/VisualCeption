<?php
declare(strict_types=1);

namespace Codeception\Module;

use Codeception\Lib\Interfaces\MultiSession;
use Codeception\Configuration;
use Codeception\Exception\ConfigurationException;
use Codeception\Exception\ImageDeviationException;
use Codeception\Module as CodeceptionModule;
use Codeception\Test\Descriptor;
use Codeception\TestInterface;
use Facebook\WebDriver\Remote\RemoteWebDriver;

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
class VisualCeption extends CodeceptionModule implements MultiSession
{

    protected array $config = [
        'maximumDeviation' => 0,
        'saveCurrentImageIfFailure' => true,
        'referenceImageDir' => 'VisualCeption/',
        'currentImageDir' => 'debug/visual/',
        'report' => false,
        'module' => 'WebDriver',
        'fullScreenShot' => false,
        'forceFullScreenShot' => false,
    ];

    /**
     * @var bool
     */
    protected bool $saveCurrentImageIfFailure;

    /**
     * @var string
     */
    private string $referenceImageDir;

    /**
     * This var represents the directory where the taken images are stored
     * @var string
     */
    private string $currentImageDir;

    /**
     * @var float
     */
    private float $maximumDeviation = 0.0;

    /**
     * @var ?RemoteWebDriver
     */
    private ?RemoteWebDriver $webDriver = null;

    /**
     * @var ?\Codeception\Module\WebDriver
     */
    private ?WebDriver $webDriverModule = null;

    /**
     * @var Utils
     */
    private Utils $utils;

    /**
     * @var TestInterface
     */
    private TestInterface $test;

    /**
     * @var ?string
     */
    private ?string $currentEnvironment;

    /**
     * @var array
     */
    private array $failed = [];

    /**
     * @var string
     */
    private string $logFile;

    /**
     * @var array
     */
    private array $templateVars = [];

    /**
     * @var string
     */
    private string $templateFile;

    /**
     * @return void
     */
    public function _initialize(): void
    {
        $this->utils = new Utils();

        $this->maximumDeviation = $this->config["maximumDeviation"];
        $this->saveCurrentImageIfFailure = (boolean)$this->config["saveCurrentImageIfFailure"];
        $this->referenceImageDir = (file_exists($this->config["referenceImageDir"]) ? "" : codecept_data_dir()) . $this->config["referenceImageDir"];

        if (!is_dir($this->referenceImageDir)) {
            $this->debug("Creating directory: $this->referenceImageDir");
            if (!mkdir($concurrentDirectory = $this->referenceImageDir, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        $this->currentImageDir = codecept_output_dir() . $this->config["currentImageDir"];
    }

    public function _beforeSuite($settings = []): void
    {
        $this->currentEnvironment = $settings['current_environment'] ?? null;

        $this->_initVisualReport();
    }

    public function _afterSuite(): void
    {
        if (!$this->config['report']) {
            return;
        }
        $failedTests = $this->failed;
        $vars = $this->templateVars;
        $referenceImageDir = $this->referenceImageDir;
        $i = 0;

        ob_start();
        include $this->templateFile;
        $reportContent = ob_get_contents();
        ob_end_clean();

        $this->debug("Trying to store file (" . $this->logFile . ")");
        file_put_contents($this->logFile, $reportContent);
    }


    /**
     * @throws \JsonException
     */
    public function _failed(TestInterface $test, $fail): void
    {
        if ($fail instanceof ImageDeviationException) {
            $this->failed[Descriptor::getTestSignatureUnique($test) . '.' . $fail->getIdentifier()] = $fail;
        }
    }


    /**
     * Event hook before a test starts
     *
     * @param TestInterface $test
     * @throws \Exception
     */
    public function _before(TestInterface $test): void
    {
        $browserModule = $this->getBrowserModule();

        if ($browserModule === null) {
            throw new ConfigurationException("VisualCeption uses the WebDriver. Please ensure that this module is activated.");
        }
        if (!class_exists('Imagick')) {
            throw new ConfigurationException("VisualCeption requires ImageMagick PHP Extension but it was not installed");
        }

        $this->webDriverModule = $browserModule;
        $this->webDriver = $this->webDriverModule->webDriver;

        $this->test = $test;
    }

    /**
     * @return \Codeception\Module|\Facebook\WebDriver\WebDriver|null
     * @throws \Codeception\Exception\ModuleException
     */
    protected function getBrowserModule(): CodeceptionModule|WebDriver|null
    {
        if ($this->hasModule($this->config['module'])) {
            return $this->getModule($this->config['module']);
        }

        foreach ($this->getModules() as $module) {
            if ($module === $this) {
                continue;
            }
            if ($module instanceof WebDriver) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Get value of the private property $referenceImageDir
     *
     * @return string Path to reference image dir
     */
    public function getReferenceImageDir(): string
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
    public function seeVisualChanges($identifier, $elementID = null, $excludeElements = [], $deviation = null): void
    {
        $this->compareVisualChanges($identifier, $elementID, $excludeElements, $deviation, true);

        // used for assertion counter in codeception / phpunit
        $this->assertTrue(true);

    }

    /**
     * Compare the reference image with a current screenshot, identified by their indentifier name
     * and their element ID.
     *
     * @param string $identifier identifies your test object
     * @param string|null $elementID DOM ID of the element, which should be screenshotted
     * @param string|array $excludeElements string of Element name or array of Element names, which should not appear in the screenshot
     * @param float|null $deviation
     */
    public function dontSeeVisualChanges(string $identifier, string $elementID = null, array|string $excludeElements = [], float $deviation = null): void
    {
        $this->compareVisualChanges($identifier, $elementID, $excludeElements, $deviation, false);

        // used for assertion counter in codeception / phpunit
        $this->assertTrue(true);
    }

    /**
     * @param $identifier
     * @param $elementID
     * @param $excludeElements
     * @param $deviation
     * @param $seeChanges
     * @return void
     */
    private function compareVisualChanges($identifier, $elementID, $excludeElements, $deviation, $seeChanges): void
    {
        $excludeElements = (array)$excludeElements;

        $maximumDeviation = (!$deviation && !is_numeric($deviation)) ? $this->maximumDeviation : (float)$deviation;

        $deviationResult = $this->getDeviation($identifier, $elementID, $excludeElements);

        if (is_null($deviationResult["deviationImage"])) {
            return;
        }

        if (($seeChanges && $deviationResult["deviation"] <= $maximumDeviation) || (!$seeChanges && $deviationResult["deviation"] > $maximumDeviation)) {
            $compareScreenshotPath = $this->getDeviationScreenshotPath($identifier);
            $deviationResult["deviationImage"]->writeImage($compareScreenshotPath);
            throw $this->createImageDeviationException($identifier, $compareScreenshotPath, $deviationResult["deviation"], $seeChanges);
        }
    }

    /**
     * @param $identifier
     * @param $compareScreenshotPath
     * @param $deviation
     * @param $seeChanges
     * @return \Codeception\Exception\ImageDeviationException
     */
    private function createImageDeviationException($identifier, $compareScreenshotPath, $deviation, $seeChanges): ImageDeviationException
    {
        $message = "The deviation of the taken screenshot is too high";
        if ($seeChanges) {
            $message = "The deviation of the taken screenshot is too low";
        }

        $message .= " ({$deviation}%) \nSee {$compareScreenshotPath} for a deviation screenshot.";

        return new ImageDeviationException(
            $message,
            $identifier,
            $this->getExpectedScreenshotPath($identifier),
            $this->getScreenshotPath($identifier),
            $compareScreenshotPath
        );
    }

    /**
     * Hide an element to set the visibility to hidden
     *
     * @param $elementSelector String of CSS Element selector, set visibility to hidden
     */
    private function hideElement($elementSelector): void
    {
        $this->setVisibility($elementSelector, false);
    }

    /**
     * Show an element to set the visibility to visible
     *
     * @param $elementSelector String of CSS Element selector, set visibility to visible
     */
    private function showElement($elementSelector): void
    {
        $this->setVisibility($elementSelector, true);
    }

    private function setVisibility($elementSelector, $isVisible): void
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
    private function getDeviation($identifier, $elementID, array $excludeElements = []): array
    {
        $coords = $this->getCoordinates($elementID);
        $this->createScreenshot($identifier, $coords, $excludeElements);

        $compareResult = $this->compare($identifier);

        $deviation = $compareResult[1] * 100;

        $this->debug("The deviation between the images is " . $deviation . " percent");

        return array(
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
        } else {
            // escape double quotes to not break JavaScript commands
            $elementId = str_replace('"', '\\"', $elementId);
        }

        $elementExists = (bool)$this->webDriver->executeScript('return document.querySelectorAll( "' . $elementId . '" ).length > 0;');

        if (!$elementExists) {
            throw new \Exception("The element you want to examine ('" . $elementId . "') was not found.");
        }

        $imageCoords = $this->webDriver->executeScript('
              var rect = document.querySelector( "' . $elementId . '" ).getBoundingClientRect();
              return {"offset_x": rect.left, "offset_y": rect.top, "width": rect.width, "height": rect.height};
        ');

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
        return $this->utils->getTestFileName($this->test, $identifier);
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
     * @throws \ImagickException
     */
    private function createScreenshot($identifier, array $coords, array $excludeElements = [])
    {
        $screenShotDir = Configuration::outputDir() . 'debug/';

        if (!is_dir($screenShotDir)) {
            if (!mkdir($screenShotDir, 0777, true) && !is_dir($screenShotDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $screenShotDir));
            }
        }

        $elementPath = $this->getScreenshotPath($identifier);
        $screenShotImage = new \Imagick();

        $this->hideElementsForScreenshot($excludeElements);

        if ($this->config["fullScreenShot"] === true || $this->config["forceFullScreenShot"] === true) {
            $height = $this->webDriver->executeScript("var ele=document.querySelector('html'); return ele.scrollHeight;");
            list($viewportHeight, $devicePixelRatio) = $this->webDriver->executeScript("return [window.innerHeight, window.devicePixelRatio]");

            $itr = $height / $viewportHeight;

            for ($i = 0; $i < (int)$itr; $i++) {
                $screenshotBinary = $this->webDriver->takeScreenshot();
                $screenShotImage->readimageblob($screenshotBinary);
                $this->webDriver->executeScript("window.scrollBy(0, {$viewportHeight});");
            }

            $screenshotBinary = $this->webDriver->takeScreenshot();
            $screenShotImage->readimageblob($screenshotBinary);
            $heightOffset = $viewportHeight - ($height - (intval($itr) * $viewportHeight));
            $screenShotImage->cropImage(0, 0, 0, $heightOffset * $devicePixelRatio);

            $screenShotImage->resetIterator();
            $fullShot = $screenShotImage->appendImages(true);

            if ($this->config["fullScreenShot"] !== true) {
                $fullShot->cropImage((int)$coords['width'], (int)$coords['height'], (int)$coords['offset_x'], (int)$coords['offset_y']);
            }
            $fullShot->writeImage($elementPath);

            $this->webDriver->executeScript("window.scrollTo(0, 0);");

        } else {
            $screenshotBinary = $this->webDriver->takeScreenshot();

            $screenShotImage->readimageblob($screenshotBinary);
            $screenShotImage->cropImage((int)$coords['width'], (int)$coords['height'], (int)$coords['offset_x'], (int)$coords['offset_y']);
            $screenShotImage->writeImage($elementPath);
        }

        $this->resetHideElementsForScreenshot($excludeElements);

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
    private function getDeviationScreenshotPath($identifier, $alternativePrefix = '')
    {
        $debugDir = Configuration::outputDir() . 'debug/';
        $prefix = ($alternativePrefix === '') ? 'compare' : $alternativePrefix;
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
            return array(null, 0, 'currentImage' => null);
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
        } catch (\ImagickException $e) {
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
        $filename = 'vcresult';
        if ($this->currentEnvironment) {
            $filename .= '.' . $this->currentEnvironment;
        }
        $this->logFile = Configuration::outputDir() . $filename . '.html';

        if (array_key_exists('templateVars', $this->config)) {
            $this->templateVars = $this->config["templateVars"];
        }

        if (array_key_exists('templateFile', $this->config)) {
            $this->templateFile = (file_exists($this->config["templateFile"]) ? "" : __DIR__) . $this->config["templateFile"];
        } else {
            $this->templateFile = __DIR__ . "/../Report/template.php";
        }
        $this->debug("VisualCeptionReporter: templateFile = " . $this->templateFile);
    }

    /**
     * Get a new loaded module
     */
    public function _initializeSession(): void
    {
        $browserModule = $this->getBrowserModule();

        $this->webDriverModule = $browserModule;
        $this->webDriver = $this->webDriverModule->webDriver;
    }

    /**
     * Loads current RemoteWebDriver instance as a session
     *
     * @param $session
     */
    public function _loadSession($session): void
    {
        $this->webDriver = $session;
    }

    /**
     * Returns current WebDriver session for saving
     *
     * @return RemoteWebDriver
     */
    public function _backupSession()
    {
        return $this->webDriver;
    }

    public function _closeSession($session = null): void
    {
        // this method will never be needed
    }
}
