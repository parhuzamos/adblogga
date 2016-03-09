<?php

	$id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : null;
	
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>adblogga -  online</title>
		<style type="text/css">
			#log {
				
				border: 1px solid gray;
			    font-family: monospace;
			    font-size: 11px;
			    background-color: black;
		        overflow-x: hidden;
			}
			
			#log span {
			    padding: 2px 4px 2px 4px;
			    float: left;
			}

			#log br {
			    clear: both;
			}

			.datetime {
			    color: white;
			    background-color: gray;
			}

			.process {
			    color: darkgray;
			    background-color: gray;
			    display: block;
			    width: 3em;
			    text-align: right;
			}

			.tag {
			    text-align: right;
			    width: 17em;
			}

			.type {
			    text-align: center;
			    width: 1em;
			}

			.msg {
			    color: white;
			    white-space: pre;
			}

			.color-030 { color: black;}
			.color-130 { color: darkgray;}
			.color-034 { color: blue;}
			.color-134 { color: lightblue;}
			.color-032 { color: green;}
			.color-132 { color: lightgreen;}
			.color-036 { color: cyan;}
			.color-136 { color: lightcyan;}
			.color-031 { color: red;}
			.color-131 { color: lightred;}
			.color-035 { color: purple;}
			.color-135 { color: lightpurple;}
			.color-033 { color: brown;}
			.color-133 { color: yellow;}
			.color-037 { color: lightgray;}

			.color-bg-40 { background-color: gray;}
			.color-bg-41 { background-color: red;}
			.color-bg-42 { background-color: green;}
			.color-bg-43 { background-color: yellow;}
			.color-bg-4322 { background-color: #FFFF99;} 
			.color-bg-44 { background-color: blue;}
			.color-bg-45 { background-color: magenta;}
			.color-bg-46 { background-color: cyan;}
			.color-bg-47 { background-color: lightgray;}
			.color-bg-4022 { background-color: #333333;} 
			.color-bg-100 { background-color: black;} 
			
			/*********************************************************************************/
			#header {
				left: 0px;
				top: 0px;
				margin: 4px;
				padding: 4px;
				height: 24px;
			}
			
			#header div {
				margin-right: 4px;
			}
			
			#header #id {
				width: 20em;
			}
			
			#load-container {
				float: left;
			}
			
			#upload-container {
				float: right;
			}
			
			.progress {
				display: none;
			}
			.result {
				display: none;
			}
		</style>
		<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
		<script type="text/javascript">
			$(function() {
			 	$("#load").click(function() {
			 		window.location = "?id="+$("#id").val();
			 	});
			 	
			 	$("#upload").click(function() {
			 		try {
				 		$("#upload-container .static").hide();
				 		$("#upload-container .progress").show();
				 		$("#upload-container .progress").text("Uploading "+$("#file")[0].files[0].name+", "+$("#file")[0].files[0].size+" bytes...");
				 		
				 		var data = new FormData();
				 		data.append("file", $("#file")[0].files[0]);
				 		
				 		$.ajax({
				 			url: "/adblogga.php",
				 			type: "POST",
				 			data: data,
				 			cache: false,
				 			dataType: "json",
				 			processData: false,
				 			contentType: false
				 		}).done(function(result) {
				 			console.log(result);
				 			$("#upload-container .result").show();
				 			if (result.success) {
				 				$("#upload-container .result .message").text("Upload success: ");
				 				$("#upload-container .result .link").text(result.html).attr("href", result.html);
				 			}
				 		}).fail(function(error) {
				 			$("#upload-container .static").show();
			 				console.error(error);
				 		}).always(function() {
				 			$("#upload-container .progress").hide();
				 		});
			 		} catch (error) {
			 			console.error(error);
			 			$("#upload-container .progress").hide();
			 			$("#upload-container .static").show();
			 		}
			 	});

				$("#upload-new").click(function() {
					$("#file").val("");
					$("#upload-container .progress").hide();
					$("#upload-container .result").hide();
					$("#upload-container .static").show();
				});
			});
		</script>
	</head>
	
	<body>
		<div id="header">
			<div id="load-container">
				ID to load: <input type="text" id="id" value="<?=$id ? $id : ""?>" />
				<button id="load">Load</button>
			</div>
			<div id="upload-container">
				<div class="progress">
				</div>
				<div class="static">
					File to upload: <input type="file" name="file" id="file"><button id="upload">Upload</button>
				</div>
				<div class="result">
					<span class="message"></span>
					<a class="link" href="#" target="_blank"></a>
					<button id="upload-new">New upload</button>
				</div>
				
			</div>
		</div>
		<div id="log">
<?php
	if ($id) {
		if ($f = fopen("data/$id.html", "r")) {
			try {
				while ($line = fgets($f)) {
					echo($line.PHP_EOL);
				}
				fclose($f);
			} catch (Exception $e) {
				fclose($f);
			}
		}
	}
?>			
		</div>
	</body>
</html>
