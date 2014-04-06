<?php
/*
Plugin Name: wp-BTComments
Version: 0.1
Description: A plugin that verifies commenters as human through a bitcoin microtransaction.
Author: Kenny Younger & Matt Snyder 
License: GPL v2
*/



// Save the comment meta data along with comment
add_action( 'comment_post', 'save_comment_meta_data' );
function save_comment_meta_data( $comment_id ) {
    if ( ( isset( $_POST['bitcoin'] ) ) && ( $_POST['bitcoin'] != '') ) {
        $bitcoin = wp_filter_nohtml_kses($_POST['bitcoin']);

        if(!BitcoinAddressValidation::checkAddress($bitcoin)) return false;

        add_comment_meta( $comment_id, 'bitcoin', $bitcoin );

        // if $bitcoin is empty, continue
        // call out to bitcoind to determine if payment with $bitcoin has been made
        // call to blockchain.info to check if n_tx > 0, if so, approve with line below
        $url = 'https://blockchain.info/address/'.$bitcoin.'?format=json';
        $payload = file_get_contents($url);
        $json = json_decode($payload);
        if( $json->{ 'n_tx' } > 0)
            wp_set_comment_status( $comment_id, 'approve' );
    }
}

// Update comment meta data from comment edit screen 
add_action( 'edit_comment', 'extend_comment_edit_metafields' );
function extend_comment_edit_metafields( $comment_id ) {
    if( ! isset( $_POST['extend_comment_update'] ) || ! wp_verify_nonce( $_POST['extend_comment_update'], 'extend_comment_update' ) ) return;
		
	if ( ( isset( $_POST['bitcoin'] ) ) && ( $_POST['bitcoin'] != '') ):
            $bitcoin = wp_filter_nohtml_kses($_POST['bitcoin']);
            update_comment_meta( $comment_id, 'bitcoin', $bitcoin );
	else :
            delete_comment_meta( $comment_id, 'bitcoin');
	endif;
}

// Add the comment meta (saved earlier) to the comment text 
// You can also output the comment meta values directly in comments template  
add_filter( 'comment_text', 'modify_comment');
function modify_comment( $text ){
    $comment_id = get_comment_ID();
    $comment = get_comment($comment_id);

    if ( '0' == $comment->comment_approved ) :
        if( $commentbitcoin = get_comment_meta( get_comment_ID(), 'bitcoin', true ) ) {
            // if this bitcoin address already exists, then use this instead of 
            // showing button to generate new address
        }

        $jquery_insert = '<p>INSERT BUTTON THAT RUNS JQUERY using commentid: '.$comment_id.'</p>';
        $checkbox_code = '<p class="comment-form-bitcoin">'.
                         '<label for="bitcoin">' . __( 'Pay to Bitcoin Address: &nbsp;' ) . '</label>'.
                         '<input id="bitcoin" readonly="readonly" name="bitcoin" type="text" size="60" tabindex="5" data-commentid="'.$comment_id.'" />'.
                         '<br><span> (May take up to 5 minutes to verify transaction.)</span></p>';
        $javascript = '<script>bitcoinid='.$comment_id.'</script>';
        $text .= $jquery_insert.$checkbox_code;
        return $text;
    endif; 

}


/* ------------------------------------------------------------------------------------------- */
// AJAX STUFF

//Let's make sure jquery is added
//TODO, only load on pages that have comment for showing
add_action('init', 'bitcoin_init');
function bitcoin_init() {
    if (!is_admin()) {
        //enable sync function below to test sync on each init (useful for debugging)
        /* bitcomment__sync_verifications(); */
        wp_enqueue_script('jquery');
        wp_register_script( 'bitcoin-js-script', plugins_url('bitcoin-confirmation.js',__FILE__ ), '3.8.2');
        wp_enqueue_script('bitcoin-js-script');
    }
}

function bitcoin_ajax_request() {
	// The $_REQUEST contains all the data sent via ajax
	if ( isset($_REQUEST) ) {
            $payload = '';

            // check that $_REQUEST('commentid') is set, if not set, return nothing.
            if ( !isset($_REQUEST['commentid']) ) { $payload .= 'test1'; }

            $commentid = $_REQUEST['commentid'];
            $comment = get_comment($commentid); // does this return an exception if 

            // check that commentid is valid commentid, if not, return nothing.
            if ( !isset($comment) ) { $payload .= 'test2'; }

            // check that '0' == $comment->comment_approved, if not return nothing
            if ( '0' != $comment->comment_approved ) { $payload .= 'test3'; }

            $commentbitcoin = get_comment_meta( $commentid, 'bitcoin', true );
            /* $payload = var_dump($commentbitcoin); */

            if( $payload == '') {
                if ( $commentbitcoin != '') {
                    // commentid is already assoc with bitcoin address, so return that bitcoin address
                    $payload = '{"address":"'.$commentbitcoin.'","label":"wp-bitcomments","test":"exists"}';
                }
                else {
                    // get new address from blockchain
                    // Now we'll return it to the javascript function // Anything outputted will be returned in the response
                    $BLOCKCHAIN_GUID = settings_get_option( 'blockchain_identifier', 'bitcomments_basics');
                    $BLOCKCHAIN_PASSWORD = settings_get_option( 'blockchain_password', 'bitcomments_basics');
                    $payload = file_get_contents('https://blockchain.info/merchant/'.$BLOCKCHAIN_GUID.'/new_address?password='.$BLOCKCHAIN_PASSWORD.'&label=wp-bitcomments');
                    $json = json_decode( $payload );
                    $commentbitcoin = $json->{'address'}; 
                    $payload = '{"address":"'.$commentbitcoin.'","label":"wp-bitcomments","commentid":"'.$commentid.'"}';

                    if( !add_comment_meta( $commentid, 'bitcoin', $commentbitcoin, false ) ) {
                        $payload .= 'broken';
                    }
                }
            }
            header( "Content-Type: application/json" );
            echo $payload;
            // If you're debugging, it might be useful to see what was sent in the $_REQUEST
            /* print_r($_REQUEST); */

	}
        // Always die in functions echoing ajax content
        die();
}

add_action( 'wp_ajax_bitcoin_ajax_request', 'bitcoin_ajax_request' );
add_action( 'wp_ajax_nopriv_bitcoin_ajax_request', 'bitcoin_ajax_request' );

/* ------------------------------------------------------------------------------------------- */
// CRON STUFF
add_filter( 'cron_schedules', 'cron_add_5min' );
 
//Create a custom wp-cron interval of 5 minutes
function cron_add_5min( $schedules ) {
    $schedules['5min'] = array(
            'interval' => 300,
            'display' => __( 'Once Every 5 Minutes' )
    );
    return $schedules;
}

//On plugin activation schedule our daily database backup 
register_activation_hook( __FILE__, 'bitcomments_sync_verifications_schedule' );
function bitcomments_sync_verifications_schedule(){
    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled( 'bitcomments_create_sync_verifications' );

    //If $timestamp == false schedule daily backups since it hasn't been done previously
    if( $timestamp == false ){
        //Schedule the event for right now, then to repeat daily using the hook 'bitcomments_create_sync_verifications'
        wp_schedule_event( time(), '5min', 'bitcomments_create_sync_verifications' );
    }
}

//Hook our function , bitcomments_create_backup(), into the action bitcomments_create_sync_verifications
add_action( 'bitcomments_create_sync_verifications', 'bitcomments_sync_verifications' );
function bitcomments_sync_verifications(){
    //query the wp comment list, return all comments without null Bitcoin Address && aren't aren't approved
    //create array of all bitcoin addresses
    //check blockchain.info for each address
    //if blockchain returns >0 n_tx, then modify comment to approved status
    $comments = get_comments( array(
            'status' => 'hold',
            'meta_key' => 'bitcoin',
    ) );
    /* echo '<!--$comments:'; */
    /* var_dump($comments); */
    /* echo '-->'; */

    $stack = array();
    $addresses = '';
    foreach($comments as $comment) :
        $commentid = $comment->comment_ID;
        $bitcoin = get_comment_meta($commentid , 'bitcoin', true );
        $addresses.=$bitcoin.'|';
        $stack[$bitcoin]=$commentid;
    endforeach;

    $addresses = rtrim($addresses,'|');
    $url = 'https://blockchain.info/multiaddr?active='.$addresses.'&format=json';
    $payload = file_get_contents($url);
    $json = json_decode($payload);
    $addresses = $json->{'addresses'};
    foreach($addresses as $address):
        $commentid = $stack[$address->address];
        if( $address->{ 'n_tx' } > 0)
            wp_set_comment_status( $commentid, 'approve' );
    endforeach;
}

require_once dirname( __FILE__ ) . '/settings.php';
