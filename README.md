# SVB Mailing
This bundle has been developed in order to send mails from multiple systems without writing a single line of html nor care about any localizations.

## How to install/configure
1. `composer require svb/mailing`
1. Make sure the bundle is correctly registered in either `config/bundles.php` (sf4/5) or `app/AppKernel.php:registerBundles` (sf3) 
1. Add a configuration for the bundle like this:
   ```
   svb_mailing:
      tries_count: 5 # number of resend tries of a mail before the delivery is set to failed (default 5)
      database:
         url: 'mysql://root:root@mysql/svb_mailing'
      connectors:
         # see connectors config
   ```

## How to use
1. Inject the service `svb_mailing.mailer` (SVB\Mailing\Mailer when using the sf auto wiring)
1. Create one or more mail objects using one of the many mail templates located within the namespace `SVB\Mailing\Mail\`
1. Pass your object(s) into the sendMails method of the `svb_mailing.mailer` service
1. You mail should either been sent, or you should have got an exception what to do

## Adding Mail Templates
1. Create a new class implementing MailInterface (AbstractMail to make your life a bit easier)  
_Hint: Mail Templates do not necessarily need to be in one folder, use sub directories to order them!_
1. Register the Template as a service and use the `svb_mailing.mail` tag (probably both already done through the sf auto wiring)

## Asynchronous resending of failed messages
Since most provider handle sent messages using message queues, most responses for a "send" request will return "200 OK".
The mailing bundle provides easy functionality for checking message states and resending failed messages using a database
to store all messages and their states. Since the worker for this needs to be executed asynchronously, we implemented a
symfony command for this: `bin/console svb:mailing:worker`. Make sure this command is executed at least once every hour
(the more frequent it's executed the better!) to ensure your mails getting resent, and the states are updated correctly. 
