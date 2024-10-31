<?php

$order            = wc_get_order( $order_id ) ? wc_get_order( $order_id ) : wc_get_order( 479 );
$user             = get_user_by( 'ID', $order->get_customer_id() );
$customer_name    = $user->display_name;
$shipping_address = $order->get_formatted_shipping_address();
$billing_address  = $order->get_formatted_billing_address();
$currency         = get_option( 'woocommerce_currency' );
$currency_symbol  = get_woocommerce_currency_symbol();

$settings = get_option( 'wpe_invoice_pdf_settings' );

$customer_address1 = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1();
$customer_state    = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
$customer_postcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode();
$customer_country  = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        a {
            color: #5D6975;
            text-decoration: none;
        }

        body {
            position: relative;
            /*width: 21cm;*/
            /*height: 29.7cm;*/
            margin: 0 auto;
            color: #001028;
            background: #FFFFFF;
            font-family: Arial, sans-serif;
            font-size: 12px;

        }

        header {
            padding: 10px 0;
            margin-bottom: 30px;
        }

        #logo {
            text-align: center;
            margin-bottom: 10px;
        }

        #logo img {
            width: 90px;
        }

        h1 {
            border-top: 1px solid #5D6975;
            border-bottom: 1px solid #5D6975;
            color: #5D6975;
            font-size: 2.4em;
            line-height: 1.4em;
            font-weight: normal;
            text-align: center;
            margin: 0 0 20px 0;
        }

        #project {
            float: left;
        }

        #project span {
            vertical-align: bottom;
            color: #5D6975;
            text-align: right;
            width: 52px;
            margin-right: 10px;
            display: inline-block;

        }

        #company {
            float: right;
            text-align: right;
        }

        #project div,
        #company div {
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        table tr:nth-child(2n-1) td {
            background: #F5F5F5;
        }

        table th,
        table td {
            text-align: center;
        }

        table th {
            padding: 5px 20px;
            color: #5D6975;
            border-bottom: 1px solid #C1CED9;
            white-space: nowrap;
            font-weight: normal;
        }

        table .service,
        table .desc {
            text-align: left;
        }

        table td {
            padding: 20px;
            text-align: right;
        }

        table td.service,
        table td.desc {
            vertical-align: top;
        }

        table td.unit,
        table td.qty,
        table td.total {
            font-size: 1.2em;
        }

        table td.grand {
            border-top: 1px solid #5D6975;;
        }

        #notices .notice {
            color: #5D6975;
            font-size: 1.2em;
        }

        footer {
            color: #5D6975;
            width: 100%;
            height: 30px;
            position: absolute;
            bottom: 0;
            border-top: 1px solid #C1CED9;
            padding: 8px 0;
            text-align: center;
        }

        ul li {
            list-style: none;
        }


    </style>
</head>
<body>
<header class="clearfix">

    <h1>INVOICE <?php echo esc_attr( $order->get_order_number() ); ?></h1>

    <div id="project">
        <div><span>TO</span> <?php echo esc_attr( $customer_name ); ?></div>
        <div>
            <span>ADDRESS</span> <?php echo esc_attr( $customer_address1 . ',' . $customer_state . ' ' . $customer_postcode . ',' . $customer_country ) ?>
        </div>
        <div><span>EMAIL</span>
            <a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_attr( $user->user_email ); ?></a>
        </div>
        <div><span>DATE</span> <?php echo esc_attr( date( "F j, Y", strtotime( $order->get_date_created() ) ) ); ?>
        </div>
        <div><span>STATUS</span> <?php echo esc_attr( ucfirst( $order->get_status() ) ); ?></div>
    </div>
</header>
<main>
    <table>
        <thead>
        <tr>

            <th class="desc">DESCRIPTION</th>
            <th style="text-align: right">PRICE</th>
            <th style="text-align: right">QTY</th>
            <th style="text-align: right">TOTAL</th>
        </tr>
        </thead>
        <tbody>


		<?php
		$ind = 0;
		foreach ( $order->get_items() as $item_id => $item ) {

			$product = $item->get_product();

			?>

            <tr>

                <td class="desc">
					<?php
					$is_visible = $product && $product->is_visible();

					//print_r($item);
					echo esc_attr( $item->get_name() );
					$metaInd = 0;
					if ( isset( $settings['show_addon'] ) && (int) $settings['show_addon'] == 1 ) {
						foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
							if ( $metaInd === 0 ) {
								echo '<br><span style="font-size: .75em">[';
							}
							echo $meta->key;
							$metaInd ++;

							if ( $metaInd === sizeof( $item->get_formatted_meta_data() ) ) {
								echo ']</span>';
							} else {
								echo ',';
							}
						}
					}
					?>
                </td>
                <td class="unit">
					<?php echo esc_attr( $currency_symbol . round( $order->get_item_subtotal( $item ) / $item->get_quantity(), 2 ) ); ?>
                </td>

                <td class="qty">
					<?php echo $item->get_quantity(); ?>
                </td>

                <td class="total">
					<?php echo esc_attr( $currency_symbol . $order->get_item_subtotal( $item ) ) . '&nbsp;'; ?>
                </td>

            </tr>
			<?php
			$ind ++;
		}
		?>


        <tr>
            <td colspan="3">SUBTOTAL</td>
            <td class="total "><?php echo esc_attr( $currency_symbol . round( $order->get_subtotal(), 2 ) ) ?></td>
        </tr>
        <tr>
            <td colspan="3">VAT</td>
            <td class="total"><?php echo esc_attr( $currency_symbol . round( $order->get_cart_tax(), 2 ) ) ?></td>
        </tr>
        <tr>
            <td colspan="3">SHIPPING</td>
            <td class="total"><?php echo esc_attr( $currency_symbol . round( $order->get_shipping_total(), 2 ) ) ?></td>
        </tr>
        <tr>
            <td colspan="3" class="grand total">GRAND TOTAL</td>
            <td class="grand total"><?php echo esc_attr( $currency_symbol . round( $order->get_total(), 2 ) ) ?></td>
        </tr>

        </tbody>
    </table>
    <br>
    <div id="notices">
		<?php if ( isset( $settings['notice']['show'] ) && (int) $settings['notice']['show'] == 1 ) { ?>

            <div>
                <strong><?php echo isset( $settings['notice']['title'] ) ? $settings['notice']['title'] : ''; ?> </strong>
            </div>
            <div class="notice"><?php echo isset( $settings['notice']['message'] ) ? wpautop( esc_attr( $settings['notice']['message'] ) ) : ''; ?></div>
		<?php } ?>

    </div>
</main>
<footer>
    Invoice was created on a computer and is valid without a signature and seal.
</footer>
</body>
</html>