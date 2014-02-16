wordpress-bitpost
=================

Prevent spam with Bitcoin micropayments!

This plugin is a working prototype. How to use:

1. Register for a free blockchain.info wallet.
2. Download the plugin to your WordPress plugin directory.
3. Enter your API guid and password.
4. Enjoy letting your blog visitors use a bitcoin microtransaction to post comments without moderation.

A good chunk of the comment form code we templated from
http://wordpress.org/plugins/twitter-comment-field — props to Corey Freeman for
such reusable code.

#### TODO
* Work out some way to validate the BTC address submitted with the form is the same one retrieved through the ajax call (that is run when you click the checkbox).
* Use the WP Setting API to store the blockchain web wallet GUID and password, negating the need to change those variables in the code.
* Refactor the code — this was hacked together afterall during a hackathon when time limits were in place. Giving DOM elements id's such as "bitcoin" is asking for collisions with other plugins' code.
* Tab order is funky
* Probably rebrand this plugin.
* Add ability to use LTC or other cryptocurrencies?
* Perhaps make the plugin have an option to auto-refund the microtransaction?
* Potentially have a button that a commenter can use to "force refresh" of the payment status (in case they paid after comment was submitted, but don't want to wait the 5 minutes for the WP-Cron system to sync up payments, or maybe WP-Cron isn't working for that partiular server, etc.).

#### Donation
Bitcoin: 19jhbHzRZR1Veh956p9eC2FKupvxXUAYT
