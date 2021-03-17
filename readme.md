# UDB3 backend

udb3-silex is the app that provides most of the backend of UiTdatabank v3, aka UDB3.

## Contributing

Several CI checks have been provided to make sure any changes are compliant with our coding standards and to detect potential bugs.

You can run all CI checks combined using the following composer script:
```
composer ci
```

Or run them individually:

- `composer test` for tests
- `composer phpstan` for static analysis
- `composer cs` for detecting coding standards violations
- `composer cs-fix` for fixing coding standards violations (where possible)

These checks will also run automatically for every PR.

## Database migrations

We use [Doctrine Migrations](http://doctrine-migrations.readthedocs.org/en/latest/index.html) to manage database schema updates.

To run the migrations, you can use the following composer script:
```
composer migrate
```

## Logs

Logs are located in the `./logs` directory.

### General logs

- `error.log` contains unforeseen errors/exceptions that occurred in HTTP requests, and general CLI errors (for example from supervisor processes) that do not get caught and logged to the other logs listed below
  
### Worker logs

The following logs contain info about CLI commands that run continuously as supervisor scripts.

- `curators-events.log` contains logs about the [uit-curatoren](https://github.com/cultuurnet/uit-curatoren/) events that get processed through the `amqp-listen-curators` CLI command
- `import-commands.log` contains logs about JSON-LD imports that get processed through the `amqp-listen-imports` CLI command
- `udb2.log` contains logs about XML imports that get processed through the `amqp-listen` CLI command
- `uitpas-events.log` contains logs about UiTPAS events that get processed through the `amqp-listen-uitpas` CLI command
  
### Service logs

The following logs contain info about specific services that can be part of HTTP requests, CLI commands, or both.

- `cdbxml_created_by_resolver.log` contains logs about the mapping of `createdby` in XML files to a user identifier that UDB3 understands
- `event_importer.log` contains logs about event JSON-LD imports
- `export.log` contains logs about event exports (any format)
- `labels.log` contains logs about the projection of label aggregates to the label tables in the database
- `media_manager.log` contains logs about the media manager, i.e. about uploads and edits of images and media objects
- `organizer-geocoordinates.log` contains logs about the geocoding of organizers
- `place-geocoordinates.log` contains logs about the geocoding of places
- `search_results.log` contains logs about the search results used in services like exports and other bulk commands
- `uitpas.log` contains logs about requests to UiTPAS _in the event exports_

### Adding a new logger

Use the `LoggerFactory::create()` method to quickly create a new logger. This way it gets stored in the right directory, correct formatting of exceptions, automatic Sentry integration, etc.
