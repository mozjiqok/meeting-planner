<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Handlers\AdminCommandHandler;
use App\Telegram\Handlers\UserCommandHandler;
use App\Telegram\Conversations\BookingConversation;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| User commands
|--------------------------------------------------------------------------
*/
$bot->onCommand('start', [UserCommandHandler::class, 'start'])
    ->description('Записаться на звонок');

$bot->onCommand('mybooking', [UserCommandHandler::class, 'myBooking'])
    ->description('Посмотреть вашу запись');

$bot->onCommand('cancel', [UserCommandHandler::class, 'cancel'])
    ->description('Отменить запись');

/*
|--------------------------------------------------------------------------
| Admin commands (silently ignored for non-admins)
|--------------------------------------------------------------------------
*/
$bot->onCommand('slots', [AdminCommandHandler::class, 'slots'])
    ->description('[admin] Слоты на 14 дней');

$bot->onCommand('bookings', [AdminCommandHandler::class, 'bookings'])
    ->description('[admin] Список записей');

$bot->onCommand('block', [AdminCommandHandler::class, 'block'])
    ->description('[admin] Заблокировать слот: /block DD.MM.YYYY HH:MM');

$bot->onCommand('unblock', [AdminCommandHandler::class, 'unblock'])
    ->description('[admin] Разблокировать слот: /unblock DD.MM.YYYY HH:MM');

/*
|--------------------------------------------------------------------------
| Fallback: unknown text
|--------------------------------------------------------------------------
*/
$bot->fallback(function (Nutgram $bot) {
    $bot->sendMessage(
        "Не понимаю вас 🤔\n\n" .
        "Доступные команды:\n" .
        "/start — записаться на звонок\n" .
        "/mybooking — посмотреть вашу запись\n" .
        "/cancel — отменить запись"
    );
});
