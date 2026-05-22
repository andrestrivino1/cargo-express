<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificacionService
{
    public function notificarCliente(User $cliente, Notification $notification): void
    {
        $cliente->notify($notification);
    }

    public function notificarRol(string $rol, Notification $notification): void
    {
        User::role($rol)->each(function ($user) use ($notification) {
            $user->notify($notification);
        });
    }
}