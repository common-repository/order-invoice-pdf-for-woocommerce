<?php

/**
 * Plugin Name:Order Invoice PDF for WooCommerce
 * Plugin URI: http://wpeden.com
 * Description: This plugin automatically generates invoice PDFs, attaches PDF with order confirmation email and lets owner and customers download these PDFs from the order table.
 * Version: 1.0
 * Author: Shafayat
 * Author URI: http://sobuj.info
 * License: GPL3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

define( "WPE_INVOICE_ASSETS", plugins_url( "assets/", __FILE__ ) );

class WpeWcInvoicePdf {
	private $base_upload_dir;

	/**
	 * WpeWcInvoicePdf constructor.
	 */
	function __construct() {
		require_once 'vendor/autoload.php';

		$upload_dir = wp_upload_dir();

		$this->base_upload_dir = $upload_dir['basedir'];


		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_save_invoice_pdf_settings', array( $this, 'save_invoice_pdf_settings' ) );
		add_action( 'wp_ajax_clear_pdf_cache', array( $this, 'clear_pdf_cache' ) );

		add_filter( 'woocommerce_my_account_my_orders_actions', array(
			$this,
			'wpe_add_my_account_order_actions'
		), 10, 2 );
		add_filter( 'woocommerce_admin_order_actions', array(
			$this,
			'wpe_add_my_account_order_actions'
		), 10, 2 );

		add_action( 'init', array( $this, 'wpe_invoice_download' ) );

		add_action( 'admin_head', array( $this, 'add_custom_invoice_icon_css' ) );

		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_invoice_pdf_to_email' ), 10, 3 );

	}

	/**
	 * Deletes all existing pdfs
	 */
	public function clear_pdf_cache() {

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'failed' );
		}

		$dir = $this->base_upload_dir . '/wpe-invoices';

		if ( file_exists( $dir ) ) {
			foreach ( scandir( $dir ) as $file ) {
				if ( '.' === $file || '..' === $file ) {
					continue;
				} else {
					unlink( "$dir/$file" );
				}
			}
			rmdir( $dir );
		}
		if ( ! file_exists( $dir ) ) {
			die( 'success' );
		} else {
			die( 'failed' );
		}

	}

	/**
	 * Saves customisations
	 */
	public function save_invoice_pdf_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'failed' );
		}

		if ( ! wp_verify_nonce( $_POST['__pinonce'], NONCE_KEY ) ) {
			die( 'failed' );
		}
		if ( isset( $_POST['invoice_options'] ) && $_POST['invoice_options'] ) {
			$data = $_POST['invoice_options'];

			function validate_values( &$item, $key ) {

				if ( $key == 'attach_with' || $key == 'show_addon' || $key == 'show' ) {
					$item = (int) sanitize_text_field( stripslashes( $item ) );

				} elseif ( $key == 'message' ) {
					$item = wp_kses_post( stripslashes( $item ) );
				} else {
					$item = sanitize_text_field( stripslashes( $item ) );
				}
			}

			array_walk_recursive( $data, 'validate_values' );

			update_option( 'wpe_invoice_pdf_settings', $data );
		}
		die( 'success' );
	}


	/**
	 * Custom action for downloading pdf in admin order table and frontend user account order table
	 *
	 * @param $actions
	 * @param $order
	 *
	 * @return mixed
	 */
	function wpe_add_my_account_order_actions( $actions, $order ) {
		$order_id = $order->get_id();
		if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) {
			$actions['name'] = array(
				'url'    => site_url( '?downloadpdfinvoice=' . $order_id ),
				'name'   => 'Invoice',
				'action' => "invoice view",
			);
		}

		return $actions;
	}

	/**
	 *Adds admin menu for plugin
	 */
	public function admin_menu() {
		$menu = add_submenu_page( 'woocommerce', 'Invoices', 'Invoices', 'manage_options', 'invoices', array(
			$this,
			'admin_main_page'
		), "" );
		add_action( 'load-' . $menu, array( $this, 'loadAdminScripts' ) );

	}

	/**
	 * Loads necessary scrips for selected admin menu page
	 */
	function loadAdminScripts() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
	}

	/**
	 * Enqueues scripts
	 */
	function enqueueAdminScripts() {
		wp_enqueue_script( "jquery" );
		wp_enqueue_script( "jquery-form" );
		wp_enqueue_script( "bootstrap-js", WPE_INVOICE_ASSETS . "/bootstrap/js/bootstrap.min.js", array( 'jquery' ) );
		wp_enqueue_style( "bootstrap-css", WPE_INVOICE_ASSETS . "/bootstrap/css/bootstrap.min.css" );
		wp_enqueue_style( "fa-css", WPE_INVOICE_ASSETS . "/font-awesome/css/font-awesome.min.css" );

	}

	/**
	 *Generates admin menu page contents
	 */
	public function admin_main_page() {
		require_once 'admin/index.php';

	}

	/**
	 * Create and saves a pdf for a specific order
	 *
	 * @param        $order_id
	 * @param string $type
	 */
	public function create_and_save_pdf( $order_id, $type = 'normal' ) {

		ob_start();
		include 'assets/templates/template1/index.php';
		$string = ob_get_clean();

		$options = new Options();
		$options->set( 'defaultFont', 'Courier' );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $string );
		$dompdf->setPaper( 'A4', 'portrait' );

		$dompdf->render();

//		$dompdf->stream( $order_id . '-invoice.pdf' );


		$output = $dompdf->output();
		if ( $type === 'temp' ) {

			wp_mkdir_p( $this->base_upload_dir . '/wpe-temp-invoices/' );
			file_put_contents( $this->base_upload_dir . '/wpe-temp-invoices/' . $order_id . '-invoice.pdf', $output );
		} else {
			wp_mkdir_p( $this->base_upload_dir . '/wpe-invoices/' );
			file_put_contents( $this->base_upload_dir . '/wpe-invoices/' . $order_id . '-invoice.pdf', $output );
		}
	}

	/**
	 * Serves a existing pdf or create and serves if pdf doesn't already exist
	 */
	public function wpe_invoice_download() {

		if ( isset( $_REQUEST['downloadpdfinvoice'] ) ) {

			$order_id = (int) $_REQUEST['downloadpdfinvoice'];

			$order = wc_get_order( $order_id );

			if ( $order ) {
				$order_user_id = $order->get_user_id();
			} else {
				die( "Order ID doesn't exist. Please contact admin." );
			}


			if ( ! current_user_can( 'manage_options' ) && get_current_user_id() !== $order_user_id ) {
				die( "You don't have permission to do this!" );
			}

			$file = $this->base_upload_dir . '/wpe-invoices/' . $order_id . '-invoice.pdf';
			if ( ! file_exists( $file ) ) {
				$this->create_and_save_pdf( $order_id );
			}
//			$this->create_and_save_pdf( $order_id );
			header( "Content-Type: application/octet-stream" );
			header( "Content-Disposition: attachment; filename=" . urlencode( basename( $file ) ) );
			header( "Content-Type: application/octet-stream" );
			header( "Content-Type: application/download" );
			header( "Content-Description: File Transfer" );
			header( "Content-Length: " . filesize( $file ) );

			$fp = fopen( $file, "r" );
			while ( ! feof( $fp ) ) {
				echo fread( $fp, 65536 );
				flush(); // this is essential for large downloads
			}
			fclose( $fp );
		}
	}

	/**
	 * Order table custom action icon renderer
	 */
	function add_custom_invoice_icon_css() {
		echo '<style>a.view.invoice::after {content:""!important;background-image: url("' . WPE_INVOICE_ASSETS . '/invoice.png' . '")  !important;background-repeat: no-repeat; background-position: center;}</style>';
	}

	/**
	 * Attaches invoice pdf to the default woocommerce order email
	 *
	 * @param $attachments
	 * @param $status
	 * @param $order
	 *
	 * @return array
	 */
	function attach_invoice_pdf_to_email( $attachments, $status, $order ) {

		$settings = get_option( 'wpe_invoice_pdf_settings' );

		if ( isset( $settings['attach_with'] ) && $settings['attach_with'] == 1 ) {

			$order_id = $order->get_id();

			$file = $this->base_upload_dir . '/wpe-invoices/' . $order_id . '-invoice.pdf';
			if ( ! file_exists( $file ) ) {
				$this->create_and_save_pdf( $order_id );
			}

			$allowed_statuses = array(
				'new_order',
				'customer_on_hold_order',
				'customer_invoice',
				'customer_processing_order',
				'customer_completed_order'
			);
			if ( isset( $status ) && in_array( $status, $allowed_statuses ) ) {
				$attachments[] = $file;
			}
		}

		return $attachments;
	}

}

new WpeWcInvoicePdf();