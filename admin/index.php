<?php
/**
 * Created by PhpStorm.
 * User: orion
 * Date: 9/13/17
 * Time: 2:46 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_enqueue_media();

$settings = get_option( 'wpe_invoice_pdf_settings' );

$logo = '';
if ( isset( $settings['logo_id'] ) ) {
	$logo = wp_get_attachment_url( $settings['logo_id'] );
}
//echo $order->get_status();

$settings = get_option( 'wpe_invoice_pdf_settings' );

?>
<style>
    select, option, input[type="text"], textarea {
        min-width: 400px;
    }

    body {
        background-color: #F1F1F1;
    }

    .font_preview {
        font-size: 26px !important;
    }

    .heading {
        position: absolute;
        right: 0;
        left: -20px;
        margin-bottom: 0px;
        padding-bottom: 17px;
        padding-left: 20px;

        background-image: linear-gradient(to bottom, #F5F5F5 0px, #E1E1E1 100%);
        background-repeat: repeat-x;

        border-bottom-color: #cccccc !important;
        border-bottom: 1px solid transparent;

        color: #333;
        background-color: #f5f5f5;

        font-size: 13pt;
        font-weight: 600;
    }

    .nav-tabs {

        background: #F1F1F1;
        position: absolute;
        left: -20px;
        padding-left: 36px;
        width: 102%;

    }

    .nav.nav-tabs > li.active > a {
        background-color: #f1f1f1 !important;
        border-bottom: 1px solid transparent;
        color: #1E78C6;
        box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.04) inset !important;
    }

    .nav.nav-tabs > li > a {

        color: grey;
        font-weight: 500;
        background: #eeeeee none repeat scroll 0 0;
        border: 1px solid #dddddd;
        margin-right: 3px;
        border-radius: 2px 2px 0 0 !important;
        font-size: 9pt;
        letter-spacing: 0.5px;
    }

    #generals, #templating {
        margin-top: 55px;
    }
</style>
<h3 class="heading">Invoice PDF Settings
    <button id="clear_invoice_cache" class="btn btn-xs btn-danger pull-right" style="margin: 0; margin-right: 10px">
        <i id="cache_icon" class="" aria-hidden="true"></i>
        Clear Cache
    </button>
</h3><br><br><br>
<form id="invoice_pdf_form" action="" method="post">
	<?php wp_nonce_field( NONCE_KEY, '__pinonce' ); ?>
    <div class="wrap">
        <div class="col-sm-12">

            <table class="form-table">
                <tbody>
                <tr>
                    <th>Attach with order email</th>
                    <td>
                        <input type="checkbox"
                               name="invoice_options[attach_with]"
                               value="1" <?php if ( isset( $settings['attach_with'] ) && (int) $settings['attach_with'] == 1 ) {
							echo 'checked';
						} ?>>
                        <p class="description">Check this to attach an invoice pdf with order emails.</p>
                    </td>
                </tr>
                <tr>
                    <th>Show Product add-on</th>
                    <td>
                        <input type="checkbox"
                               name="invoice_options[show_addon]"
                               value="1" <?php if ( isset( $settings['show_addon'] ) && (int) $settings['show_addon'] == 1 ) {
							echo 'checked';
						} ?>>
                        <p class="description">Check this to show product add-ons details bellow the product
                            name in invoice, if purchased.</p>
                    </td>
                </tr>
                </tbody>

            </table>
            <h4>Message/Notice</h4>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">Show Footer Message</th>
                    <td>
                        <input type="checkbox" name="invoice_options[notice][show]"
                               value="1" <?php if ( isset( $settings['notice']['show'] ) && (int) $settings['notice']['show'] == 1 ) {
							echo 'checked';
						} ?>>
                        <p class="description">To show a custom message at the bottom of the pdf.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Title</th>
                    <td>
                        <input type="text" name="invoice_options[notice][title]"
                               value="<?php if ( isset( $settings['notice']['title'] ) ) {
							       echo esc_attr( $settings['notice']['title'] );
						       } ?>">
                        <p class="description">To show above the message; Ex. <strong>Notice:</strong></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Message</th>
                    <td>
                        <textarea rows="3" name="invoice_options[notice][message]"
                                  placeholder=""><?php if ( isset( $settings['notice']['message'] ) ) {
		                        echo esc_attr( $settings['notice']['message'] );
	                        } ?></textarea>
                        <p class="description">To show at the bottom of the page; <br>Ex. 1. This is an
                            electronic
                            generated invoice so doesn't require any signature. <br>
                            &nbsp;&nbsp;&nbsp;&nbsp; 2. Here is another one.</p>
                    </td>
                </tr>
                </tbody>
            </table>


            <div class="submit col-sm-12" style="padding-left: 0"><input type="submit" name="submit" id="submit"
                                                                         class="button button-primary"
                                                                         value="Save Changes"></div>
            <div id="submit_response" style="display: none;" class="alert alert-success col-sm-6"
                 role="alert">
                Settings saved successfully.
            </div>

        </div>
    </div>
</form>
<script type='text/javascript'>
    jQuery(document).ready(function ($) {

        $('#clear_invoice_cache').on('click', function () {
            var clear = confirm('This will remove all previously created PDFs.');
            if (clear) {
                $('#cache_icon').addClass('fa fa-spinner fa-spin');
                $.ajax({
                        url: ajaxurl + '?action=clear_pdf_cache',
                        success: function (res) {
                            if (res === 'success') {
                                $('#cache_icon').removeClass('fa fa-spinner fa-spin');
                                $('#clear_invoice_cache').text('Done');
                                setTimeout(function () {
                                    $('#clear_invoice_cache').text('Clear Cache');
                                }, 2000);
                            }
                        }
                    }
                )
            }
        });

        $('body').on('submit', 'form', function (e) {
            e.preventDefault();
            $(this).ajaxSubmit({
                url: ajaxurl + '?action=save_invoice_pdf_settings',
                success: function (res) {
                    if (res === 'success') {
                        $('#submit_response').css("display", "block");
                    }
                }
            });
        });
    });
</script>