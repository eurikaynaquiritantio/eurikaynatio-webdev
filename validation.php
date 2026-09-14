<?php
/**
 * validation.php — shared validation functions.
 * Required by every endpoint that processes form input (contact,
 * and later login/register) so validation rules live in ONE place
 * instead of being copy-pasted into every file.
 *
 * Every function returns an error message string when the input is
 * invalid, or an empty string "" when it's valid — so callers do:
 *
 *     $error = validate_email($email);
 *     if ($error !== "") { ...collect it... }
 */

function validate_name($name) {
    if (trim($name) === '') {
        return "Full name is required.";
    }
    if (strlen(trim($name)) < 2) {
        return "Full name must be at least 2 characters.";
    }
    return "";
}

function validate_email($email) {
    if (trim($email) === '') {
        return "Email is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }
    return "";
}

function validate_password($password, $min_length = 8) {
    if ($password === '') {
        return "Password is required.";
    }
    if (strlen($password) < $min_length) {
        return "Password must be at least $min_length characters.";
    }
    return "";
}

function validate_password_match($password, $confirm) {
    if ($password !== $confirm) {
        return "Passwords do not match.";
    }
    return "";
}

// Birthday on the VIP Club signup form is optional (it just unlocks a
// birthday perk), so an empty string is valid — only a non-empty value
// that isn't a real calendar date gets rejected.
function validate_birthday($birthday) {
    $birthday = trim($birthday);
    if ($birthday === '') {
        return "";
    }
    $date = DateTime::createFromFormat('Y-m-d', $birthday);
    $errors = DateTime::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        return "Please enter a valid birthday.";
    }
    if ($date > new DateTime()) {
        return "Birthday can't be in the future.";
    }
    return "";
}