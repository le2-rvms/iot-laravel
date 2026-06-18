#!/bin/sh
set -eux

rm -f public/hot
rm -rf public/build

echo "Production: run setup."

if [ -n "${NPM_REGISTRY:-}" ]; then
    pnpm config set registry "${NPM_REGISTRY}"
fi

pnpm install --frozen-lockfile

pnpm ls
pnpm run build --mode ${APP_ENV}
