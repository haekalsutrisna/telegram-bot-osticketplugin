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
        // Log that the function is triggered
        error_log('onTicketCreated called with ticket ID: ' . $ticket->getId());

        global $ost;
        $ticketLink = $ost->getConfig()->getUrl() . 'scp/tickets.php?id=' . $ticket->getId();
        $ticketId = $ticket->getNumber();
        $title = $ticket->getSubject() ?: 'No subject';
        $createdBy = $ticket->getName() . " (" . $ticket->getEmail() . ")";
        $chatid = 'YOUR_TELEGRAM_CHAT_ID'; // Replace with your actual Telegram chat ID

        // Fetch the Help Topic
        $helpTopic = $ticket->getTopic() ? $ticket->getTopic()->getName() : 'No help topic';

        // Log ticket details
        error_log('Ticket details: ID = ' . $ticketId . ', Created by = ' . $createdBy . ', Subject = ' . $title . ', Help Topic = ' . $helpTopic);

        // Fetch the last message of the ticket
        $messageObj = $ticket->getLastMessage();
        $body = $messageObj ? $messageObj->getMessage() : 'No content';

        // Escape the message for HTML
        $body = strip_tags($body);

        // Fetch custom form data
        $formData = $this->getTicketFormData($ticket);

        // Prepare payload for Telegram
        $payload = array(
            "method" => "sendMessage",
            "chat_id" => $chatid,
            "text" => "<b>New Ticket:</b> <a href=\"" . $this->escapeHtml($ticketLink) . "\">#" . $this->escapeHtml($ticketId) . "</a>\n"
                     . "<b>Created by:</b> " . $this->escapeHtml($createdBy) . "\n"
                     //. "<b>Subject:</b> " . $this->escapeHtml($title) . "\n"
                     . "<b>Help Topic:</b> " . $this->escapeHtml($helpTopic) . "\n"
                     . ($formData ? "\n<b>Ticket Details:</b>\n" . $formData : '')
                     . ($body ? "<b>Message:</b>\n" . $body : ''), 
            "parse_mode" => "html",
            "disable_web_page_preview" => "True"
        );

        // Log the payload
        error_log('Payload to be sent to Telegram: ' . json_encode($payload));

        // Send payload to Telegram
        $this->sendToTelegram($payload);
    }

    // Function to send the payload to Telegram
    function sendToTelegram($payload) {
        try {
            $data_string = json_encode($payload);
            $url = 'https://api.telegram.org/botYOUR_BOT_TOKEN/sendMessage'; // Replace YOUR_BOT_TOKEN with actual bot token

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
        } catch (Exception $e) {
            error_log('Error posting to Telegram: ' . $e->getMessage());
        }
    }

    // Function to fetch all form data for the ticket
    function getTicketFormData($ticket) {
        $formData = "";
        $entries = DynamicFormEntry::forTicket($ticket->getId()); // Fetch form entries for the ticket

        foreach ($entries as $entry) {
            $answers = $entry->getAnswers();
            foreach ($answers as $answer) {
                $formData .= "<b>" . $this->escapeHtml($answer->getField()->get('label')) . ":</b> " . $this->escapeHtml($answer->getValue()) . "\n";
            }
        }

        return $formData;
    }

    // Function to escape HTML special characters
    function escapeHtml($text) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
