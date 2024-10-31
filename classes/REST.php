<?php

namespace Palasthotel\WordPress\Reaction;

use Palasthotel\WordPress\Reaction\Components\Component;
use Palasthotel\WordPress\Reaction\Model\CommentReactions;
use Palasthotel\WordPress\Reaction\Model\PostReaction;
use WP_Comment;
use WP_Post;
use WP_REST_Server;
use WP_Term;

class REST extends Component {

	public function getRepo(){
		return $this->getPlugin()->repo;
	}

	public function onCreate() {
		parent::onCreate();
		add_action( 'rest_api_init', [ $this, 'init' ] );
	}

	public function init() {

		$postTypes     = get_post_types( [ "public" => true ] );
		$postTypeSlugs = array_keys( $postTypes );
		$taxonomies    = get_taxonomies( [ "public" => true ] );
		$taxSlugs      = array_keys( $taxonomies );

		$objects = apply_filters(
			reaction::FILTER_REST_OBJECTS,
			array_merge(
				$postTypeSlugs,
				$taxSlugs,
				[ 'comment' ],
			)
		);

		$sanitize_intval = function($value){
			return intval($value);
		};

		// ------------------------------------------------------------------------
		// add reactions to core rest entities
		// ------------------------------------------------------------------------
		register_rest_field( $objects, 'reactions', [
			"get_callback" => function ( $arr ) {
				$reactions = [];

				if ( isset( $arr["taxonomy"] ) && isset( $arr["id"] ) ) {
					// TODO: get term reactions

				} else if ( isset( $arr["type"] ) && $arr["type"] == "comment" ) {
					$reactions = $this->getRepo()->commentReactionsDb->getReactions(
						intval( $arr["id"] )
					);
					$reactions = $this->commentReactionsToJson( $reactions );

				} else if ( isset( $arr["type"] ) && isset( $arr["id"] ) ) {
					$post_id   = intval( $arr["id"] );
					$reactions = $this->getRepo()->postReactionsDb->getReactions( $post_id );
					$reactions = $this->postReactionsToJson( $reactions );
				}

				return $reactions;
			}
		] );

		// ------------------------------------------------------------------------
		// all object base routes
		// ------------------------------------------------------------------------
		foreach ( $objects as $object ) {
			register_rest_route(
				reaction::REST_NAMESPACE,
				"/$object/(?P<id>\d+)",
				[
					[
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => '__return_true',
						'callback'            => function ( \WP_REST_Request $request ) use ( $object ) {
							$id = intval( $request->get_param( "id" ) );
							if ( post_type_exists( $object ) ) {
								$reactions = $this->getRepo()->postReactionsDb->getReactions( $id );

								return $this->postReactionsToJson( $reactions );
							} else if ( taxonomy_exists( $object ) ) {
								return [];
							}

							return $this->commentReactionsToJson(
								$this->getRepo()->commentReactionsDb->getReactions(
									$id,
									get_current_user_id()
								)
							);
						},
					],
					[
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => '__return_true',
						'callback'            => function ( \WP_REST_Request $request ) use ( $object ) {
							$id       = $request->get_param( "id" );
							$reaction = $request->get_param( "reaction" );
							$operation = $request->get_param("operation");
							$unique = $request->get_param("unique");
							$authenticated = $request->get_param("authenticated");
							$value = $operation == "increment" ? 1 : -1;
							$success = false;
							$userId = get_current_user_id();

							if ( post_type_exists( $object ) ) {
								$success = $this->getRepo()->incrementPostReaction(
									$id,
									$reaction,
									$authenticated ? $userId : 0,
									$value
								);
							} else if(taxonomy_exists( $object )){
								// TODO: taxonomy

							} else {
								$success = $this->getRepo()->setCommentReaction(
									$id,
									$reaction,
									$userId,
									$unique
								);
							}

							return [ "success" => $success ];
						},
						'args'                => [
							'id'       => [
								'validate_callback' => function ( $value, $request, $param ) use ( $object ) {
									if ( post_type_exists( $object ) ) {
										return get_post($value) instanceof WP_Post;
									} else if ( taxonomy_exists( $object ) ) {
										return get_term($value, $object) instanceof WP_Term;
									}
									return get_comment($value) instanceof WP_Comment;
								},
								'sanitize_callback' => $sanitize_intval,
							],
							'reaction' => [
								'validate_callback' => function ( $value ) {
									return ! empty( $value );
								},
								'sanitize_callback' => 'sanitize_text_field',
							],
							'operation' => [
								"type" => "string",
								"default" => "increment",
								"enum" => [ 'increment', 'decrement'],
							],
							'unique' => [
								"type" => "boolean",
								"default" => true,
							],
							'authenticated' => [
								"type" => "boolean",
								"default" => "false",
								"description" => "Weather or not to user current user",
							]
						],
					],
				]
			);
		}

		// ------------------------------------------------------------------------
		// delete comment reaction
		// ------------------------------------------------------------------------
		register_rest_route(
			reaction::REST_NAMESPACE,
			"/comment/(?P<id>\d+)",
			[
				"methods" => WP_REST_Server::DELETABLE,
				'permission_callback' => '__return_true',
				'callback'            => function ( \WP_REST_Request $request ) {
					$comment_id = $request->get_param("id");
					$reaction = $request->get_param("reaction");
					$userId = get_current_user_id();
					$success = $this->getRepo()->commentReactionsDb->unsetReaction(
						$userId,
						$comment_id,
						$reaction
					);
					return ["success" => $success ];
				},
				"args" => [
					'id'       => [
						'validate_callback' => function ( $value, $request, $param ) use ( $object ) {
							return get_comment($value) instanceof WP_Comment;
						},
						'sanitize_callback' => $sanitize_intval,
					],
					"reaction" => [
						"type" => "string",
						'sanitize_callback' => 'sanitize_text_field',
					]
				]
			]
		);

		// ------------------------------------------------------------------------
		// only for post types
		// ------------------------------------------------------------------------
		foreach ( $postTypeSlugs as $postType ) {
			register_rest_route(
				reaction::REST_NAMESPACE,
				"/$postType/(?P<id>\d+)/comments",
				[
					[
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => '__return_true',
						'callback'            => function ( \WP_REST_Request $request ) {
							$id        = intval( $request->get_param( "id" ) );
							$user_id = $request->get_param("onlyCurrentUser") ? get_current_user_id() : 0;
							$reactions = $this->getRepo()->commentReactionsDb->getReactionsByPostId( $id, $user_id );
							return array_map( function ( $reaction ) {
								return [
									"id"       => $reaction->comment_id,
									"reaction" => $reaction->reaction,
									"count"    => $reaction->count,
								];
							}, $reactions );
						},
						'args'                => [
							'id' => [
								'validate_callback' => function ( $value, $request, $param ) {
									$value = intval( $value );

									return get_post( $value ) instanceof WP_Post;
								},
							],
							'onlyCurrentUser' => [
								'type' => "boolean",
								"default" => false,
								'validate_callback' => function ( $value, $request, $param ) {
									return $value == false || get_current_user_id() > 0;
								},
								'description' => 'If true only current users reactions are returned.',
							],
						]
					],
					[
						'methods'             => WP_REST_Server::EDITABLE,
						'permission_callback' => '__return_true',
						'callback'            => function ( \WP_REST_Request $request ) {

							$id        = $request->get_param( "id" );
							$reaction  = $request->get_param( "reaction" );
							$reactions = $this->getRepo()->commentReactionsDb->getReactionsByPostId( $id );

							return array_map( function ( $reaction ) {
								return [
									"id"       => $reaction->comment_id,
									"reaction" => $reaction->reaction,
									"count"    => $reaction->count,
								];
							}, $reactions );
						},
						'args'                => [
							'id'         => [
								'validate_callback' => function ( $value, $request, $param ) {
									return get_post( $value ) instanceof WP_Post;
								},
								'sanitize_callback' => 'intval',
							],
							'comment_id' => [
								'validate_callback' => function ( $value, $request, $param ) {
									return get_comment( $value ) instanceof WP_Comment;
								},
								'sanitize_callback' => 'intval',
							],
							'reaction'   => [
								'validate_callback' => function ( $value ) {
									return ! empty( $value );
								},
								'sanitize_callback' => 'sanitize_text_field',
							],
						],
					],
				],
			);
		}
	}

	/**
	 * @param PostReaction[] $reactions
	 *
	 * @return array
	 */
	private function postReactionsToJson( array $reactions ) {
		return array_map( function ( $reaction ) {
			return [
				"reaction" => $reaction->reaction,
				"count"    => $reaction->count,
			];
		}, $reactions );
	}

	/**
	 * @param CommentReactions[] $reactions
	 *
	 * @return array
	 */
	private function commentReactionsToJson( array $reactions ) {
		return array_map( function ( $reaction ) {
			return [
				"reaction" => $reaction->reaction,
				"count"    => $reaction->count,
			];
		}, $reactions );
	}
}
