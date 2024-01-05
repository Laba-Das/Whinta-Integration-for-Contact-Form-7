<?php
/*
 * Plugin Name: Whinta Integration for Contact Form 7
 * Plugin URI: https://www.Teckshop.net/our-plugin/
 * Description: Seamlessly integrate WhatsApp messaging into your WordPress website using the Whinta API. Configure API keys and a default message from the settings page. When visitors submit a Contact Form 7 form with their phone number, this plugin triggers a WhatsApp message, ensuring quick and efficient communication.
 * Version: 1.0.1
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Teckshop.net
 * Author URI: https://www.Teckshop.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://www.Teckshop.net/our-plugin/
 * Text Domain: my-basics-plugin
 * Domain Path: /languages
 */

error_log('API URL: ' . $api_url);
error_log('Receiver Number: ' . $receiverNumber);
error_log('Message: ' . $message);
error_log('Country Code: ' . $country_code);
error_log('Phone: ' . $phone);

// Add a menu item to the admin dashboard for plugin settings.
function whinta_api_menu() {
    add_menu_page('Whinta API Settings', 'Whinta API', 'manage_options', 'whinta-api-settings', 'whinta_api_page');
}
add_action('admin_menu', 'whinta_api_menu');

// Create the plugin settings page.
function whinta_api_page() {
    ?>
    <div class="wrap">
        <h2>Whinta API Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('whinta_api_settings');
            do_settings_sections('whinta-api-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Define and register the settings.
function whinta_api_settings_init() {
    register_setting('whinta_api_settings', 'whinta_api_appkey');
    register_setting('whinta_api_settings', 'whinta_api_authkey');

    register_setting('whinta_api_settings', 'whinta_api_default_message');
}

add_action('admin_init', 'whinta_api_settings_init');

// Define fields and sections for the settings page.
function whinta_api_settings_fields() {
    add_settings_section('whinta_api_section', 'Whinta API Configuration', null, 'whinta-api-settings');

    add_settings_field('whinta_api_appkey', 'App Key', 'whinta_api_appkey_callback', 'whinta-api-settings', 'whinta_api_section');
    add_settings_field('whinta_api_authkey', 'Auth Key', 'whinta_api_authkey_callback', 'whinta-api-settings', 'whinta_api_section');

    add_settings_field('whinta_api_default_message', 'Default Message', 'whinta_api_default_message_callback', 'whinta-api-settings', 'whinta_api_section');
}

add_action('admin_init', 'whinta_api_settings_fields');

// Callback functions for rendering settings fields.
function whinta_api_appkey_callback() {
    $value = esc_attr(get_option('whinta_api_appkey'));
    echo '<input type="text" name="whinta_api_appkey" value="' . $value . '" />';
}

function whinta_api_authkey_callback() {
    $value = esc_attr(get_option('whinta_api_authkey'));
    echo '<input type="text" name="whinta_api_authkey" value="' . $value . '" />';
}

function whinta_api_default_message_callback() {
    $value = esc_attr(get_option('whinta_api_default_message'));
    echo '<textarea name="whinta_api_default_message" rows="4" cols="50">' . $value . '</textarea>';
}

// Function to send a WhatsApp message using Whinta API.
function send_whatsapp_message($receiverNumber, $message) {
    $api_url = 'https://whinta.com/api/create-message';

    $appkey = get_option('whinta_api_appkey');
    $authkey = get_option('whinta_api_authkey');

    $data = array(
        'appkey' => $appkey,
        'authkey' => $authkey,
        'to' => $receiverNumber,
        'message' => $message, // Use the provided message.
        'sandbox' => $sandbox,
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response) {
        // Handle the response, e.g., log it or display a message.
        $response_data = json_decode($response, true);
        if (isset($response_data['message_status']) && $response_data['message_status'] === 'Success') {
            // The message was sent successfully.
            // You can log this or show a success message.
            error_log('WhatsApp message sent successfully');
        } else {
            // Handle errors here.
            // You can log the error or provide feedback.
            error_log('WhatsApp message sending failed');
        }
    } else {
        // Handle the case where the API request failed.
        // You can log this or provide an error message.
        error_log('WhatsApp API request failed');
    }
}
// Function to get the default message from settings.
function get_default_message() {
    return get_option('whinta_api_default_message');
}


function contact_form_7_submission_callback($WPCF7) {
    // Get the submitted form data.
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();

        // Check if there's a phone field in the form (user's number).
        if (isset($posted_data['phone'])) {
            $userNumber = '91' . sanitize_text_field($posted_data['phone']); // Add '91' at the beginning.

            // Get the WhatsApp message from the hidden field and allow HTML formatting.
            $whatsappMessage = isset($posted_data['whatsapp-message']) ? $posted_data['whatsapp-message'] : 'Default message for the user';

            // Check if there's a "your-name" field in the form.
            if (isset($posted_data['your-name'])) {
                $name = sanitize_text_field($posted_data['your-name']);

                // Add the name to the WhatsApp message using a shortcode.
                $whatsappMessage = str_replace('{name}', $name, $whatsappMessage);
            }
			
            // Replace {number} with the user's number in the owner message.
            $websiteOwnerNumber = isset($posted_data['website-owner-number']) ? '91' . sanitize_text_field($posted_data['website-owner-number']) : '';
            $websiteOwnerMessage = isset($posted_data['website-owner-message']) ? $posted_data['website-owner-message'] : 'Default message for the website owner';

            // Replace {name} and {number} placeholders in the owner message.
            $websiteOwnerMessage = str_replace('{name}', $name, $websiteOwnerMessage);
            $websiteOwnerMessage = str_replace('{number}', $userNumber, $websiteOwnerMessage);

            // Send the WhatsApp message to the user.
            send_whatsapp_message($userNumber, $whatsappMessage, 'html');

            // Send the WhatsApp message to the website owner.
            if (!empty($websiteOwnerNumber)) {
                send_whatsapp_message($websiteOwnerNumber, $websiteOwnerMessage, 'text');
            }
        }
    }
}
add_action('wpcf7_mail_sent', 'contact_form_7_submission_callback');





// Enqueue the JavaScript code
function enqueue_custom_script() {
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'custom-script.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script');
