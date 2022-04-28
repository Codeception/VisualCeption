<?php

class MultiSessionCest
{

    /**
     * Checking that different sessions are taken
     */
    public function dontSeeVisualChangesInMultiSessions(WebGuy $I, $scenario)
    {
        $I->amOnPage("/multiSession.html");
        $I->dontSeeVisualChanges("block", ".block");

        $friend = $I->haveFriend('friend');
        $friend->does(function (WebGuy $I) {
            $I->amOnPage("/multiSessionChanged.html");
            $I->dontSeeVisualChanges("blockInAnotherSession", ".block");
        });

        $I->amOnPage("/multiSession.html");
        $I->dontSeeVisualChanges("block", ".block");
    }
}
