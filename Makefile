.PHONY: dev print-env serve horizon logs vite cli-bash

L1_PRIVATE_DIR := ${HOME}/rvms/1prod/compose-L1-private/
L2_HOST_DIR := $(HOME)/rvms/2host/compose-L2-host/

ENV_CONTEXT_FILES := \
	$(L1_PRIVATE_DIR)_env/L_/0loc.env \
	$(L2_HOST_DIR)_env/L2/0loc.env \
	$(L2_HOST_DIR)_env/.pass.env

SERVICE_ENV_FILE := $(L2_HOST_DIR)gps/iot-laravel/.env.php

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
	pnpm install
	@$(LOAD_ENV) pnpm run dev

cli-bash:
	@$(LOAD_ENV) exec "$${SHELL:-/bin/bash}" -l
