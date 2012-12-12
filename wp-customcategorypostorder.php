<?php
/**
 * @package Custom Post
 * @author Faaiq Ahmed
 * @version 1.0.0
 */
/*
Plugin Name: Custom Category Post Order
Description: Arrange Post through drag n drop interface of selected category.
Author: Faaiq Ahmed, Sr Wordpress Developer,faaiqsj@gmail.com
Version: 1.5.1
*/

global $ccpo_db_version;	
$ccpo_db_version = "2.0";

function ccpo_menu() {
	global $current_user, $wpdb;
		$role = $wpdb->prefix . 'capabilities';
		$current_user->role = array_keys($current_user->$role);
		$current_role = $current_user->role[0];
		$role = get_option( 'ccpo_order_manager', 'editor' );
		add_menu_page('Post Orders', 'Post Order', 'editor', 'ccpo', 'post_order_category');
		add_submenu_page( "ccpo", "Order Permission", "Permission", 'editor', "subccpo", "ccpo_admin_right" );
				
		if($current_role != 'editor') {
				add_submenu_page( "ccpo", "Post Order", "Post Order", $role, "subccpo1", "post_order_category" );
		}
}

add_action('admin_menu', 'ccpo_menu');


function ccpo_admin_right() {
		global $wp_roles;
		
		$role = $_POST['role'];
		if(isset($_POST) and $role != "") {
				
				update_option( "ccpo_order_manager", $role );
				print "Role Updated";
		 
		}
		$role = get_option( 'ccpo_order_manager', 'editor' );
    $roles = $wp_roles->get_names();
		$select  = "";
		foreach($roles as $key=> $label) {
				if($key == $role) {
						$select .= '<option value="'.$key.'" selected>'.$label.'</option>';		
				}else {
						$select .= '<option value="'.$key.'">'.$label.'</option>';		
				}
				
		}
		
		print '<div class="wrap">
	<h2>Who Can Arrange Post</h2>
	<form method="post">';
	wp_nonce_field('update-options');
	
	print '<table class="form-table">
		<tr valign="top">
		<th scope="row">Select Role:</th>
		<td><select name="role" id="row">'.$select.'</select></td>
		</tr>';
		print '<tr valign="top"><td>
		<input type="submit" class="button" value="Submit" />
		</td></tr>
		</table>';
}

function post_order_category() {
	global $wpdb;
	
	$category = $_POST['category'];
	
	$args = array(
	'type'                     => 'post',
	'child_of'                 => '',
	'parent'                   => '',
	'orderby'                  => 'name',
	'order'                    => 'ASC',
	'hide_empty'               => true,
	'exclude'                  => array(1),
	'hierarchical'             => true,
	'taxonomy'                 => 'category',
	'pad_counts'               => true );

	$categories = get_categories( $args );
	
	$opt = array();
	$opt[] = '<option value="" selected>Selected</option>';
	
	
	foreach($categories as $id => $cat) {
			if($cat->term_id == $category) {
				 $opt[] = '<option value="'.$cat->term_id.'" selected>'.$cat->name.'</option>';
			}else  {
				 $opt[] = '<option value="'.$cat->term_id.'">'.$cat->name.'</option>';
			}
		}
	
	$temp_order = array();
	if($category >0 ) {
		
		$sql = "select * from ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$category' order by id";
		$order_result = $wpdb->get_results($sql);
		for($k =0 ;$k < count($order_result); ++$k) {
				$order_result_incl[$order_result[$k]->post_id] = $order_result[$k]->incl;
		}
		
		$args = array(
			'category__in'        => array($category),
			'posts_per_page'			=> -1,
			'post_type'       => 'post',
			'orderby'            => 'title',
			'post_status'     => 'publish',
			'order' => 'DESC' 	
		);
		
	 global $custom_cat,$stop_join;
	 $stop_join = true;
	 $custom_cat = $category;
	 $query = new WP_Query( $args );
	 $stop_join = false;
	 $custom_cat = 0;
	 $posts_array = $query->posts;
	 
	 for($j = 0; $j < count($posts_array); ++$j) {
		$temp_order[$posts_array[$j]->ID] = $posts_array[$j];
	 }
	}
	
	$checked = get_option( "ccpo_category_ordering_".$category );
	
			?>
				<div id="fb-root"></div>
				<script>(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
				fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));</script>
				<div style="padding-top:50px;">Please like us on Facebook: <div class="fb-like" data-href="http://www.facebook.com/pages/Wordpress-Plugin/138994529573328" data-send="false" data-width="450" data-show-faces="true"></div></div>
			<?php
	print '<div class="wrap">
	<h2>Custom Category Post Order</h2>
	<form method="post">';
	wp_nonce_field('update-options');
	
	print '<table class="form-table">
		<tr valign="top">
		<th scope="row">Select Category:</th>
		<td><select name="category" id="category">'.implode("",$opt).'</select></td>
		</tr>';
		
		
		if($category >0 ) {
				print '<th scope="row">Use Ordering for selected Category:</th>
				<td><input type="checkbox" name="category_ordering" rel="'.$category.'" id="user_ordering_category" value="1" '.$checked.'></td>
				</tr>';
		}
		
		print '<tr valign="top"><td colspan="2">
		<input type="submit" class="button" value="Load Posts" />
		<br>
		<small>Note: Initially some post will not display remove or add link. to resolve this case move any post up or down and press load posts button again.</small>
		</td></tr>
		</table>';
		
		$html = '<ul id="sortable" style="width:100%;">';
		
		for($i = 0; $i < count( $order_result); ++$i) {
			$post_id = $order_result[$i]->post_id;
			$post = $temp_order[$post_id];
			unset($temp_order[$post_id]);
			$total = check_order_table($post->ID,$category);
			$od = $order_result_incl[$post->ID];
			if($od == 1) {
				$edit = '<small><a href="javascript:void(0);" onclick="rempst('.$post->ID.','.$category.')">Remove</a></small>';
			}else {
				$edit = '<small><a href="javascript:void(0);" onclick="rempst('.$post->ID.','.$category.')">Add</a></small>';
			}
			if($checked == "checked") {
				 if($total >0 ) {
						$html .= '<li class="sortable" id="'.$post->ID.'" rel="'.$post->ID.'">';
						$html .= '<div id="post" class="drag_post">'.$post->post_title.'<div style="float:right;border:0px solid;width:50px;" id="id_'.$post->ID.'">'.$edit.'</div></div>';
				 }
			}else {
				 $html .= '<li class="sortable" id="'.$post->ID.'" rel="'.$post->ID.'">';
				 $html .= '<div id="post" class="drag_post">'.$post->post_title.'<div style="float:right;border:0px solid;width:50px;"  id="id_'.$post->ID.'">'.$edit.'</div></div>';
			}
			$html .= '</li>';
		}
		
		foreach($temp_order as $temp_order_id => $temp_order_post) {
			$post_id = $temp_order_id;
			$post = $temp_order_post;
			$total = check_order_table($post->ID,$category);
			$html .= '<li class="sortable" id="'.$post->ID.'" rel="'.$post->ID.'">';
			$html .= '<div id="post" class="drag_post">'.$post->post_title.'<div style="float:right;border:0px solid;width:50px;"></div></div>';
			$html .= '</li>';
		}
		
		$html .= '</ul>';
		print $html;
		
		
		
		print '<input type="hidden" name="action" value="update" />
		</form>
		</div>';
		print  '<style>
			.drag_post {
				 border:1px solid #cccccc;;
				 background:#F1F1F1;
				 padding:5px;
			}
      #sortable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
      #sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }
      #sortable li span { position: absolute; margin-left: -1.3em; }
      </style>
      <script>
      jQuery(function() {
			jQuery("#user_ordering_category").click(function(){
				 var category = jQuery(this).attr("rel");
				 var checked = jQuery(this).attr("checked");
				 jQuery.post(\'admin-ajax.php\', {checked:checked,category:category,action:\'user_ordering\'});
			});
      jQuery( "#sortable" ).sortable({
            start: function (event, ui) {
               
            },
            change:  function (event, ui) {
               
							 
            },
						update: function(event, ui) {
				     var newOrder = jQuery(this).sortable(\'toArray\').toString();
						 var category = jQuery("#category").attr("value");
				     jQuery.post(\'admin-ajax.php\', {order:newOrder,category:category,action:\'build_order\'});
						}
       });
				 //jQuery( "#sortable" ).disableSelection();
       });
			 function rempst(post_id,cat_id) {
				 jQuery.post(\'admin-ajax.php\', {post_id:post_id,category:cat_id,action:\'rmppost\'},
				 function success(data) {
						jQuery("#id_"+post_id).html(data);
				 }
				 
				 );
			 }
      </script>';
			
			?>

			<?php
}	

add_action('wp_ajax_rmppost', 'rmppost');

function rmppost() {
	 global $wpdb; // this is how you get access to the database
	 $category = $_POST['category'];
	 $post_id = $_POST['post_id'];
	 
	 $incl = $wpdb->get_var("select incl from ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$category' and post_id = '$post_id'");
	 $new_incl = ($incl == 1) ? 0 : 1;
	 $wpdb->query("update ".$wpdb->prefix."ccpo_post_order_rel set incl = '$new_incl' where category_id = '$category' and post_id = '$post_id'");
	 
	  if($new_incl == 1) {
		 $edit = '<small><a href="javascript:void(0);" onclick="rempst('.$post_id.','.$category.')">Remove</a></small>';
		}else {
		 $edit = '<small><a href="javascript:void(0);" onclick="rempst('.$post_id.','.$category.')">Add</a></small>';
		}
		print $edit;
	 die(); // this is required to return a proper result
}

add_action('wp_head', 'add_slideshowjs');
function add_slideshowjs() {
		
}

function check_order_table($post,$cat) {
	 global $wpdb; // this is how you get access to the database
	 $total = $wpdb->get_var("select count(*) as total from  wp_ccpo_post_order_rel where category_id = '$cat' and post_id = '$post'");
	 return $total;
}
add_action('init', 'process_post');

function process_post(){
 global $wp_query;
 wp_enqueue_script( 'jquery-ui-sortable', '/wp-includes/js/jquery/ui/jquery.ui.sortable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.8.20', 1 );

}



add_action('wp_ajax_build_order', 'build_order_callback');

function build_order_callback() {
	global $wpdb; // this is how you get access to the database
	
	$order = explode(",",$_POST['order']);
	$category = $_POST['category'];
	//$wpdb->query("delete from ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$category'");
	
	$total = $wpdb->get_var("select count(*) as total from ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$category'");
	
	if($total == 0) {
		foreach($order as $post_id) {
				$value[] = "('$category', '$post_id')";
		}
		$sql = "insert into ".$wpdb->prefix."ccpo_post_order_rel (category_id,post_id)  values ".implode(",",$value);
		$wpdb->query($sql);	
	}else {
		$results = $wpdb->get_results("select * from ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$category' order by id");
		foreach($results as $index => $result_row) {
				$result_arr[$result_row->post_id] = $result_row;
		}
		$start =0;
		foreach($order as $post_id) {
				$inc_row = $result_arr[$post_id];
				$incl = $inc_row->incl;
				$row = $results[$start];
				++$start;
				$id = $row->id;
				$sql = "update ".$wpdb->prefix."ccpo_post_order_rel set post_id = '$post_id',incl = '$incl' where id = '$id'";
				$wpdb->query($sql);
		}	
	}
	die(); // this is required to return a proper result
}


function ccpo_query_join($args) {
	global $wpdb,$custom_cat,$stop_join;
	 $category_id = get_query_var("cat");
	 
	 if(!$category_id) {
			$category_id = $custom_cat;
	 }
	 
	if(get_option( "ccpo_category_ordering_".$category_id ) == "checked" && $stop_join == false) {
			$args .= " INNER JOIN ".$wpdb->prefix."ccpo_post_order_rel ON ".$wpdb->posts.".ID = ".$wpdb->prefix."ccpo_post_order_rel.post_id and incl = 1  ";
	}
	return $args;
}



function ccpo_query_where($args) {
	 global $wpdb,$custom_cat,$stop_join;
	 $category_id = get_query_var("cat");
	 if(!$category_id) {
			$category_id = $custom_cat;
	 }
	 if(get_option( "ccpo_category_ordering_".$category_id ) == "checked" && $stop_join == false) {
			$args .= " AND ".$wpdb->prefix."ccpo_post_order_rel.category_id = '".$category_id."'";
	 }
	 return $args;
}


function ccpo_query_orderby($args) {
	global $wpdb,$custom_cat,$stop_join;
	 $category_id = get_query_var("cat");
	 	 if(!$category_id) {
			$category_id = $custom_cat;
	 }
	 
	if(get_option( "ccpo_category_ordering_".$category_id ) == "checked" && $stop_join == false) {
	 $args = $wpdb->prefix."ccpo_post_order_rel.id ASC";
	}
	return $args;
}

if(substr(basename($_SERVER['REQUEST_URI']),0,8) != 'edit.php') {
	 add_filter('posts_join', 'ccpo_query_join');
	 add_filter('posts_where', 'ccpo_query_where');
	 add_filter('posts_orderby', 'ccpo_query_orderby');
}

add_action('wp_ajax_user_ordering', 'user_ordering');

function user_ordering() {
	global $wpdb; // this is how you get access to the database
	$category = $_POST['category'];
	$checked = $_POST['checked'];
	if($checked == 'checked') {
	 update_option( "ccpo_category_ordering_".$category, "checked");
	}else {
	 update_option( "ccpo_category_ordering_".$category, "");
	}

	die(); // this is required to return a proper result
}


function ccpo_update_post_order($post_id) {
		global $wpdb;
	if ( !wp_is_post_revision( $post_id ) ) {
		$post = get_post( $post_id, $output );
		$cats = get_the_category( $post_id );
		foreach($cats as $key => $cat) {
				$cat_id = $cat->term_id;
				$total = $wpdb->get_var("select count(*) as total from  ".$wpdb->prefix."ccpo_post_order_rel where category_id = '$cat_id' and post_id = '$post_id'");
				if($total == 0) {
						$sql = "insert into ".$wpdb->prefix."ccpo_post_order_rel (category_id,post_id) values ('$cat_id','$post_id')";
						$wpdb->query($sql);
				}
		}
	}
}
add_action( 'save_post', 'ccpo_update_post_order' );


function ccpo_install() {
		global $wpdb;
		global $ccpo_db_version;
		$table_name = $wpdb->prefix."ccpo_post_order_rel";
		
	 $sql = "CREATE TABLE IF NOT EXISTS $table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`category_id` int(11) NOT NULL,
				`post_id` int(11) NOT NULL,
				`incl` tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
	 ) ;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('ccpo_db_version', $ccpo_db_version);
		
}	
register_activation_hook(__FILE__, 'ccpo_install');

	
function ccpo_uninstall() {
		global $wpdb;
		global $ccpo_db_version;
		$table_name = $wpdb->prefix."ccpo_post_order_rel";
		
		$sql = "DROP TABLE IF EXISTS $table_name";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		delete_option('ccpo_db_version');
		$sql = "delete from  wp_options where option_name like 'ccpo%'";
		dbDelta($sql);
}	
register_deactivation_hook(__FILE__, 'ccpo_uninstall');
