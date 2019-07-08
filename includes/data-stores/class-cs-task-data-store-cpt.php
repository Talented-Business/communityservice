<?php
/**
 * CS_Task_Data_Store_CPT class file.
 *
 * @package CommunityService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CS Task Data Store: Stored in CPT.
 *
 * @version  1.0
 */
class CS_Task_Data_Store_CPT extends CS_Data_Store_WP implements CS_Object_Data_Store_Interface, CS_Task_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_year',
		'_thumbnail_id',
		'_task_image_gallery',
		'_edit_last',
		'_edit_lock',
	);
	protected $array_meta_keys = array('years','houses');

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 *
	 * @var bool
	 */
	protected $extra_data_saved = false;

	/**
	 * Stores updated props.
	 *
	 * @var array
	 */
	protected $updated_props = array();

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new task in the database.
	 *
	 * @param CS_Task $task Task object.
	 */
	public function create( &$task ) {
		if ( ! $task->get_date_created( 'edit' ) ) {
			$task->set_date_created( current_time( 'timestamp', true ) );
		}

		$id = wp_insert_post(
			apply_filters(
				'communityservice_new_task_data',
				array(
					'post_type'      => 'cs-task',
					'post_status'    => $task->get_status() ? $task->get_status() : 'publish',
					'post_author'    => get_current_user_id(),
					'post_title'     => $task->get_name() ? $task->get_name() : __( 'Task', 'communityservice' ),
					'post_content'   => $task->get_description(),
					'post_excerpt'   => $task->get_short_description(),
					'post_parent'    => $task->get_parent_id(),
					'comment_status' => $task->get_reviews_allowed() ? 'open' : 'closed',
					'ping_status'    => 'closed',
					'menu_order'     => $task->get_menu_order(),
					'post_date'      => gmdate( 'Y-m-d H:i:s', $task->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $task->get_date_created( 'edit' )->getTimestamp() ),
					'post_name'      => $task->get_slug( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$task->set_id( $id );

			$this->update_post_meta( $task, true );

			$task->save_meta_data();
			$task->apply_changes();

			$this->clear_caches( $task );

			do_action( 'communityservice_new_task', $id );
		}
	}

	/**
	 * Method to read a task from the database.
	 *
	 * @param CS_Task $task Task object.
	 * @throws Exception If invalid task.
	 */
	public function read( &$task ) {
		$task->set_defaults();
		$post_object = get_post( $task->get_id() );

		if ( ! $task->get_id() || ! $post_object || 'cs-task' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid task.', 'communityservice' ) );
		}

		$task->set_props(
			array(
				'name'              => $post_object->post_title,
				'slug'              => $post_object->post_name,
				'date_created'      => 0 < $post_object->post_date_gmt ? cs_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified'     => 0 < $post_object->post_modified_gmt ? cs_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'status'            => $post_object->post_status,
				'description'       => $post_object->post_content,
				'short_description' => $post_object->post_excerpt,
				'parent_id'         => $post_object->post_parent,
				'menu_order'        => $post_object->menu_order,
//				'reviews_allowed'   => 'open' === $post_object->comment_status,
			)
		);

		$this->read_task_data( $task );
		$this->read_extra_data( $task );
		$task->set_object_read( true );
	}

	/**
	 * Method to update a task in the database.
	 *
	 * @param CS_Task $task Task object.
	 */
	public function update( &$task ) {
		$task->save_meta_data();
		$changes = $task->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'description', 'short_description', 'name', 'parent_id', 'reviews_allowed', 'status', 'menu_order', 'date_created', 'date_modified', 'slug' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_content'   => $task->get_description( 'edit' ),
				'post_excerpt'   => $task->get_short_description( 'edit' ),
				'post_title'     => $task->get_name( 'edit' ),
				'post_parent'    => $task->get_parent_id( 'edit' ),
				'comment_status' => $task->get_reviews_allowed( 'edit' ) ? 'open' : 'closed',
				'post_status'    => $task->get_status( 'edit' ) ? $task->get_status( 'edit' ) : 'publish',
				'menu_order'     => $task->get_menu_order( 'edit' ),
				'post_name'      => $task->get_slug( 'edit' ),
				'post_type'      => 'task',
			);
			if ( $task->get_date_created( 'edit' ) ) {
				$post_data['post_date']     = gmdate( 'Y-m-d H:i:s', $task->get_date_created( 'edit' )->getOffsetTimestamp() );
				$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $task->get_date_created( 'edit' )->getTimestamp() );
			}
			if ( isset( $changes['date_modified'] ) && $task->get_date_modified( 'edit' ) ) {
				$post_data['post_modified']     = gmdate( 'Y-m-d H:i:s', $task->get_date_modified( 'edit' )->getOffsetTimestamp() );
				$post_data['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s', $task->get_date_modified( 'edit' )->getTimestamp() );
			} else {
				$post_data['post_modified']     = current_time( 'mysql' );
				$post_data['post_modified_gmt'] = current_time( 'mysql', 1 );
			}

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $task->get_id() ) );
				clean_post_cache( $task->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $task->get_id() ), $post_data ) );
			}
			$task->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.

		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				array(
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', 1 ),
				),
				array(
					'ID' => $task->get_id(),
				)
			);
			clean_post_cache( $task->get_id() );
		}

		$this->update_post_meta( $task );

		$task->apply_changes();

		$this->clear_caches( $task );

		do_action( 'communityservice_update_task', $task->get_id() );
	}

	/**
	 * Method to delete a task from the database.
	 *
	 * @param CS_Task $task Task object.
	 * @param array      $args Array of args to pass to the delete method.
	 */
	public function delete( &$task, $args = array() ) {
		$id        = $task->get_id();
		$post_type = $task->is_type( 'variation' ) ? 'task_variation' : 'task';

		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			do_action( 'communityservice_before_delete_' . $post_type, $id );
			wp_delete_post( $id );
			$task->set_id( 0 );
			do_action( 'communityservice_delete_' . $post_type, $id );
		} else {
			wp_trash_post( $id );
			$task->set_status( 'trash' );
			do_action( 'communityservice_trash_' . $post_type, $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read task data. Can be overridden by child classes to load other props.
	 *
	 * @param CS_Task $task Task object.
	 * @since 1.0
	 */
	protected function read_task_data( &$task ) {
		$id             = $task->get_id();
		$years = get_post_meta($id,'years',true);
		$houses = get_post_meta($id,'houses',true);
		if(!is_array($years))$years = array();
		if(!is_array($houses))$houses = array();
		$task->set_props(
			array(
				'category_ids'       => $this->get_term_ids( $task, 'task_cat' ),
				'tag_ids'            => $this->get_term_ids( $task, 'task_tag' ),
				'image_id'           => get_post_thumbnail_id( $id ),
				'duties'             => get_post_meta($id,'duties',true),
				'years'              => $years,
				'houses'              => $houses,
			)
		);

	}

	/**
	 * Read extra data associated with the task, like button text or task URL for external tasks.
	 *
	 * @param CS_Task $task Task object.
	 * @since 1.0
	 */
	protected function read_extra_data( &$task ) {
		foreach ( $task->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $task, $function ) ) ) {
				$task->{$function}( get_post_meta( $task->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Convert visibility terms to props.
	 * Catalog visibility valid values are 'visible', 'catalog', 'search', and 'hidden'.
	 *
	 * @param CS_Task $task Task object.
	 * @since 1.0
	 */
	protected function read_visibility( &$task ) {
		$terms           = get_the_terms( $task->get_id(), 'task_visibility' );
		$term_names      = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
		$featured        = in_array( 'featured', $term_names, true );

		$task->set_props(
			array(
				'featured'           => $featured,
			)
		);
	}

	/**
	 * Read attributes from post meta.
	 *
	 * @param CS_Task $task Task object.
	 */
	protected function read_attributes( &$task ) {
		$meta_attributes = get_post_meta( $task->get_id(), '_task_attributes', true );

		if ( ! empty( $meta_attributes ) && is_array( $meta_attributes ) ) {
			$attributes = array();
			foreach ( $meta_attributes as $meta_attribute_key => $meta_attribute_value ) {
				$meta_value = array_merge(
					array(
						'name'         => '',
						'value'        => '',
						'position'     => 0,
					),
					(array) $meta_attribute_value
				);

				// Check if is a taxonomy attribute.
				if ( ! empty( $meta_value['is_taxonomy'] ) ) {
					if ( ! taxonomy_exists( $meta_value['name'] ) ) {
						continue;
					}
					$id      = cs_attribute_taxonomy_id_by_name( $meta_value['name'] );
					$options = cs_get_object_terms( $task->get_id(), $meta_value['name'], 'term_id' );
				} else {
					$id      = 0;
					$options = cs_get_text_attributes( $meta_value['value'] );
				}

				$attribute = new CS_Task_Attribute();
				$attribute->set_id( $id );
				$attribute->set_name( $meta_value['name'] );
				$attribute->set_options( $options );
				$attribute->set_position( $meta_value['position'] );
				$attributes[] = $attribute;
			}
			$task->set_attributes( $attributes );
		}
	}

	/**
	 * Helper method that updates all the post meta for a task based on it's settings in the CS_Task class.
	 *
	 * @param CS_Task $task Task object.
	 * @param bool       $force Force update. Used during create.
	 * @since 1.0
	 */
	protected function update_post_meta( &$task, $force = false ) {
		$meta_key_to_props = array(
			'_task_image_gallery' => 'gallery_image_ids',
			'_thumbnail_id'          => 'image_id',
			'duties'=>'duties',
			'years'=>'years',
			'houses'=>'houses',
		);

		// Make sure to take extra data (like task url or text for external tasks) into account.
		$extra_data_keys = $task->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $task, $meta_key_to_props );
		
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $task->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			switch ( $prop ) {
				case 'gallery_image_ids':
					$updated = update_post_meta( $task->get_id(), $meta_key, implode( ',', $value ) );
					break;
				case 'image_id':
					if ( ! empty( $value ) ) {
						set_post_thumbnail( $task->get_id(), $value );
					} else {
						delete_post_meta( $task->get_id(), '_thumbnail_id' );
					}
					$updated = true;
					break;
				default:
					$updated = update_post_meta( $task->get_id(), $meta_key, $value );
					break;
			}
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		// Update extra data associated with the task like button text or task URL for external tasks.
		if ( ! $this->extra_data_saved ) {
			foreach ( $extra_data_keys as $key ) {
				if ( ! array_key_exists( '_' . $key, $props_to_update ) ) {
					continue;
				}
				$function = 'get_' . $key;
				if ( is_callable( array( $task, $function ) ) ) {
					$value = $task->{$function}( 'edit' );
					$value = is_string( $value ) ? wp_slash( $value ) : $value;

					if ( update_post_meta( $task->get_id(), '_' . $key, $value ) ) {
						$this->updated_props[] = $key;
					}
				}
			}
		}

	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @param CS_Task $task Task object.
	 * @param bool       $force Force update. Used during create.
	 * @since 1.0
	 */
	protected function update_terms( &$task, $force = false ) {
		$changes = $task->get_changes();

		if ( $force || array_key_exists( 'category_ids', $changes ) ) {
			$categories = $task->get_category_ids( 'edit' );

			if ( empty( $categories ) && get_option( 'default_task_cat', 0 ) ) {
				$categories = array( get_option( 'default_task_cat', 0 ) );
			}

			wp_set_post_terms( $task->get_id(), $categories, 'task_cat', false );
		}
		if ( $force || array_key_exists( 'tag_ids', $changes ) ) {
			wp_set_post_terms( $task->get_id(), $task->get_tag_ids( 'edit' ), 'task_tag', false );
		}
	}

	/**
	 * Update visibility terms based on props.
	 *
	 * @since 1.0
	 *
	 * @param CS_Task $task Task object.
	 * @param bool       $force Force update. Used during create.
	 */
	protected function update_visibility( &$task, $force = false ) {
		$changes = $task->get_changes();

		if ( $force || array_intersect( array( 'featured'), array_keys( $changes ) ) ) {
			$terms = array();

			if ( $task->get_featured() ) {
				$terms[] = 'featured';
			}
		}
	}

	/**
	 * Update attributes which are a mix of terms and meta data.
	 *
	 * @param CS_Task $task Task object.
	 * @param bool       $force Force update. Used during create.
	 * @since 1.0
	 */
	protected function update_attributes( &$task, $force = false ) {
		$changes = $task->get_changes();

		if ( $force || array_key_exists( 'attributes', $changes ) ) {
			$attributes  = $task->get_attributes();
			$meta_values = array();

			if ( $attributes ) {
				foreach ( $attributes as $attribute_key => $attribute ) {
					$value = '';

					delete_transient( 'cs_layered_nav_counts_' . $attribute_key );

					if ( is_null( $attribute ) ) {
						if ( taxonomy_exists( $attribute_key ) ) {
							// Handle attributes that have been unset.
							wp_set_object_terms( $task->get_id(), array(), $attribute_key );
						}
						continue;

					} elseif ( $attribute->is_taxonomy() ) {
						wp_set_object_terms( $task->get_id(), wp_list_pluck( (array) $attribute->get_terms(), 'term_id' ), $attribute->get_name() );
					} else {
						$value = cs_implode_text_attributes( $attribute->get_options() );
					}

					// Store in format CS uses in meta.
					$meta_values[ $attribute_key ] = array(
						'name'         => $attribute->get_name(),
						'value'        => $value,
						'position'     => $attribute->get_position(),
					);
				}
			}
			update_post_meta( $task->get_id(), '_task_attributes', $meta_values );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param CS_Task $task Task object.
	 * @since 1.0
	 */
	protected function clear_caches( &$task ) {
		cs_delete_task_transients( $task->get_id() );
		//CS_Cache_Helper::incr_cache_prefix( 'task_' . $task->get_id() );
	}

	/**
	 * Returns a list of task IDs ( id as key => parent as value) that are
	 * featured. Uses get_posts instead of cs_get_tasks since we want
	 * some extra meta queries and ALL tasks (posts_per_page = -1).
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_featured_task_ids() {
		$task_visibility_term_ids = cs_get_task_visibility_term_ids();

		return get_posts(
			array(
				'post_type'      => array( 'cs-task'),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'relation' => 'AND',
					array(
						'taxonomy' => 'task_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array( $task_visibility_term_ids['featured'] ),
					),
					array(
						'taxonomy' => 'task_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array( $task_visibility_term_ids['exclude-from-catalog'] ),
						'operator' => 'NOT IN',
					),
				),
				'fields'         => 'id=>parent',
			)
		);
	}

	/**
	 * Return a list of related tasks (using data like categories and IDs).
	 *
	 * @since 1.0
	 * @param array $cats_array  List of categories IDs.
	 * @param array $tags_array  List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit       Limit of results.
	 * @param int   $task_id  Task ID.
	 * @return array
	 */
	public function get_related_tasks( $cats_array, $tags_array, $exclude_ids, $limit, $task_id ) {
		global $wpdb;

		$args = array(
			'categories'  => $cats_array,
			'tags'        => $tags_array,
			'exclude_ids' => $exclude_ids,
			'limit'       => $limit + 10,
		);

		$related_task_query = (array) apply_filters( 'communityservice_task_related_posts_query', $this->get_related_tasks_query( $cats_array, $tags_array, $exclude_ids, $limit + 10 ), $task_id, $args );

		// phpcs:ignore WordPress.VIP.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col( implode( ' ', $related_task_query ) );
	}

	/**
	 * Builds the related posts query.
	 *
	 * @since 1.0
	 *
	 * @param array $cats_array  List of categories IDs.
	 * @param array $tags_array  List of tags IDs.
	 * @param array $exclude_ids Excluded IDs.
	 * @param int   $limit       Limit of results.
	 *
	 * @return array
	 */
	public function get_related_tasks_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		global $wpdb;

		$include_term_ids            = array_merge( $cats_array, $tags_array );
		$exclude_term_ids            = array();
		$task_visibility_term_ids = cs_get_task_visibility_term_ids();

		if ( $task_visibility_term_ids['exclude-from-catalog'] ) {
			$exclude_term_ids[] = $task_visibility_term_ids['exclude-from-catalog'];
		}

		if ( 'yes' === get_option( 'communityservice_hide_out_of_stock_items' ) && $task_visibility_term_ids['outofstock'] ) {
			$exclude_term_ids[] = $task_visibility_term_ids['outofstock'];
		}

		$query = array(
			'fields' => "
				SELECT DISTINCT ID FROM {$wpdb->posts} p
			",
			'join'   => '',
			'where'  => "
				WHERE 1=1
				AND p.post_status = 'publish'
				AND p.post_type = 'task'

			",
			'limits' => '
				LIMIT ' . absint( $limit ) . '
			',
		);

		if ( count( $exclude_term_ids ) ) {
			$query['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $exclude_term_ids ) ) . ' ) ) AS exclude_join ON exclude_join.object_id = p.ID';
			$query['where'] .= ' AND exclude_join.object_id IS NULL';
		}

		if ( count( $include_term_ids ) ) {
			$query['join'] .= " INNER JOIN ( SELECT object_id FROM {$wpdb->term_relationships} INNER JOIN {$wpdb->term_taxonomy} using( term_taxonomy_id ) WHERE term_id IN ( " . implode( ',', array_map( 'absint', $include_term_ids ) ) . ' ) ) AS include_join ON include_join.object_id = p.ID';
		}

		if ( count( $exclude_ids ) ) {
			$query['where'] .= ' AND p.ID NOT IN ( ' . implode( ',', array_map( 'absint', $exclude_ids ) ) . ' )';
		}

		return $query;
	}

	/**
	 * Returns an array of tasks.
	 *
	 * @param  array $args Args to pass to CS_Task_Query().
	 * @return array|object
	 * @see cs_get_tasks
	 */
	public function get_tasks( $args = array() ) {
		$query = new CS_Task_Query( $args );
		return $query->get_tasks();
	}

	/**
	 * Search task data for a term and return ids.
	 *
	 * @param  string   $term Search term.
	 * @param  string   $type Type of task.
	 * @param  bool     $include_variations Include variations in search or not.
	 * @param  bool     $all_statuses Should we search all statuses or limit to published.
	 * @param  null|int $limit Limit returned results. @since 3.5.0.
	 * @return array of ids
	 */
	public function search_tasks( $term, $type = '', $include_variations = false, $all_statuses = false, $limit = null ) {
		global $wpdb;

		$post_types    = array( 'task' );
		$post_statuses = current_user_can( 'edit_private_tasks' ) ? array( 'private', 'publish' ) : array( 'publish' );
		$type_join     = '';
		$type_where    = '';
		$status_where  = '';
		$limit_query   = '';
		$term          = cs_strtolower( $term );

		// See if search term contains OR keywords.
		if ( strstr( $term, ' or ' ) ) {
			$term_groups = explode( ' or ', $term );
		} else {
			$term_groups = array( $term );
		}

		$search_where   = '';
		$search_queries = array();

		foreach ( $term_groups as $term_group ) {
			// Parse search terms.
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $term_group, $matches ) ) {
				$search_terms = $this->get_valid_search_terms( $matches[0] );
				$count        = count( $search_terms );

				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
				if ( 9 < $count || 0 === $count ) {
					$search_terms = array( $term_group );
				}
			} else {
				$search_terms = array( $term_group );
			}

			$term_group_query = '';
			$searchand        = '';

			foreach ( $search_terms as $search_term ) {
				$like              = '%' . $wpdb->esc_like( $search_term ) . '%';
				$term_group_query .= $wpdb->prepare( " {$searchand} ( ( posts.post_title LIKE %s) OR ( posts.post_excerpt LIKE %s) OR ( posts.post_content LIKE %s ) OR ( postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s ) )", $like, $like, $like, $like ); // @codingStandardsIgnoreLine.
				$searchand         = ' AND ';
			}

			if ( $term_group_query ) {
				$search_queries[] = $term_group_query;
			}
		}

		if ( ! empty( $search_queries ) ) {
			$search_where = 'AND (' . implode( ') OR (', $search_queries ) . ')';
		}

		if ( $type && in_array( $type, array( 'virtual', 'downloadable' ), true ) ) {
			$type_join  = " LEFT JOIN {$wpdb->postmeta} postmeta_type ON posts.ID = postmeta_type.post_id ";
			$type_where = " AND ( postmeta_type.meta_key = '_{$type}' AND postmeta_type.meta_value = 'yes' ) ";
		}

		if ( ! $all_statuses ) {
			$status_where = " AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "') ";
		}

		if ( $limit ) {
			$limit_query = $wpdb->prepare( ' LIMIT %d ', $limit );
		}

		// phpcs:ignore WordPress.VIP.DirectDatabaseQuery.DirectQuery
		$search_results = $wpdb->get_results(
			// phpcs:disable
			"SELECT DISTINCT posts.ID as task_id, posts.post_parent as parent_id FROM {$wpdb->posts} posts
			LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
			$type_join
			WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')
			$search_where
			$status_where
			$type_where
			ORDER BY posts.post_parent ASC, posts.post_title ASC
			$limit_query
			"
			// phpcs:enable
		);

		$task_ids = wp_parse_id_list( array_merge( wp_list_pluck( $search_results, 'task_id' ), wp_list_pluck( $search_results, 'parent_id' ) ) );

		if ( is_numeric( $term ) ) {
			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'task_variation' === $post_type && $include_variations ) {
				$task_ids[] = $post_id;
			} elseif ( 'task' === $post_type ) {
				$task_ids[] = $post_id;
			}

			$task_ids[] = wp_get_post_parent_id( $post_id );
		}

		return wp_parse_id_list( $task_ids );
	}

	/**
	 * Get the task type based on task ID.
	 *
	 * @since 1.0
	 * @param int $task_id Task ID.
	 * @return bool|string
	 */
	public function get_task_type( $task_id ) {
		$post_type = get_post_type( $task_id );
		if ( 'task_variation' === $post_type ) {
			return 'variation';
		} elseif ( 'cs-task' === $post_type ) {
			return 'task';
		} else {
			return false;
		}
	}

	/**
	 * Add ability to get tasks by 'reviews_allowed' in CS_Task_Query.
	 *
	 * @since 3.2.0
	 * @param string   $where Where clause.
	 * @param WP_Query $wp_query WP_Query instance.
	 * @return string
	 */
	public function reviews_allowed_query_where( $where, $wp_query ) {
		global $wpdb;

		if ( isset( $wp_query->query_vars['reviews_allowed'] ) && is_bool( $wp_query->query_vars['reviews_allowed'] ) ) {
			if ( $wp_query->query_vars['reviews_allowed'] ) {
				$where .= " AND $wpdb->posts.comment_status = 'open'";
			} else {
				$where .= " AND $wpdb->posts.comment_status = 'closed'";
			}
		}

		return $where;
	}

	/**
	 * Get valid WP_Query args from a CS_Task_Query's query variables.
	 *
	 * @since 3.2.0
	 * @param array $query_vars Query vars from a CS_Task_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {

		// Map query vars to ones that get_wp_query_args or WP_Query recognize.
		$key_mapping = array(
//			'status'         => 'post_status',
			'page'           => 'paged',
			'include'        => 'post__in',
		);
		foreach ( $key_mapping as $query_key => $db_key ) {
			if ( isset( $query_vars[ $query_key ] ) ) {
				$query_vars[ $db_key ] = $query_vars[ $query_key ];
				unset( $query_vars[ $query_key ] );
			}
		}

		// These queries cannot be auto-generated so we have to remove them and build them manually.

		$wp_query_args = parent::get_wp_query_args( $query_vars );
		
		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$wp_query_args['post_type']   = 'cs-task';

		// Handle task categories.
		if ( ! empty( $query_vars['category'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'task_cat',
				'field'    => 'slug',
				'terms'    => $query_vars['category'],
			);
		}

		// Handle task tags.
		if ( ! empty( $query_vars['tag'] ) ) {
			unset( $wp_query_args['tag'] );
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'task_tag',
				'field'    => 'slug',
				'terms'    => $query_vars['tag'],
			);
		}

		// Handle date queries.
		$date_queries = array(
			'date_created'      => 'post_date',
			'date_modified'     => 'post_modified',
		);
		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				foreach ( $existing_queries as $query_index => $query_contents ) {
					unset( $wp_query_args['meta_query'][ $query_index ] );
				}

				$wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
			}
		}

		// Handle paginate.
		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		// Handle reviews_allowed.
		if ( isset( $query_vars['reviews_allowed'] ) && is_bool( $query_vars['reviews_allowed'] ) ) {
			add_filter( 'posts_where', array( $this, 'reviews_allowed_query_where' ), 10, 2 );
		}

		return apply_filters( 'communityservice_task_data_store_cpt_get_tasks_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Query for Tasks matching specific criteria.
	 *
	 * @since 3.2.0
	 *
	 * @param array $query_vars Query vars from a CS_Task_Query.
	 *
	 * @return array|object
	 */
	public function query( $query_vars ) {
		$args = $this->get_wp_query_args( $query_vars );

		if ( ! empty( $args['errors'] ) ) {
			$query = (object) array(
				'posts'         => array(),
				'found_posts'   => 0,
				'max_num_pages' => 0,
			);
		} else {
			$query = new WP_Query( $args );
		}

		if ( isset( $query_vars['return'] ) && 'objects' === $query_vars['return'] && ! empty( $query->posts ) ) {
			// Prime caches before grabbing objects.
			update_post_caches( $query->posts, array( 'task', 'task_variation' ) );
		}

		$tasks = ( isset( $query_vars['return'] ) && 'ids' === $query_vars['return'] ) ? $query->posts : array_filter( array_map( 'cs_get_task', $query->posts ) );

		if ( isset( $query_vars['paginate'] ) && $query_vars['paginate'] ) {
			return (object) array(
				'tasks'      => $tasks,
				'total'         => $query->found_posts,
				'max_num_pages' => $query->max_num_pages,
			);
		}

		return $tasks;
	}
}
