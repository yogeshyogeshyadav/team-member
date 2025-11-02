<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Yogesh_Elementor_Team_Ajax_Widget extends Widget_Base {

    public function get_name() {
        return 'yogesh-team-members-ajax';
    }

    public function get_title() {
        return __( 'Yogesh Team Members Grid', 'yogesh-team-members' );
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return array( 'general' );
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'yogesh-team-members' ),
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => __( 'Members per page', 'yogesh-team-members' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 100,
            ]
        );

        $this->add_control(
            'filter_role',
            [
                'label' => __( 'Default filter by role (designation)', 'yogesh-team-members' ),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __( 'e.g. Manager, Developer', 'yogesh-team-members' ),
            ]
        );

        $this->add_control(
            'filter_skills',
            [
                'label' => __( 'Default filter by skills (comma separated)', 'yogesh-team-members' ),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __( 'PHP,JavaScript', 'yogesh-team-members' ),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __( 'Layout', 'yogesh-team-members' ),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'grid' => 'Grid',
                    'list' => 'List',
                ],
                'default' => 'grid',
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __( 'Columns (grid only)', 'yogesh-team-members' ),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'default' => '3',
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => __( 'Show search box', 'yogesh-team-members' ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_dark_toggle',
            [
                'label' => __( 'Show dark/light toggle', 'yogesh-team-members' ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render - server-side: output container and initial data.
     * In editor mode we render actual initial items so Elementor live preview shows real data.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $container_id = 'yogesh-tm-' . wp_generate_password( 6, false, false );

        // Data attributes for JS
        $data_atts = array(
            'data-per-page'   => absint( $settings['per_page'] ),
            'data-filter-role'=> esc_attr( $settings['filter_role'] ),
            'data-filter-skills'=> esc_attr( $settings['filter_skills'] ),
            'data-layout'     => esc_attr( $settings['layout'] ),
            'data-columns'    => esc_attr( $settings['columns'] ),
            'data-show-search'=> ( 'yes' === $settings['show_search'] ) ? '1' : '0',
            'data-show-toggle'=> ( 'yes' === $settings['show_dark_toggle'] ) ? '1' : '0',
        );

        $data_attr_string = '';
        foreach ( $data_atts as $k => $v ) {
            $data_attr_string .= sprintf( ' %s="%s"', $k, esc_attr( $v ) );
        }

        // Enqueue frontend script/style
        wp_enqueue_script( 'yogesh-tm-widget' );
        wp_enqueue_style( 'yogesh-tm-style' );

        // Root container
        echo '<div id="' . esc_attr( $container_id ) . '" class="yogesh-tm-widget" ' . $data_attr_string . '>';
        // Controls (search + toggle) will be injected by JS, but for editor show them server-side too
        // If in editor mode, render initial items server-side for live preview
        if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
            $this->render_initial_items_server_side( $settings, $container_id );
        } else {
            // show loading UI which JS will replace
            echo '<div class="yogesh-tm-loading">' . esc_html__( 'Loading team members...', 'yogesh-team-members' ) . '</div>';
        }

        echo '</div>';
    }

    /**
     * Helper used to render in the Elementor editor for live preview: fetches a few posts server-side.
     */
    protected function render_initial_items_server_side( $settings, $container_id ) {
        $args = array(
            'post_type' => YOGESH_TM_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => absint( $settings['per_page'] ),
        );

        $meta_query = array( 'relation' => 'AND' );
        if ( ! empty( $settings['filter_role'] ) ) {
            $meta_query[] = array( 'key' => 'role', 'value' => sanitize_text_field( $settings['filter_role'] ), 'compare' => '=' );
        }
        if ( ! empty( $settings['filter_skills'] ) ) {
            $skills = array_map( 'trim', explode( ',', $settings['filter_skills'] ) );
            $skills_clauses = array( 'relation' => 'OR' );
            foreach ( $skills as $skill ) {
                $skills_clauses[] = array( 'key' => 'skills', 'value' => $skill, 'compare' => 'LIKE' );
            }
            $meta_query[] = $skills_clauses;
        }
        if ( count( $meta_query ) > 1 ) {
            $args['meta_query'] = $meta_query;
        }

        $q = new WP_Query( $args );

        // Render markup that matches what JS will render
        $layout = $settings['layout'];
        $columns = intval( $settings['columns'] );

        $wrapper_classes = 'yogesh-tm-list';
        if ( 'grid' === $layout ) {
            $wrapper_classes = 'yogesh-tm-grid yogesh-tm-col-' . $columns;
        }

        // Header: search & toggle if enabled
        if ( 'yes' === $settings['show_search'] || 'yes' === $settings['show_dark_toggle'] ) {
            echo '<div class="yogesh-tm-controls">';
            if ( 'yes' === $settings['show_search'] ) {
                echo '<input class="yogesh-tm-search" placeholder="' . esc_attr__( 'Search members...', 'yogesh-team-members' ) . '" />';
            }
            if ( 'yes' === $settings['show_dark_toggle'] ) {
                echo '<button class="yogesh-tm-toggle" data-mode="light">' . esc_html__( 'Dark', 'yogesh-team-members' ) . '</button>';
            }
            echo '</div>';
        }

        echo '<div class="' . esc_attr( $wrapper_classes ) . '">';

        if ( $q->have_posts() ) {
            while ( $q->have_posts() ) {
                $q->the_post();
                $id = get_the_ID();

                // prefer ACF
                if ( function_exists( 'get_field' ) ) {
                    $full_name = get_field( 'full_name', $id ) ?: get_the_title();
                    $role = get_field( 'role', $id );
                    $photo_id = get_field( 'profile_photo', $id );
                    $email = get_field( 'email', $id );
                    $skills = get_field( 'skills', $id );
                } else {
                    $full_name = get_post_meta( $id, 'full_name', true ) ?: get_the_title();
                    $role = get_post_meta( $id, 'role', true );
                    $photo_id = get_post_meta( $id, 'profile_photo', true );
                    $email = get_post_meta( $id, 'email', true );
                    $skills = get_post_meta( $id, 'skills', true );
                }

                if ( is_string( $skills ) ) {
                    $skills = array_map( 'trim', explode( ',', $skills ) );
                }

                // markup
                echo '<div class="yogesh-tm-item">';
                echo '<div class="yogesh-tm-photo">';
                if ( ! empty( $photo_id ) && is_numeric( $photo_id ) ) {
                    echo wp_get_attachment_image( (int) $photo_id, 'medium' );
                } elseif ( has_post_thumbnail( $id ) ) {
                    echo get_the_post_thumbnail( $id, 'medium' );
                } else {
                    echo '<div class="yogesh-placeholder">' . esc_html__( 'No Image', 'yogesh-team-members' ) . '</div>';
                }
                echo '</div>'; // photo

                echo '<div class="yogesh-tm-meta">';
                echo '<h4 class="yogesh-name">' . esc_html( $full_name ) . '</h4>';
                if ( $role ) echo '<div class="yogesh-role">' . esc_html( $role ) . '</div>';
                if ( $email ) echo '<div class="yogesh-email"><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>';
                if ( ! empty( $skills ) && is_array( $skills ) ) {
                    echo '<div class="yogesh-skills">';
                    foreach ( $skills as $s ) {
                        echo '<span class="yogesh-skill">' . esc_html( $s ) . '</span>';
                    }
                    echo '</div>';
                }
                echo '</div>'; // meta

                echo '</div>'; // item
            }
            wp_reset_postdata();
        } else {
            echo '<div class="yogesh-no-members">' . esc_html__( 'No team members found.', 'yogesh-team-members' ) . '</div>';
        }

        echo '</div>'; // wrapper

        // Render pager placeholders (JS will replace)
        echo '<div class="yogesh-tm-pagination"></div>';
    }

    /**
     * Editor preview template - not required to include complex logic; server-side render covers live preview.
     */
    protected function _content_template() {
        ?>
        <#
        var cols = settings.columns || '3';
        var layout = settings.layout || 'grid';
        var show_search = settings.show_search === 'yes';
        var show_toggle = settings.show_dark_toggle === 'yes';
        #>
        <div class="yogesh-tm-widget">
            <# if ( show_search || show_toggle ) { #>
                <div class="yogesh-tm-controls">
                    <# if ( show_search ) { #>
                        <input class="yogesh-tm-search" placeholder="<?php echo esc_attr_e( 'Search members...', 'yogesh-team-members' ); ?>" />
                    <# } #>
                    <# if ( show_toggle ) { #>
                        <button class="yogesh-tm-toggle" data-mode="light">Dark</button>
                    <# } #>
                </div>
            <# } #>

            <div class="yogesh-tm-grid yogesh-tm-col-{{ cols }}">
                <# for ( var i=0; i < Math.max(1, settings.per_page); i++ ) { #>
                    <div class="yogesh-tm-item">
                        <div class="yogesh-tm-photo" style="width:120px;height:120px;background:#eee;border-radius:50%;"></div>
                        <div class="yogesh-tm-meta">
                            <h4 class="yogesh-name">Member Name</h4>
                            <div class="yogesh-role">Designation</div>
                        </div>
                    </div>
                <# } #>
            </div>

            <div class="yogesh-tm-pagination">Page 1</div>
        </div>
        <?php
    }
}
