<?php
// Mail configuration used by API endpoints. Edit these values to match your SMTP
// provider. Defaults are set to a local mail-catcher (MailHog/Papercut) which
// is convenient for development on XAMPP/Windows.

return [
    // driver: 'smtp' or 'mail'. Use 'smtp' for authenticated SMTP relays.
    // Default to 'smtp' so the code uses an authenticated transport.
    'driver' => getenv('APP_MAIL_DRIVER') ?: 'smtp',

    // SMTP settings (used when driver === 'smtp').
    // Defaults are set for Gmail SMTP with STARTTLS on port 587.
    // You MUST provide APP_MAIL_PASSWORD (an app password) for Gmail to work.
    'host' => getenv('APP_MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => getenv('APP_MAIL_PORT') ?: 587,
    // default username: same as the from-address if not provided
    'username' => getenv('APP_MAIL_USERNAME') ?: (getenv('APP_MAIL_FROM_ADDRESS') ?: 'mcrei.dev.gma@gmail.com'),
    'password' => getenv('APP_MAIL_PASSWORD') ?: 'piez jpuy ymml eccy',
    // encryption: '', 'tls' or 'ssl'
    'encryption' => getenv('APP_MAIL_ENCRYPTION') ?: 'tls',

    // From address/name (fallbacks will be computed if invalid)
    'from_address' => getenv('APP_MAIL_FROM_ADDRESS') ?: 'mcrei.dev.gma@gmail.com',
    'from_name' => getenv('APP_MAIL_FROM_NAME') ?: 'MCREI',
];
