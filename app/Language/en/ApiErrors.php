<?php

declare(strict_types=1);

/**
 * API Error Translations
 *
 * Maps error codes returned by the ci4-api-starter API to localized strings.
 * Keys must match the exact lowercase/trimmed value returned by the API.
 * Add entries here when you encounter new untranslated API error messages.
 */
return [
    // Auth
    'email_already_registered' => 'This email is already registered.',
    'invalid_credentials'      => 'Invalid credentials.',
    'account_not_verified'     => 'Your account email has not been verified.',
    'account_suspended'        => 'Your account has been suspended.',
    'account_pending_approval' => 'Your account is awaiting administrator approval.',
    'invalid_or_expired_token' => 'The link is invalid or has expired.',
    'token_already_used'       => 'This link has already been used.',
    'email_not_found'          => 'No account found with that email address.',
    'verification_email_sent'  => 'A verification email has been sent.',
    'email_already_verified'   => 'This email has already been verified.',
    // Generic
    'unauthorized'             => 'You are not authorized to perform this action.',
    'forbidden'                => 'Access denied.',
    'not_found'                => 'The requested resource was not found.',
    'server_error'             => 'An unexpected server error occurred.',
    'too_many_requests'        => 'Too many requests. Please slow down.',
];
