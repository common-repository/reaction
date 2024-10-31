<?php

use Palasthotel\WordPress\Reaction\Model\CommentReactions;
use Palasthotel\WordPress\Reaction\Model\PostReaction;
use Palasthotel\WordPress\Reaction\Reaction;

function reaction_plugin(){
	return Reaction::instance();
}

/**
 * @param int $post_id
 *
 * @return PostReaction[]
 */
function reaction_get_reactions_by_post($post_id): array {
	return reaction_plugin()->repo->postReactionsDb->getReactions($post_id);
}

/**
 * @param int $post_id
 * @param string|null $reaction
 *
 * @return int
 */
function reaction_count_reactions_by_post( $post_id, $reaction = null ) {
	return reaction_plugin()->repo->postReactionsDb->countReactions($post_id, $reaction);
}

/**
 * @param int $comment_ID
 *
 * @return CommentReactions[]
 */
function reaction_get_reactions_by_comment( $comment_ID ): array {
	return reaction_plugin()->repo->commentReactionsDb->getReactions($comment_ID);
}

/**
 * @param int $comment_ID
 * @param string|null $reaction
 *
 * @return int
 */
function reaction_count_reactions_by_comment($comment_ID, $reaction = null){
	return reaction_plugin()->repo->commentReactionsDb->countReactions($comment_ID, $reaction);
}
