Wordpress RemoteFS
==================

Detach attachments to remote filesystem.
Stupid simple and minimalistic solution for Heroku, 12factor and CDN support.
And also almost transparent for WP, plugins and for user.

## Purpose
User for making Wordpress [12factor](http://12factor.net/)-compatible and Heroku-friendly.
Attachments are automatically put to remote filesystem and not stored in local filesystem.

## Features
* supports FTP and FTPs as remote FS
* easy extendable
* configurable via options page or via environment variables
* auto put attachments to remote
* attachment image sizes support
* [types](https://wordpress.org/plugins/types/) image resizing support
* [image editor](http://en.support.wordpress.com/images/image-editing/) support
* prevent file duplicates
* auto delete files

## Roadmap
* SFTP support
* Amazon S3 support
* other services
* better logging and error handling

## Contributing
Feel free to make pull requests and issues.
