<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Yogesh_Team_Widget extends Widget_Base {

    public function get_name() {
        return 'yogesh_team_members';
    }

    public function get_title() {
        return __( 'Team Members Grid', 'yogesh' );
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Settings', 'yogesh' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'members_per_page',
            [
                'label' => __( 'Members per page', 'yogesh' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __( 'Layout', 'yogesh' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __( 'Grid', 'yogesh' ),
                    'list' => __( 'List', 'yogesh' ),
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        ?>
        <div class="yogesh-team-widget" 
             data-per-page="<?php echo esc_attr( $this->get_settings('members_per_page') ); ?>" 
             data-layout="<?php echo esc_attr( $this->get_settings('layout') ); ?>">
            <input type="text" class="yogesh-team-search" placeholder="Search members..." />
            <div class="yogesh-team-grid"></div>
            <div class="yogesh-pagination">
                <button class="prev">Prev</button>
                <span class="page-info"></span>
                <button class="next">Next</button>
            </div>
        </div>
        <?php
    }
}

add_action( 'wp_enqueue_scripts', function() {
    wp_register_script(
        'yogesh-team-ajax',
        plugins_url( '/assets/js/yogesh-team.js', dirname(__FILE__) ),
        [ 'jquery' ],
        '1.0',
        true
    );
    wp_enqueue_script('yogesh-team-ajax');
    wp_localize_script('yogesh-team-ajax', 'yogeshTeamData', [
        'apiUrl' => home_url( '/wp-json/team/v1/members' ),
    ]);

    wp_enqueue_style( 'yogesh-team-style', plugins_url( '/assets/css/yogesh-team.css', dirname(__FILE__) ) );
});
