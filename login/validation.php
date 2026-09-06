<?php
// THE HANGAR - GUND-ORDER SYSTEM
// INPUT VALIDATION MODULE
// Based on webdev1-midterm-discussion-master validation architecture

function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validateEmailFormat(string $value): ?string
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Please enter a valid communication frequency (email address).";
}

function validateMinLength(string $value, string $label, int $min): ?string
{
    return mb_strlen(trim($value)) >= $min ? null : "$label must be at least $min characters long.";
}

function validateCallsignFormat(string $value): ?string
{
    if (trim($value) === '') {
        return null; // Checked by validateRequired
    }
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
        return "Callsign may only contain letters, numbers, hyphens, and underscores.";
    }
    if (mb_strlen($value) < 3 || mb_strlen($value) > 30) {
        return "Callsign must be between 3 and 30 characters.";
    }
    return null;
}

function validateLoginInput(array $post): array
{
    $identifier = trim($post['identifier'] ?? '');
    $password   = trim($post['password'] ?? '');

    $errors = array_filter([
        validateRequired($identifier, 'Pilot Callsign or Email'),
        validateRequired($password, 'Security Passcode'),
    ]);
    $errors = array_values($errors);

    return [
        'errors' => $errors,
        'data'   => [
            'identifier' => $identifier, // raw value for parameterized DB comparison; escape only at HTML output time
            'password'   => $password,
        ],
    ];
}

function validateRegisterInput(array $post): array
{
    $callsign = trim($post['callsign'] ?? '');
    $email    = trim($post['email'] ?? '');
    $password = trim($post['password'] ?? '');

    $errors = array_filter([
        validateRequired($callsign, 'Pilot Callsign'),
        validateCallsignFormat($callsign),
        validateRequired($email, 'Email Address'),
        validateEmailFormat($email),
        validateRequired($password, 'Security Passcode'),
        validateMinLength($password, 'Security Passcode', 6),
    ]);
    $errors = array_values($errors);

    return [
        'errors' => $errors,
        'data'   => [
            'callsign' => $callsign,
            'email'    => $email,
            'password' => $password,
        ],
    ];
}
