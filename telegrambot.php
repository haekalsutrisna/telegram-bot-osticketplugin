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
        $ticketNumber = $ticket->getNumber(); // %{ticket.number}
        $title = $ticket->getSubject() ?: 'No subject'; // %{ticket.subject}
        $createdBy = $ticket->getName()." (".$ticket->getEmail().")"; // %{ticket.name} and %{ticket.email}
        $helpTopicObj = $ticket->getHelpTopic(); // %{ticket.topic}
        $helpTopic = is_object($helpTopicObj) ? $helpTopicObj->getName() : 'No help topic';
        $message = $ticket->getLastMessage()->getMessage() ?: 'No content'; // %{message}
        $chatid = '-4510801959';

        // Log the ticket details
        error_log('Ticket details: ID = ' . $ticket->getId() . ', Created by = ' . $createdBy . ', Subject = ' . $title . ', DeptLoc = ' . $deptloc);
    
        // Construct the message text with the variables
        $messageText = "<b>New Ticket:</b> <a href=\"".$ticketLink."\">#".$ticketNumber."</a>\n"
                     . "<b>Created by:</b> ".$createdBy."\n"
                     . "<b>Subject:</b> ".$title."\n"
                     . "<b>Help Topic:</b> ".$helpTopic."\n"
                     . "<b>Department Location:</b> ".$deptloc."\n" // Add the custom field deptloc here
                     . ($body ? "<b>Message:</b>\n".$body : '');
    
        // Prepare the payload
        $payload = array(
            "method" => "sendMessage",
            "chat_id" => $chatid,
            "text" => $messageText,
            "parse_mode" => "html",
            "disable_web_page_preview" => "True"
        );
    
        // Log the payload
        error_log('Payload to be sent to Telegram: ' . json_encode($payload));
    
        // Send the message to Telegram
        $this->sendToTelegram($payload);
    }
    
       

    function sendToTelegram($payload) {
        try {
            global $ost;

            $data_string = json_encode($payload);
            $url = 'https://api.telegram.org/bot${TOKEN}/sendMessage';

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
