# SVB Mailing
This bundle has been developed in order to send mails from multiple systems without writing a single line of html nor care about any localizations.

## How to install/configure
1. `composer require svb/mailing`
1. Make sure the bundle is correctly registered in either `config/bundles.php` (sf4/5) or `app/AppKernel.php:registerBundles` (sf3) 
1. Add a configuration for the bundle like this:
   ```
   svb_mailing:
       mailjet_api_key: '%env(resolve:MAILJET_API_KEY)%'
       mailjet_api_secret: '%env(resolve:MAILJET_API_SECRET)%'
   ```
   (edit this depending on how the secrets are loaded: parameters, environment vars, etc)

## How to use
1. Inject the service `svb_mailing.mailer` (SVB\Mailing\Mailer when using the sf autowiring)
1. Create one or more mail objects using one of the many mail templates located within the namespace `SVB\Mailing\Mail\`
1. Pass your object(s) into the sendMails method of the `svb_mailing.mailer` service
1. You mail should either been sent, or you should have got an exception what to do

## Adding new Mail Templates
1. Create a new class implementing MailInterface (AbstractMail to make your life a bit easier) within src/mails  
_Hint: Mail Templates do not necessarily need to be in one folder, use sub directories to order them!_
1. Register the Template as as service and use the `svb_mailing.mail` tag
