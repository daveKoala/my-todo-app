# Laravel Google Keep clone

## Start / set up.

All these commands are from the project root. The same level as this `README.md` file

```bash

./vendor/bin/sail up

```

If you have cloned this project, `./vendor` will not be present and teh required packages will not be installed.

**Install dependencies using Docker (no local PHP needed)**∫c

```:bash


docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

### Databases

The first time you star the project there will be no databases or seeded tables. So

```:bash
./vendor/bin/sail artisan migrate:status
```

## Use the interactive shell (the terminal within the container)

```:bash
./vendor/bin/sail shell
```
