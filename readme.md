# VisualCeption

![](http://www.thewebhatesme.com/wp-content/uploads/visualception.png)

Visual regression tests for [Codeception](http://codeception.com/).

[![Build Status](https://travis-ci.org/Codeception/VisualCeption.svg?branch=master)](https://travis-ci.org/Codeception/VisualCeption)

This module can be used to compare the current representation of a website element with an expected. It was written on the shoulders of codeception and integrates in a very easy way.

**WARNING** This module can reduce the execution speed of acceptance tests. Use it only for visual regression test suite and not for regular end to end testing.

**Example**

![](http://www.thewebhatesme.com/VisualCeption/compare.png)

## How it works

VisualCeption uses a combination of the "make a screenshot" feature in webdriver, imagick and native JavaScript to compare visual elements on a website. This comparison is done in five steps:

1. **Take a screenshot** of the full page using webdriver.
2. **Calculate the position** and size of the selected element using JavaScript.
3. **Crop the element** out of the full screenshot using imagick.
4. **Compare the element** with an older version of the screenshot that has been proofed as valid using imagick. If no previous image exists the current image will be used fur future comparions. As an effect of this approach the test has to be **run twice** before it works.
5. If the deviation is too high **throw an exception** that is caught by Codeception.

## Requirements

VisualCeption needs the following components to run:

* **Codeception** VisualCeption is a module for [Codeception](http://codeception.com/). It will need a running version of this tool.
* **Imagick** For comparing two images VisualCeption is using the imagick library for php. For more information visit [php.net](http://www.php.net/manual/de/book.imagick.php) or the [installation guide](http://www.php.net/manual/en/imagick.setup.php).
* **WebDriver module** This tool only works with the webdriver module in Codeception at the moment.

## Installation

Make sure you have php-imagick extension installed. Run `php -m` to see if imagick extension is enabled.

Then add VisualCeption to composer.json:

```
composer require "codeception/visualception:*" --dev
```

### Configuration

To use the VisualCeption module you have to configure it.

**Example Configuration**

```yaml
modules:
    enabled:
        - WebDriver:
            url: http://localhost.com
            browser: firefox
        - VisualCeption:
            maximumDeviation: 5                                   # deviation in percent
            saveCurrentImageIfFailure: true                       # if true, VisualCeption saves the current
            fullScreenShot: true                                  # fullpage screenshot
```

* **referenceImageDir** (default: `'VisualCeption/'`) VisualCeption uses an "old" image for calculating the deviation. These images have to be stored in data directory (tests/_data) or be relative to it.
* **currentImageDir** (default: `'debug/visual/'`) temporary directory for current processed images. Relative to output dir `tests/_output`.
* **maximumDeviation** (default: `0`) When comparing two images the deviation will be calculated. If this deviation is greater than the maximum deviation the test will fail.
* **saveCurrentImageIfFailure** (default: `true`) When the test fails, the current image will be saved too, so it's easier to change the reference image with this one. The image will appear beside the compare image with the prefix "current."
* **report** (default: `false`) When enabled an HTML report with diffs for failing tests is generated. Report is stored in `tests/_output/vcresult.html`.
* **module** (default: `'WebDriver'`) module responsible for browser interaction, default: WebDriver.
* **fullScreenShot** (default: `false`) fullpage screenshot for Chrome and Firefox
* **forceFullScreenShot** (default: `false`) force fullpage screenshot for Chrome and Firefox. Useful when element to capture is outside the initial window dimensions of WebDriver.

## Usage

VisualCeption is really easy to use. There are only two methods that will be added to $I <code>seeVisualChanges</code> and <code>dontSeeVisualChanges</code>.

```php
$I->seeVisualChanges("uniqueIdentifier1", "elementId1");
$I->dontSeeVisualChanges("uniqueIdentifier2", "elementId2");

$I->dontSeeVisualChanges("uniqueIdentifier3", "elementId3", array("excludeElement1", "excludeElement2"));

$I->dontSeeVisualChanges("uniqueIdentifier3", "elementId3", array("excludeElement1", "excludeElement2"), $deviation]);
```

* **uniqueIdentifier** For comparing the images it is important to have a stable name. This is the corresponding name.
* **elementId** It is possible to only compare a special div container. The element id can be passed. *You can use CSS locators*.
* **excludeElements** Optional parameter as string or an array of strings to exclude an element from the screenshot. Maybe there is an animated image in your test container, so you can ignore it. *You can use CSS locators*.
* **$deviation** Optional parameter as float use if it is necessary to establish deviation coefficient other than configuration.

**Example Usage**
```php
$I->seeVisualChanges( "subNavigation", "#subNav" );
$I->dontSeeVisualChanges("content", "div.content", array("#intro"));
```

If you need more information about the test run please use the command line debug option (-d or --debug).

## HTML Reports

Enable Reports in config and use nice HTML output to see all failed visual tests with their image diffs on a page:

```yaml
modules:
    enabled:
        - WebDriver:
            url: http://localhost.com
            browser: firefox
        - VisualCeption:
            report: true
            templateFile: "/report/template2.php" # Absolute path or relative from module dir to report template. Default "/report/template.php"
```

## Restriction

VisualCeption uses the WebDriver module for making the screenshots. As a consequence we are not able to take screenshots via google chrome as the chromedriver does not allow full page screenshots.

## Run tests with Docker
```
docker-compose up --abort-on-container-exit
```
