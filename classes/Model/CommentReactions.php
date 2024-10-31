<?php

namespace Palasthotel\WordPress\Reaction\Model;

class CommentReactions {

	var int $comment_id;
	var string $reaction;
	var int $count;

	private function __construct(int $comment_id, string $reaction, int $count){
		$this->comment_id = $comment_id;
		$this->reaction = $reaction;
		$this->count = $count;
	}

	public static function build(int $comment_id, string $reaction, int $count){
		return new self($comment_id, $reaction, $count);
	}
}
