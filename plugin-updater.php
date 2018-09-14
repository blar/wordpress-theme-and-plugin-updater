<?php

declare(strict_types = 1);

add_filter('extra_plugin_headers', function($headers) {
	$headers['Update URI'] = 'Update URI';
	return $headers;
});

add_filter('pre_set_site_transient_update_plugins', function($checked_data) {
	if(empty($checked_data->checked)) {
		return $checked_data;
	}
	$wordpressVersion = get_bloginfo('version');
	$plugins = get_plugins();
	foreach($plugins as $fileName => $plugin) {
		$updateUri = $plugin['Update URI'];

		if(empty($updateUri)) {
			continue;
		}

		$response = wp_remote_post($updateUri, [
			'user-agent' => 'WordPress/' . $wordpressVersion,
			'body' => json_encode([
				'plugin' => $plugin['Name'],
				'version' => $plugin['Version']
			])
		]);

		if(is_wp_error($response)) {
			continue;
		}

		if($response['response']['code'] !== 200) {
			continue;
		}

		$body = json_decode($response['body']);

		if(empty($body)) {
			continue;
		}

		if(version_compare($body->version, $plugin['Version'], '<=')) {
			continue;
		}

		$checked_data->response[$fileName] = (object) [
			'plugin' => $fileName,
			'slug' => dirname($fileName),
			'new_version' => $body->version,
			'date' => $body->date,
			'package' => $body->package,
			'url' => $body->url,
		];

	}

	return $checked_data;
});

#if (is_admin()) {
	get_transient('update_plugins', NULL);
#}
