<?php

/**
 * Controller for LinkedIn API
 * 
 * @author Bryan Slop <bdslop@gmail.com>
 */
class LinkedIN_API_Controller {

	private $_domain;
	private $_api_key;
	private $_api_secret;
	private $_redirect_uri;
	private $_scope;
	private $_access_token;
	private $_is_authorized = false;

	function __construct( array $settings ) {
		$https = ( isset( $_SERVER['HTTPS'] ) && "on" == $_SERVER['HTTPS'] ) ? "https://" : "http://";

		$this->_domain = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->_api_key = $settings['api_key'];
		$this->_api_secret = $settings['api_secret'];
		$this->_redirect_uri = $settings['redirect_uri'];
		$this->_scope = $settings['scope'];
		
		if ( $this->checkAccessTokenCookie() ) {
			$this->_is_authorized = true;
			$this->_access_token = get_transient( $this->getUserIdFromCookie() );
			
		}

	}

	/**
	 * Redirect to authorization login of LinkedIn. If redirect is set to false then return the link to the authorization page.
	 * 
	 * @param type $redirect If set to true, redirect user. If set to false, return link to authorization page. A custom link also be given.
	 * @param type $redirect_uri The redirect URI to use after authorization is completed. If not set, the default will be used.
	 * @return String Link to authorization page.
	 */
	public function getAuthorizationCode( $redirect = TRUE, $headers = TRUE ) {
		//Set the parameters to get authorization code
		$params = array(
		    'response_type' => 'code',
		    'client_id' => $this->_api_key,
		    'scope' => $this->_scope,
		    'state' => uniqid( '', true ),
		    'redirect_uri' => $this->_redirect_uri
		);

		// Check if the current page is http or https
		$https = ( ! empty( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? "https://" : "http://";
		// Set the page to redirect to the current page so you will be redirected to the same page after authorizing
		$_SESSION['redirect_to'] = $https . "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $_SESSION['redirect_to'] = ( isset( $redirect ) && true != $redirect && false != $redirect ) ? $redirect : $_SESSION['redirect_to'];
		// Save the 'state' to prevet CSRF attack.
		$_SESSION['state'] = $params['state'];

		// Link to authorization page
		$url = "https://www.linkedin.com/uas/oauth2/authorization?" . http_build_query( $params );

		// Check if user needs to be redirected or needs the link
		if ( TRUE == $redirect ) {
                    if( $headers ) {
			Header("Location: $url");
			exit;
                    } else {
                        ?>
                        <script type="text/javascript">
                            location.href="<?php echo $url; ?>";
                        </script>
                        <?php
                    }
		} else {
			return $url;
		}

	}

	/**
	 * Get the LinkedIn access token
	 * 
	 * @return mixed Return the access token. Returns false on failure.
	 */
	public function retrieveAccessToken( $set_cookie = TRUE ) {

//		if ( ! empty( $_COOKIE['linkedin_access_token'] ) ) {
//
//			$this->_access_token = $_COOKIE['linkedin_access_token'];
//			
//			return $this->_access_token;
		/*} else*/ if ( isset( $_GET['code'] ) ) {
			// Set status of authorized to true
			$this->_is_authorized = true;

			// Get tauthorization code
			$authorization_code = $_GET['code'];

			// Create access token request link
			$params = array(
			    'grant_type' => 'authorization_code',
			    'code' => $authorization_code,
			    'client_id' => $this->_api_key,
			    'client_secret' => $this->_api_secret,
			    'redirect_uri' => $this->_redirect_uri
			);
			$url = "https://www.linkedin.com/uas/oauth2/accessToken?" . http_build_query( $params );
			
			// Set stream context to method POST
			$context = stream_context_create(
				array(
				    'http' => array(
					'method' => 'POST'
				    )
				) );

			// Send request and retrieve access token
			$result = file_get_contents( $url, false, $context );

			// Check if result is not false
			if ( $result != FALSE ) {
				// PHP Native object
				$result = json_decode( $result );

				if( $set_cookie ) {
					// Put the token in a cookie
					setcookie( "linkedin_access_token", $result->access_token, time() + $result->expires_in - ( 60 * 60 * 24 * 10 ), "/", $_SERVER['HTTP_HOST'] );
//					setcookie( "linkedin_access_token", $result->access_token, time() + 30, "/", $_SERVER['HTTP_HOST'] );
				}
				$this->_access_token = $result->access_token;

				return $result;
			} else {
				return false;
			}
		} else {
			$this->_is_authorized = false;
			return false;
		}

	}

	/**
	 * Fetch data from linkedin API
	 * 
	 * @param string $resource
	 * @param string $method
	 * @return mixed
	 */
	public function fetch( $resource, $method = 'GET', $languages = 'en-US' ) {

		if ( ! empty( $this->_access_token ) && FALSE != $this->_access_token ) {

			$params = array(
			    'oauth2_access_token' => $this->_access_token,
			    'format' => 'json',
			);

			// Need to use HTTPS
			$url = 'https://api.linkedin.com' . $resource . '?' . http_build_query( $params );
			// Tell stream to make a (GET, POST, PUT, or DELETE) request
			$context = stream_context_create(
				array( 'http' =>
				    array(
					'method' => $method,
					'header'=>"Accept-language: $languages\r\n"
				    )
				)
			);

			// Hocus Pocus
			$response = @file_get_contents( $url, false, $context );
			
			if( FALSE !== $response ) {
			    // Native PHP object, please
			    return new LIAC_Data( json_decode( $response ) );
			} else {
			    // Failed to get data, might be revoked authorization so authorize (again)
			    $this->getAuthorizationCode( TRUE, FALSE );
			}
		} else {
                        $this->getAuthorizationCode( TRUE, FALSE );
		}

	}

	/**
	 * Check if authorization is complete
	 * @return boolean
	 */
	public function isAuthorized() {

		return $this->_is_authorized;

	}

	/**
	 * Check if the access token cookie still exists
	 * @return boolean
	 */
	public function checkAccessTokenCookie() {
//		var_dump($_COOKIE);
		if ( ! empty( $_COOKIE['linkedin_access_token'] ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Check if user has an access token
	 * @return boolean
	 */
	public function hasAccessToken() {
            
		if ( ! empty( $this->_access_token ) && false != $this->_access_token ) {
			return true;
		} else {
			return false;
		}

	}
	
	public function getAccessToken() {
		return $this->_access_token;
	}
	
	public function setAccessToken( $access_token ) {
		$this->_access_token = $access_token;
	}
	
	public function getUserIdFromCookie() {
		return $_COOKIE["linkedin_access_token"];
	}
}
