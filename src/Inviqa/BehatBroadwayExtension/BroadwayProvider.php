<?php

namespace Inviqa\BehatBroadwayExtension;

use Broadway\CommandHandling\SimpleCommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\ReadModel\InMemory\InMemoryRepository;

class BroadwayProvider
{
    private $store;
    private $eventBus;
    private $commandBus;
    private $playhead;
    private $readRepositories;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->store = new TraceableEventStore(new InMemoryEventStore());
        $this->eventBus = new SimpleEventBus();
        $this->commandBus = new SimpleCommandBus();
        $this->playhead = -1;
        $this->readRepositories = [];
    }

    public function addProjector($projector)
    {
        $this->eventBus->subscribe($projector);
    }

    public function assertEventsOccurred($events)
    {
        assert($this->store->getEvents() == $events);
    }

    public function dispatchCommand($commandHandler, $command)
    {
        $this->store->trace();
        $this->commandBus->subscribe($commandHandler);
        $this->commandBus->dispatch($command);
    }

    public function attachExistingEvents($events, $id)
    {
        $messages = array();
        foreach ($events as $event) {
            $this->playhead++;
            $messages[] = DomainMessage::recordNow($id, $this->playhead, new Metadata(array()), $event);
        }

        $this->store->append($id, new DomainEventStream($messages));
    }

    /**
     * @return mixed
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @return mixed
     */
    public function getEventBus()
    {
        return $this->eventBus;
    }

    public function getReadRepository($name)
    {
        if (!isset($this->readRepositories[$name])) {
            $this->readRepositories[$name] = new InMemoryRepository();
        }

        return $this->readRepositories[$name];
    }
}
