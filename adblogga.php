#!/usr/bin/php
<?php

	define("ADB", getenv("ANDROID_HOME")."/platform-tools/adb");

	define("ADB_COMMAND_LINE", ADB." logcat -v time");
	define("LINE_REG_EXP", '/^([^\s]+)\s+([^\s]+)\s+([A-Z])\/(.*?)\((.*?)\):\s(.*)$/');


	$fg = array();
	$fg['black'] = '0;30';
	$fg['dark_gray'] = '1;30';
	$fg['blue'] = '0;34';
	$fg['light_blue'] = '1;34';
	$fg['green'] = '0;32';
	$fg['light_green'] = '1;32';
	$fg['cyan'] = '0;36';
	$fg['light_cyan'] = '1;36';
	$fg['red'] = '0;31';
	$fg['light_red'] = '1;31';
	$fg['purple'] = '0;35';
	$fg['light_purple'] = '1;35';
	$fg['brown'] = '0;33';
	$fg['yellow'] = '1;33';
	$fg['light_gray'] = '0;37';
	$fg['white'] = '0;37';

	$bg = array();
	$bg['black'] = '40';
	$bg['red'] = '41';
	$bg['green'] = '42';
	$bg['yellow'] = '43';
	$bg['yellow_dim'] = '43;22';
	$bg['blue'] = '44';
	$bg['magenta'] = '45';
	$bg['cyan'] = '46';
	$bg['light_gray'] = '47';
	$bg['black_dim'] = '40;22';
	$bg['black_bright'] = '100';

	//RED,GREEN,YELLOW,BLUE,MAGENTA,CYAN,WHITE
	$tgc = array('0;31', '0;32', '0;33', '0;34', '0;35', '0;36', '0;37');
	$tgccount = count($tgc);
	$tagcolors = array();
	$tagcolorindex = 0;

	$typecolors = array(
		"V" => array($fg['white'], $bg['black']),
		"D" => array($fg['black'], $bg['blue']),
		"I" => array($fg['black'], $bg['green']),
		"W" => array($fg['black'], $bg['yellow']),
		"E" => array($fg['black'], $bg['red']),
		"F" => array($fg['black'], $bg['red']),
	);

	function c($str, $color, $bgcolor = null) {
		return sprintf("\033[%s%sm%s\033[0m", $color, $bgcolor ? ';' . $bgcolor : "", $str);
	}


	function colorForTag($tag) {
		global $tagcolors;
		global $tagcolorindex;
		global $tgc;
		global $tgccount;

		$c = @$tagcolors[$tag];
		if (!$c) {
			if ($tagcolorindex < $tgccount-1) {
				$tagcolorindex++;
			} else {
				$tagcolorindex = 0;
			}

			$c = array();
			$c[0] = $tgc[$tagcolorindex];
			$c[1] = null; // black

			$tagcolors[$tag] = $c;
		}

		return $c;
	}

    function signal_handler($signal) {
        switch($signal) {
            case SIGTERM:
                exit;
            case SIGKILL:
                exit;
            case SIGINT:
                exit;
        }
    }

    function ec($message, $breakline = true) {
    	global $fg, $bg;
    	echo(c("[adblogga]", $fg['black'], $bg['yellow_dim']).c(" ".date("H:i:s")." ", $fg['white'], $bg['black_dim']).$message.($breakline ? PHP_EOL : ""));
    }

    function outputLine(&$line) {
    	global $typecolors, $fg, $bg;

		$match = array();
		if (!preg_match(LINE_REG_EXP, $line, $match)) {
			echo($line);
			return;
		}

		//date+time
		echo(c($match[2].' ', $fg['white'], $bg['black_dim']));

		//process
		echo(c(str_pad($match[5], 8, ' ', STR_PAD_BOTH), $fg['black'], $bg['black_bright']));

		//thread?
		//echo(c(str_pad($match[4], 8, ' ', STR_PAD_LEFT), $fg['black'], $bg['black_bright']));

		//tag
		$tag = substr(trim($match[4]), 0, 32);
		$c = colorForTag($tag);
		echo(c(str_pad($tag, 32, ' ', STR_PAD_LEFT).' ', $c[0]));

		//type
		$c = $typecolors[$match[3]];
		echo(c(' '.$match[3].' ', $c[0], $c[1]));

		echo(' '.$match[6]);

		echo(PHP_EOL);
    }

    function setup() {
    	pcntl_signal(SIGTERM, "signal_handler");
    	pcntl_signal(SIGINT, "signal_handler");
    	
    	if (file_exists(ADB) == FALSE) {
    		echo("Error: adb executable not found. Please, define the ANDROID_HOME environment variable which should point to your Android SDK root.\n");
    		echo("Aborting.\n");
    		
    		exit(1);
    	}
    }

    function main($argv) {
    	$descriptorspec = array(
    			0 => array('pipe', 'r'), // stdin
    			1 => array('pipe', 'w'), // stdout
    			2 => array('pipe', 'a') // stderr
    	);
    	
		//TODO: use the built in cmdline arguments processor function, add -c param
		$clear = array_search("--clear", $argv);
		if ($clear) {
			$clear = $argv[$clear+1];
		}
		
		//TODO: use the built in cmdline arguments processor function, add -p param
		$profile = array_search("--profile", $argv);
		if ($profile) {
			$profile = $argv[$profile+1];
		} else {
			$profile = "";
		}
		
		$home = getenv("HOME");
		$excludesfile = sprintf($home."/.config/adblogga/%s-excludes.txt", $profile);
		$includesfile = sprintf($home."/.config/adblogga/%s-includes.txt", $profile);
	
		if (($profile) && (file_exists($excludesfile))) {
			$excludes = array_filter(preg_split("/\n/", file_get_contents($excludesfile)));
		} else {
			$excludes = array();
		}
		if (($profile) && (file_exists($includesfile))) {
			$includes = array_filter(preg_split("/\n/", file_get_contents($includesfile)));
		} else {
			$includes = array();
		}
	
		ec("Started.");
		stream_set_blocking(STDIN, 0);
	
		while(true) {
			$exited = false;
			$process = proc_open(ADB_COMMAND_LINE, $descriptorspec, $pipes);
	
			if (is_resource($process)) {
				fclose($pipes[0]); 
				fclose($pipes[2]); 
	
				$adbout = $pipes[1];
				$firstmessage = true;
				while (true) { 
					$in = array($adbout, STDIN);
					$empty = array();
					if (!@stream_select($in, $empty, $empty, null)) {
						echo(PHP_EOL);
						break;
					}
					$adbdata = array_search($adbout, $in);
					$userinput = array_search(STDIN, $in); 
	
					// check if device is unplugged (because adb logcat is stopped then)
					$sta = proc_get_status($process);
					if (@$sta['running'] == false) {
						ec("Device disconnected?");
						$exited = true;
						break;
					}
	
					if ($userinput !== false) {
						$char = fgetc(STDIN);
						if (ord($char) == 10) {
							stream_set_blocking(STDIN, 1);
							ec("Enter command: ", false);
							$input = fgets(STDIN); 
							$input = trim($input);
							if ($input) {
								if ($input == "c") {
									exec('reset');
									ec("Clear command received.");
									continue;
								} else if ($input == "cc") {
									exec(ADB.' logcat -c && reset');
									ec("Clear command for logcat received.");
									continue;
								} else if ($input[0] == "-") {
									$exclude = strtolower(substr($input, 1));
									if ($exclude) {
										$in = array_search($exclude, $includes);
										if ($in !== false) {
											ec("Removed from includes: ".$exclude);
											unset($includes[$in]);
											if ($profile) {
												file_put_contents($includesfile, join("\n", $includes));		
											}
										} else {
											$excludes[] = $exclude;
											$excludes = array_unique($excludes);
											if ($profile) {
												file_put_contents($excludesfile, join("\n", $excludes));
											}
										}
									}
									ec("Excludes are: ");
									echo(var_export($excludes).PHP_EOL);
									ec("Press Enter to continue...", false);
									fgets(STDIN);
								} else if ($input[0] == "+") {
									if ($input == "+*") {
										$includes = array();
									} else {
										$includes[] = strtolower(substr($input, 1));
										$includes = array_unique($includes);
									}
									if ($profile) {
										file_put_contents($includesfile, join("\n", $includes));
									}
									ec("Includes are: ");
									echo(var_export($includes).PHP_EOL);
									ec("Press Enter to continue...", false);
									fgets(STDIN);
								} else if ($input == "!") {
									ec("Settings: ");
									echo("Profile: $profile".PHP_EOL);
									echo("Clear if: $clear".PHP_EOL);
									ec("Includes: ");
									echo(join("\n", $includes).PHP_EOL);
									ec("Excludes: ");
									echo(join("\n", $excludes).PHP_EOL);
								} else if ($input == "?") {
									ec("Help.");
									ec("Accepted commands are:");
									ec("+<something>			add to include list");
									ec("-<something>			add to exclude list");
									ec("!						show settings, includes and excludes");
									ec("c 						clear screen");
									ec("cc 						clear logcat and screen");
									ec("exit 					exit adblogga");
									ec("x 						exit adblogga");
									ec("?						this help");
								} else if (($input == "exit") || ($input == "x")) {
									break;
								} else {
									ec("Unknown command: ".$input);
								}
							} else {
								//exec('reset');
								//ec("Clearing screen...");
								ec(str_repeat("-", 170));
							}
							stream_set_blocking(STDIN, 0);
						}
					}
	
					if ($adbdata === false) {
						continue;
					}
					$line = fgets($adbout, 1024);
					$loline = strtolower($line);
	
					if ($firstmessage) {
						$firstmessage = false;
						ec("Connected.");
					}
	
					if ($excludes) {
						$skip = false;
						foreach($excludes as $exclude) {
							if (strpos($loline, $exclude) !== false) {
								$skip = true;
								break;
							}
						}
						if ($skip) {
							continue;
						}
					}
					if ($includes) {
						$skip = false;
						foreach($includes as $include) {
							$skip = true;
							if (strpos($loline, $include) !== false) {
								$skip = false;
								break;
							}
						}
						if ($skip) {
							continue;
						}
					}
	
					if ($clear) {
						if (strpos($line, $clear) !== FALSE) {
							exec(ADB.' logcat -c && reset');
							ec("Clear pattern found. Clearing output. ( ".$line." )");
						}
					}
				} 
			} else {
				ec("Error: could not start \"".ADB_COMMAND_LINE."\"");
			}
	
			ec("Closing handle and process...");
			@fclose($adbout); 
			@proc_terminate($process);
	
			if ($exited) {
				ec("Restarting...");
			} else {
				ec("Exiting...");
				break;
			}
		}
	
		ec("Finished.");
    }
    
    setup();
    main($argv);