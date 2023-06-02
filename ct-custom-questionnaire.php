<?php
/**
 * Plugin Name: WP QuizAdventure
 * Description: A custom questionnaire plugin with yes/no questions and grouped into steps.
 * Version: 1.0
 * Author: createIT
 */

// Create custom post type for questions
function cq_create_question_post_type() {
    $labels = array(
        'name' => 'Questions',
        'singular_name' => 'Question',
        'menu_name' => 'Questions',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-editor-help',
        'has_archive' => false,
        'rewrite' => array('slug' => 'questions'),
    );

    register_post_type('cq_question', $args);
}

add_action('init', 'cq_create_question_post_type');

// Create custom taxonomy for steps
function cq_create_steps_taxonomy() {
    $labels = array(
        'name' => 'Steps',
        'singular_name' => 'Step',
        'menu_name' => 'Steps',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
    );

    register_taxonomy('cq_step', 'cq_question', $args);
}

add_action('init', 'cq_create_steps_taxonomy');

// Add custom meta box for question settings
function cq_add_question_settings_meta_box() {
    add_meta_box(
        'cq_question_settings',
        'Question Settings',
        'cq_question_settings_meta_box_callback',
        'cq_question',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'cq_add_question_settings_meta_box');

/**
 * Implement the cq_question_settings_meta_box_callback function for rendering the custom meta box for question settings (score value and recommendation text):
 */

function cq_question_settings_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('cq_question_settings_nonce', 'cq_question_settings_nonce_field');

    // Get stored values
    $score_value = get_post_meta($post->ID, 'cq_score_value', true);
    $recommendation_text = get_post_meta($post->ID, 'cq_recommendation_text', true);

    // Render meta box fields
    echo '<p><label for="cq_score_value">Score Value:</label>';
    echo '<input type="number" id="cq_score_value" name="cq_score_value" value="' . esc_attr($score_value) . '" min="0" step="1" style="width: 100%;" /></p>';

    echo '<p><label for="cq_recommendation_text">Recommendation Text:</label>';
    echo '<textarea id="cq_recommendation_text" name="cq_recommendation_text" rows="4" style="width: 100%;">' . esc_textarea($recommendation_text) . '</textarea></p>';
}

/**
 * Add the following function to save the custom meta box values:
 */

function cq_save_question_settings_meta_box_data($post_id) {
    // Verify nonce
    if (!isset($_POST['cq_question_settings_nonce_field']) || !wp_verify_nonce($_POST['cq_question_settings_nonce_field'], 'cq_question_settings_nonce')) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save score value
    if (isset($_POST['cq_score_value'])) {
        update_post_meta($post_id, 'cq_score_value', intval($_POST['cq_score_value']));
    }

    // Save recommendation text
    if (isset($_POST['cq_recommendation_text'])) {
        update_post_meta($post_id, 'cq_recommendation_text', sanitize_textarea_field($_POST['cq_recommendation_text']));
    }
}

add_action('save_post', 'cq_save_question_settings_meta_box_data');

/**
 * First, create a shortcode function to display the questionnaire:
 */


function cq_display_questionnaire_shortcode($atts) {
    // Extract the shortcode attributes
    $atts = shortcode_atts( array(
        'id' => null,
    ), $atts );

    // Get the quiz post by ID
    $quiz_post = get_post($atts['id']);

    if ( ! $quiz_post || $quiz_post->post_type !== 'ct_quiz' ) {
        return '<p class="error">Invalid quiz ID!</p>';
    }

    // Get the steps for this quiz
    $steps = get_post_meta( $quiz_post->ID, 'steps', true );

    if ( ! $steps ) {
        return '<p class="error">No quiz steps found!</p>';
    }

    // Convert the comma-separated list of step IDs to an array
    $step_ids = explode( ',', $steps );

    // Enqueue scripts and styles
    wp_enqueue_style('cq-styles', plugins_url('css/styles.css', __FILE__));
    wp_enqueue_script('cq-scripts', plugins_url('js/scripts.js', __FILE__), array('jquery'), null, true);

    // Prepare output
    $output = '<div id="cq-questionnaire">';
    $output .= cq_display_steps($step_ids, $quiz_post->ID);
    $output .= cq_display_questionnaire($step_ids, $quiz_post->ID);
    $output .= '</div>';

    return $output;
}


add_shortcode('cq_questionnaire', 'cq_display_questionnaire_shortcode');

/**
 * Create functions to render the steps and questions:
 */

function cq_display_steps($step_ids, $quiz_id) {

    $steps = get_post_meta( $quiz_id, 'steps', true );

    $output = '<div class="cq-steps">';
    if ( $steps ) {
        $step_ids = explode( ',', $steps );
        $step_counter = 0;
        foreach ( $step_ids as $key => $step_id ) {
            $step_counter++;
            $term = get_term_by( 'id', $step_id, 'cq_step' );
            $output .= '<div class="cq-step cq-step-' . ($step_counter) . '" data-step-id="' . $term->term_id . '">' . $term->name . '</div>';
        }
    }

    $output .= '</div>';

    return $output;
}



// Shortcode to display the questionnaire
function cq_display_questionnaire($step_ids, $quiz_id) {

    $steps = get_post_meta( $quiz_id, 'steps', true);

    // Initialize the output variable
    $output = '<div class="qp-questionnaire" id="qp-questionnaire">';
    if ( $steps ) {
        $step_ids = explode( ',', $steps );;
        $step_counter = 0;
        foreach ( $step_ids as $key => $step_id ) {
            $step_counter++;
            $term = get_term_by( 'id', $step_id, 'cq_step' );
            $output .= '<div class="qp-step" id="qp-step-' . esc_attr($step_counter) . '" ' . ($step_counter > 1 ? 'style="display:none;"' : '') . '>';
            $output .= '<h2>' . esc_html($term->name) . '</h2>';

            $question_args = array(
                'post_type' => 'cq_question',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'cq_step',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ),
                ),
                'orderby' => 'menu_order',
                'order' => 'ASC',
            );

            $question_query = new WP_Query($question_args);
            if ($question_query->have_posts()) {
                while ($question_query->have_posts()) {
                    $question_query->the_post();
                    $output .= '<div class="qp-question">';
                    $output .= '<p>' . esc_html(get_the_title()) . '</p>';
                    $output .= '<label><input type="radio" name="cq_question_' . esc_attr(get_the_ID()) . '" value="yes"> Yes</label>';
                    $output .= '<label><input type="radio" name="cq_question_' . esc_attr(get_the_ID()) . '" value="no"> No</label>';
                    $output .= '</div>';
                }
            }
            wp_reset_postdata();

            $output .= '<div class="qp-navigation">';

            $step_ids = explode( ',', $steps );

            if ($step_counter > 1) {
                $output .= '<button type="button" class="qp-prev-step">Previous</button>';
            }
            if ($step_counter < count($step_ids)) {
                $output .= '<button type="button" class="qp-next-step">Next</button>';
            } else {
                $output .= '<input type="submit" value="Submit" class="qp-submit">';
            }
            $output .= '</div>';

            $output .= '</div>';
        }
    }

    $output .= '</div>';
    $output .= '<div id="qp-results" style="display:none;"></div>';

    return $output;
}


// AJAX handler for form submission
function cq_submit_questionnaire() {
    parse_str($_POST['form_data'], $form_data);

    // Calculate the score and recommendations
    $score = 0;
    $total = 0;
    $recommendations = array();

    foreach ($form_data as $key => $value) {
        if (strpos($key, 'cq_question_') === 0) {
            $question_id = intval(substr($key, 12));
            $question_score = intval(get_post_meta($question_id, 'cq_score_value', true));
            $question_recommendation = get_post_meta($question_id, 'cq_recommendation_text', true);

            if ($value === 'yes') {
                $score += $question_score;
            } else {
                if (!empty($question_recommendation)) {
                    $recommendations[] = $question_recommendation;
                }
            }
            $total += $question_score;
        }
    }

    // Calculate the total percentage score
    $percentage = $total > 0 ? round(($score / $total) * 100) : 0;

    $max_score_possible = $total; // The total score possible

    $hash = cq_save_results($percentage, $max_score_possible, $score, serialize($recommendations));

    $results_url = get_results_url($hash);

    wp_send_json_success(array('url' =>  $results_url));
    wp_die();
}
add_action('wp_ajax_submit_questionnaire', 'cq_submit_questionnaire');
add_action('wp_ajax_nopriv_submit_questionnaire', 'cq_submit_questionnaire');



/**
 * enqueue the script with localized data for AJAX:
 */

function cq_enqueue_scripts() {
    wp_enqueue_script('cq-scripts', plugins_url('js/scripts.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cq-scripts', 'cq_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cq_save_results_nonce')
    ));

    // Localize the script with the AJAX URL
    wp_localize_script('qp-scripts', 'cq_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'cq_enqueue_scripts');


/**
 *  load the custom page template from the plugin folder
 */

function cq_results_shortcode($atts) {
    // Start output buffering
    ob_start();

    // Include the content of the page-results.php file
    include plugin_dir_path(__FILE__) . 'page-results.php';

    // Return the buffered output
    return ob_get_clean();
}

add_shortcode('questionnaire_results', 'cq_results_shortcode');

/**
 * create the table upon plugin activation
 */

function cq_create_results_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cq_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        hash varchar(255) NOT NULL,
        total_score decimal(5, 0) NOT NULL,
        max_score_possible decimal(5, 2) NOT NULL,
        raw_score decimal(5, 2) NOT NULL,
        recommendations text NOT NULL,
        email_share_count int(10) UNSIGNED NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hash (hash)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'cq_create_results_table');


/**
 * Add an AJAX handler to save the user's results and generate the hash
 */


function cq_save_results($total_score, $max_score_possible, $score, $recommendations) {
    // Generate a unique hash
    $hash = wp_hash(wp_generate_uuid4(), 'secure_auth');

    // Save the results in the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'cq_results';
    $wpdb->insert($table_name, array(
        'hash' => $hash,
        'total_score' => $total_score,
        'max_score_possible' => $max_score_possible,
        'raw_score' => $score,
        'recommendations' => $recommendations,
    ));

    // Return the generated hash
    return $hash;
}

add_action('wp_ajax_cq_save_results', 'cq_save_results');
add_action('wp_ajax_nopriv_cq_save_results', 'cq_save_results');

function get_results_url($hash){

    $results_url = site_url('/results/') . $hash;
    return $results_url;
}


/**
 * AJAX handler to send the email
 */


function cq_send_results_email() {
    // Verify nonce
    check_ajax_referer('cq_save_results_nonce', 'nonce');

    // Get POST data
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $hash = isset($_POST['hash']) ? sanitize_text_field($_POST['hash']) : '';

    // Get the results from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'cq_results';
    $results = $wpdb->get_row("SELECT * FROM $table_name WHERE hash = '$hash'");

    // Send the email
    $subject = 'Your Questionnaire Results';

    // Load email template
    $template = file_get_contents(plugin_dir_path(__FILE__) . 'email-template.html');

    // Replace placeholders with actual values
    $template = str_replace('{score}', $results->total_score, $template);
    $template = str_replace('{url}', get_results_url($hash), $template);


    $headers = array(
        'From: Your Name <noreply@example.com>',
        'Content-Type: text/html; charset=UTF-8'
    );

    if (wp_mail($email, $subject, $template, $headers)) {
        // Update the email_share_count in the database
        $wpdb->update($table_name, array('email_share_count' => ++$results->email_share_count), array('id' => $results->id));

        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
    die();
}

add_action('wp_ajax_cq_send_results_email', 'cq_send_results_email');
add_action('wp_ajax_nopriv_cq_send_results_email', 'cq_send_results_email');


/**
 * create an admin list table to display all the results from the cq_results custom table
 */

// Add a new admin menu item
add_action('admin_menu', 'cq_add_results_list_page');

function cq_add_results_list_page() {
    $custom_post_type_slug = 'cq_question'; // Replace with your custom post type slug

    add_submenu_page(
        "edit.php?post_type={$custom_post_type_slug}",
        __('Questionnaire Results', 'cq'),
        __('Results', 'cq'),
        'manage_options',
        'cq-results',
        'cq_render_results_list_page'
    );
}

// Render the list table on the admin page
function cq_render_results_list_page() {
    require_once('class-cq-results-list-table.php');
    $resultsListTable = new CQ_Results_List_Table();
    $resultsListTable->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php _e('Questionnaire Results', 'cq'); ?></h1>
        <form method="post">
            <?php $resultsListTable->display(); ?>
        </form>
    </div>
    <?php
}

/**
 * new settings section and custom fields for the thank you messages and score ranges.
 */



function cq_thank_you_settings_menu() {

    $custom_post_type_slug = 'cq_question'; // Replace with your custom post type slug
    add_submenu_page(
        "edit.php?post_type={$custom_post_type_slug}",
        __('Thank You Messages', 'cq'),
        __('Thank You Messages', 'cq'),
        'manage_options',
        'cq_thank_you_settings',
        'cq_thank_you_settings_page'
    );
}

add_action('admin_menu', 'cq_thank_you_settings_menu');

function cq_thank_you_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Thank You Messages', 'cq'); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('cq_thank_you_settings');
            do_settings_sections('cq_thank_you_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'cq_register_settings');

function cq_register_settings() {
    // Register a new settings section
    add_settings_section(
        'cq_thank_you_messages',
        'Thank You Messages',
        null,
        'cq_thank_you_settings'
    );

    // Register custom fields for thank you messages
    for ($i = 1; $i <= 3; $i++) {
        add_settings_field(
            "cq_thank_you_message_{$i}",
            "Thank You Message {$i}",
            'cq_add_thank_you_message_field',
            'cq_thank_you_settings',
            'cq_thank_you_messages',
            array('message_id' => $i)
        );

        register_setting('cq_thank_you_settings', "cq_thank_you_message_{$i}");
        register_setting('cq_thank_you_settings', "cq_thank_you_message_{$i}_min_score");
        register_setting('cq_thank_you_settings', "cq_thank_you_message_{$i}_max_score");
    }
}


function cq_add_thank_you_message_field($args) {
    $message_id = $args['message_id'];
    $message = get_option("cq_thank_you_message_{$message_id}");
    $min_score = get_option("cq_thank_you_message_{$message_id}_min_score");
    $max_score = get_option("cq_thank_you_message_{$message_id}_max_score");
    ?>
    <p>
        <textarea name="cq_thank_you_message_<?php echo $message_id; ?>" rows="3" cols="50"><?php echo esc_textarea($message); ?></textarea><br>
        Score range: <input type="number" name="cq_thank_you_message_<?php echo $message_id; ?>_min_score" value="<?php echo esc_attr($min_score); ?>" min="0" max="100" step="1"> - <input type="number" name="cq_thank_you_message_<?php echo $message_id; ?>_max_score" value="<?php echo esc_attr($max_score); ?>" min="0" max="100" step="1">
    </p>
    <?php
}

function cq_get_thank_you_message($total_score) {

    for ($i = 1; $i <= 3; $i++) {
        $message = get_option("cq_thank_you_message_{$i}");
        $min_score = get_option("cq_thank_you_message_{$i}_min_score");
        $max_score = get_option("cq_thank_you_message_{$i}_max_score");

        if ($total_score >= $min_score && $total_score <= $max_score) {
            return $message;
        }
    }

    return '';
}

function cq_thank_you_settings_saved_notice() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Thank You Messages settings have been saved.', 'cq'); ?></p>
        </div>
        <?php
    }
}

add_action('admin_notices', 'cq_thank_you_settings_saved_notice');


/**
 * Pretty permalink for results page
 */

function cq_custom_rewrite_tag() {
    add_rewrite_tag('%cq_hash%', '([^&]+)');
}
add_action('init', 'cq_custom_rewrite_tag');

function cq_custom_rewrite_rule() {
    add_rewrite_rule('^results/([^/]+)/?', 'index.php?pagename=results&cq_hash=$matches[1]', 'top');
}
add_action('init', 'cq_custom_rewrite_rule');

/**
 * Quiz
 */

function cpt_quiz_register() {
    $quiz_args = array(
        'public' => true,
        'publicly_queryable' => false,
        'label' => 'Quizzes',
        'supports' => array('title', 'editor', 'thumbnail'),
    );
    register_post_type('ct_quiz', $quiz_args);

}
add_action('init', 'cpt_quiz_register');


// Add a meta box to the post editor screen
function custom_meta_box() {
    add_meta_box(
        'custom-meta-box', // ID of the meta box
        'Steps', // Title of the meta box
        'custom_meta_box_callback', // Callback function to render the meta box
        'ct_quiz', // Screen on which to show the meta box
        'normal', // Context in which to show the meta box
        'high' // Priority of the meta box
    );
}
add_action( 'add_meta_boxes', 'custom_meta_box' );

// Callback function to render the meta box
function custom_meta_box_callback( $post ) {
// Get the existing steps for this post
$steps = get_post_meta( $post->ID, 'steps', true );

// Get all the taxonomy terms for the 'steps' taxonomy
$terms = get_terms( array(
    'taxonomy' => 'cq_step',
    'hide_empty' => false,
) );
?>
<div class="custom-meta-box">
    <?php wp_nonce_field( basename( __FILE__ ), 'custom-meta-box-nonce' ); ?>

    <p><label for="steps">Steps:</label></p>
    <ul id="steps-list">
        <?php
        // Loop through the existing steps and output them in the correct order
        if ( $steps ) {
            $step_ids = explode( ',', $steps );
            foreach ( $step_ids as $step_id ) {
                $term = get_term_by( 'id', $step_id, 'cq_step' );

                if ( $term ) {
                    echo '<li data-term-id="' . $term->term_id . '">' . $term->name . ' <button class="delete-quiz-step button-secondary">Delete</button></li>';
                }
            }
        }
        ?>
    </ul>

    <p><label for="new-step">Add a new step:</label></p>
    <select id="new-step" name="new-step">
        <?php
        // Loop through the taxonomy terms and output them as options
        foreach ( $terms as $term ) {
            echo '<option value="' . $term->term_id . '">' . $term->name . '</option>';
        }
        ?>
    </select>
    <button id="add-step" class="button">Add Step</button>

    <input type="hidden" id="steps" name="steps" value="<?php echo esc_attr( $steps ); ?>">
</div>

<script>
    jQuery( document ).ready( function( $ ) {
        var $stepsList = $( '#steps-list' );
        var $newStep = $( '#new-step' );
        var $addStep = $( '#add-step' );
        var $steps = $( '#steps' );

        // Add a step to the list when the Add Step button is clicked
        $addStep.on( 'click', function() {
            var termId = $newStep.val();
            var termName = $newStep.find( 'option:selected' ).text();

            // Don't add the step if it's already in the list
            if ( $stepsList.find( 'li[data-term-id="' + termId + '"]' ).length ) {
                alert( 'Step already added!' );
                return;
            }

            // Add the step to the list and update the hidden input
            $stepsList.append( '<li data-term-id="' + termId + '">' + termName + '</li>' );
            updateStepsInput();
        } );

        // Delete a step from the list when the Delete button is clicked
        $stepsList.on( 'click', '.delete-quiz-step', function() {
            $( this ).parent().remove();
            updateStepsInput();
        } );

        // Update the order of the steps when they are dragged and dropped
        $stepsList.sortable( {
            update: function() {
                updateStepsInput();
            },
        } );

        // Update the value of the hidden input with the current order of the steps
        function updateStepsInput() {
            var stepIds = [];

            $stepsList.find( 'li' ).each( function() {
                stepIds.push( $( this ).data( 'term-id' ) );
            } );

            $steps.val( stepIds.join( ',' ) );
        }
    } );
</script>
    <?php
}

// Save the meta box data
function save_custom_meta_box_data( $post_id ) {
    // Verify the nonce
    if ( ! isset( $_POST['custom-meta-box-nonce'] ) || ! wp_verify_nonce( $_POST['custom-meta-box-nonce'], basename( __FILE__ ) ) ) {
        return;
    }

    // Update the steps meta data
    if ( isset( $_POST['steps'] ) ) {
        update_post_meta( $post_id, 'steps', sanitize_text_field( $_POST['steps'] ) );
    }
}
add_action( 'save_post', 'save_custom_meta_box_data' );




/**
 * Testing email
 */

function mailtrap($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'sandbox.smtp.mailtrap.io';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 2525;
    $phpmailer->Username = constant( 'MAILTRAP_USERNAME' );
    $phpmailer->Password =constant( 'MAILTRAP_PASSWORD' );
}

/**
 * uncomment to enable testing email
 */
// add_action('phpmailer_init', 'mailtrap');



