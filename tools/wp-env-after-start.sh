#!/usr/bin/env bash
set -e

pnpm run wp-env:cli wp theme activate twentytwentyfive
pnpm run wp-env:cli wp plugin activate --all
