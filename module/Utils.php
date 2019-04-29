<?php

namespace Codeception\Module\VisualCeption;

use Codeception\Test\Descriptor;
use Codeception\TestInterface;

class Utils
{
    public function getTestFileName(TestInterface $test, $identifier)
    {
        $filename = preg_replace('~\W~', '.', Descriptor::getTestSignatureUnique($test));
        return mb_strcut($filename, 0, 249 - strlen($identifier), 'utf-8') . '.' . $identifier . '.png';
    }
}
