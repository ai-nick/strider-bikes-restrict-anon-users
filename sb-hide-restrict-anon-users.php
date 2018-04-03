<?php 
/*
Plugin Name: Strider Bikes Restrict Anon Users  
Plugin URI: https://github.com/nickwilliamsnewby
Description: gives a meta box to each page, checking it will hide the page from users who are not logged in
Author: Nicholas Williams
Version: 1.0.0
Author URI: http://williamssoftwaresolutions.com
Text Domain: sbbgRestrictedFromAnon
*/

if (!defined('ABSPATH')) {
    exit;
}

if(! defined( 'STRIDER_BIKES_RESTRICT_ANON_PATH' ) ) define('STRIDER_BIKES_RESTRICT_ANON_PATH', dirname( __FILE__ ) );
if(! defined( 'STRIDER_BIKES_RESTRICT_ANON_FILE' ) ) define('STRIDER_BIKES_RESTRICT_ANON_FILE', ( __FILE__ ) );




class Strider_Bikes_Restrict_Anon{

	/**
	 * @var object
	 */
	private static $_instance = false;

	/**
	 * @var string
	 */
	private $_plugin_url = '';

	/**
	 * @var string
	 */
    private $_plugin_template_path = '';

    //protected $_meta_boxes = array();
    //protected $_post_type = '';
    private $_grav_id = 0;



    function __construct(){
        //$this->_post_type = 'lp_unlock_oncomplete_cpt';
        $this->_tab_slug = sanitize_title( 'sb-restrict-anon' );
        $this->_grav_id = get_option('sb_bg_check_abg_grav_ID');
        $this->_plugin_template_path = STRIDER_BIKES_RESTRICT_ANON_PATH.'/templates/';
        $this->_plugin_url  = untrailingslashit( plugins_url( '/', STRIDER_BIKES_RESTRICT_ANON_FILE ));

        add_action( 'load-post.php', array( $this, 'sb_bg_add_meta_boxes' ), 0 );
        add_action( 'load-post-new.php', array( $this, 'sb_bg_add_meta_boxes' ), 0 );
        add_action('wp', array($this, 'restrict_until_complete_maybe'));
        add_action('wp', array($this, 'add_menu_filter'));
        
        add_action('admin_menu', array($this, 'sb_bg_restrict_anon_create_menu'));
    }

function add_loginout_link( $items, $args ) {
    if (is_user_logged_in() && $args->menu == 'primary') {
        $items .= '<li><a href="'. wp_logout_url() .'">Log Out</a></li>';
    }
    elseif (!is_user_logged_in() && $args->menu == 'primary') {
        $items .= '<li><a href="'.site_url().'/register">Register</a></li>';
        $items .= '<li><a href="'.site_url().'/login">Log In</a></li>';
    }
    return $items;
}

    function sb_bg_restrict_anon_create_menu(){
            //create new top-level menu
        add_menu_page('Strider Bikes Anon User Restrictions', 'Anon User Restrictions', 'administrator',__FILE__, array($this, 'sb_bg_restrict_pages_page') );
        //add_submenu_page(__FILE__,'Strider Bikes Bg Check Canidates', 'Background Check Candidates', 'administrator','sbbgCheckCanidates', array($this, 'sb_bg_check_candidates_admin_page'));
        //call register settings function
        //add_action( 'admin_init', array($this, 'register_sb_bg_check_settings') );
    }
 
    function add_menu_filter(){
        add_filter('nav_menu_link_attributes', array($this,'sb_bg_hide_appropriate_nav_links'), 10, 3);

        add_filter('wp_nav_menu_items', array($this, 'add_loginout_link'), 10, 2 );
    }
    function sb_bg_hide_appropriate_nav_links($atts, $item, $args){
        if( $args->menu == 'primary' ){
            $id = $item->object_id;
            $itemUnlocked = $this->lp_unlock_check_ze_page($id, 'sb_bg_lock_until_logged_in');
            if (!$itemUnlocked){
                $atts['style'] = 'display: none;';
            }
        }
        return $atts;
    }

   // add meta box to set page as locked until background check is complete
    public function sb_bg_add_meta_boxes() {
        $prefix                                        = '_lp_';
        new RW_Meta_Box(
            apply_filters( 'sb_bg_lock_page_until_bgCheck', array(
                    'title'      => 'Restrict Anonymous User Access',
                    'post_types' => 'page',
                    'context'    => 'normal',
                    'priority'   => 'high',
                    'fields'     => array(
                        array(
                            'name'        => 'Logged in Users Only',
                            'id'          => "sb_bg_lock_until_logged_in",
                            'type'        => 'checkbox',
                            'description' => __('Do you want to block this page from user who are not Logged In', 'sbbgCheck'),
                            'std'         => 0
                        )
                    )
                )
            )
        );
        }

    function restrict_until_complete_maybe(){
            global $wp_query;
            $pID = $wp_query->get_queried_object_id();
            $unlocked = $this->lp_unlock_check_ze_page($pID, 'sb_bg_lock_until_logged_in');
            if (!$unlocked){
                wp_redirect(get_site_url().'/login');
                exit;
            }
        }

    function lp_unlock_check_ze_page($cPageId, $metaKey){
            $cUser = learn_press_get_current_user();
            $lockVar = get_post_meta($cPageId, $metaKey, true);
            $isUnlocked = true;
            if($lockVar<1){
                return $isUnlocked;
            } else {
                if($metaKey == 'sb_bg_lock_until_logged_in' && !$cUser->ID){
                    return false;
                }
            }
            return $isUnlocked;
        }
    	/**
	 * @return bool|Strider_Bikes_Restrict_Anon because OOP is fun
	 */
	static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

}
//create an instance of our add - ons main class 
add_action( 'init', array( 'Strider_Bikes_Restrict_Anon', 'instance' ) );
