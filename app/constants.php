<?php

// Enable/disable debug
defined('DEBUG') or define('DEBUG', false);
defined('DEBUG_FILE') or define('DEBUG_FILE', __DIR__.'/../data/debug.log');

// Application version
defined('APP_VERSION') or define('APP_VERSION', '1.0.12');

// Base directory
define('BASE_URL_DIRECTORY', dirname($_SERVER['PHP_SELF']));

// Database driver: sqlite, mysql or postgres
defined('DB_DRIVER') or define('DB_DRIVER', 'sqlite');

// Sqlite configuration
defined('DB_FILENAME') or define('DB_FILENAME', 'data/db.sqlite');

// Mysql/Postgres configuration
defined('DB_USERNAME') or define('DB_USERNAME', 'root');
defined('DB_PASSWORD') or define('DB_PASSWORD', '');
defined('DB_HOSTNAME') or define('DB_HOSTNAME', 'localhost');
defined('DB_NAME') or define('DB_NAME', 'kanboard');

// LDAP configuration
defined('LDAP_AUTH') or define('LDAP_AUTH', false);
defined('LDAP_SERVER') or define('LDAP_SERVER', '');
defined('LDAP_PORT') or define('LDAP_PORT', 389);
defined('LDAP_START_TLS') or define('LDAP_START_TLS', false);
defined('LDAP_SSL_VERIFY') or define('LDAP_SSL_VERIFY', true);
defined('LDAP_BIND_TYPE') or define('LDAP_BIND_TYPE', 'anonymous');
defined('LDAP_USERNAME') or define('LDAP_USERNAME', null);
defined('LDAP_PASSWORD') or define('LDAP_PASSWORD', null);
defined('LDAP_ACCOUNT_BASE') or define('LDAP_ACCOUNT_BASE', '');
defined('LDAP_USER_PATTERN') or define('LDAP_USER_PATTERN', '');
defined('LDAP_ACCOUNT_FULLNAME') or define('LDAP_ACCOUNT_FULLNAME', 'displayname');
defined('LDAP_ACCOUNT_EMAIL') or define('LDAP_ACCOUNT_EMAIL', 'mail');
defined('LDAP_ACCOUNT_ID') or define('LDAP_ACCOUNT_ID', '');
defined('LDAP_USERNAME_CASE_SENSITIVE') or define('LDAP_USERNAME_CASE_SENSITIVE', false);

// Google authentication
defined('GOOGLE_AUTH') or define('GOOGLE_AUTH', false);
defined('GOOGLE_CLIENT_ID') or define('GOOGLE_CLIENT_ID', '');
defined('GOOGLE_CLIENT_SECRET') or define('GOOGLE_CLIENT_SECRET', '');

// GitHub authentication
defined('GITHUB_AUTH') or define('GITHUB_AUTH', false);
defined('GITHUB_CLIENT_ID') or define('GITHUB_CLIENT_ID', '');
defined('GITHUB_CLIENT_SECRET') or define('GITHUB_CLIENT_SECRET', '');

// Proxy authentication
defined('REVERSE_PROXY_AUTH') or define('REVERSE_PROXY_AUTH', false);
defined('REVERSE_PROXY_USER_HEADER') or define('REVERSE_PROXY_USER_HEADER', 'REMOTE_USER');
defined('REVERSE_PROXY_DEFAULT_ADMIN') or define('REVERSE_PROXY_DEFAULT_ADMIN', '');
defined('REVERSE_PROXY_DEFAULT_DOMAIN') or define('REVERSE_PROXY_DEFAULT_DOMAIN', '');

// Mail configuration
defined('MAIL_FROM') or define('MAIL_FROM', 'notifications@kanboard.local');
defined('MAIL_TRANSPORT') or define('MAIL_TRANSPORT', 'mail');
defined('MAIL_SMTP_HOSTNAME') or define('MAIL_SMTP_HOSTNAME', '');
defined('MAIL_SMTP_PORT') or define('MAIL_SMTP_PORT', 25);
defined('MAIL_SMTP_USERNAME') or define('MAIL_SMTP_USERNAME', '');
defined('MAIL_SMTP_PASSWORD') or define('MAIL_SMTP_PASSWORD', '');
defined('MAIL_SMTP_ENCRYPTION') or define('MAIL_SMTP_ENCRYPTION', null);
defined('MAIL_SENDMAIL_COMMAND') or define('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -bs');

// Enable or disable "Strict-Transport-Security" HTTP header
defined('ENABLE_HSTS') or define('ENABLE_HSTS', true);

// Default files directory
defined('FILES_DIR') or define('FILES_DIR', 'data/files/');

