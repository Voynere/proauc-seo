=== Contact Form 7 - amoCRM - Integration ===
Contributors: https://codecanyon.net/user/itgalaxycompany
Tags: amocrm, amocrm leads, business leads, contact form 7, contact form 7 amocrm, form, integration, lead finder, lead management, lead scraper, leads, marketing leads, sales leads.

== Description ==

The main task of this plugin is a send your Contact Form 7 forms directly to your amoCRM account.

= Features =

* Integrate your `Contact Form 7` forms with amoCRM;
* You can choice that your want to generate - lead, incoming lead or contact;
* You can set up each form personally, specify which information you want to get;
* Sending in two modes: immediately when submitting the form or with a slight delay through `WP Cron`;
* Creation of the lead, occurs together with the creation / binding (used existing if there is) of the contact and company. (if their fields are filled);
* Support creating task to lead;
* Custom fields are loaded from the CRM;
* Supports uploading files (links to them are automatically added to the `note`);
* Supports for `utm` params in `URL` to use in custom fields;
* Supports for `roistat_visit` cookie to use;
* Supports for `_ym_uid` cookie to use;
* Supports for `GA Client ID` cookie to use;
* Multiple pipeline support;
* Image previews;
* Integrate unlimited Contact Form 7 forms;
* Sends Google Analytics data with lead to CRM;
* Super easy to set-up;

== Installation ==

1. Extract `cf7-amocrm-lead-generation.zip` and upload it to your `WordPress` plugin directory
(usually /wp-content/plugins ), or upload the zip file directly from the WordPress plugins page.
Once completed, visit your plugins page.
2. Be sure `Contact Form 7` Plugin is enabled.
3. Activate the plugin through the `Plugins` menu in WordPress.
4. Go to the `Contact Form 7` -> `Integration`.
5. Find `Integration with amoCRM` and click the button `Go to setup`.
6. Create integration in your amo - Settings -> Integration.
7. Enter the domain name of your account `amoCRM` (without schema, i.e. http:// or https://).
8. Enter Secret key, Integration ID and Authorization code
9. Save settings.
10. When editing forms your can see the tab `amoCRM`.

== Changelog ==

= 2.4.9 =
Fixed: compatibility with `SG Optimizer`.
Fixed: processing of uploaded files due to changes in CF7 5.4

= 2.4.8 =
Chore: use `webpack` to build assets.
Chore: remove the slash at the beginning and at the end of the domain, as the user can accidentally indicate this.
Fixed: creating an empty contact if the lead is successfully created and all contact fields are empty.
Chore: utm fields in the deal field list.
Fixed: selection of a value in the list, if the value contains an html entities.
Chore: optimization of work with tokens.
Fixed: loss of a link with leads when updating a contact.
Fixed: saving `Google Analytics Tracking ID` without re-specifying all fields.
Feature: added new shortcode - [formTitle]

= 2.3.5 =
Chore: drop old amo sdk.
Fixed: use of `_ga` cookie when sending via `wp cron`.
Fixed: creating a custom field for ga data lead.
Chore: minor improvements in downloading the log through the admin panel.
Fixed: send `incoming lead`.
Feature: send by wp cron (with a delay) or immediately.

= 2.2.2 =
Fixed: send lead sale value.
Chore: processing disabled integration error.
Feature: the ability to indicate that the contact's phone is mobile, by default the phone is set as work.

= 2.1.1 =
Chore: ability to change the redirect link for integration.
Feature: if a responsible person is assigned for the deal, then assign it to the task and contact.
Feature: authorization process in amoCRM changed to oauth2 (using api key is no longer relevant).

= 1.22.3 =
Chore: more flexible resolving user ip.
Fixed: save utm tags method name.
Fixed: send enum fields.
Feature: reset fields cache by button without cron.
Chore: use composer autoloader.
Feature: ability to send a value from any cookie.

= 1.20.3 =
Chore: show only deal stages in select.
Fixed: show checked state `update contact` checkbox.
Chore: apply filter `itglx_cf7amo_lead_fields_before_send` for lead fields in `unsorted`.
Feature: added new filter `itglx_cf7amo_lead_fields_before_send`.
Feature: update for an existing contact (search by phone and email).
Feature: support for processing utm tags when using caching plugins.
Feature: added the ability to log (disabled by default).

= 1.16.2 =
Chore: the list of users id is displayed next to the field of the responsible.
Fixed: contact processing.
Feature: creating a company.
Feature: ability to disable data `ip, user agent, date and time, referrer` in a note.

= 1.14.2 =
Fixed: blank values for missing utm.
Fixed: check maybe no extension `php-mbstring`.
Feature: creating a task for a deal.

= 1.13.3 =
Chore: view enhancement in admin panel.
Fixed: compatibility `GeoIP Detection`.
Feature: Support sending cookie `_ym_uid` to CRM.
Feature: ability to specify one contact id for all leads.
Feature: populate the value of the select and multiselect field from the form field.

= 1.11.1 =
Fixed: create note by type `lead`.
Feature: use any `utm_` params in `URL`.
Feature: multiple responsible user.
Chore: change send form hook to `wpcf7_mail_sent`.

= 1.10.0 =
Feature: Support for `GA Client ID`.
Fixed: special mail tags support.
Feature: Support list and multilist field.
Feature: Support sending cookie `roistat_visit` to CRM.

= 1.7.0 =
Feature: Search for an existing (by phone and email) contact, before creating a new one (by deal type).
Feature: Support for `utm` params in `URL` to use in custom fields.
Fixed: The name of the `unsorted` does not contain the name of the form.
Feature: Support for many pipeline.

= 1.5.0 =
Feature: Support for uploaded files. Links to them are automatically added to the `note`.
Fixed: Check whether plugin `Contact Form 7` is active on the `Network`.

= 1.4.0 =
Changed: send for a `note` to a `contact - now not only `incoming leads`
Added: creating a deal with a contact
Added: send `note` (ip, user agent, date and time, referrer ...) for `contact` (only incoming leads)

= 1.2.2 =
Fixed: save form settings
Fixed: duplicate analytics fields in CRM
Added: Ability to send `incoming lead`

= 1.1.1 =
Fixed: Google Analytics data - utm_source param
Added: Sends Google Analytics data with lead to CRM

= 1.0.0 =
Initial public release
