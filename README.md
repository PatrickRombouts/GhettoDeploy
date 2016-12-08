# GhettoDeploy
Quick and dirty code deployment in PHP, crossplatform with an easy setup

# Requirements
- Git
- wget
- ncftpput

Install them in your global directory (or add the paths to %PATH% on windows) or specify the binaries in the general section of pusher.config.php

# Configuration
The configuration should go in pusher.config.php, feel free to add sections under 'sites' as you can select them on the commandline

# Example usage with the example configuration
- php pusher.php --site mysite --backup --deploy
- php pusher.php --site mysite --backup 
- php pusher.php --site mysite --prepare
- php pusher.php --site mysite --branch staging --deploy

# FAQ
- Why would I need this?
Good question. Are you tired of pushing your updated code manually to your (S)FTP client? Do you love automatic deployment but work on your projects instead of having a fulltime job to setup a deployment thingie? Then you need this!

- I'm a windows user and I don't have wget/git/ncftpput!
You could use replacements like http://gnuwin32.sourceforge.net/packages/wget.htm 
NcFTP can be found here http://www.ncftp.com/download/
For Git use the Windows installer.

- Why isn't this in awesome classes?
Because. This is kinda grandfathered code and it works as-is. If i ever have spare time i'll add fancy classes. Feel free to go ahead and push the changes :)