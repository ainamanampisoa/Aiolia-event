<?php

namespace App\MessageHandler;

use App\Message\SendRemindersMessage;
use App\Service\EventReminderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendRemindersHandler
{
    public function __construct(
        private readonly EventReminderService $reminderService
    ) {
    }

    public function __invoke(SendRemindersMessage $message): void
    {
        // On envoie les rappels pour 24h et 2h avant
        $this->reminderService->sendReminders([24, 2]);
    }
}
