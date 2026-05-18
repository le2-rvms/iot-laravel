#!/bin/sh
set -eux

rm -f public/hot
rm -rf public/build

echo "Production/Staging: run setup."

if ! command -v pnpm >/dev/null 2>&1; then
    npm install -g pnpm
fi

if [ -n "${NPM_REGISTRY:-}" ]; then
    pnpm config set registry "${NPM_REGISTRY}"
fi

pnpm install --frozen-lockfile

pnpm ls
pnpm run build --mode ${DOCKER_ENV}
