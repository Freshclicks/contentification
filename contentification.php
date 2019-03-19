<?php
/**
 * Plugin Name: Contentification
 * Plugin URI: htttps://freshclicks.net/wp-plugins/contentification
 * Description: Gamafiy content performance by showing GA scores around bounce rates and goal completions.
 * Version: 3.1.5
 * Author: FreshClicks
 * Author URI: htttps://freshclicks.net/
 * License: GPLv3
 * Text Domain: contentification
 * Domain Path: /language
 */

defined( 'ABSPATH' ) or die;

/**
 * 
 * ADMIN SECTION
 * 
 */

// Register the menu.
add_action( "admin_menu", "contentification_menu" );
function contentification_menu() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "Contentification",            // Page title
                  "Contentification",            // Menu title
                  "manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
                  "fc-contentification",            // Menu slug
                  "fc_contentification_options"     // Callback that prints to the admin section
			   );
	add_submenu_page( "options-general.php",  // Which menu parent
                  "Contentification Sync",            // Page title
                  "Contentification Sync",            // Menu title
                  "manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
                  "fc-contentification-sync",            // Menu slug
                  "contentification_get_analytics_data"     // Callback that prints to the admin section
    );
} 

// Print the markup for the page
function fc_contentification_options() {
	if ( !current_user_can( "manage_options" ) )  {
		 wp_die( __( "You do not have sufficient permissions to access this page." ) );
	}
	?>
	<form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
		<input type="hidden" name="action" value="update_contentificaition_settings" />
		<h1><?php _e("Content Gamification", "contentificaition"); ?></h1>
		<p>Contentificaition, is a WordPress plugin that attempts to gamify your content. 
		We do this by pulling in the last 30 days worth of Google Analytics data along with bounce rate and goals completion.
		We then attempt to weight the results based on relative importance of the page to the site 
		(how much traffic does this page get compared to the other pages on the site), then how well does this page meet a site goal.
		</p>
		<h2>Set-up</h2>
		<p>This is a little complicated, so buckle up!<p>
		<p></p>
		<p>	
		<ol>
			<li>Get access to the Google API
				<ol>
					<li>
						<?php 
							echo sprintf( __( '%sclick here%s to select or create a new Google Project - ( MUST be logged in to your Google account )', 'contentificaition' ), '<a target="_blank" href=\'https://console.developers.google.com/\'>', '</a>' ) 
						?>
					</li>
					<li>In the Google Project page, Give your project a name and click the "create" button.</li>
					<li>After the project is created, click the "+ Enable APIs and Services" button. Click the "Google Analytics API" tile, then the "enable" button.</li>
					<li>Once the "overview" page loads, click the "Credentials" link in the left column, then the "Create Credentials" > service account key.</li>
					<li>Select new service account, give it a name, choose Role > "project > viewwe", key Type: JSON. Click the "create" button.</li>
					<li>Save to your desktop. Open the file and copy the "client email" from the string.</li>
					<li>In Google Analytics admin, add the "client email" as user with read access</li>
					<li>Upload the JSON file to a safe place on your website.</li>
					<li>Record the path the the and name of the JSON file (ignore the begining "/"): <input class="" type="text" name="contentification_path" value="<?php echo trim(get_option('contentification_path')); ?>" /></li>
				</ol>
			<li>Choose the Google Analytics view you want to use: <input class="" type="text" name="contentification_analytics_view" value="<?php echo trim(get_option('contentification_analytics_view')); ?>" /></li>
			<li>Save: <input class="button button-primary" type="submit" value="<?php _e("Save", "contentificaition"); ?>" />	
		</ol>
		</form>
		<h2>Sync GA with your site</h2>
		<?php 
			echo sprintf( __( '%sSync%s', 'contentificaition' ), '<a class="button button-primary" href=\'options-general.php?page=fc-contentification-sync\'>', '</a>' ) 
		?>

	<?php
		//print get_option('contentification_path'); 
		//print get_option('contentification_analytics_view');
		// print get_home_path(). $saved_location;

}

add_action( 'admin_post_update_contentificaition_settings', 'contentification_handle_save' );

function contentification_handle_save() {

	// Get the options that were sent
	$secret_path = (!empty($_POST["contentification_path"])) ? $_POST["contentification_path"] : NULL;
	$analytics_view = (!empty($_POST["contentification_analytics_view"])) ? $_POST["contentification_analytics_view"] : NULL;
 
	// Validation would go here
 
	// Update the values
	update_option("contentification_path", trim($secret_path), TRUE);
	update_option("contentification_analytics_view", trim($analytics_view), TRUE);
 
	// Redirect back to settings page
	// The ?page=fc-contentification corresponds to the "slug" 
	// set in the fourth parameter of add_submenu_page() above.
	$redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=fc-contentification&status=success";
	header("Location: ".$redirect_url);
	exit;
 }
/**
 * 
 * Add column to sort - where the things get really interesting
 * 
 */
// ADD NEW COLUMN
add_filter( 'manage_posts_columns', 'contentification_filter_posts_columns' );
function contentification_filter_posts_columns( $columns ) {
    $columns['contentification'] = __( 'Contentification' );
    return $columns;
}

add_filter( 'manage_hoagp-physicians_posts_columns', 'contentification_filter_posts_columns' );


function contentification_filter_posts_hoagp_physicians_columns( $columns ) {
    $columns['contentification'] = __( 'Contentification' );
    return $columns;
}

add_filter( 'manage_pages_columns', 'contentification_filter_posts_columns' );


function contentification_filter_pages_columns( $columns ) {
    $columns['contentification'] = __( 'contentification' );
    return $columns;
}

add_action( 'manage_posts_custom_column', 'contentification_details_column', 10, 2);
add_action( 'manage_posts_hoagp-physicians_custom_column', 'contentification_details_column', 10, 2);
add_action( 'manage_pages_custom_column', 'contentification_details_column', 10, 2);

function contentification_elevation($var){
	if($var >= 80){$bounce_notice ='severe';}
	elseif(($var < 80)&&($var > 70)){$bounce_notice ='high';}
	elseif(($var <= 70)&&($var > 60)){$bounce_notice ='elevated';}
	elseif(($var <= 60)&&($var > 50)){$bounce_notice ='guarded';}
	else{$bounce_notice ='low';}
	
	return $bounce_notice;
}

function contentification_goal_elevation($var){
	if($var < 1){$bounce_notice ='severe';}
	elseif(($var <= 1)&&($var < 2)){$bounce_notice ='high';}
	elseif(($var <= 2)&&($var < 3)){$bounce_notice ='elevated';}
	elseif(($var <= 3)&&($var < 5)){$bounce_notice ='guarded';}
	else{$bounce_notice ='low';}
	
	return $bounce_notice;
}

function get_contentification_styles(){
	$contentification_styles = '<style>.severe{background-color: #ff0000}
				.high{background-color: #ff3300}
				.elevated{background-color: #ff9900}
				.guarded{background-color: #00cc00}
				.low{background-color: #006600}
				.nuetral{background-color: #CCC;}
				.score-box{padding: 5px; color: #FFF;width: 25%;text-align: center;display: inline-block; border-style: solid; border-color: #fff; border-width: 1px;}
				.chart{color: #FFF; padding: 0px 5px;}
				</style>';
	return $contentification_styles;
}

function contentification_details_column( $column, $post_id ) {  
	if ( $column == 'contentification' ) {
		$saved = get_post_meta( $post_id, 'contentification', true ); // Get the saved values
		$defaults = contentification_meta_box_defaults(); // Get the default values
		$details = wp_parse_args( $saved, $defaults ); // Merge the two in case any fields don't exist in the saved data
		
		print get_contentification_styles();
		
		if($details['bouncerate']){
			print '<small><span class="score-box '.contentification_elevation($details['bouncerate']).'">Bounces: '.$details['bouncerate'].'</small></span>';
		}else{
			print '<small><span class="score-box nuetral">Bounces: N/A</small></span>';
		}
		if($details['goal_rate']){
			print '<small><span class="score-box '.contentification_goal_elevation($details['goal_rate']).'">Goals: '.$details['goal_rate'].'</small></span>';
		}else{
			print '<small><span class="score-box nuetral">Goals: N/A</small></span>';
		}
		if($details['exitrate']){
			print '<small><span class="score-box '.contentification_elevation($details['exitrate']).'">Exit: '.$details['exitrate'].'</small></span>';
		}else{
			print '<small><span class="score-box nuetral">Exit: N/A</small></span>';
		}
	}//end column
	

}
  

function contentification_meta_box() {
	//get all post types
	$screens = get_post_types();
	//itterate over each post type so that all types get the meta value
	foreach ( $screens as $screen ) {
			add_meta_box(
					'contentification',
					__( 'Content Gamification', 'contentification' ),
					'contentification_render_meta_box',
					$screen, 'side'
			);
	}
}
add_action( 'add_meta_boxes', 'contentification_meta_box');


//create default values
function contentification_meta_box_defaults() {
	return array(
			'start_date' => '',
			'end_date' => '',
			'pageviews' => '',
			'bouncerate' => '',
			'exitrate' => '',
			'percentage_pageviews' => '',
			'goals' => '',
			'goal_rate' => '',
			'goal_weight' => ''
	);
}


// This is the function called in `contentification_meta_box()`
function contentification_render_meta_box() {
	// Variables
	global $post; // Get the current post data
	$saved = get_post_meta( $post->ID, 'contentification', true ); // Get the saved values
	$defaults = contentification_meta_box_defaults(); // Get the default values
	$details = wp_parse_args( $saved, $defaults ); // Merge the two in case any fields don't exist in the saved data
	print get_contentification_styles();
	?>
		<p>How well does this page perform?</p>
		<p>Report range: <?php echo esc_attr( $details['start_date'] ); ?> - <?php echo esc_attr( $details['end_date'] ); ?></p>
		<fieldset>
			<div>
				<?php _e( 'PageViews: ', 'contentification' );?>
				<?php echo esc_attr( $details['pageviews'] ); ?>
			</div>
			<div>
				<?php _e( '% of sites total pageviews: ', 'contentification' );?>
				<?php echo esc_attr( $details['percentage_pageviews'] ); ?>
			</div>
				<?php _e( 'Bounce Rate: ', 'contentification' );?> <span class="chart <?php echo contentification_elevation($details['bouncerate']) ?>"> <?php echo esc_attr( $details['bouncerate'] ); ?></span>
			<div>
				<?php _e( 'Exit Rate: ', 'contentification' );?><span class="chart <?php echo contentification_elevation($details['exitrate']) ?>"> <?php echo esc_attr( $details['exitrate'] ); ?></span>
			</div>
			<div>
				<?php _e( 'Goals Completed: ', 'contentification' );?>
				<?php echo esc_attr( $details['goals'] ); ?>
			</div>
			<div>
				<?php _e( 'Goals as a Percentage of site goals: ', 'contentification' );?>
				<?php echo esc_attr( $details['goal_weight'] ); ?>
			</div>
			<div>
			<?php _e( 'Goal Conversion Rate: ', 'contentification' );?> <span class="chart <?php echo contentification_goal_elevation($details['goal_rate']) ?>"> <?php echo esc_attr( $details['goal_rate'] ); ?></span>
			</div>
		</fieldset>
	<?php
	wp_nonce_field( 'hoag_local_form_metabox_nonce', 'hoag_local_form_metabox_process' );
}

function contentification_get_analytics_data() {
	print '<h2>Contentification Sync</h2>';
	// echo get_the_post_thumbnail( $post_id, array( 80, 80 ) );
	// Load the Google API PHP Client Library.
	require_once __DIR__ . '/google-api-php/vendor/autoload.php';
	// credentials in JSON format. Place them in this directory or
	// change the key file location if necessary.
	$saved_location = get_option('contentification_path');
	$KEY_FILE_LOCATION = get_home_path('.'). $saved_location;;
	// Create and configure a new client object.
	$client = new Google_Client();
  	//$client->setApplicationName("Hello Analytics Reporting");
	$client->setAuthConfig($KEY_FILE_LOCATION);
	$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
 
	$analytics = new Google_Service_Analytics( $client );

	// Google Analytics account view ID
	//get_option("contentification_analytics_view")
	$analytics_id = get_option('contentification_analytics_view');

	// Get unique pageviews and average time on page.
	try {
		$optParams = array();
		// Required parameter
  		//$metrics    = 'ga:uniquePageviews,ga:avgTimeOnPage';
  		$metrics    = 'ga:pageviews, ga:bounceRate, ga:exitRate';
  		$goals = 'ga:goalCompletionsAll';
  		$start_date = date('Y-m-d', strtotime('-30 days'));
  		$end_date   = date('Y-m-d', strtotime('-1 days'));

  		// Optional parameters
		// optParams['filters']      = 'ga:pagePath==/';
		$optParams['filters'] = 'ga:pagePath!@?';
  		$optParams['dimensions']  = 'ga:pagePath';
  		$optParams['sort']        = '-ga:pageviews';
  		$optParams['max-results'] = '10000';

		//Optional paramaters for goals
		$optParamsGoals['filters'] = 'ga:goalCompletionLocation!@?'; 
  		$optParamsGoals['dimensions']  = 'ga:goalCompletionLocation';
  		$optParamsGoals['sort']        = 'ga:goalCompletionLocation';
  		$optParamsGoals['max-results'] = '10000';

  		$pageviews = $analytics->data_ga->get( $analytics_id,
            $start_date,
            $end_date, $metrics, $optParams);

  		$pageviewtotals = $analytics->data_ga->get( $analytics_id,
            $start_date,
            $end_date, $metrics);

  		$goalpages = $analytics->data_ga->get( $analytics_id,$start_date,$end_date, $goals, $optParamsGoals);
  
			$goaltotals = $analytics->data_ga->get( $analytics_id,$start_date,$end_date, $goals);
			
		//get total pageviews
		if( $pageviewtotals->getRows() ) {
			//print_r( $pageviewtotals->getRows() );
			$pageview_totals = $pageviewtotals->getRows();
			$pageview_total = $pageview_totals[0][0];
		}
		//get total goals
		if( $goaltotals->getRows() ) {
			//print_r( $goaltotals->getRows() );
			//print_r( $pageviewtotals->getRows() );
			$goal_totals = $goaltotals->getRows();
			$goal_total = $goal_totals[0][0];
		}
        
    	// get individual page views
    	if( $pageviews->getRows() ) {     
			$pageview_array = $pageviews->getRows();
			$count = 0;
			$goal_count = 0;
        	foreach($pageview_array as $pages){
                // find ID of page in WP
                $post_id = url_to_postid($pages[0]);
                //look to see if there is a match in WP
                if($post_id){
                    //if true
                    //set page weight as a percentage of traffic
					$percent_traffic = round(($pages[1] / $pageview_totals[0][0] * 100), 2);

					//lets unset an previous set goal values
					unset($goals);
					$goal_weight = 0;
					$page_goals = 0;
					$goal_conversion_rate = 0;
					//lets check to see if there is a goal set for this
					if( $goalpages->getRows() ) {
						//loop through each goal result
						foreach($goalpages->getRows() as $goals){
							//if there is a match
							if ($pages[0] == $goals[0]){
								$goal_weight = ($goals[1] / $goal_total)*100;
								$page_goals  = $goals[1];
								$goal_conversion_rate = round(($goals[1] / $pages[1] * 100), 2);
								$goal_count++;
								//print '<strong> GOAL '.$goals[1].' FOUND: </strong>';
								//break out of current loop but not outer loop
								continue;
							}
						}
					}
					// insert sessions into wp custom meta field
					$meta_key = 'contentification';
					$meta_value = array(
                        'start_date' => $start_date,
                        'end_date' => $end_date,
						'pageviews' => $pages[1],
						'bouncerate' => round($pages[2],2).'%',
						'exitrate' => round($pages[3],2).'%',
						'percentage_pageviews' => $percent_traffic.'%',
						'goals' => $page_goals,
						'goal_rate' => $goal_conversion_rate.'%',
                        'goal_weight' => round($goal_weight).'%'
					);
					// Save our submissions to the database
					update_post_meta( $post_id, $meta_key, $meta_value);
					$count++;
					print $pages[0].' with '.$pages[1].' pageviews, '.$pages[2].' bounces &amp; '.$page_goals.' <strong>goals...</strong>';
                    print '<br>';
                }//end if match found
			}//end foreach loop
			if($goal_count > 0){
				$report_goals_found = $goal_count.' goals set.';
			}
			print '<h3>COMPLETED: '.$count.' analytics added &amp; '.$report_goals_found.'</h3>';

		}//end pageview if true	
	} catch(Exception $e) {
  		echo 'There was an error : - ' . $e->getMessage();
	} 
}

/**
 * 
 * 
 * Cron-job rules
 * 
 * 
 */

// create a scheduled event (if it does not exist already)
function cronstarter_activation() {
	if( !wp_next_scheduled( 'contification_cronjob' ) ) {  
	   wp_schedule_event( time(), 'daily', 'contification_cronjob' );  
	}
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activation');

// unschedule event upon plugin deactivation
function cronstarter_deactivate() {	
	// find out when the last event was scheduled
	$timestamp = wp_next_scheduled ('contification_cronjob');
	// unschedule previous event if any
	wp_unschedule_event ($timestamp, 'contification_cronjob');
} 
register_deactivation_hook (__FILE__, 'cronstarter_deactivate');
 
// hook that function onto our scheduled event:
add_action ('contification_cronjob', 'cronstarter_activation'); 