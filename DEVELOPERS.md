# OSEC Developer Readme

Open Source Event calendar is based on All-in-One Event Calendar 2.3.4. 

All 236 classes where rewritten using PHP Namespaces and composer for dependency management. 

Repository with all available sources are at [github.com/digitaldonkey/open-source-event-calendar](https://github.com/digitaldonkey/open-source-event-calendar).

### Composer

Plugin will be lelivered without dev dependencies (--no-dev). 

To use developer tools you need to do ´composer install´. 

## Events, EventEntity and EventInstances

Events are posts (post type `osec_event`) with a (ID) related to `wp_osec_events` table.
Repeatables are based on [RFC-5545 Recurrence concept](https://devguide.calconnect.org/iCalendar-Topics/Recurrences/) 
and will show up in `wp_osec_event_instances` table as event insatnces which only contain start and end date/time and ID. 

(See also [3.8.5.3. Recurrence Rule](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html))

They may turn into child-events when "Edit this instance" (link is on Single page) is used: this will create a derivate Post/Event set to stay on its own.  

To debug event-instances I recommend a SQL view like the following

```sql
CREATE VIEW wp_osec_event_instances_readable_date AS
SELECT id, post_id, `start`, DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d %H:%i') AS 'start_formatted',
       `end`, DATE_FORMAT(FROM_UNIXTIME(`end`), '%Y-%m-%d %H:%i') AS 'end_formatted' FROM wp_osec_event_instances;
```


## Ensure coding standards before commit. 

```
# Codesniffer 
composer run-script phpcs
ddev phpunit


# A few Tests only. 
vendor/bin/phpunit
ddev phpunit

# Before commit
vendor/bin/grumphp run
```

## Testing 

@see [wordpress.org/.../plugin-unit-tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)

### PHPunit 

According to [supported-version-chart](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/#supported-version-chart)

and https://packagist.org/packages/phpunit/phpunit#9.6.20 
we will need 

```
wp scaffold plugin-tests open-source-event-calendar

composer require --dev "phpunit/phpunit:^9.6"
composer require --dev yoast/phpunit-polyfills:"^2.0"

```


### Install Test scripts in ddev 

Ensure you have subversion available 

```
 @file .ddev/config.yaml
 webimage_extra_packages: [subversion]
```

Alternatively... 

```
ddev ssh 
sudo su
apt update
apt install subversion
exit 
```

**initialize once **

```
cd /var/www/html/wp-content/plugins/all-in-one-event-calendar
mkdir -p /var/www/phpunit_wp_cache \
&& export TMPDIR="/var/www/phpunit_wp_cache" \
&& cd /var/www/html/wp-content/plugins/open-source-event-calendar \
&& bin/install-wp-tests.sh phpunit root root db:3306 6.6.1
```


So Finally testing: 

```
ddev ssh 
cd /var/www/html/wp-content/plugins/open-source-event-calendar
 ./vendor/bin/phpunit phpcs --standard=phpcs.xml
 or 
 composer run phpcs
```
