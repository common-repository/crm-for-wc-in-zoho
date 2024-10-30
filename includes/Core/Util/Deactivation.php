<?php

namespace BitPress\BIT_WC_ZOHO_CRM\Core\Util;

/**
 * Class handling plugin deactivation.
 *
 * @since 1.0.0
 * @access private
 * @ignore
 */
final class Deactivation
{

	/**
	 * Registers functionality through WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function register()
	{
		add_action(
			'bit_wc_zoho_crm_deactivation',
			function () {
				// TODO: 			
			}
		);
	}
}
