<?php
class UCI_WebAuth_Adapter {

  /**
   * Authenticates a user against the UCINetID authentication system.
   * This function should be hooked to the WP filter, 'authenticate'.
   *
   * http://ben.lobaugh.net/blog/7175/wordpress-replace-built-in-user-authentication
   * 
   * @since   1.0.0
   * 
   * @return  WP_User on success, WP_Error on failure.
   */
  public static function authenticate($user, $username, $password) {

    require_once( 'WebAuth.php' );
    $auth = new WebAuth();

    // Redirect user to WP homepage after logging out.
    $want_logout = $GLOBALS['_GET']['loggedout'] === 'true';
    if ( $want_logout ) {
      $auth->logout( get_home_url() );
      exit;
    }

    // Redirect user to UCINetID login if not already authenticated.
    if ( ! $auth->is_logged_in() ) {
      $auth->login();
    }

    // Deny access if user is not of a particular affiliation.
    if ( strpos( $auth->uci_affiliations, 'faculty' ) === false ) {

      if ( wp_get_referer() ) {
        wp_safe_redirect( wp_get_referer() );
      } else {
        wp_safe_redirect( get_home_url() );
      }
    } else {

      // Check if user exists in WP db (by their campus_id).
      if ( ! $user = get_user_by( 'login', $auth->ucinetid ) ) {

        // User does not exist within WP db, so create them.
        // @TODO: LDAP access to retrieve additional user info for account creation goes here.

        $userdata = array(
          'user_login'  => $auth->ucinetid,
          'role'        => 'author'
        );

        $user_id = wp_insert_user( $userdata );
        $user = new WP_User( $user_id );

      }
    }

    // Prevent WordPress from using its default authentication.
    remove_action('authenticate', 'wp_authenticate_username_password', 20);

    return $user;
  }

}
?>