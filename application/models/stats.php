<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Statistics
 * 
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Incident Model  
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class Stats_Model extends ORM
{
	
	/**
	 * Creates a new site in centralized stat tracker
	 * @param sitename - name of the instance
	 * @param url - base url 
	 */
	static function create_site( $sitename, $url ) 
	{
		$stat_url = 'http://tracker.ushahidi.com/px.php?task=cs&sitename='.urlencode($sitename).'&url='.urlencode($url);
		
		// FIXME: This method of extracting the stat_id will only work as 
		//        long as we are only returning the id and nothing else. It
		//        is just a quick and dirty implementation for now.
		$stat_id = trim(strip_tags($this->_curl_req($stat_url))); // Create site and get stat_id
		
		if($stat_id > 0){
			$settings = ORM::factory('settings',1);
			$settings->stat_id = $stat_id;
			$settings->save();
			return $stat_id;
		}
		
		return false;
	}
	
	static function get_hit_stats($range=31)
	{		
		$settings = ORM::factory('settings', 1);
		$stat_id = $settings->stat_id;
	    
	    $stat_url = 'http://tracker.ushahidi.com/px.php?task=stats&siteid='.urlencode($stat_id).'&period=day&range='.urlencode($range);
		$response = simplexml_load_string(self::_curl_req($stat_url));
		
		$visits = '{label:"Visits",data:[';
		$uniques = '{label:"Uniques",data:[';
		$pageviews = '{label:"Pageviews",data:[';
		$i = 0;
		foreach($response->visits->result as $res) {
			$timestamp = strtotime($res['date'])*1000;
			$date = strtotime($res['date']);
			
			if($i != 0) {
				$visits .= ',';
				$uniques .= ',';
				$pageviews .= ',';
			}
			
			$visits .= '['.$timestamp.',';
			$uniques .= '['.$timestamp.',';
			$pageviews .= '['.$timestamp.',';
			
			if(isset($res->nb_visits)){ 
				$visits .= $res->nb_visits;
				$data['raw'][$date]['visits'] = (string)$res->nb_visits;
			}else{
				$visits .= '0';
				$data['raw'][$date]['visits'] = '0';
			}
			
			if(isset($res->nb_uniq_visitors)){ 
				$uniques .= $res->nb_uniq_visitors;
				$data['raw'][$date]['uniques'] = (string)$res->nb_uniq_visitors;
			}else{
				$uniques .= '0';
				$data['raw'][$date]['uniques'] = '0';
			}
			
			if(isset($res->nb_actions)){ 
				$pageviews .= $res->nb_actions;
				$data['raw'][$date]['pageviews'] = (string)$res->nb_actions;
			}else{
				$pageviews .= '0';
				$data['raw'][$date]['pageviews'] = '0';
			}
			
			$visits .= ']';
			$uniques .= ']';
			$pageviews .= ']';
			
			$i++;
		}
		$visits .= ']}';
		$uniques .= ']}';
		$pageviews .= ']}';
		
		$data['graph'] = "[$visits,$uniques,$pageviews]";
		
		return $data;
	}
	
	static function get_hit_countries($range=31)
	{
		$settings = ORM::factory('settings', 1);
		$stat_id = $settings->stat_id;
	    
	    $stat_url = 'http://tracker.ushahidi.com/px.php?task=stats&siteid='.urlencode($stat_id).'&period=day&range='.urlencode($range);
		$response = simplexml_load_string(self::_curl_req($stat_url));
		
		$data = array();
		foreach($response->countries->result as $res) {
			$date = (string)$res['date'];
			foreach($res->row as $row){
				$code = (string)$row->code;
				$data[$date][$code]['label'] = (string)$row->label;
				$data[$date][$code]['uniques'] = (string)$row->nb_uniq_visitors;
				$data[$date][$code]['logo'] = 'http://tracker.ushahidi.com/piwik/'.(string)$row->logo;
			}
		}
		
		return $data;
		
	}
	
	static function get_report_stats($range=31)
	{
		$reports = ORM::factory('incident')->find_all();
		$reports_categories = ORM::factory('incident_category')->find_all();
		
		// Gather some data into an array on incident reports
		$report_data = array();
		foreach($reports as $report) {
			$timestamp = (string)strtotime(substr($report->incident_date,0,10));
			$report_data[$report->id] = array(
				'date'=>$timestamp,
				'mode'=>$report->incident_mode,
				'active'=>$report->incident_active,
				'verified'=>$report->incident_verified
			);
			
			if(!isset($verified_counts['verified'][$timestamp])) {
				$verified_counts['verified'][$timestamp] = 0;
				$verified_counts['unverified'][$timestamp] = 0;
				$approved_counts['approved'][$timestamp] = 0;
				$approved_counts['unapproved'][$timestamp] = 0;
			}
			
			if($report->incident_verified == 1){
				$verified_counts['verified'][$timestamp]++;
			}else{
				$verified_counts['unverified'][$timestamp]++;
			}
			
			if($report->incident_active == 1){
				$approved_counts['approved'][$timestamp]++;
			}else{
				$approved_counts['unapproved'][$timestamp]++;
			}
		}
	
		$category_counts = array();
		$lowest_date = 9999999999; // Really far in the future.
		$highest_date = 0;
		foreach($reports_categories as $report){
			$c_id = $report->category_id;
			$timestamp = $report_data[$report->incident_id]['date'];
			
			if($timestamp < $lowest_date) $lowest_date = $timestamp;
			if($timestamp > $highest_date) $highest_date = $timestamp;
			
			if(!isset($category_counts[$c_id][$timestamp])) $category_counts[$c_id][$timestamp] = 0;
			
			$category_counts[$c_id][$timestamp]++;
		}
		
		// Populate date range
		$date_range = array();
		$add_date = $lowest_date;
		while($add_date <= $highest_date){
			$date_range[] = $add_date;
			$add_date += 86400;
		}
		
		// Zero out days that don't have a count
		foreach($category_counts as &$arr) {
			foreach($date_range as $timestamp){
				if(!isset($arr[$timestamp])) $arr[$timestamp] = 0;
				if(!isset($verified_counts['verified'][$timestamp])) $verified_counts['verified'][$timestamp] = 0;
				if(!isset($verified_counts['unverified'][$timestamp])) $verified_counts['unverified'][$timestamp] = 0;
				if(!isset($approved_counts['approved'][$timestamp])) $approved_counts['approved'][$timestamp] = 0;
				if(!isset($approved_counts['unapproved'][$timestamp])) $approved_counts['unapproved'][$timestamp] = 0;
			}
			// keep dates in order
			ksort($arr);
			ksort($verified_counts['verified']);
			ksort($verified_counts['unverified']);
			ksort($approved_counts['approved']);
			ksort($approved_counts['unapproved']);
			
		}
		
		// Add all our data sets to the array we are returning
		$data['category_counts'] = $category_counts;
		$data['verified_counts'] = $verified_counts;
		$data['approved_counts'] = $approved_counts;
		
		return $data;
	}
	
	/**
	 * Helper function to send a cURL request
	 * @param url - URL for cURL to hit
	 */
	public function _curl_req( $url )
	{
		// Make sure cURL is installed
		if (!function_exists('curl_exec')) {
			throw new Kohana_Exception('stats.cURL_not_installed');
			return false;
		}
		
		$curl_handle = curl_init();
		curl_setopt($curl_handle,CURLOPT_URL,$url);
		curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,15); // Timeout set to 15 seconds. This is somewhat arbitrary and can be changed.
		curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1); //Set curl to store data in variable instead of print
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);
		
		return $buffer;
	}

}
