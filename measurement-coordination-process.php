<?php
/*
Plugin Name: Measurement coordination process Plugin
Plugin URI:  https://simplyct.co.il
Description: Plugin to generate a Measurement coordination form using a shortcode.
 * Version:           1.0.0
 * Author:            Roy BenMenachem
 * Author URI:        https://simplyct.co.il
*/

// check for PriorityAPI
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('PriorityAPI/priority18-api.php')) {

} else {
    add_action('admin_notices', function () {
        printf('<div class="notice notice-error"><p>%s</p></div>', __('In order to use Priority Custom API extension, Priority WooCommerce API must be activated', 'p18a'));
    });

}


// Enqueue CSS and JS files
function enqueue_custom_files() {
    // Enqueue CSS file
    wp_enqueue_style('custom-styles', plugins_url('css/custom-styles.css', __FILE__));
    wp_enqueue_script('jquery', 'https://code.jquery.com/jquery-3.7.1.min.js');
    wp_enqueue_script('jquery-ui', 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js');
    wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css' );
    // Enqueue JS file
    wp_enqueue_script('custom-script', plugins_url('/js/custom-script.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('ajax-script', plugins_url('/js/ajax-scripts.js', __FILE__), array('jquery'));
    // Localize the script to use AJAX
    //wp_localize_script('custom-script', 'ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_localize_script('ajax-script', 'ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));

}
add_action('wp_enqueue_scripts', 'enqueue_custom_files');



// Add your AJAX action
add_action('wp_ajax_check_order_tel', 'check_order_tel');
add_action('wp_ajax_nopriv_check_order_tel', 'check_order_tel');

// AJAX callback function
function check_order_tel() {
    if(isset($_REQUEST['order_phone']) && $_REQUEST['order_phone'] != '') {
        $order_phone = $_REQUEST["order_phone"];
    }
    if(isset($_REQUEST['order_num']) && $_REQUEST['order_num'] != '') {
        $order_num = $_REQUEST["order_num"];
    }

    PriorityAPI\API::instance()->run();
	// make request
    $url_addition = 'ORDERS?$select=ROYY_CUSTDES,CDES,CURDATE,ORDNAME,ORDSTATUSDES&$filter=ORDNAME eq \''.$order_num.'\' and CORI_ORDEMAIL eq \''.$order_phone .'\' &$expand=ORDERITEMS_SUBFORM($select = PDES,TQUANT)'  ;
	$response = PriorityAPI\API::instance()->makeRequest('GET', $url_addition, null,true);

    if ($response['code']<=201) {
		$body_array = json_decode($response["body"],true);
        if(!empty($body_array['value'])){
            foreach($body_array['value'][0]['ORDERITEMS_SUBFORM'] as $item ){
                $items[] = $item;
                $data['ORDERITEMS_SUBFORM'] = $items;
            }
            $data['ORDNAME'] = $body_array['value'][0]['ORDNAME'];
            $data['ORDSTATUSDES'] = $body_array['value'][0]['ORDSTATUSDES'];
            $data['ROYY_CUSTDES'] = $body_array['value'][0]['ROYY_CUSTDES'];
            // $data = [
            //     'ordname' => $body_array['value']['ORDNAME'],
            //     'ordstatus' => $body_array['value']['ORDSTATUSDES'],
            // ]
   

            // Read the CSV file
            $csv = get_field('status_file',$_POST['current_post_id'] )['url'];
            if (($handle = fopen($csv, "r")) !== FALSE) {
                // Loop through each row in the CSV file
                while (($data_csv = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Assuming your CSV file has two columns (name and description)
                    $status = $data_csv[0]; // Get the value from the first column
                    if($status == $data['ORDSTATUSDES']){
                        $description = $data_csv[1]; // Get the value from the second column
                        break;
                    } 
                    else{
                        $description = '';
                    }     
                }
                fclose($handle);
            } 

            $response = array(
                'find_order' => true,
                'message' => 'success',
                'order_data' => $data,
                'description' => $description
            );
        }
        else{
            $response = array(
                'find_order' => false,
                'message' => 'לא נמצאה הזמנה התואמת את החיפוש'
                // Add more data to the response if needed
            );
        }

	}
	if($response['code'] >= 400){
		// Example response - modify as needed
        $response = array(
            'find_order' => false,
            'message' => 'Error!'
            // Add more data to the response if needed
        );
	}

	// if (!$response['status']) {
	// 	$response = array(
    //         'find-order' => false,
    //         'message' => 'Error!'
    //         // Add more data to the response if needed
    //     );
	// }

    

    // Return JSON response
    wp_send_json($response);

    // Always exit to avoid further execution
    wp_die();
}

add_action('wp_ajax_process_form', 'process_form');
add_action('wp_ajax_nopriv_process_form', 'process_form');


function process_form(){
    $data = $_POST['data'];
    $resultArray = array();
    parse_str($data, $resultArray);
    $order_date = (!empty($resultArray['choose_date']) ? $resultArray['choose_date'] : $resultArray['datepicker']);
    $order_tel = $resultArray['order_tel'];
    $order_num = $resultArray['order_num'];
    $order_status = $resultArray['order_status'];
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $admin_email = get_option('admin_email');
    // Send email
    //$to = $admin_email; // Change this to your email address
    $to = "Service.hamikzoanim@gmail.com";
    $subject = 'פרטי תיאום מדידה';

    if($order_status == 'לתיאום מיידי' || $order_status == 'המתנה לתיאום' || $order_status == 'הזמנה למדידה'){
        if (!empty($order_date)) {
            $body = "לקוח עם מספר טלפון: ".$order_tel;
            $body.= "<br/>";
            $body.= "מספר הזמנה: ".$order_num;
            $body.= "<br/>";
            $body.= "מבקש סטטוס: ".$order_status;
            $body.= "<br/>";
            $body.= "בתאריך : ".$order_date;
            $body.= "<br/>";
            if(!empty($resultArray['order_remark'])){
                $body.= "הערת הלקוח: ".$resultArray['order_remark'];
                $body.= "<br/>";
            }
            
            wp_mail($to, $subject, $body, $headers);
            $msg = 'בקשתך התקבלה בהצלחה';
            $msg.= "<br/>";
            $msg.= 'שים לב!';
            $msg.= 'קבלת העדכון לא מהווה את אישורו,';
            $msg.= "<br/>";
            $msg.= 'נציג מטעמנו יצור איתכם קשר בהקדם לאשר את הפרטים המשך יום טוב ';
            $response = array(
                'message' => $msg,
                //'message' => $body
                // Add more data to the response if needed
            );
        }
        else{
            $response = array(
                'message' => 'בחירת תאריך הינה חובה'
                // Add more data to the response if needed
            );
        }
    }
    else{
        $body = "לקוח עם מספר טלפון: ".$order_tel;
        $body.= "<br/>";
        $body.= "מספר הזמנה: ".$order_num;
        $body.= "<br/>";
        $body.= "סטטוס: ".$order_status;
        $body.= "<br/>";
        if(!empty($resultArray['order_remark'])){
            $body.= "הערת הלקוח: ".$resultArray['order_remark'];
            $body.= "<br/>";
        }
        wp_mail($to, $subject, $body, $headers);

        $response = array(
            'message' => 'פניתך התקבלה בהצלחה',
        );
    }
    wp_send_json($response);
    wp_die();
    
}


// Function to generate the custom form HTML
function measurement_coordination_form_shortcode() {
    ob_start(); // Start output buffering

    // Form HTML - Customize this according to your requirements

    ?>
    <div class="measurement_coordination_process_wrapper">
        <h1><?php esc_html_e( 'תהליך תאום מדידה:', 'carpentry' ); ?></h1>
        <form id="process_form">
            <!-- STEP 1 SECTION -->
            <section class="step_1">
                <?php 
                 $csv = get_field('status_file')['url'];
                 $post_id = get_the_ID();
                 //print_r($csv);
                 ?>
                 <input class="csv_file_status" type="hidden" value="<?php echo $csv;?>">
                 <input class="current_post_id" type="hidden" value="<?php echo $post_id;?>">
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="order_tel"><?php esc_html_e( 'מספר טלפון', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="tel" name="order_tel" id="order_tel" />
                </p>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="order_num"><?php esc_html_e( 'מספר הזמנה', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="order_num" id="order_num" />
                </p>

                <div class="validation_btn">
                    <button type="button" name="check_order" class="check_order button-secondary">
                        <?php esc_html_e( 'חיפוש הזמנה', 'carpentry' ); ?>
                        <div class="loader_wrap">
                            <div class="loader_spinner">
                                <img src="<?php echo plugins_url('/images/loader.svg', __FILE__); ?>" alt="">
                            </div>
                        </div>
                    </button>
                </div>
                <div class="error_msg"></div>
            </section>
            <section class="step_2">
                <h2>
                    <?php esc_html_e( ' שלום', 'carpentry' ); ?>
                    <span class="order_username"></span>
                </h2>
                <h2><?php esc_html_e( 'פרטי הזמנה:', 'carpentry' ); ?></h2>
                <dl>
                    <div class="order_details_title">
                        <dt><?php esc_html_e( 'מספר הזמנה:', 'carpentry' ); ?></dt>
                        <dd class="order_name"></dd>
                    </div>
                    <div class="order_details_title">
                        <dt><?php esc_html_e( 'סטטוס הזמנה:', 'carpentry' ); ?></dt>
                        <dd class="order_status"></dd>
                        <div class="tooltip">
                            <div class="tooltip_icon" aria-hidden="true">?</div>
                            <div class="tooltip_txt">
                           </div>
                        </div>
                        
                    </div>
                </dl>
                <input type="hidden" name="order_status">
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'תיאור', 'carpentry' ); ?></th>
                            <th><?php esc_html_e( 'כמות', 'carpentry' ); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="remarks_wrapper">
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="order_remark"><?php esc_html_e( 'הזן את ההערה שלך כאן', 'woocommerce' ); ?></label>
                        <textarea placeholder="<?php esc_html_e( 'הזן את ההערה שלך כאן', 'woocommerce' ); ?>" id="order_remark" name="order_remark"  rows="6" ></textarea>
                    </p>
                </div>
                <div class="radio_btns_wrap">
                    <h2><?php esc_html_e( 'בחר תאריך תיאום:', 'carpentry' ); ?></h2>
                    <div class="radio_item">
                        <input type="radio" id="one_week" name="choose_date" value="<?php esc_html_e( 'שבוע הבא', 'carpentry' ); ?>">
                        <label for="one_week"><?php esc_html_e( 'שבוע הבא', 'carpentry' ); ?></label>
                    </div>
                    <div class="radio_item">
                        <input type="radio" id="two_week" name="choose_date" value="<?php esc_html_e( 'עוד שבועיים', 'carpentry' ); ?>">
                        <label for="two_week"><?php esc_html_e( 'עוד שבועיים', 'carpentry' ); ?></label>
                    </div>
                    <div class="radio_item">
                        <input type="radio" id="open_calendar" name="choose_date" value="">
                        <label for="open_calendar"><?php esc_html_e( 'תאריך אחר', 'carpentry' ); ?></label>
                    </div>
                    <div id="datepickerContainer">
                        <input type="text" id="datepicker" name="datepicker">
                    </div>
                </div>
                <div class="step_2_btns_wrapper">
                    <button type="button" class="prev_btn"><?php esc_html_e( 'הקודם', 'carpentry' ); ?></button>
                    <button type="submit" class="send_btn">
                        <?php esc_html_e( 'שליחה', 'carpentry' ); ?>
                        <div class="loader_wrap">
                            <div class="loader_spinner">
                                <img src="<?php echo plugins_url('/images/loader.svg', __FILE__); ?>" alt="">
                            </div>
                        </div>
                    </button>
                </div>
                <h3 class="send_msg_wrapper">

                </h3>
            </section>
        </form>
        <div id="response"></div>
       
    </div>
    <?php

    return ob_get_clean(); // Return the buffered content



}
add_shortcode('measurement_coordination_form', 'measurement_coordination_form_shortcode'); // Register shortcode with the name 'custom_form'


