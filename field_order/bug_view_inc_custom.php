<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This include file prints out the bug information
 * $f_bug_id MUST be specified before the file is included
 *
 * @package MantisBT
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses access_api.php
 * @uses authentication_api.php
 * @uses bug_api.php
 * @uses category_api.php
 * @uses columns_api.php
 * @uses compress_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses current_user_api.php
 * @uses custom_field_api.php
 * @uses date_api.php
 * @uses event_api.php
 * @uses gpc_api.php
 * @uses helper_api.php
 * @uses html_api.php
 * @uses lang_api.php
 * @uses last_visited_api.php
 * @uses prepare_api.php
 * @uses print_api.php
 * @uses project_api.php
 * @uses string_api.php
 * @uses tag_api.php
 * @uses utility_api.php
 * @uses version_api.php
 */

if( !defined( 'BUG_VIEW_INC_ALLOW' ) ) {
	return;
}

require_api( 'access_api.php' );
require_api( 'authentication_api.php' );
require_api( 'bug_api.php' );
require_api( 'category_api.php' );
require_api( 'columns_api.php' );
require_api( 'compress_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'current_user_api.php' );
require_api( 'custom_field_api.php' );
require_api( 'date_api.php' );
require_api( 'event_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'html_api.php' );
require_api( 'lang_api.php' );
require_api( 'last_visited_api.php' );
require_api( 'prepare_api.php' );
require_api( 'print_api.php' );
require_api( 'project_api.php' );
require_api( 'string_api.php' );
require_api( 'tag_api.php' );
require_api( 'utility_api.php' );
require_api( 'version_api.php' );

require_css( 'status_config.php' );

$f_bug_id = gpc_get_int( 'id' );

bug_ensure_exists( $f_bug_id );

$t_bug = bug_get( $f_bug_id, true );

# In case the current project is not the same project of the bug we are
# viewing, override the current project. This ensures all config_get and other
# per-project function calls use the project ID of this bug.
$g_project_override = $t_bug->project_id;

access_ensure_bug_level( config_get( 'view_bug_threshold' ), $f_bug_id );

$f_history = gpc_get_bool( 'history', config_get( 'history_default_visible' ) );

$t_fields = config_get( $t_fields_config_option );
$t_fields = columns_filter_disabled( $t_fields );

compress_enable();

if( $t_show_page_header ) {
	layout_page_header( bug_format_summary( $f_bug_id, SUMMARY_CAPTION ), null, 'view-issue-page' );
	layout_page_begin( 'view_all_bug_page.php' );
}

$t_action_button_position = config_get( 'action_button_position' );

$t_bugslist = gpc_get_cookie( config_get_global( 'bug_list_cookie' ), false );

$t_bug_id = $f_bug_id;

$detail_rows = array(array("id", "project", "category", "view-status", "date-submitted", "last-modified"),
					 array("reporter","assigned-to","due-date"),
					 array("priority","severity","reproducibility"),					 
					 array("status","resolution",""),
					 array("projection","eta"),
					 array("platform","os","os-version"),					 
					 array("product-version","product_build"),
					 array("target-version","fixed-in-version","custom:3"),
					 array("custom:5","custom:4"));

$field_visibility = array("versions" 				=> version_should_show_product_version( $t_bug->project_id ),
						  "product-version" 		=> version_should_show_product_version( $t_bug->project_id ) && in_array( 'product_version', $t_fields ),
						  "fixed-in-version" 		=> version_should_show_product_version( $t_bug->project_id ) && in_array( 'fixed_in_version', $t_fields ),
						  "product-build" 			=> version_should_show_product_version( $t_bug->project_id ) && in_array( 'product_build', $t_fields ) && ( config_get( 'enable_product_build' ) == ON ),
						  "target-version" 			=> version_should_show_product_version( $t_bug->project_id ) && in_array( 'target_version', $t_fields ) && access_has_bug_level( config_get( 'roadmap_view_threshold' ), $f_bug_id ),
						  "project" 				=> in_array( 'project', $t_fields ),
						  "id" 						=> in_array( 'id', $t_fields ),
						  "date-submitted" 			=> in_array( 'date_submitted', $t_fields ),
						  "last-modified" 			=> in_array( 'last_updated', $t_fields ),
						  "tags" 					=> in_array( 'tags', $t_fields ) && access_has_bug_level( config_get( 'tag_view_threshold' ), $t_bug_id ),
						  "view-status" 				=> in_array( 'view_state', $t_fields ),
						  "due-date" 				=> in_array( 'due_date', $t_fields ) && access_has_bug_level( config_get( 'due_date_view_threshold' ), $f_bug_id ),
						  "reporter"				=> in_array( 'reporter', $t_fields ),
						  "assigned-to" 				=> in_array( 'handler', $t_fields ) && access_has_bug_level( config_get( 'view_handler_threshold' ), $f_bug_id ),
						  "additional-information" 	=> !is_blank( $t_bug->additional_information ) && in_array( 'additional_info', $t_fields ),
						  "steps-to-reproduce" 		=> !is_blank( $t_bug->steps_to_reproduce ) && in_array( 'steps_to_reproduce', $t_fields ),
						  "monitor-box" 			=> !$t_force_readonly,
						  "relationships-box" 		=> !$t_force_readonly,
						  "sponsorships-box" 		=> config_get( 'enable_sponsorship' ) && access_has_bug_level( config_get( 'view_sponsorship_total_threshold' ), $f_bug_id ),
						  "history" 				=> $f_history,
						  "profiles" 				=> config_get( 'enable_profiles' ),
						  "platform" 				=> $t_show_profiles && in_array( 'platform', $t_fields ),
						  "os" 						=> $t_show_profiles && in_array( 'os', $t_fields ),
						  "os-version" 				=> $t_show_profiles && in_array( 'os_version', $t_fields ),
						  "projection" 				=> in_array( 'projection', $t_fields ),
						  "eta" 					=> in_array( 'eta', $t_fields ),
						  "category" 				=> in_array( 'category_id', $t_fields ),
						  "priority" 				=> in_array( 'priority', $t_fields ),
						  "severity" 				=> in_array( 'severity', $t_fields ),
						  "reproducibility"			=> in_array( 'reproducibility', $t_fields ),
						  "status"					=> in_array( 'status', $t_fields ),
						  "resolution"				=> in_array( 'resolution', $t_fields ),
						  "summary"					=> in_array( 'summary', $t_fields ),
						  "description"				=> in_array( 'description', $t_fields )
						  );

$t_form_title = lang_get( 'bug_view_title' );
$t_wiki_link = config_get_global( 'wiki_enable' ) == ON ? 'wiki.php?id=' . $f_bug_id : '';

if( access_has_bug_level( config_get( 'view_history_threshold' ), $f_bug_id ) ) {
	if( $f_history ) {
		$t_history_link = '#history';
		$t_history_label = lang_get( 'jump_to_history' );
	} else {
		$t_history_link = 'view.php?id=' . $f_bug_id . '&history=1#history';
		$t_history_label = lang_get( 'display_history' );
	}
} else {
	$t_history_link = '';
}

$t_bug_reminder_link = 'bug_reminder_page.php?bug_id=' . $f_bug_id;

$t_top_buttons_enabled = !$t_force_readonly && ( $t_action_button_position == POSITION_TOP || $t_action_button_position == POSITION_BOTH );
$t_bottom_buttons_enabled = !$t_force_readonly && ( $t_action_button_position == POSITION_BOTTOM || $t_action_button_position == POSITION_BOTH );
$t_bug_overdue = bug_is_overdue( $f_bug_id );

$t_can_attach_tag = is_field_visible("tags") && !$t_force_readonly && access_has_bug_level( config_get( 'tag_attach_threshold' ), $f_bug_id );
$t_summary = is_field_visible("summary") ? bug_format_summary( $f_bug_id, SUMMARY_FIELD ) : '';
$t_description = is_field_visible("description") ? string_display_links( $t_bug->description ) : '';
$t_steps_to_reproduce = is_field_visible("steps-to-reproduce") ? string_display_links( $t_bug->steps_to_reproduce ) : '';
$t_additional_information = is_field_visible("additional-information") ? string_display_links( $t_bug->additional_information ) : '';

$t_links = event_signal( 'EVENT_MENU_ISSUE', $f_bug_id );

#
# Start of Template
#

echo '<div class="col-md-12 col-xs-12">';
echo '<div class="widget-box widget-color-blue2">';
echo '<div class="widget-header widget-header-small">';
echo '<h4 class="widget-title lighter">';
echo '<i class="ace-icon fa fa-bars"></i>';
echo $t_form_title;
echo '</h4>';
echo '</div>';

echo '<div class="widget-body">';

echo '<div class="widget-toolbox padding-8 clearfix noprint">';
echo '<div class="btn-group pull-left">';

# Send Bug Reminder
if( $t_show_reminder_link ) {
	print_small_button( $t_bug_reminder_link, lang_get( 'bug_reminder' ) );
}

if( !is_blank( $t_wiki_link ) ) {
	print_small_button( $t_wiki_link, lang_get( 'wiki' ) );
}

foreach ( $t_links as $t_plugin => $t_hooks ) {
	foreach( $t_hooks as $t_hook ) {
		if( is_array( $t_hook ) ) {
			foreach( $t_hook as $t_label => $t_href ) {
				if( is_numeric( $t_label ) ) {
					print_bracket_link_prepared( $t_href );
				} else {
					print_small_button( $t_href, $t_label );
				}
			}
		} elseif( !empty( $t_hook ) ) {
			print_bracket_link_prepared( $t_hook );
		}
	}
}

# Jump to Bugnotes
print_small_button( '#bugnotes', lang_get( 'jump_to_bugnotes' ) );

# Display or Jump to History
if( !is_blank( $t_history_link ) ) {
	print_small_button( $t_history_link, $t_history_label );
}

echo '</div>';

# prev/next links
echo '<div class="btn-group pull-right">';
if( $t_bugslist ) {
	$t_bugslist = explode( ',', $t_bugslist );
	$t_index = array_search( $f_bug_id, $t_bugslist );
	if( false !== $t_index ) {
		if( isset( $t_bugslist[$t_index-1] ) ) {
			print_small_button( 'view.php?id='.$t_bugslist[$t_index-1], '&lt;&lt;' );
		}

		if( isset( $t_bugslist[$t_index+1] ) ) {
			print_small_button( 'view.php?id='.$t_bugslist[$t_index+1], '&gt;&gt;' );
		}
	}
}
echo '</div>';
echo '</div>';

echo '<div class="widget-main no-padding">';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-condensed">';

if( $t_top_buttons_enabled ) {
	echo '<thead><tr class="bug-nav">';
	echo '<tr class="top-buttons noprint">';
	echo '<td colspan="6">';
	html_buttons_view_bug_page( $t_bug_id );
	echo '</td>';
	echo '</tr>';
	echo '</thead>';
}

if( $t_bottom_buttons_enabled ) {
	echo '<tfoot>';
	echo '<tr class="noprint"><td colspan="6">';
	html_buttons_view_bug_page( $t_bug_id );
	echo '</td></tr>';
	echo '</tfoot>';
}

echo '<tbody>';

$first_row = true;

// list the custom fields which have been repositioned
// so that they are not outputted twice
$handled_custom_fields = array();
foreach($detail_rows as $row) {
	foreach($row as $field_name) {
		if (is_custom_field($field_name)) {
			array_push($handled_custom_fields, get_custom_field_id($field_name));
		}
	}
}

// print detail rows
foreach($detail_rows as $row) {
	if ($first_row) {
		// first row has vertical labels
		$first_row = false;
		print_row_vert_labels($row);	
	}
	else {
		print_row_horz_labels($row);	
	}
}

#
# Bug Details Event Signal
#

event_signal( 'EVENT_VIEW_BUG_DETAILS', array( $t_bug_id ) );

# spacer
echo '<tr class="spacer"><td colspan="6"></td></tr>';
echo '<tr class="hidden"></tr>';

#
# Bug Details (screen wide fields)
#

# Summary
if( is_field_visible("summary") ) {
	echo '<tr>';
	echo '<th class="bug-summary category">', lang_get( 'summary' ), '</th>';
	echo '<td class="bug-summary" colspan="5">', $t_summary, '</td>';
	echo '</tr>';
}

# Description
if(  is_field_visible("description") ) {
	echo '<tr>';
	echo '<th class="bug-description category">', lang_get( 'description' ), '</th>';
	echo '<td class="bug-description" colspan="5">', $t_description, '</td>';
	echo '</tr>';
}

# Steps to Reproduce
if( is_field_visible("steps-to-reproduce") ) {
	echo '<tr>';
	echo '<th class="bug-steps-to-reproduce category">', lang_get( 'steps_to_reproduce' ), '</th>';
	echo '<td class="bug-steps-to-reproduce" colspan="5">', $t_steps_to_reproduce, '</td>';
	echo '</tr>';
}

# Additional Information
if( is_field_visible("additional-information") ) {
	echo '<tr>';
	echo '<th class="bug-additional-information category">', lang_get( 'additional_information' ), '</th>';
	echo '<td class="bug-additional-information" colspan="5">', $t_additional_information, '</td>';
	echo '</tr>';
}

# Tagging
if( is_field_visible("tags") ) {
	echo '<tr>';
	echo '<th class="bug-tags category">', lang_get( 'tags' ), '</th>';
	echo '<td class="bug-tags" colspan="5">';
	tag_display_attached( $t_bug_id );
	echo '</td></tr>';
}

# Attach Tags
if( $t_can_attach_tag ) {
	echo '<tr class="noprint">';
	echo '<th class="bug-attach-tags category">', lang_get( 'tag_attach_long' ), '</th>';
	echo '<td class="bug-attach-tags" colspan="5">';
	print_tag_attach_form( $t_bug_id );
	echo '</td></tr>';
}

# spacer
echo '<tr class="spacer"><td colspan="6"></td></tr>';
echo '<tr class="hidden"></tr>';

# Custom Fields
$t_custom_fields_found = false;
$t_related_custom_field_ids = custom_field_get_linked_ids( $t_bug->project_id );
custom_field_cache_values( array( $t_bug->id ) , $t_related_custom_field_ids );

foreach( $t_related_custom_field_ids as $t_id ) {

	#EXTENSION BY BERRE
    # do not show custom fields again which have already been printed
	if (in_array($t_id, $handled_custom_fields)) {
		continue;
	}    
	#END OF EXTENSION

	if( !custom_field_has_read_access( $t_id, $f_bug_id ) ) {
		continue;
	} # has read access

	$t_custom_fields_found = true;
	$t_def = custom_field_get_definition( $t_id );

	echo '<tr>';
	echo '<th class="bug-custom-field category">', string_display( lang_get_defaulted( $t_def['name'] ) ), '</th>';
	echo '<td class="bug-custom-field" colspan="5">';
	print_custom_field_value( $t_def, $t_id, $f_bug_id );
	echo '</td></tr>';
}

if( $t_custom_fields_found ) {
	# spacer
	echo '<tr class="spacer"><td colspan="6"></td></tr>';
	echo '<tr class="hidden"></tr>';
}

# CUSTOM EXTENSION by berre
# show all attached files (like in Mantis 1.x)
if ( ON == config_get('show_attachments_in_details') ) {
   echo '<tr>';
    echo '<th class="bug-tags category" name="attachments" id="attachments">', lang_get( 'attached_files' );
    echo '<td class="bug-attachments" colspan="7">';
    print_bug_attachments_list( $t_bug_id, null);
    echo '</td></tr>';
}
# END OF CUSTOM EXTENSION

echo '</tbody></table>';
echo '</div></div></div></div></div>';

# User list sponsoring the bug
if( is_field_visible("sponsorships-box") ) {
	define( 'BUG_SPONSORSHIP_LIST_VIEW_INC_ALLOW', true );
	include( $t_mantis_dir . 'bug_sponsorship_list_view_inc.php' );
}

# Bug Relationships
if( is_field_visible("relationships-box") ) {
	relationship_view_box( $t_bug->id );
}

# User list monitoring the bug
if( is_field_visible("monitor-box")) {
	define( 'BUG_MONITOR_LIST_VIEW_INC_ALLOW', true );
	include( $t_mantis_dir . 'bug_monitor_list_view_inc.php' );
}

# Bugnotes and "Add Note" box
if( 'ASC' == current_user_get_pref( 'bugnote_order' ) ) {
	define( 'BUGNOTE_VIEW_INC_ALLOW', true );
	include( $t_mantis_dir . 'bugnote_view_inc.php' );

	if( !$t_force_readonly ) {
		define( 'BUGNOTE_ADD_INC_ALLOW', true );
		include( $t_mantis_dir . 'bugnote_add_inc.php' );
	}
} else {
	if( !$t_force_readonly ) {
		define( 'BUGNOTE_ADD_INC_ALLOW', true );
		include( $t_mantis_dir . 'bugnote_add_inc.php' );
	}

	define( 'BUGNOTE_VIEW_INC_ALLOW', true );
	include( $t_mantis_dir . 'bugnote_view_inc.php' );
}

# Allow plugins to display stuff after notes
event_signal( 'EVENT_VIEW_BUG_EXTRA', array( $f_bug_id ) );

# Time tracking statistics
if( config_get( 'time_tracking_enabled' ) &&
	access_has_bug_level( config_get( 'time_tracking_view_threshold' ), $f_bug_id ) ) {
	define( 'BUGNOTE_STATS_INC_ALLOW', true );
	include( $t_mantis_dir . 'bugnote_stats_inc.php' );
}

# History
if( $t_show_history ) {
	define( 'HISTORY_INC_ALLOW', true );
	include( $t_mantis_dir . 'history_inc.php' );
}

layout_page_end();

last_visited_issue( $t_bug_id );

#EXTENSION BY BERRE
function is_custom_field($field_name)
{
	return substr( $field_name, 0, 7 ) === "custom:";
}

function get_custom_field_id($custom_field_name)
{
	return (int)substr($custom_field_name, 7);
}

function is_field_visible($field_name)
{	
	// is this a custom field?
	if (is_custom_field($field_name)) {
		global $t_bug;

		$custom_field_id = get_custom_field_id($field_name);
		return custom_field_has_read_access( $custom_field_id, $t_bug->id ) && custom_field_has_value($custom_field_id, $t_bug->id);
	}

	global $field_visibility;
	return $field_visibility[$field_name];
}


function get_field_lang($field_name)
{
	// resolve non-conventional field names
	switch($field_name)
	{		
		case "project": return lang_get( "email_project" );	
		case "last-modified": return lang_get( "last_update" );	

		default: return lang_get( str_replace("-", "_", $field_name));	
	}
	
}

function print_custom_field($field_id, $bug_id)
{
	$t_def = custom_field_get_definition( $field_id );

	echo '<th class="bug-custom-field category">', string_display( lang_get_defaulted( $t_def['name'] ) ), '</th>';
	echo '<td class="bug-custom-field">';
	print_custom_field_value( $t_def, $field_id, $bug_id );
	echo '</td>';
}

function print_field_value($field_name)
{
	global $t_bug;
	global $t_bug_id;

	switch ($field_name)
	{
		case "id": echo string_display_line( bug_format_id( $t_bug->id ) ); break;
		case "project": echo string_display_line( project_get_name( $t_bug->project_id ) ); break;
		case "category": echo string_display_line( category_full_name( $t_bug->category_id ) ) ; break;
		case "view-status": echo string_display_line( get_enum_element( 'view_state', $t_bug->view_state ) ); break;
		case "date-submitted": echo date( config_get( 'normal_date_format' ), $t_bug->date_submitted ); break;
		case "last-modified": echo date( config_get( 'normal_date_format' ), $t_bug->last_updated ); break;	

		case "priority": echo string_display_line( get_enum_element( 'priority', $t_bug->priority )); break;
		case "severity": echo string_display_line( get_enum_element( 'severity', $t_bug->severity )); break;
		case "reproducibility": echo string_display_line( get_enum_element( 'reproducibility', $t_bug->reproducibility )); break;
		case "reporter": print_user_with_subject( $t_bug->reporter_id, $t_bug_id ); break;
		case "assigned-to": print_user_with_subject( $t_bug->handler_id, $t_bug_id ); break;

		case "due-date": date( config_get( 'normal_date_format' ), $t_bug->due_date );

		case "status": {
			$t_status_label = html_get_status_css_class( $t_bug->status );			
			echo '<i class="fa fa-square fa-status-box ' . $t_status_label . '"></i> ';
			echo string_display_line( get_enum_element( 'status', $t_bug->status ) ), '</td>';
			break;
		}
		case "resolution": echo string_display_line( get_enum_element( 'resolution', $t_bug->resolution ) ); break;
		case "projection": echo string_display_line( get_enum_element( 'projection', $t_bug->projection ) ); break;
		case "eta": echo string_display_line( get_enum_element( 'eta', $t_bug->eta ) ); break;

		case "platform": echo string_display_line( $t_bug->platform ); break;
		case "os": echo string_display_line( $t_bug->os ); break;
		case "os-version": echo string_display_line( $t_bug->os ); break;

		case "product-version": echo  string_display_line(prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->version, $t_bug->project_id ) )); break;		
		case "target-version": string_display_line(prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->target_version, $t_bug->project_id ) )); break;
		case "fixed-in-version": echo string_display_line(prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->fixed_in_version, $t_bug->project_id )) ); break;
		case "product-build": echo string_display_line( $t_bug->build ); break;


		default: echo "!!" , $field_name; break;
	}
}

function print_field($field_name)
{
	if (is_custom_field($field_name))
	{
		global $t_bug;
		print_custom_field(get_custom_field_id($field_name), $t_bug->id);
	} else {
		echo '<th class="bug-' . $field_name . ' category">', get_field_lang($field_name), '</th>';
		echo '<td class="bug-' . $field_name . '"" >';			
		print_field_value($field_name);
		echo '</td>';
	}

	// todo: for due-date if bug is overdue the class overdue has to be added to the td
}

function print_row_vert_labels($row)
{
	// if all fields are not visibile -> skip this row
	$skip = true;

	foreach ($row as $field_name) {
		if (is_field_visible($field_name)) {
			$skip = false;
			break;
		}
	}

	if ($skip) return;

	echo '<tr class="bug-header">';
	// print the labels first
	foreach ($row as $field_name) {
		echo '<th class="bug-' . $field_name . ' category">', is_field_visible($field_name) ? get_field_lang($field_name) : '', '</th>';
	}
	echo '</tr>';
	
	echo '<tr class="bug-header-data">';
	// now the data
	foreach ($row as $field_name) {
		echo '<td class="bug-id">', print_field_value($field_name), '</td>';
	}	

	echo '</tr>';

	# spacer
	echo '<tr class="spacer"><td colspan="6"></td></tr>';
	echo '<tr class="hidden"></tr>';
}

function print_row_horz_labels($row)
{
	// if all fields are not visibile -> skip this row
	$skip = true;

	foreach ($row as $field_name) {
		if (is_field_visible($field_name)) {
			$skip = false;
			break;
		}
	}

	if ($skip) return;

	echo '<tr>';

	$t_spacer = 0;

	foreach ($row as $field_name) {
		if (is_field_visible($field_name))
			print_field($field_name);
		else
			$t_spacer += 2;
	}

	if( $t_spacer > 0 ) {
		echo '<td colspan="', $t_spacer, '">&#160;</td>';
	}

	echo '</tr>';
}

function custom_field_has_value($field_id, $bug_id)
{
	return custom_field_get_value($field_id, $bug_id) != null;
}

#END OF EXTENSION