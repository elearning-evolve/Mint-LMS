<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Core REST controller (`/wp/v2/mint-*`) for Mint LMS content types.
 *
 * The types keep `show_in_rest` so the block editor works, but lesson, quiz and
 * question bodies are learner-gated content: access is decided by enrollment,
 * not by the post being published. Reads are therefore limited to users who can
 * edit the content; learners are served through the `mintlms/v1` routes only.
 */
final class ContentPostsRestController extends \WP_REST_Posts_Controller {

	/**
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$postType = get_post_type_object( $this->post_type );

		if ( ! $postType instanceof \WP_Post_Type || ! current_user_can( $postType->cap->edit_posts ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view this content.', 'mint-lms' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * A published Mint post is not public: only users who can edit it may read it here.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function check_read_permission( $post ) {
		if ( ! $post instanceof \WP_Post || ! PostTypes::isContentType( $post->post_type ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post->ID );
	}
}
