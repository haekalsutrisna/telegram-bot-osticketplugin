<?php

require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class TelegramPlugin extends Plugin {
    var $config_class = "TelegramPluginConfig";

    function bootstrap() {
        Signal::connect('ticket.created', array($this, 'onTicketCreated'), 'Ticket');
        error_log('TelegramPlugin bootstrap connected to ticket.created signal');
    }

    function onTicketCreated($ticket) {
        // Check if the function is triggered
        error_log('onTicketCreated called with ticket ID: ' . $ticket->getId());

        global $ost;
        $ticketLink = $ost->getConfig()->getUrl().'scp/tickets.php?id='.$ticket->getId();
        $ticketId = $ticket->getNumber();
        $title = $ticket->getSubject() ?: 'No subject';
        $createdBy = $ticket->getName()." (".$ticket->getEmail().")";
        $priority = $ticket->getPriority();
        $message = $ticket->getLastMessage()->getMessage() ?: 'No content';
        $chatid = '-4510801959';

        // Log the ticket details
        error_log('Ticket details: ID = ' . $ticketId . ', Created by = ' . $createdBy . ', Subject = ' . $title);

        if ($this->getConfig()->get('telegram-include-body')) {
            $body = $ticket->getLastMessage()->getMessage() ?: 'No content';
            $body = str_replace('<p>', '', $body);
            $body = str_replace('</p>', '<br />' , $body);
            $breaks = array("<br />","<br>","<br/>");
            $body = str_ireplace($breaks, "\n", $body);
            $body = preg_replace('/\v(?:[\v\h]+)/', '', $body);
            $body = strip_tags($body);

            error_log('Message body included: ' . $body);
        } else {
            $body = '';
        }

        $payload = array(
            "method" => "sendMessage",
            "chat_id" => $chatid,
            "text" => "<b>New Ticket:</b> <a href=\"".$ticketLink."\">#".$ticketId."</a>\n<b>Created by:</b> ".$createdBy."\n<b>Subject:</b> ".$title."\n<b>Priority:</b> ".$priority."\n<b>Priority:</b> ".$priority."\n".$body.($body?"\n<b>Message:</b>\n".$body:''),
            "parse_mode" => "html",
            "disable_web_page_preview" => "True"
        );

        // Log the payload
        error_log('Payload to be sent to Telegram: ' . json_encode($payload));

        // Call sendToTelegram method
        $this->sendToTelegram($payload);
    }

    function sendToTelegram($payload) {
        try {
            global $ost;

            $data_string = json_encode($payload);
            $url = 'https://api.telegram.org/bot8073846070:AAHGUGW1WgGqXzeeb8ulgjNhddO8s0_w0Fg/sendMessage';

            // Log the URL being used
            error_log('Sending to Telegram using webhook URL: ' . $url);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ));

            $result = curl_exec($ch);

            // Log the result of curl_exec
            if ($result === false) {
                throw new Exception($url . ' - ' . curl_error($ch));
            } else {
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                error_log('Curl result: ' . $result . ' | Status code: ' . $statusCode);

                if ($statusCode != '200') {
                    throw new Exception($url . ' Http code: ' . $statusCode);
                }
            }

            curl_close($ch);
        } catch(Exception $e) {
            error_log('Error posting to Telegram: '. $e->getMessage());
        }
    }

    function escapeText($text) {
        $text = str_replace('&', '&amp;', $text);
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);

        return $text;
    }
}
