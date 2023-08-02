#!/usr/bin/php
<?php

	define("APPLICATION", "adblogga");
	define("VERSION", "v1.1");
	define("DESCRIPTION", "Color coded ADB (Android Debug Bridge) logcat output with on-the-fly configurable filters for any part of the log entry.");

	define("ADB", getenv("ANDROID_HOME")."/platform-tools/adb");

	define("LINE_REG_EXP", '/^([^\s]+)\s+([^\s]+)\s+([A-Z])\/(.*?)\((.*?)\):\s(.*)$/');

	define("MAX_DELAY_BETWEEN_PROCID_UPDATE", 5);
	define("DATE_FORMAT", "Ymd-His");

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

	$lastProcCollectTime = 0;
	$lastProcCollectFilename = "";
	$processIds = array();

	function resetArrayIndexes($array) {
	    $a = array();
	    foreach($array as $v) {
	        $a[] = $v;
        }
        sort($a);
        return $a;
    }
	
	class Settings {
		public $excludes = array();
		public $includes = array();	
		
		public $clear = null;
		
		public $profile = null;
		public $profileFileName = null;
		
		public $onlyPackage = null;
		
		public $saveToFile = null;
		
		function __construct($json = null) {
			if ($json != null) {
				$obj = json_decode($json);
				foreach ($obj as $key => $value) {
					$this->$key = $value;
				}
				
				$this->excludes = resetArrayIndexes($this->excludes);
				$this->includes = resetArrayIndexes($this->includes);
			}
		}
 	}
	
	$settings = null;

	function procStartCollect() {
		global $lastProcCollectTime;
		global $lastProcCollectFilename;

		//TODO: only exec every second sec
		$lastProcCollectTime = time();
		$lastProcCollectFilename = "/tmp/adblogga-proc.tmp";
		shell_exec("nohup adb shell ps >$lastProcCollectFilename &");
	}

	//TODO: don't call to often!!!
	function updateProcessIds($force = false, $filter = null) {
		global $lastProcCollectTime;
		global $processIds;

		$time = time();
		if ((!$force) && ($lastProcCollectTime + MAX_DELAY_BETWEEN_PROCID_UPDATE > $time)) {
			return;
		}
		$lastProcCollectTime = $time;

		//ec("updateProcessIds $time");

	    $filter = strtolower($filter);

	    exec(ADB." shell ps 2>/dev/null", $out, $status);
	    
	    if ($status == 0) {
	        $i = 0;

	        $processes = array();
	        foreach($out as $line) {
	        	if ($filter && strpos($line, $filter) === false) {
	            	continue;
	        	}
	        	if (preg_match("/^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+(.*)/", $line, $match)) {
	            	$processes[$match[9]] = $match[2];
	        	}
	    	}

	    	if ($filter && (count($processes) == 1)) {
	        	$processIds = reset($processes);
		    } else {
		        $processIds = $processes;
	    	}
	    } else {
	    	return null;
	    }
	}


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
    	echo(c("[adblogga]", $fg['black'], $bg['yellow_dim']).c(" ".date("H:i:s")." ", $fg['white'], $bg['black_dim'])." ".$message.($breakline ? PHP_EOL : ""));
    }
    
    function ecError($message, $breakline = true) {
    	global $fg, $bg;
    	echo(c("[adblogga]", $fg['white'], $bg['red']).c(" ".date("H:i:s")." ", $fg['white'], $bg['black_dim'])." ".$message.($breakline ? PHP_EOL : ""));
    }
    
    function waitEnter() {
    	ec("Press Enter to continue...", false);
    	fgets(STDIN);
    }

    function outputLine($line, $isIncluded, $onlyProcessId) {
    	global $typecolors, $fg, $bg;

		$match = array();
		if (!preg_match(LINE_REG_EXP, $line, $match)) {
			echo($line);
			return true;
		}

		//skip lines not matching the given processId
		$processId = $match[5];
		if (($isIncluded == false) && (($onlyProcessId != $processId) && ($onlyProcessId != null))) {
			return false;
		}

		//date+time
		echo(c($match[2].' ', $fg['white'], $bg['black_dim']));

		//process
		echo(c(str_pad($processId, 8, ' ', STR_PAD_BOTH), $fg['black'], $bg['black_bright']));

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
		
		return true;
    }
    
    function outputHTMLLine($linenum, $line, $isIncluded, $onlyProcessId) {
    	global $typecolors, $fg, $bg;

		$match = array();
		if (!preg_match(LINE_REG_EXP, $line, $match)) {
			echo($line);
			return;
		}

		//skip lines not matching the given processId
		$processId = $match[5];
		if (($isIncluded == false) && (($onlyProcessId != $processId) && ($onlyProcessId != null))) {
			return;
		}

		//date+time
		echo("<a name=\"{$linenum}\" href=\"#{$linenum}\"><span class='datetime'>".$match[2]."</span></a>");

		//process
		echo("<span class='process'>".$processId."</span>");

		//thread?
		//echo(c(str_pad($match[4], 8, ' ', STR_PAD_LEFT), $fg['black'], $bg['black_bright']));

		//tag
		$tag = substr(trim($match[4]), 0, 32);
		$c = colorForTag($tag);
		$c = str_replace(";", "", $c);
		echo("<span class='tag color-$c[0]'>".$tag."</span>");

		//type
		$c = $typecolors[$match[3]];
		$c = str_replace(";", "", $c);
		echo("<span class='type color-$c[0] color-bg-$c[1]'>".$match[3]."</span>");

		echo("<span class='msg'>".$match[6]."</span>");

		echo("<br>".PHP_EOL);
    }
    
    
    function response($success = null, $error = null) {
    	$response = new StdClass();
    	$response->success = $success != null;
    	if ($success != null) {
    		foreach($success as $key=>$value) {
    			$response->$key = $value;
    		}
    	} else {
    		$response->error = $error;
    	}
		echo(json_encode($response));
		exit(1);
    }
    
    function invalidUpload() {
    	$message = <<<MESSAGE
Error! No file data was received.
Send a file like this:
$ curl -F file=@/home/user/filename.ext http://adblogga.csinalom.com/adblogga.php
MESSAGE;
		response(null, $message);
    }
    
    function createColoredTXT($filename) {
    	$outfilename = $filename.".txt";
		if ($f = fopen($filename, "r")) {
			try {
				ob_start();
				while ($line = fgets($f)) {
					outputLine($line, false, null);
				}
				file_put_contents($outfilename, ob_get_contents());
				ob_end_clean();
				
				return "http://adblogga.csinalom.com/".$outfilename;
			} catch (Exception $e) {
				fclose($f);
				unlink($filename);					
			}
		}
    }
    
    function createColoredHTML($filename, $md5) {
    	$outfilename = $filename.".html";
		if ($f = fopen($filename, "r")) {
			try {
				ob_start();
				$linenum = 1;
				while ($line = fgets($f)) {
					outputHTMLLine($linenum, $line, false, null);
					$linenum++;
				}
				file_put_contents($outfilename, ob_get_contents());
				ob_end_clean();
				
				return "http://adblogga.csinalom.com/?id=$md5";
			} catch (Exception $e) {
				fclose($f);
				unlink($filename);					
			}
		}
    	return false;
    }
    
    function processUploadedData() {
    	if (!isset($_SERVER["HTTP_HOST"])) {
    		return false;
    	}
		if (isset($_FILES["file"])) {
			$filename = $_FILES["file"]["tmp_name"];
			$md5 = md5($filename);
			$target = "data/".$md5;
			if (move_uploaded_file($filename, $target)) {
				$txt = createColoredTXT($target);
				$html = createColoredHTML($target, $md5);
				
				//TODO: bit.ly links here!!! but don't include token ;)
				response(
         			array(
				         "id" => $md5, 
				         "txt" => $txt,
				         "html" => $html,
	         		)
				);
			} else {
				invalidUpload();
			}
		} else {
			invalidUpload();
		}
	    exit;
    }

    function setup() {
		@date_default_timezone_set(@date_default_timezone_get());

		if (function_exists('pcntl_signal1')) {
			pcntl_signal(SIGTERM, "signal_handler");
			pcntl_signal(SIGINT, "signal_handler");
		}

    	if (file_exists(ADB) == FALSE) {
    		echo("Error: adb executable not found. Please, define the ANDROID_HOME environment variable which should point to your Android SDK root.\n");
    		echo("Aborting.\n");

    		exit(1);
    	}
    }
    
    function saveSettings() {
    	global $settings;

    	if ($settings->profile) {
    		file_put_contents($settings->profileFileName, json_encode($settings));
    	}
    }
    
    function getProfileFilename($profile) {
		$fn = preg_replace("/[^a-z0-9\.]/", "", strtolower($profile));
		$home = getenv("HOME");
		$configdir = $home."/.config/adblogga";
		if (!file_exists($configdir)) {
			if (!file_exists($home."/.config")) {
				$configdir = $home."/.adblogga";
			}
			mkdir($configdir);
		}
		$fn = sprintf($configdir."/%s.json", $fn);
		return $fn;
    }
    
    function loadSettings($cmdlineoptions) {
		$deviceDef = "";
		if (isset($cmdlineoptions["d"])) {
			if ($cmdlineoptions["d"] === false) {
				ecError("No device specified! Check your command line.");
				exit(1);
			}
			$deviceDef = "-s ".$cmdlineoptions["d"];
			define("DEVICE", $cmdlineoptions["d"]);
			define("DEVICEDEF", $deviceDef);
		} else {
			$deviceDef = "";
			exec(ADB." devices", $out, $status);

			$out = preg_split('/'.PHP_EOL.'/', trim(join(PHP_EOL, $out)));
	    	if ($status == 0) {
	    		$c = count($out);
	    		if ($c == 1) {
	    			// no device
	    			$deviceDef = "";
	    			define("DEVICE", "<any>");
	    			define("DEVICEDEF", $deviceDef);
	    		} else if ($c > 2) {
	    			// multiple devices
	    			ecError("Multiple devices found. Select one device and restart with -d<device> parameter.");
	    			ec("List of devices: ");
	    			foreach(array_splice($out, 1) as $line) {
	    				ec("    ".$line);
	    			}
	    			exit(1);
	    		} else {
	    			// one device only
	    			$d = trim($out[1]);
	    			$d = substr($d, 0, strpos($d, "\t"));
	    			$deviceDef = "-s ".$d;
	    			define("DEVICE", $d);
	    			define("DEVICEDEF", $deviceDef);
	    		}
	    	}
	    	
		}
		define("ADB_COMMAND_LINE", ADB." {$deviceDef} logcat -v time");
    	
		if (($profile = @$cmdlineoptions["P"]) != null) {
			$fn = getProfileFilename($profile);
			if (file_exists($fn)) {
				ec("Loading profile '{$profile}' from '{$fn}'.");
				try {
					$settings = new Settings(file_get_contents($fn));
					$settings->profileFileName = $fn;
				} catch (Exception $e) {
					ec("Error occoured while loading profile '$profile' from the file '$fn': ".$e);
					exit(1);
				}
			} else {
				ec("The profile '$profile' does not exist yet (file not found: {$fn}). Using defaults.");
				waitEnter();
				$settings = new Settings();
				$settings->profile = $profile;
				$settings->profileFileName = $fn;
			}
		} else {
			$settings = new Settings();
		}
		if (isset($cmdlineoptions["p"])) {
			$settings->onlyPackage = $cmdlineoptions["p"];
		}
		if (isset($cmdlineoptions["c"])) {
			$settings->clear = $cmdlineoptions["c"];
		}
		if (isset($cmdlineoptions["s"])) {
			$fn = $cmdlineoptions["s"];
			if (isset($cmdlineoptions["S"])) {
				$pi = pathinfo($fn);
				$fn = sprintf("%s%s%s-%s.%s",$pi["dirname"], DIRECTORY_SEPARATOR, $pi["filename"], date(DATE_FORMAT), $pi["extension"]);
			}
			$settings->saveToFile = $fn;
			testOutputFile($settings->saveToFile);
		}
		if (isset($cmdlineoptions["i"])) {
			$f = $cmdlineoptions["i"];
			if (!is_array($f)) {
				$f=array($f);
			}
			foreach($f as $v) {
				addInclude($settings, $v, false);
			}
		}
		if (isset($cmdlineoptions["e"])) {
			$f = $cmdlineoptions["e"];
			if (!is_array($f)) {
				$f=array($f);
			}
			foreach($f as $v) {
				addExclude($settings, $v, false);
			}
		}
		if (isset($cmdlineoptions["o"])) {
			fileUpload($cmdlineoptions["o"]);
			exit;
		}
		
		if (isset($cmdlineoptions["publish"])) {
			exec(ADB.' '.DEVICEDEF.' logcat -c && reset');
			$settings->saveToFile = tempnam(null, null);
			testOutputFile($settings->saveToFile);
			$settings->inpublish = true;
		}

		return $settings;		
    }
    
    function testOutputFile($filename) {
		if (touch($filename) === FALSE) {
			ec("Can't save to file \"{$fn}\".");
			exit(1);
		} else {
			ec("Saving output to \"{$filename}\".");
		}
	}
    
    function fileUpload($filename) {
		if (file_exists($filename)) {
			ec("Uploading: $filename...");
			$post = array("file" => "@$filename");
			
			$c = curl_init("http://adblogga.csinalom.com/adblogga.php");
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $post);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($c);
			curl_close($c);
			try {
				$response = json_decode($result);
				if ($response->success) {
					ec("Upload finished.");
					ec("View online: ".$response->html);
					ec("View in terminal: ");
					ec("$ curl -s \"".$response->txt."\" | less -R");
				} else {
					ec("Upload error: ".$response->error);
				}
			} catch (Exception $e) {
				ec("Not a valid response returned: ".$e);
				exit(1);
			}
		} else {
			ec("Error: file \"$filename\" does not exist.");
			exit(1);
		}
    }
    
    function addInclude(&$settings, $value, $dosave = false) {
		if ($value == "*") {
			$settings->includes = array();
		} else {
			$settings->includes[] = strtolower($value);
			$settings->includes = array_unique($settings->includes);
		}
		ec("Including: $value");
		if ($dosave && $settings->profile) {
			saveSettings();
		}
    }
    
    function addExclude(&$settings, $value, $dosave = false) {
		$exclude = strtolower($value);
		if ($exclude) {
			$in = @array_search($exclude, $settings->includes);
			if ($in !== false) {
				ec("Removed from includes: ".$exclude);
				unset($settings->includes[$in]);
				if ($settings->profile) {
					saveSettings();
				}
			} else {
				$settings->excludes[] = $exclude;
				$settings->excludes = array_unique($settings->excludes);
				if ($dosave && $settings->profile) {
					saveSettings();
				}
			}
			ec("Excluding: $exclude");
		}
    }
    
    function appendSettings($profile) {
		$fn = getProfileFilename($profile);
		if (file_exists($fn)) {
			try {
				$settings = new Settings(file_get_contents($fn));
				$settings->profileFileName = $fn;
			} catch (Exception $e) {
				ec("Error occoured while loading profile '$profile' from the file '$fn': ".$e);
				exit(1);
			}
		} else {
			ec("The profile '$profile' has no settings saved yet.");
			$settings = new Settings();
			$settings->profile = $profile;
			$settings->profileFileName = $fn;
		}

		return $settings;		
    }
    
    function listProfiles($dir = null) {
    	if ($dir == null) {
    		ec("List of profiles:");
			$home = getenv("HOME");
			listProfiles($home."/.config/adblogga");
			
			if (!file_exists($home."/.config")) {
				listProfiles($home."/.adblogga");
			}
		} else {
			$d = dir($dir);
			while (false !== ($entry = $d->read())) {
			   	$pi = pathinfo($entry);
			   	if ($pi["extension"] == "json") {
			   		ec("    {$pi["filename"]}");
			   	}
			}
			$d->close();
		}
    }
    
    function showHelp() {
		echo(sprintf("%s %s", APPLICATION, VERSION).PHP_EOL);
		echo(PHP_EOL);
		echo(DESCRIPTION.PHP_EOL);
		echo(PHP_EOL);
		echo("Usage: ".APPLICATION." [option]".PHP_EOL);
		echo(PHP_EOL);
		echo("Options:".PHP_EOL);
		echo(PHP_EOL);
		echo("    -d<device>                          Use the specified device (only necessary if multiple devices are connected).".PHP_EOL);
		echo("    -p<com.example>                     Show only messages from the com.example package/app.".PHP_EOL);
		echo("    -P<profile>                         Load the given profile.".PHP_EOL);
		echo("    -i<filter>                          Include this filter. Can be specified many times.".PHP_EOL);
		echo("    -e<filter>                          Exclude this filter. Can be specified many times.".PHP_EOL);
		echo("    -c<clear-string>                    Clear the terminal if \"clear-string\" is found in a message.".PHP_EOL);
		echo("    -s<log-filename>                    Save the messages to \"log-filename\".".PHP_EOL);
		echo("    -o<log-filename>                    Upload the log file and get and url where it can be viewed.".PHP_EOL);
		echo("    -S                                  Append ".DATE_FORMAT." to the \"log-filename\". Must be used with -s<log-filename>.".PHP_EOL);
		echo("    --publish                           Clear and then save the messages to a temp file and when exiting, upload them and get the urls (like -o<log-filename>).".PHP_EOL);
		echo("    -h, --help                          This help.".PHP_EOL);
		echo("".PHP_EOL);
		echo(PHP_EOL);
    	
    }
    

    function main() {
    	global $processIds;
    	global $settings;
    	
    	if (isset(getopt(null,array("help::"))["help"]) || isset(getopt("h::")["h"])) {
    		showHelp();
    		exit(0);
    	}

    	$descriptorspec = array(
    			0 => array('pipe', 'r'), // stdin
    			1 => array('pipe', 'w'), // stdout
    			2 => array('pipe', 'w'), // stderr
    	);

    	
    	$settings = loadSettings(getopt("d::p::P::c::s::S::i::e::o::", array("publish::")));
    	
		ec("Started.");
		stream_set_blocking(STDIN, 0);
		
		$saveToFile = null;
		if ($settings->saveToFile) {
			$saveToFile = fopen($settings->saveToFile, "w");
		}

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

					updateProcessIds();

					if ($userinput !== false) {
						$char = fgetc(STDIN);
						if (ord($char) == 10) {
							stream_set_blocking(STDIN, 1);
							ec("Enter command: ", false);
							$input = fgets(STDIN);
							$input = trim($input);
							if ($input) {
								if ($input[0] == "p") {
									$pack = trim(strtolower(substr($input, 1)));
									if ($pack == "*") {
										$pack = "";
									}
									$settings->onlyPackage = $pack;
									saveSettings();
									if ($pack != "") {
										ec("Showing log entries from package: \"".$settings->onlyPackage."\".");
									} else {
										ec("Showing log entries from all packages.");
									}
									waitEnter();
								} else if ($input[0] == "l") {
									$settings = loadSettings(array("P" => substr($input, 1)));
									waitEnter();
								} else if ($input[0] == "L") {
									listProfiles();
									waitEnter();
								} else if ($input[0] == "a") {
									$append = appendSettings(substr($input, 1));
									foreach($settings as $key => $value) {
										if (is_array($settings->$key)) {
											$settings->$key = array_unique(array_merge($settings->$key, $append->$key));
										} else {
											$settings->$key = $append->$key;
										}
									}
									ec("Appending profile '{$append->profile}' from '{$append->profileFileName}'.");
									waitEnter();
								} else if ($input[0] == "s") {
									$settings->profile = substr($input, 1);
									$settings->profileFileName = getProfileFilename($settings->profile);
									saveSettings();
									ec("Saving profile '{$settings->profile}' to '{$settings->profileFileName}'.");
									waitEnter();
								} else if ($input == "c") {
									exec('reset');
									ec("Clear command received.");
									continue;
								} else if ($input == "cw") {
									exec('reset');
									if ($settings->saveToFile) {
										fclose($saveToFile);
										$saveToFile = fopen($settings->saveToFile, "w");
									}
									ec("Clear command and file truncate request received.");
								} else if ($input == "cc") {
									exec(ADB.' '.DEVICEDEF.' logcat -c && reset');
									ec("Clear command for logcat received.");
									continue;
								} else if ($input[0] == "-") {
								    $a = preg_split("/,/", substr($input, 1));
								    foreach($a as $v) {
                                        addExclude($settings, trim($v));
                                    }
									// ec("Excludes are: ");
									// echo(var_export($settings->excludes).PHP_EOL);
									// waitEnter();
								} else if ($input[0] == "+") {
									addInclude($settings, substr($input, 1));
									// ec("Includes are: ");
									// echo(var_export($settings->includes).PHP_EOL);
									// waitEnter();
								} else if ($input[0] == "o") {
									if ($settings->saveToFile) {
										fclose($saveToFile);
										fileUpload($settings->saveToFile);
										$saveToFile = fopen($settings->saveToFile, "a");
										waitEnter();
									} else {
										ec("Error: not saving messages to file :(. Next time tart with -s<log-filename>");
										waitEnter();
									}
								} else if ($input == "!") {
									ec("Settings: ");
									echo("Package: ".($settings->onlyPackage ? $settings->onlyPackage : "<none>").PHP_EOL);
									echo("Profile: $settings->profile".PHP_EOL);
									echo("Clear if: $settings->clear".PHP_EOL);
									echo("Save to file: ".($settings->saveToFile ? $settings->saveToFile : "<none>").PHP_EOL);
									ec("Includes: ");
									echo(join("\n", $settings->includes).PHP_EOL);
									ec("Excludes: ");
									echo(join("\n", $settings->excludes).PHP_EOL);
									waitEnter();
								} else if ($input == "?") {
									ec("Accepted commands are:");
									ec("p<packagename>          only show messages from given package (com.example.application1)");
									ec("l<profile>              load profile: current settings are overwritten from this profile");
									ec("L                       list available profiles");
									ec("a<profile>              append profile: this profile is added to the current settings");
									ec("s<profile>              save profile: current settings are overwriting the given profile");
									ec("o                       if currently saving to messages to file, get an url where messages can be viewed online (like --publish), incremental");
									ec("+<something>            add to include list");
									ec("-<something>            add to exclude list");
									ec("!                       show current settings (package, profile, includes, excludes, ...)");
									ec("c                       clear screen");
									ec("cw                      clear screen and truncate log file if any (see -s<log-filename>)");
									ec("cc                      clear logcat and screen");
									ec("exit                    exit adblogga");
									ec("x                       exit adblogga");
									ec("?                       this help");
									waitEnter();
								} else if (($input == "exit") || ($input == "x")) {
									break;
								} else {
									ec("Unknown command: ".$input);
								}
							} else {
								//exec('reset');
								//ec("Clearing screen...");
								$separator = str_repeat("-", 170);
								ec($separator);
								if ($saveToFile) {
									fwrite($saveToFile, $separator);
								}
							}
							stream_set_blocking(STDIN, 0);
						}
					}

					if ($adbdata === false) {
						continue;
					}
					$line = fgets($adbout, 4096);
					$loline = strtolower($line);
					
					if ($saveToFile) {
						fwrite($saveToFile, $line);
					}

					if ($firstmessage) {
						$firstmessage = false;
						ec("Connected.");
					}

					if ($settings->excludes) {
						$skip = false;
						foreach($settings->excludes as $exclude) {
							if (strpos($loline, $exclude) !== false) {
								$skip = true;
								break;
							}
						}
						if ($skip) {
							continue;
						}
					}

					if ($settings->onlyPackage && ((strpos($loline, $settings->onlyPackage) !== false) && ((strpos($line, "ActivityManager") !== false) || (strpos($line, "WindowState") !== false) || (strpos($line, "ACTIVITY_STATE") !== false)))) {
						//ec("Updating process ids (\"$settings->onlyPackage\")... $line");
						updateProcessIds(true);
					}

					$processId = @$processIds[$settings->onlyPackage];
					if ($settings->onlyPackage) {
						$processId = $processId ? $processId : 1;
					}

					$isIncluded = false;
					if ($settings->includes) {
						$skip = true;
						foreach($settings->includes as $include) {
							if (strpos($loline, $include) !== false) {
								$skip = false;
								$isIncluded = true;
								break;
							}
						}
						if ($skip && $processId == null) {
							continue;
						}
					}

					if ($settings->clear) {
						if (strpos($line, $settings->clear) !== FALSE) {
							exec(ADB.' '.DEVICEDEF.' logcat -c && reset');
							ec("Clear pattern found. Clearing output. ( ".$line." )");
						}
					}

					$printed = outputLine($line, $isIncluded, $processId);
					if ($printed && $saveToFile) {
						fwrite($saveToFile, $line);
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
				ec("Waiting for ".DEVICE." to connect...");
			} else {
				ec("Exiting...");
				break;
			}
		}
		
		if ($saveToFile) {
			fclose($saveToFile);
		}

		if (@$settings->inpublish) {
			fileUpload($settings->saveToFile);
		}

		ec("Finished.");
    }

    processUploadedData();
    setup();
    main();