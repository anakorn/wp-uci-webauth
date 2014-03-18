<?php
class WebAuth
{
    // The URLs to the web authentication at login.uci.edu
    public $login_url    = 'https://login.uci.edu/ucinetid/webauth';
    public $logout_url   = 'https://login.uci.edu/ucinetid/webauth_logout';
    public $check_url    = 'http://login.uci.edu/ucinetid/webauth_check';

    // The cookie - the name of the cookie is 'ucinetid_auth'
    public $cookie;

    // The user's URL - indicates where to goes upon authentication
    public $url;

    // The user's remote address - matched against the auth_host
    public $remote_addr;

    // The various errors that might crop up are stored in this array
    public $errors = array();

    // These are the defined vars from login.uci.edu
    public $time_created = 0;
    public $ucinetid = '';
    public $campus_id = '';
    public $age_in_seconds = 0;
    public $max_idle_time = 0;
    public $auth_fail = '';
    public $seconds_since_checked = 0;
    public $last_checked = 0;
    public $auth_host = '';

    // Constructor for the web authentication
    public function __construct() {
        
        // First, let's check the PHP version
        $php_version = phpversion();
        if ($php_version < 4) {
            $this->errors[1] = "Warning, designed to work with PHP 4.x";
        }

        // Next, we'll grab some key global variables
        $cookie_vars_array = $GLOBALS['_COOKIE'];
        $get_vars_array = $GLOBALS['_GET'];
        $server_vars_array = $GLOBALS['_SERVER'];

        // Let's get the client's ip address
        $this->remote_addr = $server_vars_array['REMOTE_ADDR'];

        // Time to construct the client's URL
        // Check the server port first
        switch ($server_vars_array['SERVER_PORT']) {
            case "443":
                $prefix = "https://";
                break;
            default:
                $prefix = "http://";
                break;
        }

        // Now, we'll add the HTTP_HOST name
        $this->url = $prefix . $server_vars_array['HTTP_HOST'];

        // Let's add the script name
        $this->url .= $server_vars_array['SCRIPT_NAME'];

        // Reconstruct the GET variables
        if (is_array($get_vars_array) && sizeof($get_vars_array) > 0) {
            $i = 0;
            $get_string = '';
            while (list($k, $v) = each($get_vars_array)) {
                if ($k != 'login' && $k != 'logout') {
                    $get_string .= (($i++ == 0) ? '?' : '&') 
                        . urlencode($k) . '=' . urlencode($v);
                }
            }
            $this->url .= $get_string;
        }
        // Done with URL construction

        // Modify the various login.uci.edu URLs with our return URL
        $this->login_url .= '?return_url=' . urlencode($this->url);
        // $this->logout_url .= '?return_url=' . urlencode($this->url);

        // Let's add the cookie called 'ucinetid_auth'
        if (isset($cookie_vars_array['ucinetid_auth']) && $cookie_vars_array['ucinetid_auth']) {
            $this->cookie = $cookie_vars_array['ucinetid_auth'];
            $this->check_url .= '?ucinetid_auth=' . $this->cookie;
        }

        // Now, let's check authentication
        $this->check_auth();

    } // end Constructor

    // Check the authentication based on cookie
    public function check_auth() {

        // First, we'll check that we even have a cookie
        if (empty($this->cookie) || $this->cookie == 'no_key') {
            return false;
        }
        
        // Check that we can connect to login.uci.edu
        if (!$auth_array = @file($this->check_url)) {
            $this->errors[2] = "Unable to connect to login.uci.edu";
            return false;
        }

        // Make sure we have an array, and build the auth values
        if (is_array($auth_array)) {
            while (list($k,$v) = each($auth_array)) {
                if (!empty($v)) {
                    $v = trim($v);
                    $auth_values = explode("=", $v);
                    if (!empty($auth_values[0]) && !empty($auth_values[1])) 
                        $this->$auth_values[0] = $auth_values[1];
                }
            }

            // Check to ensure auth_host is verified
            if ($this->auth_host != $this->remote_addr) {
                $this->errors[3] = "Warning, the auth host doesn't match.";
                // @TODO: For purposes of testing, auth host mismatch should be OK.
                // return false;
                return true;
            }
            return true;
        }
    } // end check_auth

    // Boolean, determines if someone's logged in
    public function is_logged_in() {
        if ($this->time_created) return true;
        else return false;
    }

    // The login function
    public function login() {
        print Header('Location: ' . $this->login_url);
        exit;
    }

    // The logout function
    public function logout($return_url) {
        print Header('Location: ' . $this->logout_url . '?return_url=' . urlencode($return_url));
        exit;
    }

}
?>