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

    // Login fields can't be empty.
    if ( $username == '' || $password == '' ) {
      return;
    }

    // Send POST request to webauth service, using the provided credentials.
    // Auth cookie 'ucinetid_auth' should be set after this.
    // $result = wp_remote_post( 'https://login.uci.edu/ucinetid/webauth', array(
    //   'ucinetid' => $username,
    //   'password' => $password
    // ) );
    // $result = wp_remote_post( 'http://127.0.0.1/test', array(
    //   'ucinetid' => $username,
    //   'password' => $password
    // ) );

    // if ( ! is_wp_error( $result ) ) {
    //   $cookie = $result['cookies'][0];
    //   setcookie( $cookie->name, $cookie->value, $cookie->expires, $cookie->path );
    // }

    // print '<pre>';
    // print_r($result);
    // print '</pre>';
    // return;

    // Verify that ucinetid auth cookie has been set.
    require_once( 'WebAuth.php' );
    $auth = new WebAuth();

    if ( !$auth->check_auth() ) {
      $user = new WP_Error( 'denied', __( "<strong>ERROR</strong>: Invalid UCINetID or password. Please try again." ) );
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