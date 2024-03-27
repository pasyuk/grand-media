<?php
/*
Controller Name: Auth
Controller Description: Authentication add-on controller
*/

/**
 * Class Gmedia_JSON_API_Auth_Controller
 */
class Gmedia_JSON_API_Auth_Controller {

	/**
	 * @param string $cookie
	 *
	 * @return false|int
	 */
	public function validate_auth_cookie( $cookie ) {

		return wp_validate_auth_cookie( $cookie, 'logged_in' );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function generate_auth_cookie( $args ) {

		/**
		 * @var $_wpnonce_auth_app
		 * @var $username
		 * @var $password
		 */
		extract( $args );

		if ( ! wp_verify_nonce( $_wpnonce_auth_app, 'gmedia_auth_app' ) ) {
			return array( 'error' => array( 'code' => 'nononce', 'message' => 'Something goes wrong (nonce error)... try again.' ) );
		}

		if ( ! $username ) {
			return array( 'error' => array( 'code' => 'nologin', 'message' => "You must include a 'username' var in your request." ) );
		}

		if ( ! $password ) {
			return array( 'error' => array( 'code' => 'nopassword', 'message' => "You must include a 'password' var in your request." ) );
		}

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			remove_action( 'wp_login_failed', $username );

			return array( 'error' => array( 'code' => 'passerror', 'message' => 'Invalid username and/or password.' ) );
		}

		$expiration = time() + apply_filters( 'auth_cookie_expiration', 1209600, $user->ID, true );

		$cookie = wp_generate_auth_cookie( $user->ID, $expiration, 'logged_in' );

		preg_match( '|src="(.+?)"|', get_avatar( $user->ID, 32 ), $avatar );

		if ( ! isset( $avatar[1] ) ) {
			$avatar[1] = '';
		}

		return array(
			'cookie' => $cookie,
			'user'   => array(
				'id'           => $user->ID,
				'username'     => $user->user_login,
				'nicename'     => $user->user_nicename,
				'email'        => $user->user_email,
				'url'          => $user->user_url,
				'registered'   => $user->user_registered,
				'displayname'  => $user->display_name,
				'firstname'    => $user->user_firstname,
				'lastname'     => $user->last_name,
				'nickname'     => $user->nickname,
				'description'  => $user->user_description,
				'capabilities' => $user->wp_capabilities,
				'avatar'       => $avatar[1],
			),
		);
	}
}
