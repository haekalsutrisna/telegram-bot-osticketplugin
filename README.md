osTicket-telegram
==============
An plugin for [osTicket](https://osticket.com) work for version 1.18+ which posts notifications to a [Telegram](https://telegram.org) channel/chat/group using Telegram Bot.

Install
--------
1. Clone this repo or download the zip file and place the contents into your `include/plugins` folder.
2. Change Telegrams Bot URL using your own bot (ex. `https://api.telegram.org/botYOUR_BOT_TOKEN/sendMessage`) in the telegrambot.php file
3. Change Telegram Chat ID on $chatid = 'YOUR_TELEGRAM_CHAT_ID'; using your Chat ID
4. Install the plugin on OSTicket

For more information about Telegram Bot, see: https://core.telegram.org/bots/api

Info
------
This plugin uses CURL and tested on osTicket (v1.17.3) and (v1.18)

Based on [(https://github.com/foamrider/osticket-telegram)]
