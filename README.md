# ko-events
This version was transformed (`*with some changes`) from this package, special for using through composer  https://packagist.org/packages/unxp/kohana-events

### Event system for Kohana framework ###

Released on 2017-11-20

By ufo-engineering

Installation
------------
composer require ufo-engineering/ko-events


Configuration
-------------

Copy `..vendor/ufo-engineering/ko-events/config/events.php` to `application/config/events.php`.
All events and their handlers have to be registered in `application/config/events.php` configuration file (by default).
But, if `events.php` not exists, the library will try to get config from DB. You should execute
dump of config tables from this file `events_tables.sql`. At the moment, if you choose DB for to store config data,
 you should add specific data manually. So, for ordinary tasks, we recommended use config-file.
Configuration is devised into four parts for each environment, but you can add your own.
To register an event, put it's class name as an array key and it's handlers as values.

Example:

```php
return [
    'events_handlers' => [
        'Event_Test'       => ['Handler_EventTest'], //Event_Test is real class in dir application/classes/Event/Test.php
        'someTestMethod'   => ['Handler_EventTest'] // alias, for example method name
        //...
        ]
];

```

Usage
-----

* Create an event class
... application/classes/Event/Test.php

```php
/**
 * Class Event_Test
 *
 * Fired when we do something
 */
class Event_Test
{
    /**
     * @var Model_Test
     */
    public $test;

    /**
     * @param Model_Test $test
     */
    function __construct(Model_Test $test)
    {
        $this->test = $test;
    }
}
```

* Create a handler class

```php
/**
 * Class Handler_EventTest
 *
 * For example: sends an email notification to user about successful registration
 */
class Handler_EventTest implements Ufo\Handler\EventHandler
{
    /**
     * Handle an event
     *
     * @param Handler_EventTest $event
     *
     * @return bool|null
     */
    public function handle($event)
    {
        // TODO send email
    }
}
```

* Fire event

```php
use Ufo\Event;

Event::fire(new Event_Test($user));
OR
Event::fire('someTestMethod', $user);
```


All event handlers will be executed in the same order they are defined in `events.php` file or DB.
By making `handle` method to return `false`, you can break the chain and no further handlers will be executed.

Extra
-----

There is an extra class `EventHandlerNotification` for handling event notification easily.
However it requires Kohana emails module from https://github.com/shadowhand/email or you can adapt email sending for your needs.

To use this helper, simply make your handlers to extend this class and implement `formatSubject` and `getReceivers` methods.

Example:

```php
/**
 * Class Event_Handler_User_SendWelcomeEmail
 *
 * Sends an email notification to user about successful registration
 */
class Event_Handler_User_SendWelcomeEmail extends Ufo\Handler\Notification\EventHandlerNotification implements Ufo\Handler\EventHandler
{
    /**
     * Handle an event
     *
     * @param Event_User_Registered $event
     *
     * @return bool|null
     */
    public function handle($event)
    {
        $this->send($event, 'Welcome aboard user ' . $event->user->username . '!');
    }

    /**
     * Format email notification subject
     *
     * @param Event_User_Registered $event
     *
     * @return string
     */
    protected function formatSubject($event)
    {
        return 'Welcome ' . $user->username;
    }

    /**
     * Get notification receivers list
     *
     * @param Event_User_Registered $event
     *
     * @return array
     */
    protected function getReceivers($event)
    {
        return array(
            $event->user->email => $event->user->username
        );
    }
}
```
### Tables ###
```
CREATE TABLE `action_handlers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `placeholders` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `system_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `handler_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

`*with some changes:`
1) added support of aliases
2) added DB config tables
