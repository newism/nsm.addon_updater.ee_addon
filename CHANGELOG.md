NSM Addon Updater - Changelog
=============================

v1.3.0
------

* [fix] Improved error handling for problematic versions.xml URLs
* [enhancement] Results table styled using NSM Morphine classes and is now column sortable
* [enhancement] Added boolean flag `$hide_incompatible` in `acc.nsm_addon_updater.php` to hide add-ons that are incompatible with NSM Addon Updater
* [enhancement] Added boolean flag `$hide_uptodate` in `acc.nsm_addon_updater.php` to hide add-ons that are up-to-date
* [enhancement] Caching functions rewritten to use CodeIgniter file helpers

v1.2.1
------

* [fix] Accessory process calls 'exit' to prevent further action

v1.2.0
------

* [enhancement] Update feeds now loaded via AJAX request

v1.1.1
------

* [fix] Problematic cache files now deleted and regenerated properly

v1.1.0
------

* [enhancement] Updated style for Morphine 2.0
* [bug fix] Check for latest version, not the next version after the currently installed version

v1.0.1
------

* [bug fix] - Error checking for servers that do not support CURLOPT_FOLLOWLOCATION

v1.0.0
------

* Initial Release