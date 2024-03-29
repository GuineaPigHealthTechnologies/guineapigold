<?php

/**
 * woocommerce_gpf_structured_data
 *
 * Enriches the on-page microdata based on Google Product Feed data values.
 */
class WoocommerceGpfStructuredData {

	/**
	 * Constructor.
	 *
	 * Make sure our dependent classes are available (sigh).
	 * Add the filter so we can modify the structured data.
	 */
	public function __construct() {
		require_once( 'woocommerce-gpf-common.php' );
		require_once( 'woocommerce-gpf-frontend.php' );
		require_once( 'woocommerce-gpf-feed-item.php' );

		// Add hook to modify JSON+LD data.
		add_filter( 'woocommerce_structured_data_product', array( $this, 'structured_data_product' ), 10, 2 );
		add_filter( 'woocommerce_structured_data_product_offer', array( $this, 'structured_data_product_offer' ), 10, 2 );
	}

	/**
	 * Filter the structured data array created by WooCommerce.
	 *
	 * @param  array $markup        The array representing the JSON+LD as
	 *                              generated by WooCommerce
	 * @param  WC_Product $product  The product being output.
	 *
	 * @return array                The modified array.
	 */
	public function structured_data_product( $markup, $product ) {
		if ( is_callable( array( $product, 'get_type' ) ) ) {
			$product_type = $product->get_type();
		} else {
			$product_type = $product->product_type;
		}
		if ( 'simple' === $product_type ) {
			return $this->structured_data_simple_product( $markup, $product );
		}
		return $markup;
	}

	/**
	 * Filter the structured data array for offers created by WooCommerce.
	 *
	 * We only interfere for in offers for variable products and variations.
	 *
	 * @param  array $markup              The array representing the JSON+LD as
	 *                                    generated by WooCommerce
	 * @param  WC_Product $offer_product  The specific product being listed as
	 *                                    an offer.
	 *
	 * @return array                The modified array.
	 */
	public function structured_data_product_offer( $markup, $offer_product ) {

		global $woocommerce_gpf_common;

		if ( is_callable( array( $offer_product, 'get_type' ) ) ) {
			$product_type = $offer_product->get_type();
		} else {
			$product_type = $offer_product->product_type;
		}
		if ( 'variable' !== $product_type && 'variation' !== $product_type ) {
			return $markup;
		}
		// Get the feed information for this product.
		$feed_item = new WoocommerceGpfFeedItem( $offer_product, $offer_product, 'google', $woocommerce_gpf_common );

		// SKU.
		if ( ! empty( $feed_item->sku ) ) {
			$markup['sku'] = $feed_item->sku;
		} else {
			$markup['sku'] = $feed_item->guid;
		}
		// Condition.
		if ( isset( $feed_item->additional_elements['condition'] ) ) {
			$markup['itemCondition'] = $feed_item->additional_elements['condition'][0];
		}
		// GTIN.
		if ( isset( $feed_item->additional_elements['gtin'][0] ) ) {
			$gtin_length = strlen( $feed_item->additional_elements['gtin'][0] );
			$key = 'gtin' . $gtin_length;
			switch ( $gtin_length ) {
				case 8:
				case 13:
				case 14:
					$markup[ $key ] = $feed_item->additional_elements['gtin'][0];
				break;
			}
		}
		// MPN.
		if ( isset( $feed_item->additional_elements['mpn'] ) ) {
			$markup['mpn'] = $feed_item->additional_elements['mpn'][0];
		}
		return $markup;
	}

	/**
	 * Filter the data array created by WooCommerce for a simple product.
	 *
	 * @param  array $markup        The array representing the JSON+LD as
	 *                              generated by WooCommerce
	 * @param  WC_Product $product  The product being output.
	 *
	 * @return array                The modified array.
	 */
	private function structured_data_simple_product( $markup, $product ) {

		global $woocommerce_gpf_common;

		// Get the feed information for this product.
		$feed_item = new WoocommerceGpfFeedItem( $product, $product, 'google', $woocommerce_gpf_common );

		// SKU.
		$markup['sku'] = $feed_item->guid;

		// Condition.
		if ( isset( $feed_item->additional_elements['condition'] ) ) {
			$markup['itemCondition'] = $feed_item->additional_elements['condition'][0];
		}

		// GTIN.
		if ( isset( $feed_item->additional_elements['gtin'][0] ) ) {
			$gtin_length = strlen( $feed_item->additional_elements['gtin'][0] );
			$key = 'gtin' . $gtin_length;
			switch ( $gtin_length ) {
				case 8:
				case 13:
				case 14:
					$markup[ $key ] = $feed_item->additional_elements['gtin'][0];
				break;
			}
		}

		// MPN.
		if ( isset( $feed_item->additional_elements['mpn'] ) ) {
			$markup['mpn'] = $feed_item->additional_elements['mpn'][0];
		}

		// Brand.
		if ( isset( $feed_item->additional_elements['brand'] ) ) {
			$markup['brand'] = $feed_item->additional_elements['brand'][0];
		}

		// Colour.
		if ( isset( $feed_item->additional_elements['color'] ) ) {
			$markup['color'] = $feed_item->additional_elements['color'][0];
		}

		return $markup;
	}

}
