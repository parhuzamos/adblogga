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

Soon...
* command line parameters (--profile, --clear)
* include
* exclude
* clear
* +*
* -* instead use +something


More?
-----
Soon...
* terminator, small font, split window, one with all log entry, one with a profile


Todo!
-----
Soon...
* getopt()
* save current profile
* load profile
* install script
* process/package support
* Mac support
* Windows support

...
---

If you made this far, buy me a drink:
[![Fund me on Gittip](https://www.gittip.com/assets/7.0.8/logo.png)](https://www.gittip.com/parhuzamos/)