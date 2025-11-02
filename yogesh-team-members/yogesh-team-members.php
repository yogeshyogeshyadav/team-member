<?php
/**
 * Plugin Name: Yogesh Team Members
 * Plugin URI:  https://example.com/
 * Description: Manage Team Members with backend (CPT + role) and frontend Elementor widget + REST API (AJAX + caching).
 * Version:     1.1.0
 * Author:      Yogesh
 * Text Domain: yogesh-team-members
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'YOGESH_TM_PLUGIN_FILE', __FILE__ );
define( 'YOGESH_TM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOGESH_TM_POST_TYPE', 'team_member' ); // internal post type
define( 'YOGESH_TM_TRANSIENT_TTL', 5 * MINUTE_IN_SECONDS ); // caching TTL for REST results

class Yogesh_Team_Members {

    public function __construct() {
        // Register hooks
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Elementor widget registration
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Editor assets for Elementor editor
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
    }

    /**
     * Register the Team Members CPT
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Team Members', 'yogesh-team-members' ),
            'singular_name'      => __( 'Team Member', 'yogesh-team-members' ),
            'menu_name'          => __( 'Team Members', 'yogesh-team-members' ),
        );

        $capabilities = array(
            'edit_post'             => 'edit_team_member',
            'read_post'             => 'read_team_member',
            'delete_post'           => 'delete_team_member',
            'edit_posts'            => 'edit_team_members',
            'edit_others_posts'     => 'edit_others_team_members',
            'publish_posts'         => 'publish_team_members',
            'read_private_posts'    => 'read_private_team_members',
            'delete_posts'          => 'delete_team_members',
            'delete_private_posts'  => 'delete_private_team_members',
            'delete_published_posts'=> 'delete_published_team_members',
            'delete_others_posts'   => 'delete_others_team_members',
            'edit_private_posts'    => 'edit_private_team_members',
            'edit_published_posts'  => 'edit_published_team_members',
            'create_posts'          => 'create_team_members',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array( 'title', 'thumbnail', 'editor' ),
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'team-members' ),
            'capability_type'    => array( 'team_member', 'team_members' ),
            'map_meta_cap'       => true,
            'capabilities'       => $capabilities,
            'show_in_rest'       => true,
            'rest_base'          => 'team-members',
        );

        register_post_type( YOGESH_TM_POST_TYPE, $args );
    }

    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route( 'team/v1', '/members', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'rest_get_members' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'role' => array( 'required' => false ),
                'skills' => array( 'required' => false ),
                'per_page' => array( 'required' => false, 'default' => 10, 'sanitize_callback' => 'absint' ),
                'page' => array( 'required' => false, 'default' => 1, 'sanitize_callback' => 'absint' ),
                'search' => array( 'required' => false ),
                'layout' => array( 'required' => false ),
            ),
        ) );
    }

    /**
     * REST callback to get members — with transient caching.
     */
    public function rest_get_members( $request ) {
        $params = $request->get_params();

        // Cache key for query args
        $cache_key = 'yogesh_tm_members_' . md5( wp_json_encode( $params ) );

        // Check transient cache
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            $cached['cached'] = true;
            return rest_ensure_response( $cached );
        }

        // Build query
        $meta_query = array( 'relation' => 'AND' );

        if ( ! empty( $params['role'] ) ) {
            $meta_query[] = array(
                'key'     => 'role',
                'value'   => sanitize_text_field( $params['role'] ),
                'compare' => '='
            );
        }

        if ( ! empty( $params['skills'] ) ) {
            $skills = array_map( 'trim', explode( ',', sanitize_text_field( $params['skills'] ) ) );
            $skills_meta = array( 'relation' => 'OR' );
            foreach ( $skills as $skill ) {
                $skills_meta[] = array(
                    'key'     => 'skills',
                    'value'   => $skill,
                    'compare' => 'LIKE'
                );
            }
            $meta_query[] = $skills_meta;
        }

        $args = array(
            'post_type'      => YOGESH_TM_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => absint( $params['per_page'] ?? 10 ),
            'paged'          => absint( $params['page'] ?? 1 ),
            'meta_query'     => $meta_query,
            's'              => sanitize_text_field( $params['search'] ?? '' ),
        );

        $q = new WP_Query( $args );

        $members = array();
        foreach ( $q->posts as $post ) {
            $members[] = $this->prepare_member_for_response( $post );
        }

        $result = array(
            'members' => $members,
            'total'   => (int) $q->found_posts,
            'page'    => (int) $args['paged'],
            'per_page'=> (int) $args['posts_per_page'],
            'cached'  => false,
        );

        // Cache for 5 minutes (adjust constant as needed)
        set_transient( $cache_key, $result, YOGESH_TM_TRANSIENT_TTL );

        return rest_ensure_response( $result );
    }

    /**
     * Prepare single member array using ACF if available
     */
    private function prepare_member_for_response( $post ) {
        $id = $post->ID;

        if ( function_exists( 'get_field' ) ) {
            $full_name = get_field( 'full_name', $id );
            $role      = get_field( 'role', $id );
            $photo_id  = get_field( 'profile_photo', $id );
            $email     = get_field( 'email', $id );
            $skills    = get_field( 'skills', $id );
        } else {
            $full_name = get_post_meta( $id, 'full_name', true );
            $role      = get_post_meta( $id, 'role', true );
            $photo_id  = get_post_meta( $id, 'profile_photo', true );
            $email     = get_post_meta( $id, 'email', true );
            $skills    = get_post_meta( $id, 'skills', true );
        }

        if ( is_string( $skills ) ) {
            $skills = array_map( 'trim', explode( ',', $skills ) );
        } elseif ( empty( $skills ) ) {
            $skills = array();
        }

        $photo_url = '';
        if ( ! empty( $photo_id ) && is_numeric( $photo_id ) ) {
            $photo_url = wp_get_attachment_image_url( (int) $photo_id, 'full' );
        } elseif ( has_post_thumbnail( $id ) ) {
            $photo_url = get_the_post_thumbnail_url( $id, 'full' );
        }

        if ( empty( $full_name ) ) {
            $full_name = get_the_title( $id );
        }

        return array(
            'id'        => (int) $id,
            'full_name' => $full_name,
            'role'      => $role,
            'email'     => $email,
            'skills'    => $skills,
            'photo'     => $photo_url,
            'permalink' => get_permalink( $id ),
            'excerpt'   => get_the_excerpt( $id ),
        );
    }

    /**
     * Register Elementor widget
     */
    public function register_elementor_widget( $widgets_manager ) {
        if ( ! defined( 'ELEMENTOR_PATH' ) || ! class_exists( 'Elementor\Widget_Base' ) ) {
            return;
        }

        require_once __DIR__ . '/includes/elementor-widget-team-ajax.php';

        if ( class_exists( 'Yogesh_Elementor_Team_Ajax_Widget' ) ) {
            $widgets_manager->register( new \Yogesh_Elementor_Team_Ajax_Widget() );
        }
    }

    /**
     * Enqueue frontend JS/CSS for widget
     */
    public function enqueue_frontend_assets() {
        wp_register_script(
            'yogesh-tm-widget',
            plugins_url( 'includes/js/yogesh-tm-widget.js', __FILE__ ),
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script( 'yogesh-tm-widget', 'YOGESH_TM', array(
            'rest_url' => esc_url_raw( rest_url( 'team/v1/members' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ) );

        wp_register_style(
            'yogesh-tm-style',
            plugins_url( 'includes/css/yogesh-tm-style.css', __FILE__ ),
            array(),
            '1.0.0'
        );
    }

    /**
     * Enqueue scripts/styles inside Elementor editor
     */
    public function enqueue_editor_assets() {
        if ( ! wp_script_is( 'yogesh-tm-widget', 'registered' ) ) {
            $this->enqueue_frontend_assets();
        }
        wp_enqueue_script( 'yogesh-tm-widget' );
        wp_enqueue_style( 'yogesh-tm-style' );
    }
}

// Initialize plugin
global $yogesh_tm_plugin;
$yogesh_tm_plugin = new Yogesh_Team_Members();

/**
 * Activation routine: register CPT (so capabilities exist), add HR role and capabilities
 */
function yogesh_tm_activate() {
    $plugin = new Yogesh_Team_Members();
    $plugin->register_post_type();
    flush_rewrite_rules();

    $caps = array(
        'edit_team_member',
        'read_team_member',
        'delete_team_member',
        'edit_team_members',
        'edit_others_team_members',
        'publish_team_members',
        'read_private_team_members',
        'delete_team_members',
        'delete_private_team_members',
        'delete_published_team_members',
        'delete_others_team_members',
        'edit_private_team_members',
        'edit_published_team_members',
        'create_team_members',
    );

    if ( ! get_role( 'hr' ) ) {
        add_role( 'hr', 'HR', array() );
    }
    $hr = get_role( 'hr' );
    if ( $hr ) {
        foreach ( $caps as $cap ) {
            $hr->add_cap( $cap );
        }
    }

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( $caps as $cap ) {
            $admin->add_cap( $cap );
        }
    }
}
register_activation_hook( __FILE__, 'yogesh_tm_activate' );

/**
 * Deactivation routine: remove HR role and remove capabilities we added (from admin)
 */
function yogesh_tm_deactivate() {
    if ( get_role( 'hr' ) ) {
        remove_role( 'hr' );
    }

    $caps = array(
        'edit_team_member',
        'read_team_member',
        'delete_team_member',
        'edit_team_members',
        'edit_others_team_members',
        'publish_team_members',
        'read_private_team_members',
        'delete_team_members',
        'delete_private_team_members',
        'delete_published_team_members',
        'delete_others_team_members',
        'edit_private_team_members',
        'edit_published_team_members',
        'create_team_members',
    );

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( $caps as $cap ) {
            $admin->remove_cap( $cap );
        }
    }

    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'yogesh_tm_deactivate' );
