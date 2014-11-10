<?php

namespace Inviqa\BehatBroadwayExtension\Context;

use Inviqa\BehatBroadwayExtension\BroadwayProvider;

trait BroadwayDictionary
{
    /**
     * @var BroadwayProvider
     */
    private $broadway;

    public function setBroadway(BroadwayProvider $broadway)
    {
        $this->broadway = $broadway;
    }
}
