# Mailchimp Subscribe Changelog

## 3.0.3 - 2020-09-02

### Fixed
- Fixed error when subscibing without adding tags (Thanks, @simonkuran).


## 3.0.2 - 2019-06-29

### Added
- Added some default values to `subscribe` service method to make it easier to work with the service directly. 


## 3.0.1 - 2019-06-28

### Added
- Added support for multisite config values using `ConfigHelper::localizedValue` (Thanks, @janhenckens). 


## 3.0.0 - 2019-06-28

> {warning} This is a more complex update than usual, things have been changed and deprecated. Make sure you read this changelog before updating. 

### Added
- Added support for setting marketing permissions when subscribing a member to a GDPR enabled audience. 
- Added support for adding `tags` when subscribing.
- Added support for setting VIP status when subscribing.
- Added `getMemberByEmail` template variable, controller action and service method.
- Added `getAudienceById` template variable, controller action and service method.
- Added `delete` controller action and service method. Implements a `permanent` parameter to permanently delete a member and make it impossible to import again.
- Added `getMarketingPermissionsByEmail` template variable and service method.
- Added `getMemberTagsByEmail` template variable and service method.
- Added `SubscribeResponse`, `MemberResponse` and `AudienceResponse` models.
- Added support for Craft's environment variable syntax (ie '$MY_ENV_VARIABLE') directly in config .

### Changed
- [Audience is the new list](https://mailchimp.com/resources/introducing-your-new-audience-dashboard/). 
- Changed signature of the subscribe service method. It now takes three parameters, where the third is an array of options. **If you access the subscribe controller directly
from your own module/plugin, this is a breaking change, you need to update your code to reflect the change.**
- Changed behavior of the unsubscribe method, it now sets the status to `unsubscribed` instead of deleting a member. Use the `delete` method to delete instead.
- Changed how interests are submitted. It should now be passed as a separate `interests` parameter, and is nested by group title. See example in the docs. **This is a breaking change, if you used interest groups in version 2, you need to update your template code.**
- Changed return type of `getInterestGroups`, it now either returns an array of groups, or null. Errors in logs/debug toolbar. **This is a breaking change, if you used `getInterestGroups` in version 2, you need to update your template code.**
- Changed the return value of the `subscribe`, `unsubscribe` and `delete` service methods to be a `SubscribeResponse`. Use property `action` to check what action triggered the response.

### Removed
- **Removed support for using a pipe-delimited string with multiple list ids as a setting, or passing to service methods or 
template variables.** If you need to subscribe to multiple lists, you should create your own controller that uses the service methods,
and handles input and output accordingly.       

### Deprecated
- Deprecated `list` controller in favor of `audience` controller.
- Deprecated `listId` config setting in favor of `audienceId`.
- Deprecated `lid` parameter in several controller actions in favor of `audienceId`.
- Deprecated `emailtype` parameter in subscribe controller actions in favor of `email_type`.
- Deprecated `mcvars` parameter in subscribe controller actions in favor of `merge_fields`.
- Deprecated `checkIfSubscribed` and `checkIfInList` service method and controller actions in favor of `getMemberByEmail`.
- Deprecated `getListInterestGroups` service method and controller action in favor of `getInterestGroups`.

### Fixed
- Fixed a bug that would make unsubscribe fail in PHP 7.2 or newer due to incorrect empty value.
- Fixed error handling in `subscribe` and `unsubscribe` service methods that would fail if the error was a PHP error.
- Fixed incorrect use of parameters in redirectToPostedUrl.
- Fixed default value of audience id in template variables and improved check in service methods.


## 2.0.5 - 2019-06-28
### Fixed
- Fixed a bug that would create an error in PHP 7.2 due to an incorrect empty value. Backported from 3.0.0.

## 2.0.4 - 2019-02-02
### Fixed
- Fixed bugs in unsubscribe controller action.

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
