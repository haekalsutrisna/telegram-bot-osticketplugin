<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class TelegramPluginConfig extends PluginConfig {
    function getOptions() {
        return array(
            'telegram' => new SectionBreakField(array(
                'label' => 'Telegram Bot Notification',
            )),
            'telegram-webhook-url' => new TextboxField(array(
                'label' => 'Telegram Bot URL',
                'configuration' => array('size'=>100, 'length'=>200),
            )),
            'telegram-include-body' => new BooleanField(array(
                'label' => 'Include Body',
                'default' => 0,
            )),
            'debug' => new BooleanField(array(
                'label' => 'Debug message in error.log',
                'default' => 0,
            )),
        );
    }
}
