DCRT
====
#### Dropbox Conflict Resolving Tool

This PHP script allows you to scan for conflicted files in your Dropbox folder and automatically resolve any conflicts. Currently, the only supported resolving method is KEEP_LATEST, which always keeps the latest version of a file and deletes any others.

#### WARNING: This script is extremely untested and may behave harmful. Use it at your own risk!

#### Also keep in Mind
* Absolutely no backups will be made
* Don't use this script on shared folders, since it will delete any conflicting files for everyone who has access to that folder.

## Usage

```
Usage: php conflict.php <command> [path]

Commands:
  status        Display a list of all conflicted files (read-only)
  resolve       Start the automatic conflict resolving process (be careful!)
```
