<?php

/**
 *
 * Plugin Name: Reaction
 * Plugin URI: https://github.com/palasthotel/wp-reaction
 * Description: Reaction apis for posts, comments and terms
 * Version: 0.2.1
 * Author: Palasthotel by Edward <edward.bock@palasthotel.de>
 * Author URI: https://palasthotel.de
 * Text Domain: reaction
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 6.2.0
 * License: http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 *
 * @copyright Copyright (c) 2023, Palasthotel
 * @package Palasthotel\WordPress\Reaction
 *
 */

namespace Palasthotel\WordPress\Reaction;

require_once dirname(__FILE__)."/vendor/autoload.php";

class Reaction extends Components\Plugin {

	const DOMAIN = "reaction";

	const REST_NAMESPACE = "reaction/v1";

	const HANDLE_REACTIONS_API = "-reaction-api";

	const FILTER_REST_OBJECTS = "reaction_rest_objects";

	var Repository $repo;
	var Ajax $ajax;

	function onCreate() {

		$this->repo = new Repository();
		$this->ajax = new Ajax($this);

		new REST($this);
		new Assets($this);

		if(WP_DEBUG){
			$this->repo->init();
		}
	}

	public function onSiteActivation() {
		parent::onSiteActivation();
		$this->repo->init();
	}

}

Reaction::instance();

require_once dirname(__FILE__)."/public-functions.php";
