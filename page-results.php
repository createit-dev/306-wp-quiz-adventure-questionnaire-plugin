<?php
/**
 * Template Name: Results (Custom Questionnaire)
 */


//get_header();

$hash = get_query_var('cq_hash');

global $wpdb;
$table_name = $wpdb->prefix . 'cq_results';
$results = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE hash = %s", $hash));

if ($results) {
    $total_score = $results->total_score;
    $recommendations = unserialize($results->recommendations);
    ?>

    <div id="cq-results">
        <h2>Your Score: <?php echo esc_html($total_score); ?>%</h2>
        <?php
            $thank_you_message = cq_get_thank_you_message($total_score);
        ?>
        <h3>Result:</h3>
        <div class="cq-thank-you-message">
            <?php echo nl2br(esc_html($thank_you_message)); ?>
        </div>

        <h3>Recommendations:</h3>
        <ul>
            <?php foreach ($recommendations as $rec) { ?>
                <li><?php echo esc_html($rec); ?></li>
            <?php } ?>
        </ul>
    </div>
    <div class="email-results-form">
        <h3>Send results to your email</h3>
        <form id="send-results-form">
            <input type="email" id="email-address" placeholder="Your email address" required>
            <input type="hidden" id="results-hash" value="<?php echo esc_attr($hash); ?>">
            <button type="submit">Send</button>
        </form>
        <div id="email-sent-message" style="display:none">Email sent successfully!</div>
    </div>

    <?php
} else {
    echo '<p>No results found for the provided hash.</p>';
}
?>

<?php
//get_footer();
