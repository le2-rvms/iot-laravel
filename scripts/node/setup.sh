#!/bin/sh
set -eux

rm -f public/hot
rm -rf public/build

echo "Production/Staging: dry run setup."

# 把源切到部分镜像后，npm audit 会去调用安全审计接口（/-/npm/v1/security/*），而镜像可能没实现这些接口
if [ -n "${NPM_REGISTRY:-}" ]; then
    npm config set registry "${NPM_REGISTRY}"
fi
npm set audit=false

npm install -g pnpm

pnpm install
pnpm ls
pnpm run build --mode ${APP_ENV}
