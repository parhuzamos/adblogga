adblogga
========

Color coded adb (Android Debug Bridge) logcat output with on-the-fly configurable filters for any part of the log entry. It's a terminal program, you shouldn't use your mouse ;).

Why?
----

* set and forget: automatically reconnects if a device is removed, no need to restart from the command line
* easy to pause the output (press Enter => you're in command mode, no messages displayed but buffered, press Enter again to exit and continue and flush the buffered messages)
* easy to enter a visual break (press Enter two times => a line is displayed in the terminal)
* easy to clear the screen (press Enter, then "c" then Enter)
* easy to log only some messages (press Enter, type "+WifiService" then Enter => **only** message containing "WifiService" will be displayed)
* easy to exclude some message (press Enter, type "-dalvikvm" then Enter => no message containing "dalvikvm" displayed)
* specify a package name on the command line to show only it's messages (more include and exclude entries can be added of course)
* profiles support (collection of include and exclude definitions)

[![A picture is worth thousand words.](http://parhuzamos.github.io/adblogga/images/20130728225034-420481645.png)](#)

For the base concept thanks to Jeff Sharkey and the coloredlogcat.py ( http://bit.ly/15XoV8U ).


Install!
--------

Clone the repo:

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

	# start colored log cat and show the messages from "com.example" process, also load include/exclude filters from the profile "controls"
	$ adblogga com.example --profile controls

Soon...
* more command line parameters (--clear)
* include
* exclude
* clear
* +<string> - only include this <string> message (can be specified multiple times)
* +*
* -* - instead use +something
* ! - show settings
* :<package> - show message from only <package> process/package

More?
-----
Soon...
* terminator, small font, split window, one with all log entry, one with a profile


Todo!
-----
Soon...
* getopt()
* store settings in json (also package name)
* object oriented structure instead of simple functions and global variables
* default
* save current profile
* load profile
* install script
* multi device support
* Mac support
* Windows support

...
---

If you made this far, buy me a drink:
[![Fund me on Gittip](https://s3-eu-west-1.amazonaws.com/com.parhuzamos/adblogga/gittip-logo.png)](https://www.gittip.com/parhuzamos/)