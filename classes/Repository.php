<?php


namespace Palasthotel\WordPress\Reaction;

use Palasthotel\WordPress\Reaction\Model\CommentReaction;
use Palasthotel\WordPress\Reaction\Source\CommentReactionsDatabase;
use Palasthotel\WordPress\Reaction\Source\PostReactionsDatabase;
use Palasthotel\WordPress\Reaction\Source\TermReactionsDatabase;
use WP_Error;

/**
 */
class Repository {

	var PostReactionsDatabase $postReactionsDb;
	var CommentReactionsDatabase $commentReactionsDb;
	var TermReactionsDatabase $termReactionsDb;

	public function __construct() {
		$this->postReactionsDb = new PostReactionsDatabase();
		$this->commentReactionsDb = new CommentReactionsDatabase();
		$this->termReactionsDb = new TermReactionsDatabase();
	}

	public function init(){
		$this->postReactionsDb->createTables();
		$this->commentReactionsDb->createTables();
		$this->termReactionsDb->createTables();
	}

	public function incrementPostReaction($post_id, $reaction, $user_id, $by = 1){
		$reaction = $this->postReactionsDb->getReaction( $post_id, $reaction, $user_id );
		$reaction->count = max(0, $by + $reaction->count);
		return $this->postReactionsDb->setReaction($reaction);
	}

	public function setCommentReaction($comment_id, $reaction, $user_id, $unique = true){
		if($unique){
			$this->commentReactionsDb->clearReactions($comment_id, $user_id);
		}
		return $this->commentReactionsDb->setReaction(CommentReaction::build(
			$comment_id,
			$reaction,
			$user_id
		));
	}

}
