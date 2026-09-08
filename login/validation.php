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

function validatePhoneFormat(string $value): ?string
{
    if (trim($value) === '') {
        return null; // Checked by validateRequired
    }
    // Normalize: strip spaces and dashes so "0917 123 4567" and "0917-123-4567" pass
    $normalized = preg_replace('/[\s\-]/', '', $value);
    // PH mobile: 09XXXXXXXXX or +639XXXXXXXXX
    return preg_match('/^(09|\+639)\d{9}$/', $normalized) ? null : "Enter a valid PH mobile number (e.g. 09171234567).";
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
    $fullName = trim($post['full_name'] ?? '');
    $phone    = trim($post['phone'] ?? '');
    $email    = trim($post['email'] ?? '');
    $password = trim($post['password'] ?? '');

    // Normalize phone (strip spaces/dashes) so the stored value matches the validator's format
    $phoneNormalized = preg_replace('/[\s\-]/', '', $phone);

    $errors = array_filter([
        validateRequired($callsign, 'Pilot Callsign'),
        validateCallsignFormat($callsign),
        validateRequired($fullName, 'Full Name'),
        validateRequired($phone, 'Mobile Number'),
        validatePhoneFormat($phoneNormalized),
        validateRequired($email, 'Email Address'),
        validateEmailFormat($email),
        validateRequired($password, 'Security Passcode'),
        validateMinLength($password, 'Security Passcode', 6),
    ]);
    $errors = array_values($errors);

    return [
        'errors' => $errors,
        'data'   => [
            'callsign'  => $callsign,
            'full_name' => $fullName,
            'phone'     => $phoneNormalized,
            'email'     => $email,
            'password'  => $password,
        ],
    ];
}
