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

class RoleChanger {
  /**
   * Register all the hooks and filters for the plugin
   */
  public function __construct() {
    // Session handling
    if(!session_id()) {
      session_start();
    }
    add_action('wp_logout',           array($this, 'unimpersonate'), 1);

    // Only admins can use this plugin (for obvious reasons)
    if(!current_user_can('add_users')) {
      return;
    }

    // Is this request attempting to impersonate someone?
    if(!empty($_GET['impersonate'])) {
      $this->impersonate($_GET['impersonate']);
    }

    add_action( 'admin_menu', array( &$this, 'add_top_level_menu' ) );
    $this->capability = "manage_options";


    add_action('wp_footer', array(&$this, 'add_role_levels_to_top'));
  }

  function add_role_levels_to_top() {
?>
    <div class="see-as-client">
      <span>
        View as:
        <a href="/?impersonate=1&role=Subscriber" class="active fasted" data-no-turbolink="true">Client</a>
        <a href="<?php echo wp_logout_url ?>" data-confirm="You'll need to sign back when you're finished viewing <?php bloginfo('name'); ?> as a prospect." data-method="delete" rel="nofollow">Prospect</a>
      </span>
    </div>
    <script type="text/javascript">
      $('.see-ass-client').hide().prependTo("#whitewrap")
    </script>
<?php
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

  public function impersonate($user_id) {
    global $current_user;
    get_currentuserinfo();

    if ($_GET['role'] == 'Prospect') {
      wp_logout();
    } else {
      $user_roles = $current_user->roles;
      $user_role = array_shift($user_roles);

      $_SESSION['impersonated_by']  = $current_user->ID;
      $_SESSION['impersonated_role'] = $user_role;

      $current_user->set_role($_GET['role']);
    }

    wp_redirect(home_url());
    exit;
  }

  public function unimpersonate() {
    global $current_user;
    get_currentuserinfo();

    if(!empty($_SESSION['impersonated_by'])) {
      $current_user->set_role($_SESSION['impersonated_role']);
      wp_redirect(admin_url());
      exit;
    }
  }

  public function redirect_to_site_as_client() {
    $impersonate_url = home_url() . '?impersonate=1&role=Subscriber';
    wp_redirect($impersonate_url); exit;
  }
}

add_action('init', create_function('', 'new DJG_Imposter;'));
