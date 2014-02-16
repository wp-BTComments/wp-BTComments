<?php
/*
Plugin Name: Bitcoin Wordpress Comments
Version: 0.1
Description: A plugin that verifies commenters as human by bitcoin microtransaction.
Author: Kenny Younger & Matt Snyder (Taken from Corey Freeman's Twitter field addition)
License: GPL
*/

$BLOCKCHAIN_PASSWORD = 'btchack021514';

add_filter('comment_form_default_fields','custom_fields');
function custom_fields($fields) {

		$commenter = wp_get_current_commenter();
		$req = get_option( 'require_name_email' );
		$aria_req = ( $req ? " aria-required='true'" : '' );

		$fields[ 'author' ] = '<p class="comment-form-author">'.
			'<label for="author">' . __( 'Name' ) . '</label>'.
			( $req ? '<span class="required">*</span>' : '' ).
			'<input id="author" name="author" type="text" value="'. esc_attr( $commenter['comment_author'] ) . 
			'" size="30" tabindex="1"' . $aria_req . ' /></p>';
		
		$fields[ 'email' ] = '<p class="comment-form-email">'.
			'<label for="email">' . __( 'Email' ) . '</label>'.
			( $req ? '<span class="required">*</span>' : '' ).
			'<input id="email" name="email" type="text" value="'. esc_attr( $commenter['comment_author_email'] ) . 
			'" size="30"  tabindex="2"' . $aria_req . ' /></p>';
					
		$fields[ 'url' ] = '<p class="comment-form-url">'.
			'<label for="url">' . __( 'Website' ) . '</label>'.
			'<input id="url" name="url" type="text" value="'. esc_attr( $commenter['comment_author_url'] ) . 
			'" size="30"  tabindex="3" /></p>';

	return $fields;
}

// Add fields after default fields above the comment box, always visible

add_action( 'comment_form_after_fields', 'additional_fields' );
add_action( 'comment_form_logged_in_after', 'additional_fields' );


function additional_fields () {
	echo '<p class="comment-form-bitcoin">'.
	'<label for="bitcoin">' . __( 'Pay to Bitcoin Address: &nbsp;' ) . '</label>'.
	'<input id="bitcoin" readonly="readonly" name="bitcoin" type="text" size="60"  tabindex="5" />'.
        '<br><span> (May take up to 5 minutes to very transaction.)</span></p>';
}

// Save the comment meta data along with comment

add_action( 'comment_post', 'save_comment_meta_data' );
function save_comment_meta_data( $comment_id ) {
	if ( ( isset( $_POST['bitcoin'] ) ) && ( $_POST['bitcoin'] != '') )
	$bitcoin = wp_filter_nohtml_kses($_POST['bitcoin']);
	add_comment_meta( $comment_id, 'bitcoin', $bitcoin );

        // if $bitcoin is empty, continue
        // call out to bitcoind to determine if payment with $bitcoin has been made
        // call to blockchain.info to check if n_tx > 0, if so, approve with line below
        $url = 'https://blockchain.info/address/'.$bitcoin.'?format=json';
        $payload = file_get_contents($url);
        /* echo '<!--$payload:'; */
        /* var_dump($payload); */
        /* echo '-->'; */

        $json = json_decode($payload);
        /* echo '<!--$json:'; */
        /* var_dump($json); */
        /* echo '-->'; */
        if( $json->{ 'n_tx' } > 0)
            wp_set_comment_status( $comment_id, 'approve' );

}

//Add an edit option in comment edit screen  
/* add_action( 'add_meta_boxes_comment', 'extend_comment_add_meta_box' ); */
/* function extend_comment_add_meta_box() { */
/*     add_meta_box( 'bitcoin', __( 'Comment Author Twitter Handle' ), 'extend_comment_meta_box', 'comment', 'normal', 'high' ); */
/* } */
/* function extend_comment_meta_box ( $comment ) { */
/*     $bitcoin = get_comment_meta( $comment->comment_ID, 'bitcoin', true ); */
/*     wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false ); */
/*     ?> */

/*     <p> */
/*         <label for="bitcoin"><?php _e( 'Pay to Bitcoin Address 2:' ); ?></label> */
/*         <input type="text" name="bitcoin" value="<?php echo esc_attr( $bitcoin ); ?>" class="widefat" /> */
/*     </p> */
/*     <?php */
/* } */

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

	$plugin_url_path = WP_PLUGIN_URL;

	if( $commentbitcoin = get_comment_meta( get_comment_ID(), 'bitcoin', true ) ) {
		$commentbitcoin = '<p class="bitcoin-follow-button">'.$commentbitcoin.'</a>';
		$text = $text.$commentbitcoin;
		return $text;
	} 

else {
		return $text;		
	}	 
}




/* ------------------------------------------------------------------------------------------- */
// AJAX STUFF

//Let's make sure jquery is added
//TODO, only load on pages that have comment for showing

function bitcoin_init() {
    if (!is_admin()) {
        //enable sync function below to test sync on each init (useful for debugging)
        /* wi_sync_verifications(); */
        wp_enqueue_script('jquery');
        wp_register_script( 'bitcoin-js-script', plugins_url('bitcoin-confirmation.js',__FILE__ ), '3.8.2');
        wp_enqueue_script('bitcoin-js-script');
    }
}
add_action('init', 'bitcoin_init');

function bitcoin_ajax_request() {
	// The $_REQUEST contains all the data sent via ajax
	if ( isset($_REQUEST) ) {
		// Now we'll return it to the javascript function
		// Anything outputted will be returned in the response
                $payload = file_get_contents('https://blockchain.info/merchant/79689497-be77-41c8-8bda-cea1c682137e/new_address?password='.$BLOCKCHAIN_PASSWORD.'&label=SheerFocus-Blog');
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
 
function cron_add_5min( $schedules ) {
    $schedules['5min'] = array(
            'interval' => 300,
            /* 'interval' => 60, */
            'display' => __( 'Once Every 5 Minutes' )
    );
    return $schedules;
}


//On plugin activation schedule our daily database backup 
register_activation_hook( __FILE__, 'wi_sync_verifications_schedule' );
function wi_sync_verifications_schedule(){
    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled( 'wi_create_sync_verifications' );

    //If $timestamp == false schedule daily backups since it hasn't been done previously
    if( $timestamp == false ){
        //Schedule the event for right now, then to repeat daily using the hook 'wi_create_sync_verifications'
        wp_schedule_event( time(), '5min', 'wi_create_sync_verifications' );
    }
}

//Hook our function , wi_create_backup(), into the action wi_create_sync_verifications
add_action( 'wi_create_sync_verifications', 'wi_sync_verifications' );
function wi_sync_verifications(){
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
    /* echo '<!--$stack:'; */
    /* var_dump($stack); */
    /* echo '-->'; */
    $addresses = rtrim($addresses,'|');
    $url = 'https://blockchain.info/multiaddr?active='.$addresses.'&format=json';
    /* echo '<!--$url:'; */
    /* var_dump($url); */
    /* echo '-->'; */
    $payload = file_get_contents($url);
    /* echo '<!--$payload:'; */
    /* var_dump($payload); */
    /* echo '-->'; */

    $json = json_decode($payload);
    /* echo '<!--$json:'; */
    /* var_dump($json); */
    /* echo '-->'; */

    $addresses = $json->{'addresses'};
    /* echo '<!--$addresses:'; */
    /* var_dump($addresses); */
    /* echo '-->'; */

    foreach($addresses as $address):
        $commentid = $stack[$address->address];
        if( $address->{ 'n_tx' } > 0)
            wp_set_comment_status( $commentid, 'approve' );
    endforeach;
}


















