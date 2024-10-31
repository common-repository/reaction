<?php

namespace Palasthotel\WordPress\Reaction\Model;

class CommentReaction {

	var int $comment_id;
	var string $reaction;
	var int $user_id;
	var bool $unique;

	private function __construct(int $comment_id, string $reaction, int $user_id){
		$this->comment_id = $comment_id;
		$this->reaction = $reaction;
		$this->user_id = $user_id;
	}

	public static function build(int $comment_id, string $reaction, int $user_id){
		return new self($comment_id, $reaction, $user_id);
	}
}
