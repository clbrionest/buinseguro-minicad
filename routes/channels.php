<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal global de llamadas para Administradores y Supervisores
Broadcast::channel('calls.all', function ($user) {
    return $user->hasAnyRole(['Administrador', 'Supervisor']);
});

// Autorizamos que un usuario escuche el canal de llamadas de su extensión,
// o cualquier extensión si tiene rol Administrador o Supervisor
Broadcast::channel('calls.{extension}', function ($user, $extension) {
    if ($user->hasAnyRole(['Administrador', 'Supervisor'])) {
        return true;
    }

    return !empty($user->extension) && (string) $user->extension === (string) $extension;
});