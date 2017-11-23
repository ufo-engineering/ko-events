<?php

namespace Ufo;

use Ufo\Handler\EventHandler;
/**
 * Class Event
 *
 * Responsible for firing events
 */
class Event
{
    /**
     * @var array
     */
    private static $events;

    /**
     * Fire an event
     *
     * @param object|string $event
     * @param object|array $data
     */
    public static function fire($event, $data = null)
    {
        $event_alias = is_string($event) ? $event : get_class($event);

        if (is_string($event)) {

            if (is_array($data)) {
                $data['action_name'] = $event;
            }

            $event = $data;
        }

        \Kohana::$log->add(\Log::INFO, '---> Event fired: '.$event_alias);

        self::load();

        $handlers = \Arr::get(self::$events, $event_alias);

        if (!count($handlers)) {
            return;
        }

        foreach ($handlers as &$handler) {
            try {
                if (self::handle($event, $handler) === false) {
                    break;
                }
            } catch (\Exception $e) {
                \Kohana::$log->add(\Log::ERROR,
                    $event_alias . ' -> ' . get_class($handler) . ': ' .
                    $e->getMessage() .
                    PHP_EOL . $e->getTraceAsString()
                );
            }
        }
    }

    /**
     * Load event hooks
     *
     * @throws \Kohana_Exception
     */
    private static function load()
    {
        if (!self::$events) {
            self::$events = \Kohana::$config->load('events.events_handlers');
            // if not exist config file then get config from DB
            self::$events = self::$events ? self::$events : self::getConfigFromDB();
        }
    }

    /**
     * @param object               $event
     * @param string|Event_Handler $handler
     *
     * @return bool
     */
    private static function handle($event, &$handler)
    {
        self::boot_handler($handler);

        if (is_object($handler) && $handler instanceof EventHandler) {
            \Kohana::$log->add(\Log::INFO, '---> Running event handler '.get_class($handler));
            return $handler->handle($event);
        }

        return true;
    }

    /**
     * Boot event handler
     *
     * @param string|Event_Handler $handler
     */
    private static function boot_handler(&$handler)
    {
        if (is_string($handler) && class_exists($handler)) {
            $handler = new $handler();
        }
    }


    /**
     * Get array represented as string
     *
     * @param array $array
     * @param int   $level
     *
     * @return string
     */
    private static function arrayToString($array, $level = 0)
    {
        $lines = array(PHP_EOL);
        foreach ($array as $name => $value) {
            $lines[] = str_repeat('    ', $level).'[' . $name . '] => ' . self::toString($value, $level);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Transform given value to string
     *
     * @param mixed $value
     * @param int   $level
     *
     * @return string
     */
    private static function toString($value, $level = 0)
    {
        if(is_array($value)) {
            return self::arrayToString($value, $level + 1);
        } else if (is_object($value) && $value instanceof \ORM) {
            return self::toString(print_r($value->as_array(), true));
        }

        return $value;
    }

    /**
     * Get config from DB
     * @return array
     */
    private static function getConfigFromDB()
    {
        $event_hendlers = [];
        $events =  \DB::select(
                        'system_actions.*',
                        ['action_handlers.name', 'handler_name'],
                        'action_handlers.placeholders')
                        ->from('system_actions')
                        ->join('action_handlers', 'INNER')
                        ->on('action_handlers.id', '=', 'system_actions.handler_id')
                        ->execute()
                        ->as_array();

        foreach ($events as $event) {
            $alias = $event['name'];
            $event_hendlers[$alias][] = $event['handler_name'];
        }

        return $event_hendlers;
    }
}
