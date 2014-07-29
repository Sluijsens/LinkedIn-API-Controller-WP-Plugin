<?php

class LIAC_Admin {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

	}

	public function admin_menu() {
		add_options_page( "LinkedIn API Controller", "API Controller LinkedIn", "manage_options", "linkedin-api-controller-settings", array( $this, "settings_page" ) );

	}

	public function settings_page() {

		$https = ( isset( $_SERVER['HTTPS'] ) && "on" == $_SERVER['HTTPS'] ) ? "https://" : "http://";
		
		$api_email = ( false != get_option( "liac-api_email", false ) ) ? get_option( "liac-api_email" ) : "";
		$api_key = ( false != get_option( "liac-api_key", false ) ) ? get_option( "liac-api_key" ) : "";
		$api_secret = ( false != get_option( "liac-api_secret", false ) ) ? get_option( "liac-api_secret" ) : "";
		$api_scope = ( false != get_option( "liac-api_scope", false ) ) ? get_option( "liac-api_scope" ) : "";
		$api_redirect = ( false != get_option( "liac-api_redirect", false ) ) ? get_option( "liac-api_redirect" ) : "$https$_SERVER[HTTP_HOST]";
		$api_languages = ( false != get_option( "liac-api_languages", false ) ) ? get_option( "liac-api_languages" ) : "";

		if ( isset( $_POST['api_key'] ) ) {
			
			$api_email = $_POST['api_email'];
			update_option( "liac-api_email", $api_email );
			
			$api_key = $_POST['api_key'];
			update_option( "liac-api_key", $api_key );

			$api_secret = $_POST['api_secret'];
			update_option( "liac-api_secret", $api_secret );

			$api_scope = $_POST['api_scope'];
			update_option( "liac-api_scope", $api_scope );

			$api_redirect = ( "" != get_option( 'liac-api_redirect' ) ) ? $_POST['api_redirect'] : "$https$_SERVER[HTTP_HOST]";
			update_option( "liac-api_redirect", $api_redirect );
			
			$api_languages = $_POST['api_languages'];
			update_option( "liac-api_languages", $api_languages );
		}
		?>
		<div class="wrap">
			<h2>LinkedIn API Controller Settings</h2>
			<p>
				Register your application at LinkedIn and fill in the fields below.
			</p>
			<form method="post" action="">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="api_email"><?php _e( "E-mail address", "liac" ); ?></label></th>
							<td>
								<input type="email" class="regular-text" name="api_email" id="api_email" value="<?php echo $api_email; ?>" />
								<p class="description">
									<?php _e( "E-mail address to send the applications to (Leave empty to disable).", "liac" ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="api_key"><?php _e( "API Key", "liac" ); ?></label></th>
							<td><input type="text" class="regular-text" name="api_key" id="api_key" value="<?php echo $api_key; ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="api_secret"><?php _e( "API Secret", "liac" ); ?></label></th>
							<td><input type="text" class="regular-text" name="api_secret" id="api_secret" value="<?php echo $api_secret; ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="api_scope"><?php _e( "API Scope", "liac" ); ?></label></th>
							<td><input type="text" class="regular-text" name="api_scope" id="api_scope" value="<?php echo $api_scope; ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="api_redirect"><?php _e( "Redirect URI", "liac" ); ?></label></th>
							<td>
								<input type="text" class="regular-text" name="api_redirect" id="api_redirect" value="<?php echo $api_redirect; ?>" />
								<p class="description">
									<?php echo sprintf(  __( "Make sure you registered the given redirect uri at LinkedIn! NOTE: When your site is a multisite and does NOT use subdomains then make sure to add the link to the homepage. For example, if you have a dutch site, this field should be something like %s/nl/ and NOT just %s!", "liac" ), $https.$_SERVER['HTTP_HOST'], $https.$_SERVER['HTTP_HOST']); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="api_labguages"><?php _e( "Languages", "liac" ); ?></label></th>
							<td>
								<input type="text" class="regular-text" name="api_languages" id="api_languages" value="<?php echo $api_languages; ?>" />
								<p class="description">
									<?php _e( "Specify the language(s) you want the profiles to be in in a comma-separated list. For example: nl-NL, en-US, de-DE etc (NOTE: a normal dash '-' not an underscore '_'!). If none of the languages are available at a user's profile then the primary will be used.", "liac" ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td><input type="submit" value="Save" class="button-primary" /></td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<?php

	}

}

new LIAC_Admin();
