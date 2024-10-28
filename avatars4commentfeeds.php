<?php
/*
Plugin Name: Avatars 4 Comment Feeds
Plugin URI: http://www.weinschenker.name/avatars-for-comment-feeds/
Description: This plugin inserts Avatars, Gravatars etc into your comment-feeds
Version:  1.0.1
Author: Jan Weinschenker
Author URI: http://www.weinschenker.name

   $Id$

    Plugin: Copyright 2008  Jan Weinschenker  (email: kontakt@weinschenker.name)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Add configure-link to plugin-list
 *
 * @since WordPress 2.7
 * @param unknown_type $links
 * @return unknown
 */
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'avatar4comments_addConfigureLink' );
function avatar4comments_addConfigureLink($links) {
	$settings_link = '<a href=\'options-general.php?page=avatars4commentfeeds.php\'>'.__('Settings').'</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Register with options-page
 */
add_option('a4cf_settings', $data, 'Avatars 4 Comment Feeds');
add_action('admin_menu', 'avatar4comments_register_options');
function avatar4comments_register_options() {
	if (function_exists('add_options_page')) {
		add_options_page('Avatars 4 Feeds', 'Avatars 4 Comment Feeds', 8, basename(__FILE__), 'avatar4comments_options_subpanel');
	}
}

/**
 * Register settings
 * 
 */
add_action('admin_init', 'avatar4comments_register_setting');
function avatar4comments_register_setting(){
	if (function_exists('register_setting')) {
		register_setting('a4cf_settings_group', 'a4cf_settings');
	}
}


/**
 * Load text-domain for localization
 */
if (function_exists('load_plugin_textdomain')) {
	if ( !defined('WP_PLUGIN_DIR') ) {
		load_plugin_textdomain('avatars4commentfeeds', str_replace( ABSPATH, '', dirname(__FILE__)));
	} else {
		load_plugin_textdomain('avatars4commentfeeds', false, dirname(plugin_basename(__FILE__)));
	}
}


/**
 * Add avatars to comment-text in feeds via filter
 */
add_filter('comment_text','avatar4comments');
function avatar4comments($content, $foo=''){ 
    $settings = get_option('a4cf_settings');
    $avatar_size = intval($settings['avatar_size']);
    if ($avatar_size < 10 or $avatar_size > 96) $avatar_size = 80;
    
    $comment = get_comment($commentID);
    $type = $comment->comment_type;
    $snaprimages = strcmp($settings['snaprimages'],'snaprimages');
    $defaultAvatar = $settings['defaultAvatar'];
    
    if (function_exists('get_avatar') and get_option('show_avatars') and is_feed() and $type != 'pingback' and $type != 'trackback'){ 
        $avatar = '<div style="float:right; margin:1em">'.get_avatar($comment, $avatar_size, $defaultAvatar).'</div>';
        $avatar = $avatar.$content.'<div style="clear:both;"></div>';
        return $avatar;
    } else if (function_exists('get_avatar') and get_option('show_avatars') and is_feed() and $snaprimages==0 and ($type == 'pingback' or $type == 'trackback')){ 
        $tbUrl = $comment->comment_author_url;
        $tbAuthor = $comment->comment_author;
        $img = '<img src="http://images.websnapr.com/?url='.$tbUrl.'&amp;size=T" style="width: '.$avatar_size.'px" alt="'.$tbAuthor.'" />';
        $avatar = '<div style="float:right; margin:1em">'.$img.'</div>';
        $avatar = $avatar.$content.'<div style="clear:both;"></div>';
        return $avatar;        
    } else {
        return $content;
    }
}


/**
 * This function renders the options-subpanel of this plugin. 
 */
function avatar4comments_options_subpanel() {
	global $_POST, $wp_rewrite;    
	if (function_exists('wp_nonce_field') and !function_exists('settings_fields')) {
		if ((bool) a4cf_vars_are_set())
		check_admin_referer('a4cf-action_option_panel');
	} else if (function_exists('settings_fields')) {
		if ((bool) a4cf_vars_are_set())
		check_admin_referer('a4cf_settings_group-options');
	}
    
    /* Get settings from database via WordPress framework */
	$a4cf_settings = get_option('a4cf_settings');
	$a4cf_flash = "";

	if (a4cf_user_is_authorized()) {
		if ((bool) a4cf_vars_are_set()) {
			$a4cf_settings['avatar_size'] = trim(attribute_escape($_POST['avatar_size']));
			$a4cf_settings['snaprimages'] = attribute_escape($_POST['snaprimages']);
			$a4cf_settings['defaultAvatar']  = trim(attribute_escape($_POST['defaultAvatar']));
			update_option('a4cf_settings', $a4cf_settings);
			$a4cf_flash = __('Settings saved.');
		}
	} else {
		$a4cf_flash = __('You don\'t have enough access rights.');
	}

	if ($a4cf_flash != ''){
		?><div id="message"class="updated fade"><p><?php echo $a4cf_flash; ?></p></div><?php 
    }
	?>
    <div class="wrap">
	<h2>Avatars 4 Comment Feeds <?php _e('Options'); ?></h2>
	<form action="" method="post"><?php
    if (function_exists('wp_nonce_field') and !function_exists('settings_fields') ) {
    	wp_nonce_field('a4cf-action_option_panel');
    } else if (function_exists('settings_fields')) {
		settings_fields('a4cf_settings_group');
	}
    ?>
    <table class="form-table">
    <tr>
        <th scope="row" valign="top"><label for="avatar_size"><?php _e('Avatar Size','avatars4commentfeeds'); ?></label></th>
        <td>
            <input type="text" id="avatar_size" name="avatar_size" value="<?php echo htmlentities($a4cf_settings['avatar_size']);?>" size="5" /> pixel<br />
                <p><?php _e('Choose an integer value between 10 and 96 (default: 80).','avatars4commentfeeds'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row" valign="top"><label for="defaultAvatar"><?php _e('Default Avatar','avatars4commentfeeds'); ?></label></th>
        <td>
            <input type="text" id="defaultAvatar" name="defaultAvatar" value="<?php echo htmlentities($a4cf_settings['defaultAvatar']);?>" size="50" /><br />
                <p><?php _e('Enter the URL of your default-Avatar. This setting is optional, you may leave it blank.','avatars4commentfeeds'); ?></p>
        </td>
    </tr>
    <tr>
        <th scope="row" valign="top"><label for="snaprimages"><?php _e('Snapshots for Trackbacks','avatars4commentfeeds'); ?></label></th>
        <td>
            <input id="snaprimages" name="snaprimages" type="checkbox"
			value="snaprimages"
			<?php checked('snaprimages', $a4cf_settings['snaprimages']);?> /> <?php _e('Show Snapshots for Trackbacks and Pingbacks.','avatars4commentfeeds'); ?>
        </td>
    </tr>
    </table>
    <p class="submit">
        <input name="submit" type="submit" value="<?php _e('Save Changes'); ?>" />
    </p>
    </form>
                    
    <h2><?php _e('Documentation and Support for this Plugin','avatars4commentfeeds'); ?></h2>
    <p><?php _e('Can be found at the <a href="http://www.weinschenker.name/avatars-for-comment-feeds/" title="go to http://www.weinschenker.name/">Plugin-Homepage</a>.','avatars4commentfeeds'); ?></p>
	 <?php
}

/**
 * Check if the user has changed any values.
 */ 
function a4cf_vars_are_set(){
    if (isset ($_POST['avatar_size']) or isset ($_POST['snaprimages']) or isset ($_POST['defaultAvatar'])) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * Check if the current user is allowed to activate plugins.
 */
function a4cf_user_is_authorized() {
	global $user_level;
	if (function_exists("current_user_can")) {
		return current_user_can('activate_plugins');
	} else {
		return $user_level > 5;
	}
}

?>