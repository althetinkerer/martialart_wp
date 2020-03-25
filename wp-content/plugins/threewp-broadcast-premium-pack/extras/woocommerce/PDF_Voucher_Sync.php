<?php
/*
Author:			Edward Plainview
Author Email:	edward@plainviewplugins.com
Author URI:		http://plainviewplugins.com
Description:	Syncs Woocommerce PDF vouchers between parent and child posts.
Plugin Name:	Broadcast Woocommerce PDF Voucher Sync
Plugin URI:		http://plainviewplugins.com/
Version:		1
*/

namespace plainview\broadcast\woocommerce_sync;

/**
	@brief		Sync the product vouchers after purchase.
	@since		2015-05-03 21:35:06
**/
class PDF_Voucher_Sync
{
	/**
		@brief		Constructor.
		@since		2015-05-02 00:17:18
	**/
	public function __construct()
	{
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'sync_pdf_vouchers' ], 100 );		// 100 = Wait until the Voucher plugin is finished.
	}

	public function sync_pdf_vouchers( $order_id )
	{
		$prefix = 'PDF Voucher Sync Sync: ';

		// No point in trying to use broadcst if it is not installed.
		if ( ! defined( 'THREEWP_BROADCAST_VERSION' ) )
			return;

		$vou_prefix = WOO_VOU_META_PREFIX;
		$order = new \WC_Order( $order_id );
		$order_items = $order->get_items();

		ThreeWP_Broadcast()->debug( $prefix . 'Order %s has %s items.', $order_id, count( $order_items ) );

		foreach( $order_items as $item )
			foreach ( $order_items as $item_id => $item )
			{
				$productid = $item['product_id'];

				ThreeWP_Broadcast()->debug( $prefix . 'Will update vouchers for product %s', $productid );

				// Get the current value.
				$vouchers = get_post_meta( $productid, $vou_prefix . 'codes', true );

				$action = new \threewp_broadcast\actions\each_linked_post();
				$action->post_id = $productid;
				$action->add_callback( function( $o ) use ( $prefix, $vou_prefix, $vouchers )
				{
					ThreeWP_Broadcast()->debug( $prefix . 'Setting vouchers for product %s on blog %s to %s.', $o->post_id, get_current_blog_id(), $vouchers );
					update_post_meta( $o->post_id, $vou_prefix . 'codes', $vouchers );
				} );
				$action->execute();
			}
	}
}
new PDF_Voucher_Sync();
