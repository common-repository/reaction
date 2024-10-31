<?php

namespace Palasthotel\WordPress\Reaction\Source;


use Palasthotel\WordPress\Reaction\Components\Database;
use Palasthotel\WordPress\Reaction\Model\CommentReaction;
use Palasthotel\WordPress\Reaction\Model\CommentReactions;

class CommentReactionsDatabase extends Database {

	private string $table;

	public function init() {
		$this->table = $this->wpdb->prefix . "reactions_to_comments";
	}

	/**
	 *
	 * @return bool
	 */
	public function setReaction( CommentReaction $reaction) {
		$result = $this->wpdb->replace(
			$this->table,
			[
				"user_id" => $reaction->user_id,
				"comment_id" => $reaction->comment_id,
				"reaction" => $reaction->reaction,
			],
			[ '%d', '%d', '%s' ]
		);
		return $result == 1 || $result == true;
	}

	/**
	 * @param int $user_id
	 * @param int $comment_ID
	 * @param string $reaction
	 *
	 */
	public function unsetReaction( $user_id, $comment_ID, $reaction ): bool {
		$result = $this->wpdb->delete(
			$this->table,
			[
				"user_id" => $user_id,
				"comment_id" => $comment_ID,
				"reaction" => $reaction,
			],
			[ '%d', '%d', '%s' ]
		);
		return $result == 1 || $result == true;
	}

	/**
	 * @param int $user_id
	 * @param int $commentId
	 *
	 * @return bool|int
	 */
	public function clearReactions( $commentId, $user_id ) {
		return $this->wpdb->delete(
			$this->table,
			[
				"user_id" => $user_id,
				"comment_id" => $commentId,
			],
			["%d", "%d"]
		);
	}

	/**
	 * @param int $comment_ID
	 * @param string|null $reaction
	 *
	 * @return array|int
	 */
	public function countReactions( $comment_ID, $reaction = null ) {
		$table = $this->table;

		if($reaction){
			return intval($this->wpdb->get_var(
				$this->wpdb->prepare("SELECT count(reaction) FROM $table WHERE comment_id = %d AND reaction = %s", $comment_ID, $reaction)
			));
		}

		$query = $this->wpdb->prepare("SELECT count(reaction) as `count`, reaction FROM $table WHERE comment_id = %d GROUP BY reaction", $comment_ID);

		return $this->wpdb->get_results( $query	);

	}

	/**
	 * @param int $comment_id
	 *
	 * @return CommentReactions[]
	 */
	public function getReactions( $comment_id, $user_id = 0 ) {
		$table = $this->table;

		if($user_id > 0){
			$query =$this->wpdb->prepare(
				"SELECT comment_id, reaction, count(reaction) as count FROM $table
                         WHERE comment_id = %d AND user_id = %d  GROUP BY reaction",
				$comment_id, $user_id
			);
		} else {
			$query =$this->wpdb->prepare(
				"SELECT comment_id, reaction, count(reaction) as count FROM $table
                         WHERE comment_id = %d  GROUP BY reaction",
				$comment_id
			);
		}

		$result = $this->wpdb->get_results( $query	);
		return array_map(function($row){
			return CommentReactions::build(
				intval($row->comment_id),
				$row->reaction,
				intval($row->count)
			);
		}, $result);
	}

	public function getReactionsByPostId($post_id, $user_id = 0){
		$table = $this->table;

		$tableComments = $this->wpdb->comments;
		$selectCommentIds = $this->wpdb->prepare(
			"SELECT comment_ID from $tableComments WHERE comment_post_ID = %d",
			$post_id
		);

		if($user_id > 0){
			$query =$this->wpdb->prepare(
				"SELECT comment_id, reaction, count(reaction) as count FROM $table
	                         WHERE comment_id IN ($selectCommentIds) AND user_id = %d GROUP BY comment_id, reaction",
				$user_id
			);
		} else {
			$query =$this->wpdb->prepare(
				"SELECT comment_id, reaction, count(reaction) as count FROM $table
	                         WHERE comment_id IN ($selectCommentIds) GROUP BY comment_id, reaction",
			);
		}

		$results = $this->wpdb->get_results($query);

		return array_map(function($row){
			return CommentReactions::build(
				intval($row->comment_id),
				$row->reaction,
				intval($row->count)
			);
		}, $results);

	}

	/**
	 * @param int $user_id
	 * @param array $comment_IDs
	 *
	 * @return array
	 */
	public function getReactionsByUser( int $user_id, array $comment_IDs ) {
		$table = $this->table;
		$inIds = "'".implode("', '", $comment_IDs)."'";
		return $this->wpdb->get_results(
			$this->wpdb->prepare("SELECT comment_id, reaction FROM $table WHERE user_id = %d AND comment_id IN ($inIds)", $user_id)
		);
	}

	/**
	 * create database tables
	 */
	public function createTables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$commentsTable = $this->wpdb->comments;
		$usersTable = $this->wpdb->users;
		$table = $this->table;

		dbDelta( "CREATE TABLE IF NOT EXISTS {$table} (
			 comment_id bigint(20) unsigned not null,
			 user_id bigint(20) unsigned not null,
			 reaction varchar(80) not null,
			 PRIMARY KEY (`comment_id`, `user_id`, `reaction`),
			 key (comment_id),
			 key (user_id),
			 key (reaction),
			 CONSTRAINT `{$table}_to_comments` FOREIGN KEY (`comment_id`) REFERENCES `{$commentsTable}` (`comment_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
			 CONSTRAINT `{$table}_to_users` FOREIGN KEY (`user_id`) REFERENCES `{$usersTable}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );

	}



}
