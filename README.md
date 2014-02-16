wordpress-bitpost
=================

Prevent spam with Bitcoin micropayments!

This plugin is a working prototype. How to use:

1. Register for a free [blockchain.info wallet](https://blockchain.info/wallet). **BE SURE TO REGISTER FOR A NEW ACCOUNT!** (See issues #1 below.)
2. Download the plugin to your WordPress plugin directory.
3. Enter your API guid and password.
4. Enjoy letting your blog visitors use a bitcoin microtransaction to post comments without moderation.

A good chunk of the comment form code we templated from
http://wordpress.org/plugins/twitter-comment-field — props to Corey Freeman for
such reusable code.

#### TODO
* Only show the "verify that you're human with bitcoin" option after the comment has been submitted AND the comment is waiting for moderation. There's no sense in showing this form to a logged-in user, or a commenter that has already been given approval for commenting without moderation. This would also aleviate the security issue of of having to work out some way to validate the BTC address submitted with the form is the same one retrieved through the ajax call (that is run when you click the checkbox), because the commentid would be sent with the ajax call, and the unique bitcoin address created would be associated with that commentid on the server.
* Use the WP Setting API to store the blockchain web wallet GUID and password, negating the need to change those variables in the code.
* Refactor the code — this was hacked together afterall during a hackathon when time limits were in place. Giving DOM elements id's such as "bitcoin" is asking for collisions with other plugins' code.
* Tab order is funky
* Probably rebrand this plugin.
* Add ability to use LTC or other cryptocurrencies?
* Perhaps make the plugin have an option to auto-refund the microtransaction?
* Potentially have a button that a commenter can use to "force refresh" of the payment status (in case they paid after comment was submitted, but don't want to wait the 5 minutes for the WP-Cron system to sync up payments, or maybe WP-Cron isn't working for that partiular server, etc.).

#### Issues
1. We used blockchain.info's web wallet because they provide an API robust enough to allow a call to create a new receive payment adddress. This does cause the web wallet to become a bit unweildy for regular use. We recommend that you create a separate account (and therefore wallet) on blockchain.info to use solely with this plugin. That has the added benefit of not exposing your day-to-day bitcoin finances to hackers that compromise your wordpress installation. (If they do, the whole point of this is the BTC amounts are very small, so you would only lose a miniscule amount of BTC anyway. If it's a concern, or for some reason the amounts do grow to a material amount, regularly log into this account and transfer the funds somewhere else.)

#### Donation
Bitcoin: 19jhbHzRZR1Veh956p9eC2FKupvxXUAYT
