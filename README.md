# WP Secure Contact Plugin

WP Secure Contact is a custom WordPress plugin that provides a secure contact form using WordPress best practices.

## Features

- Custom shortcode to display the contact form
- Input sanitization and validation
- CSRF protection using WordPress nonces
- Success message after form submission
- Admin dashboard menu integration
- Admin-side CRUD: view and delete messages securely
- Capability checks to restrict admin access

## Usage

1. Activate the plugin from the WordPress dashboard.
2. Create a page (e.g., Contact).
3. Add the following shortcode to the page:

   [wpsc_contact]

4. Publish the page and view the contact form on the frontend.

## Security

This plugin follows WordPress secure coding standards, including:

- Sanitizing user inputs
- Using nonces to prevent CSRF attacks

## Author

Mohammad Shadullah
