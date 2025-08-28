<?php

// Validate that a required field is not empty
function validateRequired(string $value, string $fieldName): ?string{
    if (trim($value) === '') {
        return "$fieldName is required.";
    }
    return null;
}

// Validate that a value is numeric and positive
function validateNumeric(string $value, string $fieldName): ?string{
    if ($value === '' || !is_numeric($value) || $value <= 0) {
        return "$fieldName must be a valid positive number.";
    }
    return null;
}

// Validate email format; can be required or optional
function validateEmail(string $value, bool $required = false): ?string{
    if ($required && trim($value) === '') {
        return "Email is required";
    }

    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email address.";
    }

    return null;
}

?>
