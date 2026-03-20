#!/usr/bin/env bash

wp i18n make-pot . languages/festival-perspectives-events.pot \
  --include="src" \
  --slug="festival-perspectives-events" \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/hirasso/festival-perspectives-events/","POT-Creation-Date":""}'
