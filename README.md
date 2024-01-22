#  Cierra Auth Package
This package is installable to Jetstream Apps, to enable login with cierra AI accounts to the app.

## Installation

You can install the package via composer, if you have the private composer of cierra configured in your repo:

```bash
composer require cierrateam/cierra-auth-package
```

You should add a client to the Auth instance. This is possible with:

ATTENTION: Run this on the Auth instance, not in your app.

```bash
php artisan passport:client
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="cierra-auth-package-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="cierra-auth-package-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag=":package_slug-views"
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
