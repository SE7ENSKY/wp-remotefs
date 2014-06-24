<?php

if (is_admin()) {
	add_action('admin_menu', 'rfs_wphook_admin_menu');
}

add_action('admin_notices', 'rfs_admin_notice');
function rfs_admin_notice(){
	global $RFS_NOTICES;

	foreach ($RFS_NOTICES as $notice) {
		switch ($notice) {
			case "not-configured" : ?>
				<div class="error">
					<p>RemoteFS is installed but not configured.</p>
					<p>Either provide configuration via <a href="/wp-admin/options-general.php?page=rfs-settings-admin">options page</a> or provide environment vars:
					<br><code>REMOTEFS_PRIVATE_URL</code>, <code>REMOTEFS_PUBLIC_URL</code>.</p>
				</div>
				<?php break ;
			case "not-implemented-private" : ?>
				<div class="error">
					<p>RemoteFS has no implementation for configured private url.</p>
					<p>Please check <a href="/wp-admin/options-general.php?page=rfs-settings-admin">options page</a>.</p>
				</div>
				<?php break ;
			default: ?>
				<div class="error">
					<?=$notice?>
				</div>
				<?php
		}
	}
}

function rfs_wphook_admin_menu() {
	add_options_page(
		'Settings Admin', 
		'RemoteFS settings', 
		'manage_options', 
		'rfs-settings-admin', 
		'rfs_wphook_admin_settings_page'
	);
	add_action('admin_init', 'rfs_register_settings');
}

function rfs_register_settings() {
	register_setting('rfs-settings-group', 'rfs-private-url');
	register_setting('rfs-settings-group', 'rfs-public-url');
}

function rfs_wphook_admin_settings_page() { ?>
	<div class="wrap">
		<h2>RemoteFS settings</h2>

		<?php if (!empty(getenv("REMOTEFS_PRIVATE_URL"))) : ?>

			<p class="description">configured via environment variables</p>
			
			<h3 class="title">Private URL</h3>
			<p><code><?=getenv("REMOTEFS_PRIVATE_URL")?></code></p>

			<h3 class="title">Public URL</h3>
			<p><code><?=getenv("REMOTEFS_PUBLIC_URL")?></code></p>

		<?php else : ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'rfs-settings-group' ); ?>
				<?php do_settings_sections( 'rfs-settings-group' ); ?>

				<h3 class="title">Private URL</h3>
				<p class="description">example: ftp://login:password@host:port/media</p>
				<textarea name="rfs-private-url" class="large-text code" rows="2"><?=get_option('rfs-private-url')?></textarea>
				
				<h3 class="title">Public URL</h3>
				<p class="description">example: http://host/media</p>
				<textarea name="rfs-public-url" class="large-text code" rows="2"><?=get_option('rfs-public-url')?></textarea>
				
				<?php submit_button(); ?>

			</form>

		<?php endif ?>
	</div>
<?php }
