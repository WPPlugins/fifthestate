<?php
namespace FifthEstate;

require_once 'utilities.php';

function post_json_to_url( $post, $reason, $app_state ) {
    //get data
    $post_id = $post->ID;

    $title = apply_filters( 'the_title', $post->post_title );
    $content = apply_filters( 'the_content', $post->post_content );
    $slug = $post->post_name;
    $excerpt  = apply_filters( 'the_excerpt', $post->post_excerpt );
    $status = $post->post_status;
    $comment_status = $post->comment_status;
    $comment_count = $post->comment_count;
    $comments = get_comments( array( 'post_id' => $post_id ) );
    $menu_order = $post->menu_order;
    $ping_status = $post->ping_status;
    $password = $post->post_password;
    $parent_id = $post->post_parent;
    $date = mysql2date( 'c', $post->post_date );
    $date_gmt = mysql2date( 'c', $post->post_date_gmt );
    $modified = mysql2date( 'c', $post->post_modified );
    $modified_gmt = mysql2date( 'c', $post->post_modified_gmt );
    $author_id = $post->post_author;
    $author = get_userdata( $author_id );
    $permalink = get_permalink( $post_id );
    $tags = get_the_tags( $post_id );
    $site_url = get_site_url();
    $blog_id = get_current_blog_id();
    $categories = get_the_category( $post_id );
    $children = get_children( array( 'post_parent' => $post_id ) );
    $thumbnail_url = get_the_post_thumbnail_url( $post = $post_id );
    $format = get_post_format() ? : 'standard';
    $edit_post_link = get_edit_post_link( $post_id );
    $delete_post_link = get_delete_post_link( $post_id );
    $is_sticky = is_sticky( $post_id );
    $has_post_thumbnail = has_post_thumbnail( $post_id );
    $has_excerpt = has_excerpt( $post_id );
    $has_post_format = has_post_format( $post_id );
    $email = $app_state['email'];
    $category = $app_state['category'];

    //set up JSON object
    $obj = new \stdClass();
    $obj->site_url = $site_url;
    $obj->blog_id = $blog_id;
    $obj->reason = $reason;
    $obj->id = $post_id;
    $obj->title = $title;
    $obj->content = $content;
    $obj->slug = $slug;
    $obj->excerpt = $excerpt;
    $obj->status = $status;
    $obj->comment_status = $comment_status;
    $obj->comment_count = $comment_count;
    $obj->comments = $comments;
    $obj->menu_order = $menu_order;
    $obj->ping_status = $ping_status;
    $obj->password = $password;
    $obj->parent_id = $parent_id;
    $obj->date = $date;
    $obj->date_gmt = $date_gmt;
    $obj->modified = $modified;
    $obj->modified_gmt = $modified_gmt;
    $obj->author = $author;
    $obj->permalink = $permalink;
    $obj->tags = $tags;
    $obj->categories = $categories;
    $obj->children = $children;
    $obj->thumbnail_url = $thumbnail_url;
    $obj->format = $format;
    $obj->edit_post_link = $edit_post_link;
    $obj->delete_post_link = $delete_post_link;
    $obj->is_sticky = $is_sticky;
    $obj->has_post_thumbnail = $has_post_thumbnail;
    $obj->has_excerpt = $has_excerpt;
    $obj->has_post_format = $has_post_format;
    $obj->email = $email;
    $obj->category = $category;
    $json = json_encode( $obj );

    //POST JSON object to a URL
    $authorization_header = 'Authorization: Bearer ' . $app_state['token'];
    curl_post( API_BASE_URL . '/wordpress_plugin_handler',
                 $json,
                 array( 'Content-Type: application/json', $authorization_header ) );
}

function post_updated_notification( $post ) {
    $app_state = get_option( 'fifthestate' );
    if ( $app_state['logged_in'] ) {
        if ( 'post' === $post->post_type ) {
            post_json_to_url( $post, 'Updated', $app_state );
        }
    }
}

function post_published_notification( $post ) {
    $app_state = get_option( 'fifthestate' );
    if ( $app_state['logged_in'] ) {
        if ( 'post' === $post->post_type ) {
            post_json_to_url( $post, 'Published', $app_state );
        }
    }
}

function post_deleted_notification( $post ){
    $app_state = get_option( 'fifthestate' );
    if ( $app_state['logged_in'] ) {
        if ( 'post' === $post->post_type ) {
            post_json_to_url( $post, 'Deleted', $app_state );
        }
    }
}

//a post is updated
add_action( 'publish_to_publish', __NAMESPACE__ . '\\' . 'post_updated_notification' );

//a post is published
add_action( 'new_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );
add_action( 'trash_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );
add_action( 'draft_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );
add_action( 'pending_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );
add_action( 'private_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );
add_action( 'future_to_publish', __NAMESPACE__ . '\\' . 'post_published_notification' );

//a post is 'deleted' (at least in so far as we are concerned)
add_action( 'publish_to_trash', __NAMESPACE__ . '\\' . 'post_deleted_notification' );
add_action( 'publish_to_draft', __NAMESPACE__ . '\\' . 'post_deleted_notification' );
add_action( 'publish_to_pending', __NAMESPACE__ . '\\' . 'post_deleted_notification' );
add_action( 'publish_to_private', __NAMESPACE__ . '\\' . 'post_deleted_notification' );
add_action( 'publish_to_future', __NAMESPACE__ . '\\' . 'post_deleted_notification' );
