

# About Kart Race Management

Kart Race Management (KRM) is a web application designed to allow race organizers to collect participant registrations, assign race numbers, categories, and tires efficiently.


**Features**

- Manage championships
- Manage races
- Collect participant registrations
- Assign categories and tires
- Track payments
- Assign and verify tires
- Assign transponders
- Export participant lists for MyLaps Orbits timekeeping



## Installation

The suggested installation method is via [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/).

An example Docker Compose configuration is available in the [`deploy`](./deploy/docker-compose.yml) folder.

1. Download or copy [`docker-compose.yml`](./deploy/docker-compose.yml)
2. Copy [`.env.example`](./deploy/.env.example) to `.env` and place it in the same folder as `docker-compose.yml`
3. Edit the following variables in your `.env` file:
   - `APP_KEY`: The application key, used to encrypt data
   - `APP_URL`: The URL under which the application will be reachable
   - `DB_PASSWORD`: Set a strong password for the database
   - `RACE_ORGANIZER_NAME`: The name of the championship and races organizer
   - `RACE_ORGANIZER_EMAIL`: The email address of the organizer for contacts
   - `RACE_ORGANIZER_ADDRESS`: The physical address of the organizer's office
   - `DB_USERNAME`: Change if desired
   - `DB_DATABASE`: Change if desired
4. Start the application using Docker Compose:

```bash
docker compose up -d
```

The application will be available at `http://localhost:8000/` (or the domain you configured).

## Usage

### First-time Setup

After installation, create an admin user:

```bash
docker compose exec app php artisan user:add --email your@email.com --role admin
```

Once logged in, you can create a new championship and configure categories and races.

### Maintenance

#### View Application Logs

```bash
docker compose logs -f app
```

#### Restart Services

```bash
docker compose restart
```

#### To Update the Application

```bash
docker compose pull
docker compose up -d
```


## Development

### Getting started

Kart Race Management (KRM) is built using the [Laravel framework](https://laravel.com/) and 
[Jetstream](https://jetstream.laravel.com/2.x/introduction.html). 
[Livewire](https://laravel-livewire.com/) is used to deliver dynamic
components, while [TailwindCSS](https://tailwindcss.com/) powers
the UI styling.

Development requirements:

- [PHP 8.3](https://www.php.net/) or above
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/en/) (version 18 or above) with npm package manager
- [MariaDB](https://mariadb.org/) (version 10.8 or above)
- [Docker](https://www.docker.com/)

### Testing

The project includes PHPUnit tests.

```
composer test
```

## Changelog

Please see [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](./.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/avvertix/kart-race-management/security/policy) on how to report security vulnerabilities.

## Credits

- [Alessio](https://github.com/avvertix)
- [All Contributors](https://github.com/avvertix/kart-race-management/contributors)

## License

Kart Race Management is open-sourced software licensed under the [AGPL-3.0 license](https://opensource.org/licenses/AGPL-3.0).