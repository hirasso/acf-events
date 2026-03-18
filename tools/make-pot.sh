#!/usr/bin/env bash

wp i18n make-pot . languages/acf-events.pot \
  --include="src,acf-events.php" \
  --slug="acf-events" \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/hirasso/acf-events/","POT-Creation-Date":""}'
