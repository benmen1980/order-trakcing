var $=jQuery.noConflict();

jQuery(document).ready(function($){

    $('.check_order').on('click', function() {
        $thisbutton = $(this);
        csv_file = $(this).closest('form').find('.csv_file_status').val(); 
        console.log("🚀 ~ $ ~ csv_file:", csv_file);
        current_post_id = $(this).closest('form').find('.current_post_id').val(); 
        console.log("🚀 ~ $ ~ current_post_id:", current_post_id);
        order_phone = $(this).closest('form').find('.form-row input#order_tel').val();
        console.log("🚀 ~ file: ajax-scripts.js:403 ~ $ ~ order_phone:", order_phone);
        order_num = $(this).closest('form').find('.form-row input#order_num').val();
        console.log("🚀 ~ file: custom-script.js:10 ~ $ ~ order_num:", order_num);

        var $span_phone_error =  $('#order_tel').next('span');
        if($span_phone_error.length > 0){
            $span_phone_error.remove();
        }
        var $span_num_error =  $('#order_num').next('span');
        if($span_num_error.length > 0){
            $span_num_error.remove();
        }

        if (order_phone.length == 0) {
            $('#order_tel').after('<span class="error">טלפון שדה חובה</span>');
            check_validate_phone = false;
        }
        else{
            check_validate_phone = true;
        }
        if (order_num.length == 0) {
            $('#order_num').after('<span class="error">מספר הזמנה שדה חובה</span>');
            check_validate_order_num = false;
        }
        else{
            check_validate_order_num = true;
        }

        if(check_validate_phone == true && check_validate_order_num == true){

            if($(".send_msg_wrapper").length){
                $(".send_msg_wrapper").empty();
            }
            if($(".error_msg .error").length){
                $(".error_msg .error").empty();
            }
            $.ajax({
                type:"POST",
                url: ajax_obj.ajax_url,
                data: {
                    'action': 'check_order_tel',
                    'order_phone': order_phone,
                    'order_num' : order_num,
                    'csv_file' : csv_file,
                    'current_post_id' : current_post_id
                },
                beforeSend: function (response) {
                    $thisbutton.addClass('loader_active');
                },
                success: function (results) {
                    console.log('success');
                    console.log(results);
                    if(results.find_order == true){
                        if($(".error_msg .error").length){
                            $(".error_msg").empty();
                        }
                        $('.step_1').hide();
                        $('.step_2').fadeIn('slow');
                        // $('.step_1').fadeOut('slow', function() {
                        //     // This function executes after the fadeOut animation completes
                        //     // Step 2: Display element with class 'step-2' with animation
                        //     $('.step_2').fadeIn('slow');
                        // });
                        if (results.hasOwnProperty("order_data")) {
                            if($(".measurement_coordination_process_wrapper form table tbody tr").length > 0){
                                $(".measurement_coordination_process_wrapper form table tbody").empty();
                            }
                            order_details =  results.order_data.ORDERITEMS_SUBFORM;
                            $.each(order_details, function(index, item) {
                                item_desc = item.PDES;
                                item_qtty = item.TQUANT;
                                $(".measurement_coordination_process_wrapper form table tbody").append("<tr><td>"+item_desc+"</td><td>"+item_qtty+"</td></tr>")
                            });
                            order_title = results.order_data.ORDNAME;
                            order_status = results.order_data.ORDSTATUSDES;
                            order_username = results.order_data.ROYY_CUSTDES;
                            status_desc = results.description;
                            console.log("🚀 ~ $ ~ status_desc:", status_desc);
                            $(".measurement_coordination_process_wrapper form h2 .order_username").text(order_username);
                            $(".measurement_coordination_process_wrapper form dd.order_name").text(order_title);
                            $(".measurement_coordination_process_wrapper form dd.order_status").text(order_status);
                            $(".measurement_coordination_process_wrapper form input[name='order_status']").val(order_status);
                            if(status_desc != null){
                                $(".order_details_title .tooltip .tooltip_txt").text(status_desc);
                                $(".order_details_title .tooltip").show();
                            }
                                
                            else{
                                $(".order_details_title .tooltip .tooltip_txt").text();
                                $(".order_details_title .tooltip").hide();
                            }
                            if(order_status != 'לתיאום מיידי' && order_status != 'המתנה לתיאום' && order_status != 'הזמנה למדידה'){
                                $(".radio_btns_wrap").hide();
                                //$(".send_btn").hide();
                            }
                            else{
                                $(".radio_btns_wrap").show();
                                //$(".send_btn").show();
                            }
                        }
                    }
                    else{
                        //if(!$(".error_msg .error").length){
                            $(".error_msg").append('<span class="error">'+results.message+'</span>');
                        //}
                    }
                },
                complete: function (data) {
                    console.log('complete');
                    $thisbutton.removeClass('loader_active');
                },
                error: function (errorThrown) {
                    console.log('error');
                    
                }
            });
        }

        
    });

    $('#process_form').on('submit', function(event) {
        event.preventDefault(); 
        $thisbutton = $(this).find('.send_btn');
        var formData = $(this).serialize();
        console.log("🚀 ~ file: ajax-scripts.js:79 ~ $ ~ formData:", formData);

        $.ajax({
            type:"POST",
            url: ajax_obj.ajax_url,
            data: {
                'action': 'process_form',
                data: formData,
            },
            beforeSend: function (response) {
                $thisbutton.addClass('loader_active');
            },
            success: function (results) {
                console.log(results);
                $(".send_msg_wrapper").html(results.message);
            },
            complete: function (data) {
                console.log('complete');
                $thisbutton.removeClass('loader_active');
                
            },
            error: function (errorThrown) {
                console.log('error');
                
            }
        });
    });


    $('.step_2_btns_wrapper .prev_btn').on('click', function() {
        $('.step_2').fadeOut('slow');
        $('.step_1').fadeIn('slow');
    });

    $('.step_2_btns_wrapper .choose_date_btn').on('click', function() {
        $('.step_2').fadeOut('slow');
        $('.step_3').fadeIn('slow');
    });

    // Function to show datepicker when radio button is selected
    $('input[name="choose_date"]').on('change', function() {
        var selectedId = $('input[name="choose_date"]:checked').attr('id'); 

        if (selectedId === 'open_calendar') {
            $('#datepickerContainer').show();
            $('#datepicker').datepicker(); // Initialize the datepicker
            $('input[name="choose_date"]:checked').val($('#datepicker').val());
        } else {
            $('#datepickerContainer').hide();
        }

    });
    
});