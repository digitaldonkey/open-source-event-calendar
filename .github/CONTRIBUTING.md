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

They may turn into child-events when "Edit this instance" (link is on Single page) is used: this will create a derivate Post/Event. @see "Base recurrence event" and "Modified recurrence events" Editor Tabs.  

event instances 
- Are references to the base (reoccouring) event.
- Are stored wp_osec_event_instances table using Unix datestamps
- To debug event-instances its helpful to use a SQL view

```sql
CREATE VIEW wp_osec_event_instances_readable_date AS
SELECT id, post_id, `start`, DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d %H:%i') AS 'start_formatted',
       `end`, DATE_FORMAT(FROM_UNIXTIME(`end`), '%Y-%m-%d %H:%i') AS 'end_formatted' FROM wp_osec_event_instances;
```

## Ensure coding standards before commit. 

We have a PHP (require_dev) based toolset.
Project has been set up using ddev. All scripts should be running stable in ddev using provided config.

### Test & Release pipline 
@.circleci/config.yml
https://app.circleci.com/pipelines/github/digitaldonkey/open-source-event-calendar

```
# Codesniffer 
ddev composer run-script phpcs

# Non blocking sniffs
ddev composer run phpcs-warnings

ddev phpunit

# A few phpunit tests @see phpunit.xml 
vendor/bin/phpunit
ddev phpunit

# Altogether @see grumphp.yml
vendor/bin/grumphp run
```

#### Using grumphp inside ddev.

Edit local `grumphp.yml`

```
EXEC_GRUMPHP_COMMAND: ddev exec -d  "/var/www/html/wp-content/plugins/open-source-event-calendar"
```
Requires reinit `ddev exec grumphp git:init` which will reconfigure the git pre-commit hook.

@see [configuring-grumphp-ddev](https://www.patrickvanefferen.nl/blog/configuring-grumphp-ddev)

# osec_recompile_templates

Enable debug mode `define('OSEC_DEBUG', true);` and add get param  
yoursite.com?osec_recompile_templates=TRUE


## Testing 

@see [wordpress.org/.../plugin-unit-tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)

### PHPunit updates version challenge

According to [supported-version-chart](https://make.wordpress.org/core/handbook/references/phpunit-compatibility-and-wordpress-versions/#supported-version-chart)

and https://packagist.org/packages/phpunit/phpunit#9.6.20 
we will need 

```
wp scaffold plugin-tests open-source-event-calendar

composer require --dev "phpunit/phpunit:^9.6"
composer require --dev yoast/phpunit-polyfills:"^2.0"

```

Current requirements are in git and ready after `composer install`.  


## Set up development  

You will need the development version of the plugin.
```
cd wp-content/plugins/
git clone git@github.com:digitaldonkey/open-source-event-calendar.git
cd open-source-event-calendar
ddev composer install
```

#### Ensure you have subversion available 

The Setup for WordPress Test scripts require subversion client (svn).

```
 # Add svn in ddev docker image
 @file .ddev/config.yaml
 webimage_extra_packages: [subversion]
 # localy will work too.
 brew install svn
 apt install svn
```

#### Initialize once

```
# in docker
ddev ssh 
PHP_TMP=$($(command -v php) -r 'echo  sys_get_temp_dir();') \
&& cd /var/www/html/wp-content/plugins/open-source-event-calendar \
&& bin/install-wp-tests.sh phpunit root root db:3306

# localy
cd wp-content/plugins/open-source-event-calenda
bin/install-wp-tests.sh phpunit root root 127.0.0.1:32805
# Port number you could get 
ddev status
```

## phpcs testing 
```
ddev ssh 
cd /var/www/html/wp-content/plugins/open-source-event-calendar
 ./vendor/bin/phpcs --standard=phpcs.xml --runtime-set testVersion 8.2-

 # alternatively  
 
 composer run phpcs
 
 # locally 
 ddev run-script phpcs
```
runtime-set testVersion 8.2 is overriding WordPress default minimum version requiremets. Explicitly set to override WP defaults in `plugin-check.ruleset.xml`.

** plugin-check.ruleset.xml** comes from [WordPress/plugin-check](https://api.github.com/repos/WordPress/plugin-check). The latest version you can download using `bin/get-latest-plugin-review-phpcs-rulesets.sh`.

## Running phpunit

After "--> Initialize once" above:

```
ddev phpunit

# run single test
ddev phpunit --filter test_get_cache_object  ./tests/Unit/Cache/CachePathTest.php
```

```
# In the Docker container
ddev ssh 
cd wp-content/plugins/open-source-event-calendar
vendor/bin/phpunit
```

## integration Testing with mocha
@see integration_tests/package.json

```
cd open-source-event-calendar/integration_tests
nvm install
npm install
npm run test
```
For for this tests the plugin must be initially disabled and all tables clean (use OSEC_UNINSTALL_PLUGIN_DATA)
