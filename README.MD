Start the dummy wordpress site for testing the availabitly-calender-plugin

```bash
docker compose up -d
```

Stop the dummy wordpress site

```bash
docker compose down
```

checkout wordpress admin dashboard

http://localhost:8000/wp-login.php

username: m.k-h@outlook.de
password: iOtZvXXSp7fCdaEVEh

# trigger the date cleanup job manually

run in WP console or a one-off snippet:

docker compose exec wordpress bash

curl -L -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /usr/local/bin/wp
wp --info

wp --path=/var/www/html core is-installed
wp --path=/var/www/html plugin activate availabitly-calender-plugin

# run now
wp --path=/var/www/html cron event run ycp_daily_cleanup_event

# ensure scheduled daily (if not already)
wp --path=/var/www/html cron event list | grep ycp_daily_cleanup_event | cat || \
wp --path=/var/www/html cron event schedule ycp_daily_cleanup_event now daily

# verify again
wp --path=/var/www/html cron event list | grep ycp_daily_cleanup_event | cat

# enable the plugin to not show all ladies at once
[ycp_calendar crew_preview="true" crew_limit="4" crew_button_text="MEHR ANZEIGEN"]