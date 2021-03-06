<?php

namespace Tienvx\Bundle\MbtBundle\Reporter;

class SlackReporter implements ReporterInterface
{
    public static function getName(): string
    {
        return 'chat/slack';
    }

    public function getLabel(): string
    {
        return 'Slack';
    }

    public static function support(): bool
    {
        return class_exists('Symfony\Component\Notifier\Bridge\Slack\SlackTransport');
    }
}
