=== AnyBackup - Backup | Preview | Restore ===
Contributors: 255bits
Tags: backup, migration, migrate, disaster recovery, restore, back up, archive, database, files, backups
Requires at least: 3.5.2
Tested up to: 4.2.2
License: MIT
Stable tag: 1.3.0
License URI: http://opensource.org/licenses/MIT
Donate Link: https://anybackup.io
WC requires at least: 2.0
WC tested up to: 2.3.9

Simple backup, restore, and testing.  Free personal plan.

== Description ==

[AnyBackup](https://anybackup.io) protects you from data loss.

*  Easily test your backups before there's an emergency with Live Preview.
*  Free for personal use.
*  No limits on restores, backups, or storage space.


Additionally, we support the following:

*  Incremental backups
*  Zero-downtime restore
*  One click migrations for switching hosts
*  Works on low-memory VPS shared environments
*  Premium support includes a direct line to the engineers

All data is stored on our secure servers in at least 3 different geological locations across the world.

For developers, our backup process is detailed in the [AnyBackup API](https://anybackup.io/api/).

== Installation ==

* Install/Activate the plugin
* Click the AnyBackup tab in your admin panel
* Click 'Enable Backups'

== Frequently Asked Questions ==

* Q: How long is your free trial?
* A: It is not limited.  We only allow you to backup and restore with 1 site, however.

* Q: Can I migrate my data with AnyBackup?
* A: Yes!  Just login to your AnyBackup account, select your site and backup, and click 'Restore'.

* Q: Can I preview what this will do to my site?
* A: Yes.  Paid users can use the 'live preview' to test backups, or preview what their site will look like after a restore.

* Q: How long will my site be down for?
* A: Practically zero downtime.  We use an exclusive "hot swap" method to make sure your site is always responsive.

* Q: How long do you keep my backups?
* A: Until you actively cancel.  We do not impose date limitations.

* Q: How much storage space do I get?
* A: All plans come with unlimited storage space.  We do not impose size limitations.


== Screenshots ==

* A backup running
* A backup finalizing
* Login screen
* Showing a backup
* Restoring a site

== Docs & Support ==

Support is available in 3 forms:

* Through our <a href='https://anybackup.io/help'>help documentation</a>
* On the support forum
* Within the app under the 'Support' link

= Requirements =

* Supports PHP 4 and above
* Supported on Wordpress 4 and above
* Multisite and BuddyPress support available for premium users(contact us)


== Services used ==

AnyBackup for Wordpress uses:

* [AnyBackup API](https://anybackup.io/api/) to synchronize backup/restore data.  We backup and encrypt all of your site's data through our documented api.
* [Stripe Checkout](http://stripe.com) used for billing.  This is only included on the AnyBackup for Wordpress admin page.


== Changelog ==

=1.3=

* Better interface

=1.2.22=

* Improved error detection

=1.2.21=

* Support Wordpress 4.2.2

=1.2.20=

* Fix backup metadata

=1.2.19=

* Woocommerce integration

=1.2.18=

* Support Wordpress 4.2.1

=1.2.17=

* Authenticate when downloading restored files.

=1.2.16= 
* Support Wordpress 4.2

=1.2.15=

* Substantial speed increases across the board by using persistent http connections.

=1.2.14=

* Login/Registration improvements

=1.2.13=

* New feature, edit your backup names

=1.2.12=

* Add gzip compression to API calls

=1.2.11=

* Fixed several edge-case bugs where changing a file while syncing would cause a backup to fail

=1.2.10=

* More resilence to network partitions and other transient failures

=1.2.9=

* Documentation - help section
* Reduce plugin size

=1.2.7=

* Security fix - check for user access before initiating restore from ajax call

=1.2.6=

* Screenshots & UI

=1.2.5=

* Backup thread polls every 5 minutes to account for php killing threads randomly

=1.2.4=

* Ignore unreadable files

=1.2.3=

* Support for WP 3.5.2+

=1.2.2=

* Loading moment

=1.2.1=

* Activation notice dismissable

=1.2.0=

* Backups start on activation
* Better error reporting to user
* Better onboarding experience

=1.1.15=

* Better support for user-initiated backups

=1.1.14=

* Fixed timezone for backup list

=1.1.13=

* Fixed more bugs relating to live preview

=1.1.11=

* Several minor bug fixes

=1.1.8=

* Backup frequency more accurately reflected.

=1.1.6=

* Better support for symlinks on Linux, OSX, and FreeBSD

=1.1.5=

* Various minor bug fixes

=1.1.0=

* Support for Windows
* Fixed an issue preventing some backups from completing.

=1.0.7=

* Style updates for initial Wordpress Plugin Directory listing

=1.0.3=

* Initial public release
* New logo
* Improved file backups

=1.0=

* Initial plugin
* Full git repository can be viewed at https://bitbucket.org/255bits/anybackup-wordpress

== Upgrade Notice ==

None
