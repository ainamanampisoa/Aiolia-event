<?php

namespace App\Scheduler;

use App\Message\SendRemindersMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class MainSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->with(
                // S'exécute toutes les heures pour vérifier s'il y a des rappels à envoyer
                // (24h et 2h avant les événements)
                // On vérifie toutes les heures pour ne pas manquer les événements
                RecurringMessage::every('1 hour', new SendRemindersMessage())
        );
    }
}
