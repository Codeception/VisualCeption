<?php

class MultipleAttributesCest
{

    /**
     * Comparing the same div with different attribute selectors
     */
    public function seeSameDivForMultipleSelectors(WebGuy $I, $scenario)
    {
        $I->amOnPage("/multipleAttributes.html");
        // class selector
        $I->dontSeeVisualChanges("multipleAttributes", ".block");
        // attribute selector without value
        $I->dontSeeVisualChanges("multipleAttributes", "[data-element]");
        // attribute selector with single quotes
        $I->dontSeeVisualChanges("multipleAttributes", "[data-element='myElement']");
        // attribute selector with double quotes
        $I->dontSeeVisualChanges("multipleAttributes", '[data-element="myElement"]');
    }
}
