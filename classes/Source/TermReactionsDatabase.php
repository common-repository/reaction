<?php

namespace Palasthotel\WordPress\Reaction\Source;


use Palasthotel\WordPress\Reaction\Components\Database;
use Palasthotel\WordPress\Reaction\Model\CommentReaction;
use Palasthotel\WordPress\Reaction\Model\PostReaction;
use Palasthotel\WordPress\Reaction\Model\TermReaction;

class TermReactionsDatabase extends Database {

	private string $table;

	public function init() {
		$this->table = $this->wpdb->prefix . "reactions_to_terms";
	}

	public function setReaction($term_id, $taxonomy, $reaction, $count = 1, $user_id = 0){
		return $this->wpdb->replace(
			$this->table,
			[
				"term_id" => $term_id,
				"taxonomy" => $taxonomy,
				"reaction" => $reaction,
				"reactions_count" => $count,
				"user_id" => $user_id,
			],
			[ '%d', '%s', '%s', '%d', '%d' ]
		);
	}

	public function unsetReaction($term_id, $taxonomy, $reaction, $user_id = 0){
		return $this->wpdb->delete(
			$this->table,
			[
				"term_id" => $term_id,
				"taxonomy" => $taxonomy,
				"reaction" => $reaction,
				"user_id" => $user_id,
			],
			["%d", '%s', "%s", "%d"]
		);
	}

	public function clearReactions($term_id, $taxonomy, $user_id = 0){
		return $this->wpdb->delete(
			$this->table,
			[
				"term_id" => $term_id,
				"taxonomy" => $taxonomy,
				"user_id" => $user_id,
			],
			["%d", "%s", "%d"]
		);
	}

	/**
	 * @param int $term_id
	 *
	 * @return TermReaction[]
	 */
	public function getReactions($term_id): array {
		$table = $this->table;
		$sql = $this->wpdb->prepare(
			"SELECT term_id, taxonomy, reaction, sum(reactions_count) as count
FROM $table WHERE term_id = %d GROUP BY term_id, taxonomy, reaction",
			$term_id,
		);
		$result = $this->wpdb->get_results($sql);

		return array_map(function($row){
			return TermReaction::build(
				intval($row->term_id),
				$row->taxonomy,
				$row->reaction,
				intval($row->count),
				0
			);
		}, $result);
	}

	/**
	 * create database tables
	 */
	public function createTables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$termTable = $this->wpdb->terms;
		$table = $this->table;

		dbDelta( "CREATE TABLE IF NOT EXISTS {$table} (
			 term_id bigint(20) unsigned not null,
    		 taxonomy varchar(100) not null,
			 user_id bigint(20) unsigned default 0,
			 reaction varchar(80) not null,
    		 reactions_count int(11) default 1,
			 PRIMARY KEY (`term_id`, `taxonomy`, `user_id`, `reaction`),
			 key (term_id),
    		 key (taxonomy),
			 key (user_id),
			 key (reaction),
    		 key (reactions_count),
			 CONSTRAINT `{$table}_to_terms` FOREIGN KEY (`term_id`) REFERENCES `{$termTable}` (`term_id`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );

	}



}
