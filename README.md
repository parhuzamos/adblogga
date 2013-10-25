adblogga
========

Color coded adb (Android Debug Bridge) logcat output with on-the-fly configurable filters for any part of the log entry. It's a terminal program, you shouldn't use your mouse ;).

Why?
----

* set and forget: automatically reconnects if a device is removed, no need to restart from the command line
* easy to pause the output (press Enter => you're in command mode, no messages displayed but buffered, press Enter again to exit and continue and flush the buffered messages)
* easy to enter a visual break (press Enter two times => a line is displayed in the terminal)
* easy to clear the screen (press Enter, then "c" then Enter)
* easy to log only some messages (press Enter, type "+WifiService" then Enter => **only** message containing "WifiService" will be displayed, repeat this to add even more messages)
* easy to exclude some message (press Enter, type "-dalvikvm" then Enter => no message containing "dalvikvm" displayed, repeat this to fine to the exclude filter)
* specify a package name on the command line (-p<package-name>) to show only it's messages (more include and exclude entries can be added of course)
* profiles support (collection of include and exclude definitions, package/process name to filter messages)

[![A picture is worth thousand words.](http://parhuzamos.github.io/adblogga/images/20130728225034-420481645.png)](#)

For the base concept thanks to Jeff Sharkey and the coloredlogcat.py ( http://bit.ly/15XoV8U ).


Install!
--------

Choose a directory for the installation and clone the repo:

    git clone https://github.com/parhuzamos/adblogga.git

Switch to the repo:

	cd adblogga

Create a symlink into your favorite directory in the path (eg ~/bin):

	ln -s `pwd`/adblogga.php ~/bin/adblogga

Use it:

	adblogga


How?
----

Start from the command line (remember, you have to start only once, it can be running for days/weeks, no restart required!):

	# show messages from "com.example.application1" package/process, also load include&exclude filters from the profile "controls"
	adblogga -pcom.example.application1 -Pcontrols

**Optional command line switches:**

* -p"packagename"
    
    Show message only for the given package/process:
```
# each syntax is the same
adblogga -pcom.example.application1
adblogga -p"com.example.application1"
adblogga -p=com.example.application1
adblogga -p="com.example.application1"
```

* -P"profile"

    Load settings (include, exclude, package/process filter) from the given profile, changes are also saved to this profile:
```
adblogga -Pdefault
```

* -c"string"

    If *string* occours in any message, the screen is cleared. Usefull for catching application start to clear the screen.
```
adblogga -c"Starting com.example.application1"
```

Soon...
* more command line parameters (--clear)
* include
* exclude
* clear
* +"string" - only include this <string> message (can be specified multiple times)
* +*
* -* - instead use +something
* ! - show settings
* p"package" - show message from only <package> process/package
* l"profile" - load settings from profile
* s"profile" - save current settings to profile


More?
-----
Soon...
* terminator, small font, split window, one with all log entry, one with a profile


Todo!
-----
* create directorty for config files (~/.config/adblogga or ~/.adblogga)
* set date.timezone if not set (removes notices for date())
	/etc/php5/cli/php.ini
		[Date]
		date.timezone = Europe/Budapest
	/etc/php5/cgi/php.ini
	 date_default_timezone_set('Europe/Budapest');
* replace/remove pcntl_signal() function on non-Unix systems
* object oriented structure instead of simple functions and global variables
* default
* install script
* multi device support
* Mac support
* Windows support

...
---

If you made this far, buy me a drink:
[![Fund me on Gittip](https://s3-eu-west-1.amazonaws.com/com.parhuzamos/adblogga/gittip-logo.png)](https://www.gittip.com/parhuzamos/)