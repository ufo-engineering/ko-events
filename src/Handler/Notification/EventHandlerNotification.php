<?php

namespace Ufo\Handler\Notification;
/**
 * Class EventHandlerNotification
 *
 * Sends an email notification
 */
abstract class EventHandlerNotification
{

    /**
     * Format email notification subject
     *
     * @param object $event
     *
     * @return string
     */
    abstract protected function formatSubject($event);

    /**
     * Get notification receivers list
     *
     * @param object $event
     *
     * @return array
     */
    abstract protected function getReceivers($event);

    /**
     * Send an email notification
     *
     * @param object $event
     * @param string $body
     *
     * @throws \Exception
     */
    protected function send($event, $body)
    {
        $mailer = $this->configureMailer($event);
        $mailer->message($body, 'text/html');
        $this->addReceivers($event, $mailer);
        $this->doSend($mailer);
    }

    /**
     * Load and configure mailer
     *
     * @param object $event
     *
     * @return \Email
     * @throws \Kohana_Exception
     */
    protected function configureMailer($event)
    {
        $mailer = \Email::factory($this->formatSubject($event));
        $this->defineFrom($event, $mailer);
        return $mailer;
    }

    /**
     * Add receivers to mailer
     *
     * @param object $event
     * @param \Email $mailer
     */
    protected function addReceivers($event, \Email $mailer)
    {
        // Load receivers
        $receivers = $this->getReceivers($event);
        if (!count($receivers)) {
            return;
        }

        \Kohana::$log->add(\Log::INFO, '----> Adding recipients to email notification "'.$this->formatSubject($event).'"');

        // Add receivers
        foreach ($receivers as $key => $receiver) {
            $this->addReceiver($mailer, $key, $receiver);
        }
    }

    /**
     * Add a receiver
     *
     * @param \Email $mailer
     * @param        $email
     * @param null   $name
     */
    protected function addReceiver(\Email $mailer, $email, $name = null)
    {
        if (!is_string($email)) {
            $email = $name;
            $name  = null;
        }

        if (\Valid::email($email)) {
            \Kohana::$log->add(\Log::INFO, '-----> Recipient: '.$email.' ('.$name.')');
            $mailer->to($email, $name);
        }
    }

    /**
     * Do emails send action
     *
     * @param \Email $mailer
     *
     * @throws \Exception
     */
    protected function doSend(\Email $mailer)
    {
        $failed = array();
        $sent   = $mailer->send($failed);

        \Kohana::$log->add(\Log::INFO, '----> Total mails sent: ' . $sent);

        if (count($failed)) {
            throw new \Exception('Failed to send to : ' . print_r($failed, true));
        }
    }

    /**
     * Define notification email from header
     *
     * @param object $event
     * @param Email  $mailer
     *
     * @throws \Kohana_Exception
     */
    protected function defineFrom($event, \Email $mailer)
    {
        $config = \Kohana::$config->load('email.' . KOHANA_ENV);
        $mailer->from($config['from'], $config['from_name']);
    }
}
