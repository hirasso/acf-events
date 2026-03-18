#!/usr/bin/env bash
set -e

pnpm run wp-env:cli wp theme activate twentytwentyfive
pnpm run wp-env:cli wp rewrite structure '/%postname%/' --hard
pnpm run wp-env:cli wp plugin activate --all

pnpm run wp-env:cli:tests wp theme activate twentytwentyfive
pnpm run wp-env:cli:tests wp rewrite structure '/%postname%/' --hard
pnpm run wp-env:cli:tests wp plugin activate --all