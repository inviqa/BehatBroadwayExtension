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
    private $playheads;
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
        $this->playheads = [];
        $this->readRepositories = [];
    }

    public function addProjector($projector)
    {
        $this->eventBus->subscribe($projector);
    }

    public function addCommandHandler($handler)
    {
        $this->commandBus->subscribe($handler);
    }

    public function assertEventsOccurred($events)
    {
        assert($this->store->getEvents() == $events);
    }

    public function assertEventsOccurredInterAlia($events)
    {
        foreach ($events as $event) {
            assert(in_array($event, $this->store->getEvents()));
        }
    }

    public function assertEventsOccurredLax($events)
    {
        $eventClasses = array_map('get_class', $this->store->getEvents());

        foreach ($events as $event) {
            assert(in_array(get_class($event), $eventClasses));
        }
    }

    public function dispatchCommand($commandHandler, $command, $extraHandlers = [])
    {
        $this->store->trace();
        $this->commandBus->subscribe($commandHandler);

        foreach ($extraHandlers as $extraHandler) {
            $this->commandBus->subscribe($extraHandler);
        }
        $this->commandBus->dispatch($command);
    }

    public function attachExistingEvents($events, $id)
    {
        $messages = array();
        foreach ($events as $event) {
            $playhead = $this->getPlayhead($id);
            $playhead++;
            $messages[] = DomainMessage::recordNow($id, $playhead, new Metadata(array()), $event);
            $this->playheads[$id] = $playhead;
        }

        $domainEventStream = new DomainEventStream($messages);
        $this->store->append($id, $domainEventStream);
        $this->eventBus->publish($domainEventStream);
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

    public function getCommandBus()
    {
        return $this->commandBus;
    }

    private function getPlayhead($id)
    {
        return isset($this->playheads[$id]) ? $this->playheads[$id] : -1;
    }
}
