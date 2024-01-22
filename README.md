#  Cierra Auth Package
This package is installable to Jetstream Apps, to enable login with cierra AI accounts to the app.

## Installation

You can install the package via composer, if you have the private composer of cierra configured in your repo:

### Install the package
```bash
composer require cierrateam/cierra-auth-package
```


### Create the app in admin instance
You should add a client to the Auth instance. This is possible with:

ATTENTION: Run this on the Auth instance, not in your app.

```bash
php artisan passport:client
```
During the creation, you get asked for:
USER: None
NAME: App Name
REDIRECT: domain.com/cierra-auth/callback

### Add .env variables
Add the data to your .env in your project:

```bash
CIERRA_AUTH_HOST="https://dev.admin.cierra.ai"
CIERRA_AUTH_CLIENT_ID="ID_HERE"
CIERRA_AUTH_CLIENT_SECRET="YOUR_SECRET"
CIERRA_AUTH_REDIRECT_AFTER_LOGIN="/"
CIERRA_APP_ID="GET IT FROM ADMIN PANEL"
```


### Add the redirect
Go to `app/Http/Middleware/Authenticate.php` and update: 

```php
protected function redirectTo(Request $request): ?string
{
    return $request->expectsJson() ? null : route('cierra-auth.login');
}
```

### Add redirect to new routes
Replace `route('login')` with `route('cierra-auth.login')` for login and `route('logout')` to `route('cierra-auth.logout')` for logout


### Optional

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="cierra-auth-package-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="cierra-auth-package-config"
```

## Usage

```php
$variable = new Cierra\Auth();
echo $variable->echoPhrase('Hello, VendorName!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Cierra GmbH](https://github.com/cierrateam)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
