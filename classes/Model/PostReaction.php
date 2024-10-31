<?php

namespace Palasthotel\WordPress\Reaction\Model;

class PostReaction {

	var int $post_id;
	var string $reaction;
	var int $count;
	var int $user_id;

	private function __construct(int $post_id, string $reaction, int $count, int $user_id) {
		$this->post_id = $post_id;
		$this->reaction = $reaction;
		$this->count = $count;
		$this->user_id = $user_id;
	}

	public static function build(int $post_id, string $reaction, int $count, int $user_id){
		return new self($post_id, $reaction, $count, $user_id);
	}
}
