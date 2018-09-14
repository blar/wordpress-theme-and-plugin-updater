<?php

declare(strict_types = 1);

add_filter('extra_theme_headers', function($headers) {
	$headers['Update URI'] = 'Update URI';
	return $headers;
});

add_filter('pre_set_site_transient_update_themes', function($checked_data) {
	if(empty($checked_data->checked)) {
		return $checked_data;
	}

	$wordpressVersion = get_bloginfo('version');
	$themes = wp_get_themes();
	foreach($themes as $directory => $theme) {
		$updateUri = $theme->get('Update URI');

		if(empty($updateUri)) {
			continue;
		}

		$response = wp_remote_post($updateUri, [
			'user-agent' => 'WordPress/' . $wordpressVersion,
			'body' => json_encode([
				'theme' => $theme->name,
				'version' => $theme->version
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

		if(version_compare($body->version, $theme->version, '<=')) {
			continue;
		}

		$checked_data->response[$directory] = [
			'theme' => $directory,
			'slug' => $directory,
			'new_version' => $body->version,
			'date' => $body->date,
			'package' => $body->package,
			'url' => $body->url
		];

		/*
		echo '<pre>';
		var_dump($checked_data);
		die();
		*/
	}

	return $checked_data;
});


if (is_admin()) {
	get_transient('update_themes', NULL);
}
