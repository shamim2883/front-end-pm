=== Front End PM ===
Contributors: shamim51
Tags: front end pm,front-end-pm,pm,private message,personal message,front end,frontend pm,frontend,message,widget,plugin,sidebar,shortcode,page,email,mail,contact form, secure contact form, simple contact form
Donate link: https://www.shamimsplugins.com/products/front-end-pm-pro/?utm_campaign=wordpress&utm_source=readme_pro&utm_medium=donate
Requires at least: 4.4
Tested up to: 4.9.8
Requires PHP: 5.4
Stable tag: 10.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system from front end.

== Description ==
Front End PM is a Private Messaging system to your WordPress site.This is full functioning messaging system from front end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.

> Some **Front End PM PRO** Features
>
> * Multiple Recipients
> * Only admin
> * Group message
> * Email Piping
> * Read Receipt
> * Email template
> * Announcement Email queue
> * Role to Role Block
>
> [View Details](https://www.shamimsplugins.com/products/front-end-pm-pro/?utm_campaign=wordpress&utm_source=readme_pro&utm_medium=description)

**Some Useful Link**

* [Basic Admin Settings](https://www.shamimsplugins.com/docs/front-end-pm/getting-started/basic-admin-settings/?utm_campaign=wordpress&utm_source=readme&utm_medium=description)
* [Basic Walkthrough](https://www.shamimsplugins.com/docs/front-end-pm/getting-started/basic-front-end-walkthrough/?utm_campaign=wordpress&utm_source=readme&utm_medium=description)
* [Remove minlength](https://www.shamimsplugins.com/docs/front-end-pm/customization/remove-minlength-message-title/?utm_campaign=wordpress&utm_source=readme&utm_medium=description)
* [Remove menu button](https://www.shamimsplugins.com/docs/front-end-pm/customization/remove-settings-menu-button/?utm_campaign=wordpress&utm_source=readme&utm_medium=description)

* If you want paid support you can contact with me through [Front End PM paid support](https://www.shamimsplugins.com/contact-us/?utm_campaign=wordpress&utm_source=readme&utm_medium=description)

[youtube https://www.youtube.com/watch?v=gd6vLF__KnM]

**Features**

* Works through a Page rather than the dashboard. This is very helpful if you want to keep your users out of the Dashboard area!
* Users can privately message one another
* Threaded messages/Individual message
* Ability to embed things into messages like YouTube, Photobucket, Flickr, Wordpress TV, more.
* Notification sound.
* Desktop notification.
* Admins can send a public announcement for all users to see or to perticular role(s).
* Admins can set the max amount of messages a user can keep in his/her box per role basis. This is helpful for keeping Database sizes down.
* Admins can set how many messages to show per page in the message box.
* Admins can set how many user to show per page in front end directory.
* Admins can set will email be sent to all users when a new announcement is published or not.
* Admins can set "to" field of announcement email.
* Admins can set Directory will be shown to all or not.
* Admins can block any user to send private message.
* Admins can set time delay between two messages send by a user.
* Admins can see all other's private message.
* Admins can block all users to send new message but they can send reply of their messages.
* Admins can hide autosuggestion for users.
* There are three types of sidebar widget.
* Users can select whether or not they want to receive messages
* Users can select whether or not they want to be notified by email when they receive a new message.
* Users can select whether or not they want to be notified by email when a new announcement is published.
* Users can block other users.

**Translation**

* please use [wordpress translation](https://translate.wordpress.org/projects/wp-plugins/front-end-pm).

**Github**

[https://github.com/shamim2883/front-end-pm/](https://github.com/shamim2883/front-end-pm/)

== Installation ==
1. Upload "front-end-pm" to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Create a new page.
1. Paste code `[front-end-pm]` for Front End pm under the HTML tab of the page editor.
1. Publish the page add select this page as "Front End PM Page" in settings page of this plugin.

Need more instruction? you can visit [Front End PM](https://www.shamimsplugins.com/contact-us/?utm_campaign=wordpress&utm_source=readme&utm_medium=installation) and contact with me for paid support.

== Frequently Asked Questions ==
= How to update? =
DO NOT UPDATE IN PRODUCTION SITE BEFORE TEST IN STAGING SITE.
Please full backup first before update so that if anything goes wrong you can recover easily.

= Can i use this plugin to my language? =
Yes. this plugin is translate ready. But If your language is not available you can make one. If you want to help us to translate this plugin to your language you are welcome. please use [wordpress translation](https://translate.wordpress.org/projects/wp-plugins/front-end-pm).

= Where to contact for paid support? =
You can visit [Front End PM](https://www.shamimsplugins.com/contact-us/?utm_campaign=wordpress&utm_source=readme&utm_medium=faq) and contact with me for paid support.

== Screenshots ==

1. Responsive
2. Messagebox.
3. Unread message count in website title
4. Front End Directory.
5. Admin settings page.
6. Messagebox settings.
7. Security settings.
8. Appearance settings.

== Changelog ==

= 10.1.4 =

* User can navigate to other messages from a view message page
* Block user now show confirmation dialog
* Drop previous version table if exists
* Message to loading gif target more accurately in css
* FIX: is_settings_page was wrong as first param was not set

= 10.1.3 =

* Menu collapse when using mobile device.
* Message/Announcement date font size decrease.
* FIX: Attachment could not be deleted when Message/Announcement edit.
* FIX: Some CSS was not applied for screen width less then 480px
* FIX: Time delay check was not applied when message sent using shortcode form.

= 10.1.2 =

* Admin can edit Messages and Announcements.
* mgs_id can be passed when insert message.
* fep_filter_message_query_sql added
* message query orderby can be empty to remove orderby.
* we can now get only count from FEP_Message_Query
* FIX: if first_last_name or last_first_name used for name then empty name also returned true.
* FIX: Memory leaks

= 10.1.1 =

* Breaking changes, If you have custom code or template changes for this plugin, make sure they are compatible with current version.
* highly performance improved
* use own database table instead of CPT
* build in caching mechanism
* Some template changes
* no more WP_Post object inside template. Now FEP_Message object
* privacy tab added in settings page.

To view any previous version changelog see [https://www.shamimsplugins.com/docs/category/front-end-pm/changelog/](https://www.shamimsplugins.com/docs/category/front-end-pm/changelog/)

== Upgrade Notice ==

= 10.1.2 =

* Admin can edit Messages and Announcements.
* FIX: Memory leaks

= 10.1.1 =

* Breaking changes, If you have custom code or template changes for this plugin, make sure they are compatible with current version.
* use own database table instead of CPT
