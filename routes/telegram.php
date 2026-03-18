<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Models\BannedUser;
use App\Telegram\Handlers\AdminCommandHandler;
use App\Telegram\Handlers\UserCommandHandler;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Ban check middleware
|--------------------------------------------------------------------------
*/
$bot->middleware(function (Nutgram $bot, $next) {
    if ($bot->userId() && BannedUser::isBanned($bot->userId())) {
        $ban = BannedUser::where('telegram_user_id', $bot->userId())->first();
        $until = $ban->banned_until ? $ban->banned_until->locale('ru')->isoFormat('D MMMM YYYY') : 'навсегда';
        $bot->sendMessage("🛑 Вы забанены. Доступ ограничен до <b>{$until}</b>.", parse_mode: 'HTML');
        return;
    }
    $next($bot);
});

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
    if ($bot->chat()?->type !== \SergiX44\Nutgram\Telegram\Properties\ChatType::PRIVATE) {
        return;
    }

    $bot->sendMessage(
        "Не понимаю вас 🤔\n\n" .
        "Доступные команды:\n" .
        "/start — записаться на звонок\n" .
        "/mybooking — посмотреть вашу запись\n" .
        "/cancel — отменить запись"
    );
});
