<?php
// Example environment configuration.
// Copy these to your hosting environment (e.g. cPanel "Environment variables",
// Apache SetEnv directives, or a .env file loaded before this script).
//
// DO NOT commit real values to version control.

putenv('APP_NAME=Student Risk Prediction');
putenv('APP_URL=https://your-domain.example/register');
putenv('APP_BASE=/register');

putenv('DB_HOST=localhost');
putenv('DB_NAME=your_db_name');
putenv('DB_USER=your_db_user');
putenv('DB_PASS=your_db_password');

putenv('GEMINI_API_KEY=your_google_gemini_api_key');
putenv('GEMINI_MODEL=gemini-2.5-flash');

putenv('MAIL_FROM=noreply@your-domain.example');
