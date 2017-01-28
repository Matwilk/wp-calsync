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

require plugin_dir_path( __FILE__ ) . '/utils.php';

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
      // fetch existing gigs from WP database
      $gigs = getExistingGigs();
      $dates = array();

      foreach($gigs as $gig) {
//        echo '<div>';
//        echo 'title = ' . $gig->post_title . 'date = ' . $gig->meta_value;
//        echo '</div>';
          $parts = explode('-', $gig->meta_value);
          //echo $parts;
          $date = $parts[1] . '-' . $parts[0] . '-' . $parts[2];
        $dates[$date] = $date;
      }

      //echo $dates;
      $events = fetchcal();
      //print_r($dates);

      echo "<h1>Gig dates</h1>";
      echo "<p>Ensure the dates you want to add are selected.  If the Google calendar data doesn't already have the right Venue and Location set then edit manually before submitting the form.";
      echo "<p>Any dates that have already have a date added are have been unselected - assumes that gig has already been added.";
      echo "<form action='".get_admin_url()."admin-post.php' method='post'>";

      echo "<input type='hidden' name='action' value='submit-form' />";

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

      $times = getTimes();

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

        $select = ' <select name="tim-' . $id . '">';
        foreach ($times as $time => $tStr) {
          $select = $select . '<option value="' . $time . '">' . $tStr . '</option>';
        }
        $select = $select . '</select>';

        echo '<input type="hidden" name="dat-' . $id . '" value="' . $eventDateStr . '"" />';

        echo "<tr>";

        if (in_array($eventDateStr, $dates)) {
          echo '<td><input type="checkbox" tabindex="-1" name="chk-' . $id . '">' . $eventDateStr . '</td>';
        }
        else {
          echo '<td><input type="checkbox" tabindex="-1" name="chk-' . $id . '" checked>' . $eventDateStr . '</td>';
        }

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

      //print_r($dates);
      $times = getTimes();

      foreach($_POST as $key => $value) {
        //echo $key . ' ' . $value;
        if (substr($key, 0, 3) === 'chk') {
          $val = substr($key, 4);
          $parts = explode('-', $_POST['dat-' . $val]);
          $date = $parts[1] . '-' . $parts[0] . '-' . $parts[2];
          $time = $times[$_POST['tim-' . $val]];
          $venue = $_POST['ven-' . $val];
          $location = $_POST['loc-' . $val];
          echo '<div>';
          echo $val . ' ' . $date . ' ' . $time . ' ' . $venue . ' ' . $location;
          echo '</div>';

          if ($venue == '') {
            $venue = 'TBC';
          }

          if ($location == '') {
            $location = 'TBC';
          }

          $post_id = wp_insert_post(array (
            'post_type' => 'show',
            'post_title' => $venue,
            //'post_content' => 'Show content',
            'post_status' => 'publish',
            'comment_status' => 'closed',   // if you prefer
            'ping_status' => 'closed',      // if you prefer
          ));

          if ($post_id) {
            // insert post meta
            add_post_meta($post_id, '_wolf_show_venue', $venue);
            add_post_meta($post_id, '_wolf_show_city', $location);
            add_post_meta($post_id, '_wolf_show_date', $date);

            if ($time != '-') {
              add_post_meta($post_id, '_wolf_show_time', $time);
            }
          }
        }
      }

      wp_redirect('edit.php?post_type=show');

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
