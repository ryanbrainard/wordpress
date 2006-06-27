<?php
require_once('admin.php');

$parent_file = 'edit.php';
$submenu_file = 'edit-comments.php';
$wpvarstoreset = array('action');

for ($i=0; $i<count($wpvarstoreset); $i += 1) {
	$wpvar = $wpvarstoreset[$i];
	if (!isset($$wpvar)) {
		if (empty($_POST["$wpvar"])) {
			if (empty($_GET["$wpvar"])) {
				$$wpvar = '';
			} else {
			$$wpvar = $_GET["$wpvar"];
			}
		} else {
			$$wpvar = $_POST["$wpvar"];
		}
	}
}

if ( isset( $_POST['deletecomment'] ) )
	$action = 'deletecomment';

switch($action) {
case 'editcomment':
	$title = __('Edit Comment');
	if ( user_can_richedit() )
		wp_enqueue_script( 'wp_tiny_mce' );
	require_once ('admin-header.php');

	$comment = (int) $_GET['comment'];

	if ( ! $comment = get_comment($comment) )
		die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'javascript:history.go(-1)'));

	if ( !current_user_can('edit_post', $comment->comment_post_ID) )
		die( __('You are not allowed to edit comments on this post.') );

	$comment = get_comment_to_edit($comment);

	include('edit-form-comment.php');

	break;

case 'confirmdeletecomment':
case 'mailapprovecomment':

	require_once('./admin-header.php');

	$comment = (int) $_GET['comment'];
	$p = (int) $_GET['p'];
	$formaction = 'confirmdeletecomment' == $action ? 'deletecomment' : 'approvecomment';
	$nonce_action = 'confirmdeletecomment' == $action ? 'delete-comment_' : 'approve-comment_';
	$nonce_action .= $comment;

	if ( ! $comment = get_comment($comment) )
		die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

	if ( !current_user_can('edit_post', $comment->comment_post_ID) )
		die( 'confirmdeletecomment' == $action ? __('You are not allowed to delete comments on this post.') : __('You are not allowed to edit comments on this post, so you cannot approve this comment.') );

	echo "<div class='wrap'>\n";
	if ( 'spam' == $_GET['delete_type'] )
		echo "<p>" . __('<strong>Caution:</strong> You are about to mark the following comment as spam:') . "</p>\n";
	elseif ( 'confirmdeletecomment' == $action )
		echo "<p>" . __('<strong>Caution:</strong> You are about to delete the following comment:') . "</p>\n";
	else
		echo "<p>" . __('<strong>Caution:</strong> You are about to approve the following comment:') . "</p>\n";
	echo "<table border='0'>\n";
	echo "<tr><td>" . __('Author:') . "</td><td>$comment->comment_author</td></tr>\n";
	echo "<tr><td>" . __('E-mail:') . "</td><td>$comment->comment_author_email</td></tr>\n";
	echo "<tr><td>". __('URL:') . "</td><td>$comment->comment_author_url</td></tr>\n";
	echo "<tr><td>". __('Comment:') . "</td><td>$comment->comment_content</td></tr>\n";
	echo "</table>\n";
	echo "<p>" . __('Are you sure you want to do that?') . "</p>\n";

	echo "<form action='".get_settings('siteurl')."/wp-admin/comment.php' method='get'>\n";
	wp_nonce_field($nonce_action);
	echo "<input type='hidden' name='action' value='$formaction' />\n";
	if ( 'spam' == $_GET['delete_type'] )
		echo "<input type='hidden' name='delete_type' value='spam' />\n";
	echo "<input type='hidden' name='p' value='$p' />\n";
	echo "<input type='hidden' name='comment' value='{$comment->comment_ID}' />\n";
	echo "<input type='hidden' name='noredir' value='1' />\n";
	echo "<input type='submit' value='" . __('Yes') . "' />";
	echo "&nbsp;&nbsp;";
	echo "<input type='button' value='" . __('No') . "' onclick=\"self.location='". get_settings('siteurl') ."/wp-admin/edit-comments.php';\" />\n";
	echo "</form>\n";
	echo "</div>\n";

	break;

case 'deletecomment':
	$comment = (int) $_REQUEST['comment'];
	check_admin_referer('delete-comment_' . $comment);

	$p = (int) $_REQUEST['p'];
	if ( isset($_REQUEST['noredir']) ) {
		$noredir = true;
	} else {
		$noredir = false;
	}

	$postdata = get_post($p) or 
		die(sprintf(__('Oops, no post with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

	if ( ! $comment = get_comment($comment) )
			 die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit-comments.php'));

	if ( !current_user_can('edit_post', $comment->comment_post_ID) )
		die( __('You are not allowed to edit comments on this post.') );

	if ( 'spam' == $_REQUEST['delete_type'] )
		wp_set_comment_status($comment->comment_ID, 'spam');
	else
		wp_delete_comment($comment->comment_ID);

	if ((wp_get_referer() != '') && (false == $noredir)) {
		wp_redirect(wp_get_referer());
	} else {
		wp_redirect(get_settings('siteurl') .'/wp-admin/edit-comments.php');
	}
	exit();
	break;

case 'unapprovecomment':
	$comment = (int) $_GET['comment'];
	check_admin_referer('unapprove-comment_' . $comment);
	
	$p = (int) $_GET['p'];
	if (isset($_GET['noredir'])) {
		$noredir = true;
	} else {
		$noredir = false;
	}

	if ( ! $comment = get_comment($comment) )
		die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

	if ( !current_user_can('edit_post', $comment->comment_post_ID) )
		die( __('You are not allowed to edit comments on this post, so you cannot disapprove this comment.') );

	wp_set_comment_status($comment->comment_ID, "hold");

	if ((wp_get_referer() != "") && (false == $noredir)) {
		wp_redirect(wp_get_referer());
	} else {
		wp_redirect(get_settings('siteurl') .'/wp-admin/edit.php?p='.$p.'&c=1#comments');
	}
	exit();
	break;

case 'approvecomment':
	$comment = (int) $_GET['comment'];
	check_admin_referer('approve-comment_' . $comment);

	$p = (int) $_GET['p'];
	if (isset($_GET['noredir'])) {
		$noredir = true;
	} else {
		$noredir = false;
	}

	if ( ! $comment = get_comment($comment) )
		die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

	if ( !current_user_can('edit_post', $comment->comment_post_ID) )
		die( __('You are not allowed to edit comments on this post, so you cannot approve this comment.') );

	wp_set_comment_status($comment->comment_ID, "approve");
	if (get_settings("comments_notify") == true) {
		wp_notify_postauthor($comment->comment_ID);
	}


	if ((wp_get_referer() != "") && (false == $noredir)) {
		wp_redirect(wp_get_referer());
	} else {
		wp_redirect(get_settings('siteurl') .'/wp-admin/edit.php?p='.$p.'&c=1#comments');
	}
	exit();
	break;

case 'editedcomment':

	$comment_ID = (int) $_POST['comment_ID'];
	$comment_post_ID = (int) $_POST['comment_post_id'];

	check_admin_referer('update-comment_' . $comment_ID);

	edit_comment();

	$referredby = $_POST['referredby'];
	if (!empty($referredby)) {
		wp_redirect($referredby);
	} else {
		wp_redirect("edit.php?p=$comment_post_ID&c=1#comments");
	}

	break;
default:
	break;
} // end switch

include('admin-footer.php');

?>
