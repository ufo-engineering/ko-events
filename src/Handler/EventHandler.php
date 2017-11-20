<?php

namespace Ufo\Handler;
/**
 * Interface Event_Handler
 *
 * Handles events
 */
interface EventHandler {

    /**
     * Handle an event
     *
     * @param object $event
     *
     * @return bool|null
     */
    public function handle($event);
}
