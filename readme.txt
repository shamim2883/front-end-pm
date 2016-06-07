=== Front End PM ===
Contributors: shamim51
Tags: front end pm,front-end-pm,pm,private message,personal message,front end,frontend pm,frontend,message,widget,plugin,sidebar,shortcode,page,email,mail,contact form, secure contact form, simple contact form,akismet check,akismet
Donate link: https://www.paypal.me/shamimhasan
Requires at least: 4.4
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system from front end.

== Description ==
Front End PM is a Private Messaging system to your WordPress site.This is full functioning messaging system from front end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.

**Some Useful Link**

* [Front End PM](http://frontendpm.blogspot.com/2015/03/front-end-pm.html)
* [How to install](http://frontendpm.blogspot.com/2015/03/how-to-install.html)
* [Changelog](http://frontendpm.blogspot.com/2015/03/changelog-of-front-end-pm.html)
* [FAQ](http://frontendpm.blogspot.com/2015/03/frequently-asked-questions.html)
* [Front End PM actions](http://frontendpm.blogspot.com/2015/03/front-end-pm-actions.html)
* [Front End PM filters](http://frontendpm.blogspot.com/2015/03/front-end-pm-filters.html)
* [Front End PM remove action](http://frontendpm.blogspot.com/2015/03/front-end-pm-remove-action.html)

* If you want paid support you can contact with me through [Front End PM paid support](http://frontendpm.blogspot.com/p/contact-us.html)

[youtube http://www.youtube.com/watch?v=SHKHTIlzr3w]

**Features**

* Works through a Page rather than the dashboard. This is very helpful if you want to keep your users out of the Dashboard area!
* Users can privately message one another
* Threaded messages
* BBCode in messages
* Ability to embed things into messages like YouTube, Photobucket, Flickr, Wordpress TV, more.
* Admins can create a page for "Front End PM" by one click (see Installation for more details).
* Admins can send a public announcement for all users to see
* Admins can set the max amount of messages a user can keep in his/her box. This is helpful for keeping Database sizes down.
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

**FEP Contact Form**

* "FEP Contact Form" is now a separate plugin from version 3.1, so that you can use that plugin with "Front End PM" or without.
* Download [FEP Contact Form](https://wordpress.org/plugins/fep-contact-form/) from wordpress.

**Translation**

* German translation thanks to palatino.
* Simplified Chinese thanks to Changmeng Hu.


You can visit [Front End PM](http://frontendpm.blogspot.com/p/contact-us.html) and contact with me for paid support.

== Installation ==
1. Upload "front-end-pm" to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Create a new page.
1. Paste code `[front-end-pm]` for Front End pm under the HTML tab of the page editor.
1. Publish the page.

Or you can create page for Front End PM by one click. Go to **Front End PM>Instruction** give a Title(required) for Front End PM page and Slug(optional) then click "Create Page". It will automatically create a page for your Message. If you keep Slug field blank, slug of page will be automatically created based on your given title.

Need more instruction? you can visit [Front End PM](http://frontendpm.blogspot.com/p/contact-us.html) and contact with me for paid support.

== Frequently Asked Questions ==
= Can i use this plugin to my language? =
Yes. this plugin is translate ready. But If your language is not available you can make one. If you want to help us to translate this plugin to your language you are welcome.

= Where is "FEP Contact Form" which was added from version 2.0? =
"FEP Contact Form" is now a separate plugin from version 3.1, so that you can use that plugin with "Front End PM" or without. 

= Why code comments is less? =
I am very busy with my job. In my leisure i code for plugins. If you want to help to add comments to the code you are welcome.(only add comments and line space change, no code change. if you want code change you can suggest me).

= Where to contact for paid support? =
You can visit [Front End PM](http://frontendpm.blogspot.com/p/contact-us.html) and contact with me for paid support.

== Screenshots ==

1. Front End pm.
2. Button widgets.
3. Text widgets.
4. Front End Directory.
5. Admin settings page.
6. Front End PM setup instruction.
7. FEP Contact Form Settings
8. FEP Contact Form Settings 2
9. FEP Contact Form

== Changelog ==

= 3.3 =

* Critical Security update. Found cross-site scripting (XSS) vulnerability and fixed.
* Please update as soon as possible.

= 3.2 =

* Security update. Admin could accidently delete all messages from database. fixed.
* Now header notification is in real time by ajax.
* FEP Contact Form link added.
* Translation issues fixed.
* Admin page changed.
* Some CSS and JS bug fixes.
* Other some minor bug fixes.
* POT file updated.

= 3.1 =

* Many useful hooks are added. so anyone can change almost anything of this plugin without changing any core code of this plugin.
* Message and announcement editor now support Wp Editor.
* Now code can be posted between backticks.
* Multiple attachment in same message.
* Now you can add multiple attachment in announcement also.
* Attachment size, amount configurable.
* Now show any new message or new announcement notification in header (configurable).
* Announcement now reset after seen. User can also delete announcement from their announcement box (only for him/her).
* Now admin can see how many users seen that announcement.
* Use of transient increases so less db query.
* Now Widgets can be used multiple times.You can cofigure widgets now. You can also use hooks.
* Now use wordpress ajax for autosuggestion when typing recipent name.
* Custom CSS support. admin can add CSS from backend to add or override this plugins CSS.
* Now script and plugin files added only when needed.
* You can also add or remove any file of this plugin using hook.
* Messages between two users can be seen.
* New options are added in admin settings.
* Some CSS and JS bug fixes.
* Other some minor bug fixes.
* POT file updated.

= 2.2 =

* New option to send attachment in both pm and contact form.
* Attachment in stored in front-end-pm folder inside upload folder and contact form attachment is stored inside front-end-pm/contact-form folder.
* Message count in header bug fixes.
* Security bug fixes where non-admin user could see all messages.

= 2.1 =

* IP blacklist now support range and wildcard.
* Email address blacklist,whitelist.
* Time delay for logged out visitors also.
* Double name when auto suggestion off fixes.
* Department name bug fixes.
* Other some small bug fixes.

= 2.0 =

* Added a secure contact form.
* Manual check of contact message.
* AKISMET check of contact message.
* Can configure CAPTCHA for contact message form.
* Separate settings page for contact message.
* Can select department and to whom message will be send for that department.
* Can set separate time delay to send message of a user via contact message.
* Reply directly to Email address from front end.
* Send Email to any email addresss from front end.
* Use wordpress nonce instead of cookie.
* All forms nonce check before process.
* Added capability check to use messaging.
* Capability and nonce check before any action.
* Security Update.
* Some css fix.
* POT file updated.

= 1.3 =

* Parent ID and time check server side.
* Escape properly before input into database.
* Some css fix.
* Email template change.
* Recommended to update because some core functions have been changed and from this version (1.3) those functions will be used.

= 1.2 =

* Using display name instead of user_login to send message (partially).
* Send email to all users when a new announcement is published (there are options to control).
* Now admins can set time delay between two messages send by a user.
* Bug fixes in bbcode and code in content when send message.
* Security fixes in autosuggestion.
* New options are added in admin settings.
* No more sending email to sender.
* Javascript fixes.

= 1.1 =

* Initial release.

== Upgrade Notice ==

= 3.3 =

* Critical Security update. Found cross-site scripting (XSS) vulnerability and fixed.
* Please update as soon as possible.

= 3.2 =

* Security update. Admin could accidently delete all messages from database. fixed.
* Now header notification is in real time by ajax.
* FEP Contact Form link added.
* Translation issues fixed.
* Admin page changed.
* Some CSS and JS bug fixes.
* Other some minor bug fixes.
* POT file updated.

= 3.1 =

* Many useful hooks are added. so anyone can change almost anything of this plugin without changing any core code of this plugin.
* Message and announcement editor now support Wp Editor.
* Now code can be posted between backticks.
* Multiple attachment in same message.
* Now you can add multiple attachment in announcement also.
* Attachment size, amount configurable.
* Now show any new message or new announcement notification in header (configurable).
* Announcement now reset after seen. User can also delete announcement from their announcement box (only for him/her).
* Now admin can see how many users seen that announcement.
* Use of transient increases so less db query.
* Now Widgets can be used multiple times.You can cofigure widgets now. You can also use hooks.
* Now use wordpress ajax for autosuggestion when typing recipent name.
* Custom CSS support. admin can add CSS from backend to add or override this plugins CSS.
* Now script and plugin files added only when needed.
* You can also add or remove any file of this plugin using hook.
* Messages between two users can be seen.
* New options are added in admin settings.
* Some CSS and JS bug fixes.
* Other some minor bug fixes.
* POT file updated.

= 2.2 =

* New option to send attachment in both pm and contact form.
* Attachment is stored in front-end-pm folder inside upload folder and contact form attachment is stored inside front-end-pm/contact-form folder.
* Message count in header bug fixes.
* Security bug fixes where non-admin user could see all messages.

= 2.1 =

* IP blacklist now support range and wildcard.
* Email address blacklist,whitelist.
* Time delay for logged out visitors also.
* Double name when auto suggestion off fixes.
* Department name bug fixes.
* Other some small bug fixes.

= 2.0 =

* Added a secure contact form.
* Manual check of contact message.
* AKISMET check of contact message.
* Can configure CAPTCHA for contact message form.
* Separate settings page for contact message.
* Can select department and to whom message will be send for that department.
* Can set separate time delay to send message of a user via contact message.
* Reply directly to Email address from front end.
* Send Email to any email addresss from front end.
* Use wordpress nonce instead of cookie.
* All forms nonce check before process.
* Added capability check to use messaging.
* Capability and nonce check before any action.
* Security Update.
* Some css fix.
* POT file updated.

= 1.3 =

* Parent ID and time check server side.
* Escape properly before input into database.
* Some css fix.
* Email template change.
* Recommended to update this plugin because some core functions (this plugin) have been changed and from this version (1.3) those functions will be used.

= 1.2 =

* Using display name instead of user_login to send message (partially).
* Send email to all users when a new announcement is published (there are options to control).
* Now admins can set time delay between two messages send by a user.
* Bug fixes in bbcode and code in content when send message.
* Security fixes in autosuggestion.
* New options are added in admin settings.
* No more sending email to sender.
* Javascript fixes.

= 1.1 =

* Initial release.