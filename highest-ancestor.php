<?php
namespace CNP;
/**
 * get_highest_ancestor
 *
 * Gets information about the most distant ancestor of the current post.
 * Useful when displaying section information.
 *
 * Current page and expected behavior:
 * hierarchical post type highest ancestor if nested, current page if top-level
 * flat post type         check for page with post type name, otherwise return post type information
 * front page             page_on_front information
 * home page              page_for_posts information
 * taxonomy archive       not accessible (e.g., /genres results in a 404, /genres/mystery-novels gives results)
 * taxonomy term archive  taxonomy term name (e.g., "Mystery Novels")
 * search results         check for page with slug of "search", otherwise return standard search results information
 * 404 page               check for page with slug of "404", otherwise return standard 404 information
 *
 * DOES NOT SUPPORT: Date archives, Author pages, or Attachment pages.
 *
 * NOTE: You can modify the output of this function by
 * adding a filter to "cnp_highest_ancestor."
 *
 * Note to self-- we could have a way to register different
 * titles, like you would register labels for a post type.
 *
 * @see get_post_ancestors()
 * @link https://codex.wordpress.org/Function_Reference/get_post_ancestors
 * @global object $post The post object, if an ID is not supplied.
 *
 * @param int $id Post ID, if you need to get the ancestor manually.
 * @param array $args {
 *      Array of arguments. Optional.
 *
 *        $check_for_page   Whether to check for a page with a matching slug
 *                          when on a posttype/taxonomy archive/single. Will also
 *                          perform the check for pages with a slug of 'search' and '404'.
 *                          Default 'true'.
 *        $post_type_pages  Pages to check for post types. Includes defaults for Search and 404.
 * }
 *
 * @return array  $ancestor {
 *      Ancestor array. Some values are separated out of the post/term object,
 *      because $post->post_title is different than $term->name
 *
 *        int    $id           Ancestor ID
 *        string $title        Ancestor title
 *        string $name         Ancestor slug
 *        object $object       Ancestor post/term object
 *        int    $found_posts  Number of posts found, if this is a search results page.
 *
 * }
 */
function get_highest_ancestor( $id = '', $args = array() ) {

	// ID setup
	global $post;
	if ( '' === $id && ! empty( $post ) ) {
		$id = $post->ID;
	}

	// Resolve Args
	$defaults = array(
		'check_for_page'  => true,
		'post_type_pages' => array(
			'search' => 'search',
			'404'    => '404',
		),
	);

	$vars = wp_parse_args( $args, $defaults );

	$ancestor = array(
		'id'     => 0,
		'title'  => '',
		'name'   => '',
		'object' => false,
	);

	/*——————————————————————————————————————————————————————————
	/  Determine Ancestor Type
	——————————————————————————————————————————————————————————*/

	$ancestor_type = '';

	$post_type = get_post_type();

	if ( false !== $post_type ) {

		if ( is_post_type_hierarchical( $post_type ) ) {
			$ancestor_type = 'hierarchical_post_type';
		} else {
			$ancestor_type = 'flat_post_type';
		}
	}

	// Chain the home checks so that we're sure we've got the right one.
	if ( is_front_page() && is_home() ) {
		$ancestor_type = 'default_home';
	} elseif ( is_front_page() ) {
		$ancestor_type = 'page_on_front';
	} elseif ( is_home() || is_singular( 'post' ) ) {
		$ancestor_type = 'page_for_posts';
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$ancestor_type = 'term';
	}

	if ( is_search() ) {
		$ancestor_type = 'search';
	}

	if ( is_404() ) {
		$ancestor_type = '404';
	}

	/*——————————————————————————————————————————————————————————
	/  Determine Ancestor Information
	——————————————————————————————————————————————————————————*/

	// There could be an Ancestor ID for any $ancestor_type,
	// if there is a page for the ancestor.

	switch ( $ancestor_type ) {

		/*——————————————————————————————————————————————————————————————————————————————
		/  Posts
		——————————————————————————————————————————————————————————————————————————————*/

		// Hierarchical post types return ancestor information.
		case 'hierarchical_post_type':

			$ancestor_ids_arr = get_post_ancestors( $post );

			// The highest level ancestor is returned as the last
			// value in the array, so we take that with array_pop.
			if ( ! empty( $ancestor_ids_arr ) ) {

				$ancestor_id       = array_pop( $ancestor_ids_arr );
				$ancestor_post_obj = get_post( $ancestor_id );

				$ancestor = array(
					'id'     => $ancestor_post_obj->ID,
					'title'  => $ancestor_post_obj->post_title,
					'name'   => $ancestor_post_obj->post_name,
					'object' => $ancestor_post_obj,
				);

			} else {

				$ancestor = array(
					'id'     => $post->ID,
					'title'  => $post->post_title,
					'name'   => $post->post_name,
					'object' => $post,
				);
			}

			break;

		// Flat post types return either a post type page, or the post type object.
		case 'flat_post_type':

			// Check for a post type page.
			$post_type_page_obj = array();
			if ( true === $vars['check_for_page'] ) {

				$post_type_page_obj = get_page_by_path( apply_filters( 'cnp_highest_ancestor_page_path', $post_type ) );

			}

			if ( ! empty( $post_type_page_obj ) ) {

				$ancestor = array(
					'id'     => $post_type_page_obj->ID,
					'title'  => $post_type_page_obj->post_title,
					'name'   => $post_type_page_obj->post_name,
					'object' => $post_type_page_obj,
				);

			} // If a post type page doesn't exist, return the post type object.
			else {

				$post_type_obj = get_post_type_object( $post_type );

				$ancestor = array(
					'id'     => 0,
					'title'  => $post_type_obj->label,
					'name'   => $post_type_obj->name,
					'object' => $post_type_obj,
				);

			}

			break;

		/*——————————————————————————————————————————————————————————————————————————————
		/  Home Page Scenarios
		——————————————————————————————————————————————————————————————————————————————*/

		// Default home setup (no front page set) returns basic home information
		case 'default_home':

			$ancestor = array(
				'id'     => 0,
				'title'  => 'home',
				'name'   => 'Home',
				'object' => false,
			);

			break;

		// Static home page includes front page post object
		case 'page_on_front':

			$front_page = get_post( get_option( 'page_on_front' ) );
			$ancestor   = array(
				'id'     => $front_page->ID,
				'title'  => $front_page->post_title,
				'name'   => $front_page->post_name,
				'object' => $front_page,
			);

			break;

		// Blog home page include page for posts object
		case 'page_for_posts':

			$page_for_posts = get_post( get_option( 'page_for_posts' ) );
			$ancestor       = array(
				'id'     => $page_for_posts->ID,
				'title'  => $page_for_posts->post_title,
				'name'   => $page_for_posts->post_name,
				'object' => $page_for_posts,
			);

			break;

		/*——————————————————————————————————————————————————————————————————————————————
		/  Taxonomy Term
		——————————————————————————————————————————————————————————————————————————————*/

		case 'term':

			$term     = get_queried_object();
			$ancestor = array(
				'id'     => $term->term_id,
				'title'  => $term->name,
				'name'   => $term->slug,
				'object' => $term,
			);

			break;

		/*——————————————————————————————————————————————————————————————————————————————
		/  Search Results
		——————————————————————————————————————————————————————————————————————————————*/

		case 'search':

			// Check for search page first.
			$search_page_obj = array();
			if ( true === $vars['check_for_page'] ) {

				$search_page_obj = get_page_by_path( $vars['post_type_pages']['search'] );

			}

			if ( ! empty( $search_page_obj ) ) {

				$ancestor = array(
					'id'     => $search_page_obj->ID,
					'title'  => $search_page_obj->post_title,
					'name'   => $search_page_obj->post_name,
					'object' => $search_page_obj,
				);

			} /// Return basic search information if page is not found.
			else {

				$ancestor = array(
					'id'     => 0,
					'title'  => 'Search Results',
					'name'   => 'search',
					'object' => false,
				);

			}

			// Add number of found posts
			global $wp_query;
			$ancestor['found_posts'] = $wp_query->found_posts;

			break;

		/*——————————————————————————————————————————————————————————————————————————————
		/  404 Page
		——————————————————————————————————————————————————————————————————————————————*/

		case '404':

			// Check for 404 page first
			$page_404_obj = array();
			if ( true === $vars['check_for_page'] ) {

				$page_404_obj = get_page_by_path( $vars['post_type_pages']['404'] );

			}

			if ( ! empty( $page_404_obj ) ) {

				$ancestor = array(
					'id'     => $page_404_obj->ID,
					'title'  => $page_404_obj->post_title,
					'name'   => $page_404_obj->post_name,
					'object' => $page_404_obj,
				);

			} // Return basic 404 information if page is not found.
			else {

				$ancestor = array(
					'id'     => 0,
					'title'  => 'Page Not Found',
					'name'   => '404',
					'object' => false,
				);

			}

			break;

	}

	/*——————————————————————————————————————————————————————————
	/  Return Ancestor
	——————————————————————————————————————————————————————————*/

	// Filter check
	$ancestor = apply_filters( 'cnp_get_highest_ancestor', $ancestor );

	return $ancestor;

}
