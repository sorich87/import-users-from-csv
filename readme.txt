=== Import Users from CSV ===
Contributors: sorich87
Tags: user, users, csv, batch, import, importer, admin
Requires at least: 3.1
Tested up to: 3.6
Stable tag: 1.0.0

Import users from a CSV file into WordPress

== Description ==

I needed to batch import users into WordPress but I didn't find any plugin which would import all the user data fields as well as user meta.

This plugin allows you to import users from an uploaded CSV file. It will add users with basic information as well as meta fields and user role.

You can also choose to send a notification to the new users and to display password nag on user login.

[Check out my other free plugins.](http://profiles.wordpress.org/users/sorich87/)

= Features =

* Imports all users fields
* Imports user meta
* Update existing users by specifying ID field
* Allows setting user role
* Sends new user notification (if the option is selected)
* Shows password nag on user login (if the option is selected)

For feature request and bug reports, [please use the forums](http://wordpress.org/tags/import-users-from-csv?forum_id=10#postform).
Code contributions are welcome [on Github](https://github.com/sorich87/Import-Users-from-CSV).

== Installation ==

For an automatic installation through WordPress:

1. Go to the 'Add New' plugins screen in your WordPress admin area
1. Search for 'Import Users from CSV'
1. Click 'Install Now' and activate the plugin
1. Upload your CSV file in the 'Users' menu, under 'Import From CSV'


Or use a nifty tool by WordPress lead developer Mark Jaquith:

1. Visit [this link](http://coveredwebservices.com/wp-plugin-install/?plugin=import-users-from-csv) and follow the instructions.


For a manual installation via FTP:

1. Upload the `import-users-from-csv` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' screen in your WordPress admin area
1. Upload your CSV file in the 'Users' menu, under 'Import From CSV'


To upload the plugin through WordPress, instead of FTP:

1. Upload the downloaded zip file on the 'Add New' plugins screen (see the 'Upload' tab) in your WordPress admin area and activate.
1. Upload your CSV file in the 'Users' menu, under 'Import From CSV'

== Frequently Asked Questions ==

= How to use? =

Click on the 'Import From CSV' link in the 'Users' menu, choose your CSV file, choose if you want to send a notification email to new users and if you want the password nag to be displayed when they login, then click 'Import'.

Each row in your CSV file should represent a user; each column identifies user data or meta data.
If a column name matches a field in the user table, data from this column is imported in that field; if not, data is imported in a user meta field with the name of the column.

Look at the example.csv file in the plugin directory to have a better understanding of how the your CSV file should be organized.
You can try importing that file and look at the result.

== Screenshots ==

1. User import screen

== Changelog ==

= 1.0.0 =
* Fixed bug where importing fields with "0" value doesn't work
* Added option to update existing users by username or email

= 0.5.1 =
* Removed example plugin file to avoid invalid header error on
installation

= 0.5 =
* Changed code to allow running import from another plugin

= 0.4 =
* Switched to RFC 4180 compliant library for CSV parsing
* Introduced IS_IU_CSV_DELIMITER constant to allow changing the CSV delimiter
* Improved memory usage by reading the CSV file line by line
* Fixed bug where any serialized CSV column content is serialized again
on import

= 0.3.2 =
* Fixed php notice when importing

= 0.3.1 =
* Don't process empty columns in the csv file

= 0.3 =
* Fixed bug where password field was overwritten for existing users
* Use fgetcsv instead of str_getcsv
* Don't run insert or update user function when only user ID was
provided (performance improvement)
* Internationalization
* Added display name to example csv file

= 0.2.2 =
* Added role to example file
* Fixed bug with users not imported when no user meta is set

= 0.2.1 =
* Added missing example file
* Fixed bug with redirection after csv processing
* Fixed error logging
* Fixed typos in documentation
* Other bug fixes

= 0.2 =
* First public release.
* Code cleanup.
* Added readme.txt.

= 0.1 =
* First release.

== Upgrade Notice ==

= 0.5.1 =
* Installation error fix.

= 0.5 =
* Code improvement for easier integration with another plugin.

= 0.4 =
* RFC 4180 compliance, performance improvement and bug fix.

= 0.3 =
Bug fix, performance improvement and internationalization.

= 0.2.2 =
Fix bug with users import when no user meta is set.

= 0.2.1 =
Various bug fixes and documentation improvements.

= 0.2 =
Code cleanup. Added readme.txt.

= 0.1 =
First release.
