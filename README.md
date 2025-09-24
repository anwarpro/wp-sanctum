# WP Sanctum - WordPress API Token Management

## Description

WP Sanctum is a WordPress plugin that brings Laravel Sanctum-like token authentication to WordPress. It allows Single Page Applications (SPA), mobile apps, and third-party clients to authenticate securely using API tokens. This plugin provides token issuance, management, revocation, CSRF protection, and per-route ability enforcement.

---

## Features

- Issue API tokens for WordPress users.
- SPA login/logout with cookies and CSRF token.
- Admin panel to view and revoke tokens.
- Per-route ability enforcement for REST API endpoints.
- Token expiration and revocation.
- REST API routes to fetch current user information.
- Compatible with WordPress REST API and custom frontends.

---

## Installation

1. Upload the `wp-sanctum` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings in **WP Sanctum** admin menu if needed.

---

## REST API Endpoints

| Endpoint                     | Method | Description                                        |
| ---------------------------- | ------ | -------------------------------------------------- |
| `/wp-sanctum/v1/token`       | POST   | Issue a new API token using username and password. |
| `/wp-sanctum/v1/user`        | GET    | Retrieve current authenticated user information.   |
| `/wp-sanctum/v1/logout`      | POST   | Revoke current token and logout.                   |
| `/wp-sanctum/v1/csrf-cookie` | GET    | Issue a CSRF cookie for SPA protection.            |
| `/wp-sanctum/v1/spa-login`   | POST   | SPA login endpoint with cookie + CSRF.             |
| `/wp-sanctum/v1/spa-logout`  | POST   | SPA logout endpoint.                               |

---

## Admin Panel

- View all issued tokens.
- Revoke individual tokens.
- Monitor last usage and assigned abilities.

---

## Usage

### SPA Example

```javascript
// Fetch CSRF Token
fetch('/wp-json/wp-sanctum/v1/csrf-cookie');

// Login
fetch('/wp-json/wp-sanctum/v1/spa-login', {
    method: 'POST',
    body: JSON.stringify({ username: 'admin', password: 'password' }),
    headers: { 'Content-Type': 'application/json' }
});
```

### Protect REST Route by Ability

```php
WP_Sanctum_Abilities::get('wp-sanctum/v1', '/admin-data', function($req) {
    return ['secret' => 'only admins'];
}, ['admin']);
```

---

## Changelog

### 1.0.0

- Initial release with token management, SPA login/logout, admin UI, and ability enforcement.

---

## License

MIT License

