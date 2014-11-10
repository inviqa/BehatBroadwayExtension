<?php

namespace Inviqa\BehatBroadwayExtension\Context;

use Behat\Behat\Context\Context;
use Inviqa\BehatBroadwayExtension\BroadwayProvider;

interface BroadwayAwareContext extends Context
{
    public function setBroadway(BroadwayProvider $broadway);
}
