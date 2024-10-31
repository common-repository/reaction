<?php


namespace Palasthotel\WordPress\Reaction;


use Palasthotel\WordPress\Reaction\Components\Component;

class Assets extends Component {

	private Components\Assets $assetHelper;

	public function onCreate() {
		$this->assetHelper = new Components\Assets($this->getPlugin());
		add_action('init', [$this, 'init']);
		add_action('wp_enqueue_scripts', [$this,'enqueue']);
	}

	public function init(){
		$this->assetHelper->registerScript(
			reaction::HANDLE_REACTIONS_API,
			"build/api.js",
			[],
			false
		);
	}

	public function enqueue(){
		wp_localize_script(
			reaction::HANDLE_REACTIONS_API,
			"Reaction",
			[
				"config" => [
					"rest_base" => esc_url_raw(rest_url(reaction::REST_NAMESPACE)),
					"rest_nonce" =>  wp_create_nonce('wp_rest'),
					"post_id" => get_the_ID(),
					"post_type" => get_post_type(),
					"get_nonce_url" => $this->getPlugin()->ajax->getAjaxUrl(),
				],
			]
		);
		wp_enqueue_script(reaction::HANDLE_REACTIONS_API);
	}

}
