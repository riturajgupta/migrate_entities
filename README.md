
INTRODUCTION
-------------

Migrate Entities module allows end user to import content from csv. Currently we have integrated migration only from csv and only for content. This module is depended on Simple node Importer module. End user needs to upload the content in CSV format and map the columns of CSV file to the fields which they want to associate with that column. Mapping column's functionality is provided by Simple Node Importer module.

We have integrated migration API. so if you are adding wrong data in CSV then it will get migrated. Suppose you are migrating email field and you have entered wrong email id in CSV like "abc@" then this will get migrated as it is.


SUMMARY
--------

* Module allows end user to import entity (node) using CSV file.
* Module allows end user to map csv columns to fields using Flexible Mapping UI.(provided by Simple node Importer module).


REQUIREMENTS
-------------

None.


INSTALLATION
---------------

* Install as usual, see http://drupal.org/node/895232 for further information.


CUSTOMIZATION
---------------

None


TROUBLESHOOTING
-----------------

None.


FAQ
---------------

None.