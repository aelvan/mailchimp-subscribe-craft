# Mailchimp Subscribe Changelog

## 2.0.3 - 2018-12-16
### Added
- Added new unsubscribe service method and controller action (thanks, @engram-design).
- Added new checkIfInList service method and template variable (thanks, @engram-design).  

### Fixed
- Fixed checkIfSubscribed to not only check if a user is in a list, but also if the user is subscribed (thanks, @engram-design).
- Fixed incorrect handling of settings list id (fixes #67) (thanks, @engram-design).
- Fixed README example (fixes #67). 

## 2.0.2 - 2018-05-14
### Fixed
- Fixed a bug that could occur in PHP 7.2 if no merge vars was defined (Thanks, @janhenckens!). 
- Fixed a bunch of readme bugs.

## 2.0.1 - 2018-04-19
### Fixed
- Fixed composer requirements

## 2.0.0 - 2017-12-07
### Added
- Initial Craft 3 release
