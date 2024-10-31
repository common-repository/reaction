<?php

namespace Palasthotel\WordPress\Reaction\Model;

class TermReaction {

	var int $term_id;
	var string $taxonomy;
	var string $reaction;
	var int $count;
	var int $user_id;

	private function __construct(int $term_id, string $taxonomy, string $reaction, int $count, int $user_id) {
		$this->term_id = $term_id;
		$this->taxonomy = $taxonomy;
		$this->reaction = $reaction;
		$this->count = $count;
		$this->user_id = $user_id;
	}

	public static function build(int $term_id, string $taxonomy, string $reaction, int $count, int $user_id){
		return new self($term_id, $taxonomy, $reaction, $count, $user_id);
	}
}
