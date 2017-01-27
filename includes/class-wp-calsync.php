<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       -
 * @since      1.0.0
 *
 * @package    Wp_Calsync
 * @subpackage Wp_Calsync/includes
 */

require plugin_dir_path( __FILE__ ) . '/fetchcal.php';

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Calsync
 * @subpackage Wp_Calsync/includes
 * @author     Matthew Wilkinson <matwilmail@gmail.com>
 */
class Wp_Calsync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Calsync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'wp-calsync';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

        // admin hooks
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action('admin_post_submit-form', array( $this, '_handle_form_action' )); // If the user is logged in
        add_action('admin_post_nopriv_submit-form', array( $this, '_handle_form_action' )); // If the user in not logged in

        add_action( 'authorise', array( $this, 'authorise' ) );

	}

	public function authorise() {
      //require_once __DIR__ . '/google-api/vendor/autoload.php';

      echo 'redirected';

      session_start();

      $client = new Google_Client();
      $client->setAuthConfigFile(__DIR__.'/client_secret.json');
      $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/gcalweb/oauth2callback.php');
      $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

      if (! isset($_GET['code'])) {
        $auth_url = $client->createAuthUrl();
        echo $auth_url;
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
      } else {
        $client->authenticate($_GET['code']);
        $_SESSION['access_token'] = $client->getAccessToken();
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/wp-admin/admin.php?page=calsync-page.php';
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
      }
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Calsync_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Calsync_i18n. Defines internationalization functionality.
	 * - Wp_Calsync_Admin. Defines all hooks for the admin area.
	 * - Wp_Calsync_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-calsync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-calsync-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-calsync-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-calsync-public.php';

		$this->loader = new Wp_Calsync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Calsync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Calsync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Calsync_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Calsync_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

    /**
     * Add admin menu
     */
    public function add_menu() {
      add_menu_page( 'Gig Import', 'Gig Import', 'manage_options', 'gig-import.php', array( $this, 'calsync_page'), 'dashicons-schedule' );

      //add_menu_page( 'Cal Sync', 'Can Sync', 'activate_plugins', 'wolf-jplayer-panel', array( $this, 'jplayer_panel' ) , 'dashicons-format-audio' );
      //add_submenu_page( 'wolf-jplayer-panel',  __( 'Manage playlists', 'wolf' ), __( 'Manage playlists', 'wolf' ), 'activate_plugins', 'wolf-jplayer-panel', array( $this, 'jplayer_panel' ) );
      //add_submenu_page( 'wolf-jplayer-panel',  __( 'Settings', 'wolf' ), __( 'Settings', 'wolf' ), 'activate_plugins', 'wolf-jplayer-options', array( $this, 'wolf_jplayer_settings' ) );
    }

    public function calsync_page() {
      $events = fetchcal();
      print_r($events);

      $ques = "something";
      echo "<form action='".get_admin_url()."admin-post.php' method='post'>";

      echo "<input type='hidden' name='action' value='submit-form' />";
      echo "<input type='hidden' name='hide' value='$ques' />";

      echo "<table>";
      echo "<tr id='r1'>";
      echo "<td><b>Date</b></td>";
      echo "<td><b>Time</b></td>";
      echo "<td><b>Venue</b></td>";
      echo "<td><b>Town</b></td>";
      echo "</tr>";

//      $id = 0;
//      foreach ($events as $event) {
//        echo '<input type="hidden" name="dat-' . $id . '" value="' . 'xxx' . '"" />';
//      }
      $id = 0;
      foreach ($events as $event) {
        $eventDateStr = $event->start->dateTime;
        if(empty($eventDateStr))
        {
          // it's an all day event
          $eventDateStr = $event->start->date;
        }
        else {
          $eventDateStr = substr($eventDateStr, 0, 10);
        }

        // conver date format
        $ymd = explode('-', $eventDateStr);
        $eventDateStr = $ymd[2] . '-' . $ymd[1] . '-' . $ymd[0];
        //echo $event->summary;
        $time = '8pm';
        $select = ' <select name="tim-' . $id . '">
  <option value="0">12am</option>
  <option value="1">1am</option>
  <option value="2">2am</option>
  <option value="3">3am</option>
  <option value="4">4am</option>
  <option value="5">5am</option>
  <option value="6">6am</option>
  <option value="7">7am</option>
  <option value="8">8am</option>
  <option value="9">9am</option>
  <option value="10">10am</option>
  <option value="11">11am</option>
  <option value="12">12pm</option>
  <option value="13">1pm</option>
  <option value="14">2pm</option>
  <option value="15">3pm</option>
  <option value="16">4pm</option>
  <option value="17">5pm</option>
  <option value="18">6pm</option>
  <option value="19">7pm</option>
  <option value="20" selected="selected">8pm</option>
  <option value="21">9pm</option>
  <option value="22">10pm</option>
  <option value="23">11pm</option>
</select> ';

        echo '<input type="hidden" name="dat-' . $id . '" value="' . $eventDateStr . '"" />';

        echo "<tr>";
        echo '<td><input type="checkbox" tabindex="-1" name="chk-' . $id . '">' . $eventDateStr . '</td>';
        //echo "<td><b>xxx</b></td>";
        echo '<td>' . $select . '</td>';
        //echo '<td><input type="text" name="tim-' . $id . '" size="10" maxlength="4" value="' . $time . '" ></td>';
        echo '<td><input type="text" name="ven-' . $id . '" size="60" maxlength="4" value="' . $event->summary . '" ></td>';
        echo '<td><input type="text" name="loc-' . $id . '" size="20" maxlength="4" value="' . $event->location . '"</td>';
        echo "</tr>";

        $id++;
      }

      echo "</table>";

      echo "<input type='submit' value='Import Dates' />";

      echo "</form>";
    }

    public function _handle_form_action(){

      //print_r($_POST);

      foreach($_POST as $key => $value) {
        //echo $key . ' ' . $value;
        if (substr($key, 0, 3) === 'chk') {
          $val = substr($key, 4);
          $date = $_POST['dat-' . $val];
          $venue = $_POST['ven-' . $val];
          $location = $_POST['loc-' . $val];
          echo '<div>';
          echo $val . ' ' . $date . ' ' . $venue . ' ' . $location;
          echo '</div>';
        }
      }

//      $post_id = wp_insert_post(array (
//        'post_type' => 'show',
//        'post_title' => 'Test show',
//        'post_content' => 'Show content',
//        'post_status' => 'publish',
//        'comment_status' => 'closed',   // if you prefer
//        'ping_status' => 'closed',      // if you prefer
//      ));
//
//      if ($post_id) {
//        // insert post meta
//        add_post_meta($post_id, '_wolf_show_venue', 'Vnyue');
//        add_post_meta($post_id, '_wolf_show_city', 'City');
//        add_post_meta($post_id, '_wolf_show_date', '06-10-2017');
//      }

    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Calsync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
