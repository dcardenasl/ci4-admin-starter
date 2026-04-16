<?php

declare(strict_types=1);

/**
 * Traducciones de errores de API
 *
 * Mapea los códigos de error devueltos por ci4-api-starter a cadenas localizadas.
 * Las claves deben coincidir con el valor exacto (minúsculas/sin espacios) que devuelve la API.
 * Añade entradas aquí cuando encuentres mensajes de error de la API sin traducir.
 */
return [
    // Autenticación
    'email_already_registered' => 'Este correo ya está registrado.',
    'invalid_credentials'      => 'Credenciales inválidas.',
    'account_not_verified'     => 'Tu dirección de correo no ha sido verificada.',
    'account_suspended'        => 'Tu cuenta ha sido suspendida.',
    'account_pending_approval' => 'Tu cuenta está pendiente de aprobación del administrador.',
    'invalid_or_expired_token' => 'El enlace es inválido o ha expirado.',
    'token_already_used'       => 'Este enlace ya ha sido utilizado.',
    'email_not_found'          => 'No se encontró ninguna cuenta con ese correo.',
    'verification_email_sent'  => 'Se ha enviado un correo de verificación.',
    'email_already_verified'   => 'Este correo ya ha sido verificado.',
    // Genérico
    'unauthorized'             => 'No estás autorizado para realizar esta acción.',
    'forbidden'                => 'Acceso denegado.',
    'not_found'                => 'El recurso solicitado no fue encontrado.',
    'server_error'             => 'Ocurrió un error inesperado en el servidor.',
    'too_many_requests'        => 'Demasiadas solicitudes. Por favor espera un momento.',
];
