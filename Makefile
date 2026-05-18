.PHONY: dev print-env serve horizon logs vite cli-bash

ENV_CONTEXT_FILES = \
	../docker-compose/env/L_/0loc.env \
	../docker-compose/env/L2/0loc.env \
	../docker-compose/env/.pass.env

SERVICE_ENV_FILE = ../docker-compose/compose-L2/gps/iot-laravel/.env.php

APP_ENV_OVERRIDES = DB_HOST=localhost DB_PORT=$${TIMESCALEDB_PORT}

LOAD_ENV = $(foreach file,$(ENV_CONTEXT_FILES),. $(file);) ${APP_ENV_OVERRIDES} set -a; . $(SERVICE_ENV_FILE); set +a;

dev: print-env
	@$(MAKE) --no-print-directory -j4 serve vite

print-env:
	env -i sh -c '$(LOAD_ENV) env'

serve:
	@$(LOAD_ENV) php artisan serve

horizon:
	@$(LOAD_ENV) php artisan horizon

logs:
	@$(LOAD_ENV) php artisan pail --timeout=0

vite:
	@$(LOAD_ENV) pnpm run dev

cli-bash:
	@$(LOAD_ENV) exec "$${SHELL:-/bin/bash}" -l
