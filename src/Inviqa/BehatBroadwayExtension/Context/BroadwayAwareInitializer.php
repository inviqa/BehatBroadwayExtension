<?php

namespace Inviqa\BehatBroadwayExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Inviqa\BehatBroadwayExtension\BroadwayProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BroadwayAwareInitializer implements ContextInitializer, EventSubscriberInterface
{
    private $broadway;

    public function __construct(BroadwayProvider $broadway)
    {
        $this->broadway = $broadway;
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScenarioTested::BEFORE => array('resetBroadway', 0),
        );
    }

    public function initializeContext(Context $context)
    {
        if (!$context instanceof BroadwayAwareContext && !$this->usesBroadwayDictionary($context)) {
            return;
        }

        $context->setBroadway($this->broadway);
    }

    private function usesBroadwayDictionary(Context $context)
    {
        $refl = new \ReflectionObject($context);
        if (method_exists($refl, 'getTraitNames')) {
            if (in_array('Inviqa\\BehatBroadwayExtension\\Context\\BroadwayDictionary', $refl->getTraitNames())) {
                return true;
            }
        }

        return false;
    }

    public function resetBroadway()
    {
        $this->broadway->reset();
    }

}
