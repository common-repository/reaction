<?php

namespace Palasthotel\WordPress\Reaction;

class Ajax extends Components\Component {
	const ACTION_NONCE = "reaction_get_nonce";
	public function onCreate() {
		parent::onCreate();
		add_action('wp_ajax_'.self::ACTION_NONCE, function(){
			wp_ajax_rest_nonce();
		});
	}

	public function getAjaxUrl(){
		return admin_url()."admin-ajax.php?action=".self::ACTION_NONCE;
	}
}
