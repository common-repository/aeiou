=== AEIOU ===
Contributors: tosend.it
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CRB85JPKCSNZ6
Tags: user, users, import, export, metadata, settings, options, BuddyPress, xprofile, Extended Profile, fields, backup, migration 
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 0.7
License: GPLv2

Advanced Export/Import (Wordpress) Object Users:
Make a full user backup, restore wherever you want! 

== Description ==

AEIOU is a powerfull plugin that export all WordPress users info into an XML file including user settings and options
and allow you to import the same data file into a new WordPress keeping all user informations about the users including
the Buddypress XProfile data of each user.

= Dependencies =

AEIOU requires SimpleXML PHP library to import data. 
Nothing more than wordpress is required for the export.

== Installation ==
1. Install AEIOU either via the WordPress.org plugin directory, or by uploading the files to your server
1. That's it. You're ready to export/import users!


== Frequently Asked Questions ==

It's too simple to do what the plugin does... However, have you got a question? Please ask us on forum. 


= Why my imported XProfile fields becomes all of text type? =

This is because the importer does not keep any information about the field except than Field Name, Field Group and Field Value. 
Any other information is left behind. So the better way to make an import that grant the exact structure for xprofile field is
to create the XProfile Fields on the new Wordpress instance before the import is done then make the import. 

= Why the user are unable to login after I done the Import? =

If you have imported the password field too then It was changed and you have to ensure that the same secret key is used in both old and new wordpress.

== Screenshots ==

We thought that a one page plugin does not requires a screenshot :)

== Changelog ==

= 0.7 (2013-01-22) =

* **New:** Xprofile bugfix granted for backcompatibility
* **Update:** XML file Version updated to 0.7
* **Bugfix:** Xprofile data was wrongly decode from base64
* **Bugfix:** Now the password is imported as is

= 0.6 (2013-01-22) =
* **Update:** Export: Better XProfile detection via ''BP_XProfile_Component''
* **Update:** Import: flushing output on each processed user
* **Bugfix:** Import: Log outputted correctly in the right state (verbose/not verbose)
* **Bugfix:** Import: If user was skipped due existing ''$userId'' was not set
* **Bugfix:** Import: User options if serialized was not deserialized before save and the output was reserialized as string

= 0.5 (2013-01-21) =
* **New:** Added public static method ''outputLog'' callable by the aeiou extensions.
* **New:** Added static object $instance to keep always the instance of AEIOU
* **Update:** Import: In case of error the WP_Error object is threated correctly
* **Update:** Import: Output is generated in log file too (Ensure to have a writable directory log under the plugin)
* **Update:** Import: Changed output messages for better compatibility with text log
* **Bugfix:** Import: Mispelled $usr instead of $user in parser method for password field

= 0.4 (2013-01-21) =
* **Update:** All checks ''if is_serialized then unserialize'' were changed with method ''maybe_unserialize''
* **Bugfix:** Password was never read from the xml file due to the wrong naming

= 0.3 (2013-01-19) =
* **New:** Added action aeiou_export_extra_data
* **New:** Now the export filename has the website name and the current date time.
* **Update:** Update software version

= 0.2 (2013-01-18) =
* **New:** Now you can export and import BuddyPress XProfile data too. If fields or group does not exists they will be created and the field will become of generic type `text`
* **New:** Added action aeiou_before_import_user
* **New:** Added action aeiou_before_import_metadata
* **New:** Added action aeiou_before_import_xprofile
* **New:** Added action aeiou_after_import_user
* **New:** Added filter aeiou_filter_row
* **New:** Added action aeiou_before_import_form
* **New:** Added action aeiou_after_import_form
* **New:** Added action aeiou_before_export_form
* **New:** Added action aeiou_after_export_form
* **New:** Options exported with metadata
* **New:** Impoter allow import of options too 
* **Update:** Better code organization.
* **Update:** Updated Italian translation file.
* **Update:** Updated translation template file.
* **Update:** Added FAQ to this page.
* **Bugfix:** Increased code execution timeout.

= 0.1 (2013-01-05) =
* First plugin release.

== Upgrade Notice ==

Nothing to say.