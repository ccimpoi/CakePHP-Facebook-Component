# Facebook CakePHP Page Tab App Component
* Author: Cosmin Cimpoi
* Version: 1.0
* License: MIT
* Website: http://www.binarycrafts.com

## Features

Component that allows a Cake app to work as a Facebook app.
I originaly wrote it to extend an existing Cake app to work on Facebook.
So for now I only have a /fb/* route that points to one controller with a 'fb' prefix in the methods.

## Setup

Most important thing is that this requires Configure::write('Security.level', 'low'); in your app/config/core.php.
This is because otherwise Cake does a ini_set('session.referer_check', $this->host); in CakeSession and that breaks the FB flow.
Put the stuff you find in bootstrap.php in your bootstrap with your values.
