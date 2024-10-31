<?php

namespace Palasthotel\WordPress\Reaction\Source;


use Palasthotel\ProLitteris\Post;
use Palasthotel\WordPress\Reaction\Components\Database;
use Palasthotel\WordPress\Reaction\Model\PostReaction;

class PostReactionsDatabase extends Database {

	private string $table;

	public function init() {
		$this->table = $this->wpdb->prefix . "reactions_to_posts";
	}

	public function setReaction( PostReaction $reaction ): bool {
		$result = $this->wpdb->replace(
			$this->table,
			[
				"post_id"         => $reaction->post_id,
				"reaction"        => $reaction->reaction,
				"reactions_count" => $reaction->count,
				"user_id"         => $reaction->user_id,
			],
			[ '%d', '%s', '%d', '%d' ]
		);

		return $result == 1 || $result == true;
	}

	public function unsetReaction( $post_id, $reaction, $user_id = 0 ) {
		return $this->wpdb->delete(
			$this->table,
			[
				"post_id"  => $post_id,
				"reaction" => $reaction,
				"user_id"  => $user_id,
			],
			[ "%d", "%s", "%d" ]
		);
	}

	public function clearReactions( $post_id, $user_id = 0 ) {
		return $this->wpdb->delete(
			$this->table,
			[
				"post_id" => $post_id,
				"user_id" => $user_id,
			],
			[ "%d", "%d" ]
		);
	}

	/**
	 * @param int $post_id
	 *
	 * @return PostReaction[]
	 */
	public function getReactions( $post_id ): array {
		$table  = $this->table;
		$sql    = $this->wpdb->prepare(
			"SELECT post_id, reaction, sum(reactions_count) as count
FROM $table WHERE post_id = %d GROUP BY post_id, reaction",
			$post_id,
		);
		$result = $this->wpdb->get_results( $sql );

		return array_map( function ( $row ) {
			return PostReaction::build(
				intval( $row->post_id ),
				$row->reaction,
				intval( $row->count ),
				0
			);
		}, $result );
	}

	public function getReaction( $post_id, $reaction, $user_id = 0 ): PostReaction {
		$table = $this->table;
		$sql   = $this->wpdb->prepare(
			"SELECT sum(reactions_count) as count
FROM $table WHERE post_id = %d AND reaction = %s AND user_id = %d",
			$post_id,
			$reaction,
			$user_id
		);

		$result = $this->wpdb->get_var( $sql );

		$reaction = PostReaction::build(
			$post_id,
			$reaction,
			0,
			$user_id
		);

		if ( $result != null ) {
			$reaction->count = intval( $result );
		}

		return $reaction;
	}

	public function countReactions( $post_id, $reaction = null ) {
		$table = $this->table;

		$query = is_string($reaction) ?
			$this->wpdb->prepare( "SELECT sum(reactions_count) FROM $table WHERE post_id = %d AND reaction = %s", $post_id, $reaction )
			:
			$this->wpdb->prepare( "SELECT sum(reactions_count) FROM $table WHERE post_id = %d", $post_id );

		return intval( $this->wpdb->get_var( $query ) );
	}

	/**
	 * create database tables
	 */
	public function createTables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$postsTable = $this->wpdb->posts;
		$table      = $this->table;

		dbDelta( "CREATE TABLE IF NOT EXISTS {$table} (
			 post_id bigint(20) unsigned not null,
			 user_id bigint(20) unsigned not null default 0,
			 reaction varchar(80) not null,
    		 reactions_count int(11) default 1,
			 PRIMARY KEY (`post_id`, `user_id`, `reaction`),
			 key (post_id),
			 key (user_id),
			 key (reaction),
    		 key (reactions_count),
			 CONSTRAINT `{$table}_to_posts` FOREIGN KEY (`post_id`) REFERENCES `{$postsTable}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );


	}


}
