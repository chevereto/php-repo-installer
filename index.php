<?php
$settings = array(
	'repo'		=> 'Chevereto/Chevereto-Free',
	'videoId'	=> 'vDw1K-Spm_E',
	'logoUrl'	=> 'https://chevereto.com/src/img/chevereto-free-logo-8bits.png',
	'timeout'	=> array(
		'start' => 10,
		'redirection' => 10
	),
	'redirect'	=> TRUE,
);
/*
// Example settings for other repos
$settings = array(
	'repo'		=> 'opencart/opencart',
	'repoPath'	=> 'upload', // Indicate which repo path to extract
	'videoId'	=> 'vDw1K-Spm_E',
	'logoUrl'	=> 'http://www.opencart.com/opencart/application/view/image/logo.png',
	'timeout'	=> array(
		'start' => 5,
		'redirection' => 5
	),
	'redirect'	=> FALSE,
);
$settings = array(
	'repo'		=> 'WordPress/WordPress',
	'videoId'	=> 'vDw1K-Spm_E',
	'logoUrl'	=> 'https://s.w.org/about/images/logos/wordpress-logo-notext-rgb.png',
	'timeout'	=> array(
		'start' => 5,
		'redirection' => 5
	),
	'redirect'	=> FALSE,
);
$settings = array(
	'repo'		=> 'drupal/drupal',
	'videoId'	=> 'vDw1K-Spm_E',
	'logoUrl'	=> 'https://www.drupal.org/files/druplicon-small.png',
	'timeout'	=> array(
		'start' => 5,
		'redirection' => 5
	),
	'redirect'	=> FALSE,
);
$settings = array(
	'repo'		=> 'PrestaShop/PrestaShop',
	'videoId'	=> 'vDw1K-Spm_E',
	'logoUrl'	=> 'http://img-cdn.prestashop.com/logo.png',
	'timeout'	=> array(
		'start' => 5,
		'redirection' => 5
	),
	'redirect'	=> FALSE,
);
*/
error_reporting(E_ALL ^ E_NOTICE);
define('ROOT_PATH', rtrim(str_replace('\\','/', __DIR__), '/') . '/'); 
define('ROOT_PATH_RELATIVE', rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/') . '/');
define('HTTP_HOST', $_SERVER['HTTP_HOST']);
define('HTTP_PROTOCOL', ((!empty($_SERVER['HTTPS']) and strtolower($_SERVER['HTTPS']) == 'on') or $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https' : 'http');
define('ROOT_URL', HTTP_PROTOCOL . "://".HTTP_HOST . ROOT_PATH_RELATIVE); // http(s)://www.mysite.com/chevereto/
define('SELF', ROOT_PATH . basename(__FILE__));
if (class_exists('ZipArchive')) {
	class my_ZipArchive extends ZipArchive {
		public function extractSubdirTo($destination, $subdir) {
			$errors = array();

			// Prepare dirs
			$destination = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $destination);
			$subdir = str_replace(array("/", "\\"), "/", $subdir);

			if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, "UTF-8") * -1) != DIRECTORY_SEPARATOR) {
				$destination .= DIRECTORY_SEPARATOR;
			}

			if (substr($subdir, -1) != "/") {
				$subdir .= "/";
			}

			// Extract files
			for ($i = 0; $i < $this->numFiles; $i++) {
				$filename = $this->getNameIndex($i);

				if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) == $subdir) {
					$relativePath = substr($filename, mb_strlen($subdir, "UTF-8"));
					$relativePath = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $relativePath);

					if (mb_strlen($relativePath, "UTF-8") > 0) {
						if (substr($filename, -1) == "/") { // Directory
							// New dir
							if (!is_dir($destination . $relativePath)) {
								if (!@mkdir($destination . $relativePath, 0755, true)) {
									$errors[$i] = $filename;
								}
							}
						} else {
							if (dirname($relativePath) != ".") {
								if (!is_dir($destination . dirname($relativePath))) {
									// New dir (for file)
									@mkdir($destination . dirname($relativePath), 0755, true);
								}
							}
							// New file
							if (@file_put_contents($destination . $relativePath, $this->getFromIndex($i)) === false) {
								$errors[$i] = $filename;
							}
						}
					}
				}
			}
			
			return $errors;
		}
	}
}
function json_error($args) {
	if(func_num_args($args) == 1 and is_object($args)) {
		if(method_exists($args, 'getMessage') and method_exists($args, 'getCode')) {
			$message = $args->getMessage();
			$code = $args->getCode();
			$context = get_class($args);
			error_log($message); // log class errors
		} else {
			return;
		}
	} else {
		if(func_num_args($args) == 1) {
			$message = $args; 
			$code = NULL;
			$context = NULL;
		} else {
			$message = func_get_arg(0);
			$code = func_get_arg(1);
			$context = NULL;
		}
	}
	return [
		'status_code' => 400,
		'error' => [
			'message'	=> $message,
			'code'		=> $code,
			'context'	=> $context
		]
	];
}
function json_output($data=[]) {
	error_reporting(0);
	@ini_set('display_errors', false);
	if(ob_get_level() === 0 and !ob_start('ob_gzhandler')) ob_start();
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').'GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
	header('Content-type: application/json; charset=UTF-8');
	
	// Invalid json request
	if(empty($data)) {
		set_status_header(400);
		$json_fail = [
			'status_code' => 400,
			'status_txt' => get_set_status_header_desc(400),
			'error' => [
				'message' => 'no request data present',
				'code' => NULL
			]
		];
		die(json_encode($json_fail));
	}
	
	// Populate missing values
	if($data['status_code'] && !$data['status_txt']){
		$data['status_txt'] = get_set_status_header_desc($data['status_code']);
	}
	
	$json_encode = json_encode($data);
	
	if(!$json_encode) { // Json failed
		set_status_header(500);
		$json_fail = [
			'status_code' => 500,
			'status_txt' => get_set_status_header_desc(500),
			'error' => [
				'message' => "data couldn't be encoded into json",
				'code' => NULL
			]
		];
		die(json_encode($json_fail));
	}
	set_status_header($data['status_code']);
	
	print $json_encode;
	die();
}
function set_status_header($code) {
	$desc = get_set_status_header_desc($code);
	if(empty($desc)) return false;
	$protocol = $_SERVER['SERVER_PROTOCOL'];
	if('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol) $protocol = 'HTTP/1.0';
	$set_status_header = "$protocol $code $desc";
	return @header($set_status_header, true, $code);
}
function get_set_status_header_desc($code) {
	$codes_to_desc = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended'
	);
	if(array_key_exists($code, $codes_to_desc)) {
		return $codes_to_desc[$code];	
	}
}
function str_replace_last($search, $replace, $subject) {
	$pos = strrpos($subject, $search);
	if($pos !== false) {
		$subject = substr_replace($subject, $replace, $pos, strlen($search));
	}
	return $subject;
}
function random_string($length) {
	switch(true) {
		case function_exists('mcrypt_create_iv') :
			$r = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
		break;
		case function_exists('openssl_random_pseudo_bytes') :
			$r = openssl_random_pseudo_bytes($length);
		break;
		case is_readable('/dev/urandom') : // deceze
			$r = file_get_contents('/dev/urandom', false, null, 0, $length);
		break;
		default :
			$i = 0;
			$r = '';
			while($i ++ < $length) {
				$r .= chr(mt_rand(0, 255));
			}
		break;
	}
	return substr(bin2hex($r), 0, $length);
}
try {
	if(isset($_REQUEST['action'])) {
		set_time_limit(600); // Allow up to five minutes...
		$temp_dir = ROOT_PATH;
		// Detect writting permissions
		if(!is_writable($temp_dir)) {
			throw new Exception(sprintf("Can't write into %s path", $temp_dir));
		}
		if(!is_writable(SELF)) {
			throw new Exception(sprintf("Can't write into %s file", $temp_dir));
		}
		$context = stream_context_create([
		'http'=> [
			'method' => 'GET',
			'header' => "User-agent: " . $settings['repo'] . "\r\n"
			]
		]);
		switch($_REQUEST['action']) {
			case 'download':
				$zipball_url = 'https://api.github.com/repos/' . $settings['repo'] . '/zipball';
				$download = file_get_contents($zipball_url, FALSE, $context);
				if($download === FALSE) {
					throw new Exception(sprintf("Can't fetch %s from GitHub (file_get_contents)", $settings['repo']), 400);
				}
				$github_json = json_decode($download, TRUE);
				if(json_last_error() == JSON_ERROR_NONE) {
					throw new Exception("Can't proceed with update procedure");
				} else {
					// Get Content-Disposition header from GitHub
					foreach($http_response_header as $header) {
						if(preg_match('/^Content-Disposition:.*filename=(.*)$/i', $header, $matches)) {
							$zip_local_filename = str_replace_last('.zip', '_' . random_string(24) . '.zip', $matches[1]);
							break;
						}
					}
					if(!isset($zip_local_filename)) {
						throw new Exception("Can't grab content-disposition header from GitHub");
					}
					if(file_put_contents($temp_dir . $zip_local_filename, $download) === FALSE) {
						throw new Exception("Can't save file");
					}
					$json_array = [
						'success' => [
							'message'   => 'Download completed',
							'code'      => 200
						],
						'download' => [
							'filename' => $zip_local_filename
						]
					];
				}
				$json_array['success'] = ['message' => 'OK'];
			break;
			case 'extract':
				
				$error_catch = [];
				foreach(['ZipArchive', 'RecursiveIteratorIterator', 'RecursiveDirectoryIterator'] as $k => $v) {
					if(!class_exists($v)) {
						$error_catch[] = strtr('%c class [http://php.net/manual/en/class.%l.php] not found', [
							'%c' => $v,
							'%l' => strtolower($v)
						]);
					}
				}
				if($error_catch) {
					throw new Exception(join("<br>", $error_catch), 100);
				}
				
				$repo_canonized_name = str_replace('/', '-', $settings['repo']);
				
				$zip_file = $temp_dir . $_REQUEST['file'];
				
				$explode_base = explode('_', $_REQUEST['file']);
				$expode_sub = explode('-', $explode_base[0]);
				$etag_short = end($expode_sub);
				
				// To be honest I don't know why GitHub prefix a "g" and sometimes prefix an "e"
				if($etag_short[0] == 'g') {
					$etag_short = substr($etag_short, 1);
				}
				
				if(empty($etag_short)) {
					throw new Exception("Can't detect zipball short etag");
				}
                // Test .zip
                if(!is_readable($zip_file)) {
                    throw new Exception('Missing '.$zip_file.' file', 400);
                }
                // Unzip .zip
                $zip = new my_ZipArchive;
                if ($zip->open($zip_file) === TRUE) {
					$folder = $repo_canonized_name . '-' . $etag_short . '/';
					if(!empty($settings['repoPath'])) {
						$folder .= $settings['repoPath'];
					}
					$zip->extractSubdirTo($temp_dir, $folder);
                    $zip->close();
                    @unlink($zip_file);
                } else {
                    throw new Exception(sprintf("Can't extract %s", $zip_file), 401);
                }
                $json_array['success'] = ['message' => 'OK', 'code' => 200];
			break;
		}
		// Inject any missing status_code
        if(isset($json_array['success']) && !isset($json_array['status_code'])) {
            $json_array['status_code'] = 200;
        }
		$json_array['request'] = $_REQUEST;
		json_output($json_array);
	}
} catch(Exception $e) {
	$json_array = json_error($e);
	$json_array['request'] = $_REQUEST;
	json_output($json_array);
}
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $settings['repo']; ?> Installer</title>
<?php if(isset($settings['logoUrl'])) { ?><link rel="shortcut icon" href="<?php echo $settings['logoUrl']; ?>"><?php } ?>

<style type="text/css">
	body, html {
		min-height: 100%;
		padding: 0;
		margin: 0;
		background: #000;
		color: rgb(220,230,230);
		
	}
	::selection {
		color: rgb(20,30,30);
		background: rgb(220,230,230);
	}
	
	@keyframes joltBG {
		0% {
			opacity: 0.3;
		}
		22%, 26% {
			opacity: 0.2;
		}
		27%, 45% {
			opacity: 0.4;
		}
		46%, 76% {
			opacity: 0.5;
		}
		76%, 78% {
			opacity: 0.05;
		}
		78% {
			opacity: 0.3;
		}
		100% {
			opacity: 0.3;
		}
	}
	@keyframes waiting {
		0% {
			opacity: 1;
		}
		50% {
			opacity: 0;
		}
		100% {
			opacity: 1;
		}
	}
	@keyframes spin {
		from {
			transform: rotateY(0);
		}
		to {
			transform: rotateY(360deg);
		}
	}

	#terminal {
		white-space: pre-wrap;
		padding: 20px;
		margin: 0;
		line-height: 1.45;
		font-family: Monaco, Consolas, "Lucida Console", monospace;
		position: relative;
		text-shadow: 2px 2px 1px rgba(0,0,0,.9);
	}
	#terminal::before {
		position: fixed;
		pointer-events: none;
		top:0;
		right: 0;
		bottom: 0;
		left:0;
		background-color: rgba(50,50,80, 0.6);
		content: '';
		z-index: 100;
		box-shadow: inset 0px 0px 20px 0px rgba(0,0,60,0.3);
		background: url('data:image/svg+xml,%3C?xml version="1.0" encoding="utf-8"?%3E %3C!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"%3E %3Csvg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="2px" height="2px" viewBox="0 0 2 2" enable-background="new 0 0 600 600" xml:space="preserve"%3E %3Cline fill="none" stroke="#000000" stroke-miterlimit="10" x1="0" y1="0.5" x2="600" y2="0.5"/%3E %3C/svg%3E');
		animation-name: joltBG;
		animation-duration: 10000ms;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
	}
	.js #terminal::after {
		position: absolute;
		pointer-events: none;
		bottom: 0;
		content: '■';
		animation-name: waiting;
		animation-duration: 1000ms;
		animation-iteration-count: infinite;
		animation-timing-function: step-end;
	}
	
	#background {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background-size: cover;
		background-position: center;
		background-repeat: no-repeat;
	}
		#background .logo {
			position: fixed;
			top: 20px;
			right: 20px;
			width: 50vw;
			max-width: 400px;
			animation-name: spin;
			animation-duration: 3s;
			animation-iteration-count: infinite;
			animation-timing-function: ease-in-out;
		}
	
	iframe, video {
		position: fixed;
		width: 100%;
		height: 100%;
		pointer-events: none;
		display: none;
	}
	
	.js iframe, .js video {
		display: block;
	}
	
	@media (max-width: 568px) {
		iframe, video {
			opacity: 0;
		}
	}
	@media (min-width: 569px) {
		#background {
			background-image: none !important;
		}
	}
	
	.status {
		text-transform: uppercase;
	}
		.status--error {
			color: red;
		}
		.status--ok {
			color: #00FF00;
		}
	
</style>
</head>
<body>
	
	<div id="background" style="background-image: url(http://img.youtube.com/vi/<?php echo $settings['videoId']; ?>/hqdefault.jpg);">
		<?php if(isset($settings['videoId'])) { ?><iframe frameborder="0" height="100%" width="100%" src="https://youtube.com/embed/<?php echo $settings['videoId']; ?>?loop=1&autoplay=1&controls=0&showinfo=0&autohide=1&playlist=vDw1K-Spm_E"></iframe><?php } ?>
		<?php if(isset($settings['logoUrl'])) { ?><img class="logo" src="<?php echo $settings['logoUrl']; ?>"><?php } ?>
	</div>

<div id="terminal">PHP GITHUB REPO INSTALLER v1.0
https://github.com/Chevereto/php-repo-installer
--

This script will install <?php echo $settings['repo']; ?> in <?php echo ROOT_PATH; ?> 
To use another path close this tab and move this file somewhere else.

<noscript><span class="status status--error">ERROR</span> JavaScript is needed to use this installer.</noscript></div>
	
	<script src="https://code.jquery.com/jquery-3.0.0.min.js"></script> 

	<script>
		$(function(){
			
			$("html").addClass("js");
			
			var settings = <?php echo json_encode($settings); ?>;
			var url = "<?php echo ROOT_URL . basename(__FILE__); ?>";
			var path = "<?php echo ROOT_PATH; ?>"
			var strings = {
					now: "<i>NOW!</i>"
				};
			var terminal = "#terminal";
			var redirectUrl = "<?php echo ROOT_URL; ?>";
			
			function countdown(seconds, callback) {
				var $countdown = $(terminal).find(".countdown").last();
				var countdownInterval = setInterval(function() {
					seconds = seconds - 1;
					if(seconds == 0) {
						$countdown.html(strings.now);
						clearInterval(countdownInterval);
						if(typeof callback == "function") {
							callback();
						}
					} else {
						$countdown.html(seconds);
					}
				}, 1000)
			}
			
			$.each(['start', 'redirection'], function(i, v) {
				if(settings.timeout[v] === 0) {
					settings.timeout[v] = strings.now;
				}
			});

			function writeLine(str, callback) {
				if(typeof str !== "object") {
					var str = [str];
				}
				for (var i=0; i<str.length; i++) {
					$(terminal).html($(terminal).html() + str[i] + '<br>');
					if(i+1 == str.length && typeof callback == "function") {
						callback();
					}
				}
			}
			
			function writeLineBreak(times, callback) {
				if(typeof times == typeof undefined) {
					var times = 1;
				}
				var i;
				for (i=0; i<times; i++) {
					writeLine("");
					if(i+1 == times && typeof callback == "function") {
						callback();
					}
				}
			};
			
			function writeConsoleCommand(str) {
				writeLine('&gt; ' + str);
			}
			
			function writeConsoleResponse(str, valid) {
				if(typeof valid == typeof undefined) {
					var valid = true;
				}
				if(typeof str !== "object") {
					var str = [str];
				}
				var valid = valid ? 'ok' : 'error';
				$.each(str, function(i,v) {
					writeLine('<span class="status status--' + valid + '">' + valid + '</span> ' + v);
				});
				
			}
			
			var TOASTY = {
				vars: {
					selector: "#mk-toasty",
					image: 'data:image/png;base64,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',
					sound: 'data:audio/mpeg;base64,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',
					width: 225,
					height: 218,
					keys: [],
					combo: "39,39,40,38", // dos pa adelante, abajo arriba
				},
				call: function() {
					var $this = this;
					this.remove();
					$("#background").append('<audio id="'+this.vars.selector.replace("#", "")+'-audio" preload="auto" autoplay="autoplay"><source src="'+this.vars.sound+'" /></audio>').append($('<img id="'+this.vars.selector.replace("#", "")+'-image" src="'+this.vars.image+'" />').css({
						position: "fixed",
						right:-this.vars.width,
						bottom: 0,
						width: this.vars.width,
						height: this.vars.height,
					}).animate({right: 0}, 300).delay(300).animate({right: -this.vars.width}, 300, function() {
						$this.remove();
					}));
					
				},
				remove: function() {
					$("[id^='"+this.vars.selector.replace("#", "")+"']").remove();
				},
				keyListener: function(e) {
					TOASTY.vars.keys.push(e.charCode || e.keyCode);
					if(TOASTY.vars.keys.toString().indexOf(TOASTY.vars.combo) >= 0) {
						TOASTY.vars.keys = [];
						TOASTY.call();
					}
				}
			};
			window.addEventListener("keyup", TOASTY.keyListener, true);
			
			var INSTALL = {
				vars: {},
				download: function() {
					var _this = this;
					writeConsoleCommand("Downloading %s from GitHub".replace("%s", settings.repo));
					$.ajax({
						url: url,
						data: {action: "download"},
					}).always(function(data, status, XHR) {
						console.log(data)
						if(!XHR) {
							writeConsoleResponse("Can't connect to %s".replace("%s", url), false);
							return;
						}
						if(data.status_code == 200) {
							_this.vars.target_filename = data.download.filename;
							writeConsoleResponse("■■■■■■■■■■ 100% - Download saved as %s".replace("%s", data.download.filename));
							_this.extract();
						} else {
							writeConsoleResponse(data.responseJSON.error.message, false);
						}
					});
				},
				extract: function(callback) {
					var _this = this;
					writeConsoleCommand("Extracting downloaded file, this could take a while...");
					$.ajax({
						url: url,
						data: {action: "extract", file: _this.vars.target_filename},
					}).always(function(data, status, XHR) {
						if(!XHR) {
							writeConsoleResponse("Can't connect to %s".replace("%s", url), false);
							return;
						}
						if(data.status_code == 200) {
							TOASTY.call();
							writeConsoleResponse("■■■■■■■■■■ 100% - Extraction done");
							writeConsoleResponse("Toasty! " + settings.repo + " installed");
							setTimeout(function() {
								writeLineBreak();
								writeLine("Process completed!");
								if(settings.redirect) {
									writeLineBreak();
									writeLine(['Redirecting ' + (typeof settings.timeout.start == "number" ? ('in <span class="countdown">' + settings.timeout.start + '</span>') : 'right now')], function() {
										if(typeof settings.timeout.start === "number") {
											countdown(settings.timeout.start, function() {
												window.location = redirectUrl;
											});
										} else {
											window.location = redirectUrl;
										}
									});
								}
							}, 500);
						} else {
							writeConsoleResponse(data.responseJSON.error.message, false);
						}
					});
				},
			};
			
			writeLine(['The process will begin ' + (typeof settings.timeout.start == "number" ? ('in <span class="countdown">' + settings.timeout.start + '</span>') : 'right now')], function() {
				writeLineBreak(1);
				if(typeof settings.timeout.start === "number") {
					countdown(settings.timeout.start, function() {
						INSTALL.download();
					});
				} else {
					INSTALL.download();
				}
			});

		});
	</script>
</body>
</html>