<?php

ob_start();
require(__DIR__ . '/{{ path('install-dir', 'home-dir') }}/wp-load.php');
$headers = headers_list();
header_remove('Location');
ob_end_flush();

$status = 'OK';

$output = array();

$output['Response Headers'] = array();
foreach($headers as $header) {
	list($key, $value) = explode(':', $header);
	$output['Response Headers'][trim($key)] = trim($value);
}

$wpvars = array(
	'Paths' => array(
		'ABSPATH',
		'WPINC',
		'WP_LAND_DIR',
		'WP_PLUGIN_DIR',
		'WP_PLUGIN_URL',
		'WP_CONTENT_DIR',
		'WP_CONTENT_URL',
		'WP_HOME',
		'WP_SITEURL',
		'WP_TEMP_DIR'
	),
	'Themes' => array(
		'BACKGROUND_IMAGE',
		'HEADER_IMAGE',
		'HEADER_IMAGE_HEIGHT',
		'HEADER_IMAGE_WIDTH',
		'HEADER_TEXTCOLOR',
		'NO_HEADER_TEXT',
		'STYLESHEETPATH',
		'TEMPLATEPATH',
		'WP_USE_THEMES'
	),
	'Security' => array(
		'DISALLOW_FILE_EDIT',
		'DISALLOW_FILE_MODS',
		'DISALLOW_UNFILTERED_HTML',
		'FORCE_SSL_ADMIN',
		'FORCE_SSL_LOGIN'
	),
	'Cron' => array(
		'DISABLE_WP_CRON',
		'WP_CRON_LOCK_TIMEOUT',
		'ALTERNATE_WP_CRON'
	),
	'Kinsta' => array(
		'KINSTA_DEV_ENV',
		'KINSTA_CDN_USERDIRS',
		'KINSTA_CDN_USERURL',
		'KINSTA_CDN_ENABLER_MIN_WP',
		'KINSTAMU_VERSION',
		'KINSTAMU_DISABLE_AUTOPURGE',
		'KINSTAMU_WHITELABEL',
		'KINSTAMU_ROLE',
		'KINSTAMU_LOGO',
		'KINSTAMU_CUSTOM_MUPLUGIN_URL'
	)
);

foreach($wpvars as $label => $keys) {
	$title = 'WordPress ' . $label;
	$output[$title] = array();
	foreach($keys as $key) {
		if(defined($key)) {
			$output[$title][$key] = constant($key);
		}
	}
}

$servervars = array(
	'KINSTA'  => 'Kinsta Server Variables',
	'HTTP_CF' => 'CloudFlare HTTP Variables',
	'HTTP'    => 'General HTTP Variables',
	'SERVER'  => 'Server Variables',
	'REQUEST' => 'Request Variables',
	'REMOTE'  => 'Remote User Variables',
	'PHP'     => 'PHP Variables'
);

foreach($_SERVER as $key => $value) {
	foreach($servervars as $prefix => $title) {
		$test = substr($key, 0, strlen($prefix));
		if($test != $prefix) {
			continue;
		}

		if(!isset($output[$title])) {
			$output[$title] = array();
		}

		$output[$title][$key] = $value;
		continue 2;
	}
}

$extensions = array(
	//'cgi' => 'CGI Configuration'
);

foreach(ini_get_all(null, false) as $key => $value) {
	$parts = explode('.', $key);
	$title = 'PHP Configuration';

	if(count($parts) > 1) {
		$prefix = array_shift($parts);
		if(!isset($extensions[$prefix])) {
			continue;
		}

		$title = $extensions[$prefix];
	}

	if(is_null($value)) {
		continue;
	}

	if(!isset($output[$title])) {
		$output[$title] = array();
	}

	$output[$title][$key] = $value;
}


print '<html>';
print '<head>';
print '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">';
print '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>';
print '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
print '</head>';
print '<body>';
print '<div class="container-fluid">';
print '<div class="container">';

print '<div class="panel-group" id="accordian" role="tablist" aria-multiselectable="true">';
$count = 0;
foreach($output as $title => $vars) {
	$headingid = 'heading' . str_pad($count, 3, '0', STR_PAD_LEFT);
	$collapseid = 'collapse' . str_pad($count++, 3, '0', STR_PAD_LEFT);

	print '<div class="panel panel-default">';
	print '<div class="panel-heading" role="tab" id="' . $headingid . '">';
	print '<h3 class="panel-title">';
	print '<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordian" href="#' . $collapseid . '" aria-expanded="false" aria-controls="' . $collapseid . '">';
	print htmlspecialchars($title);
	print '</a>';
	print '</h3>';
	print '</div>';
	print '<div id="' . $collapseid . '" class="panel-collapse collapse" role="tabpanel" aria-labelledby="' . $headingid . '">';
	if(is_array($vars) && count($vars) > 0) {
		print '<table class="table table-striped" style="font-size: 14px; table-layout: fixed; word-wrap: break-word">';
		foreach($vars as $key => $value) {
			print '<tr>';
			print '<th>' . htmlspecialchars($key) . '</th>';
			print '<td>' . gettype($value) . '</td>';
			print '<td>';
			if(!is_string($value)) {
				print nl2br(htmlspecialchars(var_export($value)));
			} else {
				print nl2br(htmlspecialchars($value));
			}
			print '</tr>';
		}
		print '</table>';
	} else {
		print '<div class="panel-body">';
		print '<center>No Records Found</center>';
		print '</div>';
	}
	print '</div>';
	print '</div>';
}
print '</div>';

print '</div>';
print '</div>';
print '</body>';
print '</html>';
