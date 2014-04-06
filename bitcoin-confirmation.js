jQuery(document).ready(function($) {
    $('<p><input type="checkbox" id="bitcoin-checkbox" name="bitcoin-checkbox" /><label for="bitcoin-checkbox">&nbsp;&nbsp;Verify that I\'m human with a Bitcoin micropayment! (No minimum.)</label></p>').insertBefore('.comment-form-bitcoin')

    $('.comment-form-bitcoin').hide();
    $('#bitcoin').val('Loading...');
    

    $('#bitcoin-checkbox').on("click", function() {
        
        if($('#bitcoin-checkbox')[0].checked) {
            $('.comment-form-bitcoin').show();

            if($('#bitcoin').val()=="Loading...") {
                $.ajax({
                    url: ajaxurl,
                    contentType: "application/json",
                    data: {
                        'action': 'bitcoin_ajax_request',
                        'commentid': $('#bitcoin').data("commentid")
                    },
                    success:function(data) {
                        console.log(data);
                        $('#bitcoin').val(data.address);
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
                });
            }
        }
        else {
            $('.comment-form-bitcoin').hide();
        }

    });
});


