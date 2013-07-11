<?php
/**
 * Plugin Name: See What Clients See
 * Plugin URI:
 * Author:  Gabriel Rios
 * Author URI:  http://gabrielrios.me
 * Description: Allow administrators to see the system with other roles eyes (Based on Imposter by Damian Gostomski)
 * Version:   0.1
 * License:   GPLv2
 */

class SeeAsClient {
  /**
   * Register all the hooks and filters for the plugin
   */
  public function __construct() {
    // Session handling
    if(!session_id()) {
      session_start();
    }

    add_action('wp_logout',           array($this, 'unimpersonate'), 1);

    // Is this request attempting to impersonate someone?
    if(!empty($_GET['impersonate'])) {
      $this->validate_impersonate($_GET['role']);
    }

    add_action( 'admin_menu', array( &$this, 'add_top_level_menu' ) );
    $this->capability = 'manage_options';

    add_action('wp_footer', array(&$this, 'add_role_levels_to_top'));

    $this->subscriber_url = add_query_arg(array( 'impersonate' => 1, 'role'=> 'subscriber'), home_url());
    $this->subscriber_url = wp_nonce_url( $this->subscriber_url, 'see-as-client' );

    $this->admin_url = add_query_arg(array( 'impersonate' => 1, 'role'=> 'finish'), home_url());
    $this->admin_url = wp_nonce_url( $this->admin_url, 'see-as-client' );

    add_action('wp_enqueue_scripts', array(&$this, 'add_scripts'));
    add_action('wp_head', array(&$this, 'add_styles'));
  }

  function add_role_levels_to_top() {
    if ($_SESSION['impersonated']) {
?>
    <div class="see-as-client">
      <span>
        View as:
        <a href="<?php echo $this->subscriber_url ?>" class="active" >Client</a>
        <a href="<?php echo wp_logout_url() ?>" data-confirm="You'll need to sign back when you're finished viewing <?php bloginfo('name'); ?> as a prospect." data-method="delete" rel="nofollow">Prospect</a>
        <a href="<?php echo $this->admin_url ?>" class="finish">Finish</a>
      </span>
    </div>
      <script type="text/javascript">
      $('.see-as-client').prependTo("#whitewrap")
        </script>
<?php
    }
  }

  function add_top_level_menu() {
    // Settings for the function call below
    $page_title = 'See What Clients see';
    $menu_title = 'See What Clients see';
    $menu_slug = 'see-as-clients';
    $function = array( &$this, 'redirect_to_site_as_client' );
    $icon_url = NULL;
    $position = '';

    // Creates a top level admin menu - this kicks off the 'display_page()' function to build the page
    $page = add_menu_page($page_title, $menu_title, $this->capability, $menu_slug, $function, $icon_url, 10);
  }

  public function validate_impersonate($new_role) {
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'see-as-client')) {
      die('NOT ONCE BITCH');
      return;
    }

    $this->impersonate($new_role);
  }

  private function impersonate($new_role) {
    global $current_user;
    get_currentuserinfo();

    if ($_GET['role'] == 'finish') {
      $this->unimpersonate();
    } else {
      echo 'Setting user role: ' . $new_role;
      $user_roles = $current_user->roles;
      $user_role = array_shift($user_roles);

      $_SESSION['impersonated']  = 1;
      if (empty($_SESSION['impersonated_role'])) {
        $_SESSION['impersonated_role'] = $user_role;
      }

      $current_user->set_role($new_role);
    }

    wp_redirect(home_url());
    exit;
  }

  public function unimpersonate() {
    global $current_user;
    get_currentuserinfo();

    if(!empty($_SESSION['impersonated'])) {
      $_SESSION['impersonated'] = "";
      $current_user->set_role($_SESSION['impersonated_role']);
      $_SESSION['impersonated_role'] = "";
      wp_redirect(admin_url());
      exit;
    }
  }

  public function redirect_to_site_as_client() {
    $this->impersonate('subscriber');
    wp_redirect(home_url());
    exit;
  }

  function add_scripts() {
    wp_deregister_script( 'jquery' );
    wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
    wp_enqueue_script( 'jquery' );
  }

  function add_styles() {
    $this->plugin_url = plugins_url( 'see_as_client' );
    wp_enqueue_style( "see_as_client", $this->plugin_url. "/css/see_style.css", array(),  '', 'screen');
  }
}

add_action('init', create_function('', 'new SeeAsClient;'));
