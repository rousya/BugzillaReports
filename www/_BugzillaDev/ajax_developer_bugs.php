<?php
/*
	Copyright by Tomas Rapkauskas bugzilla_base/license.txt 
	All rights reserved.
	To use this component please contact slidertom@gmail.com to obtain a license.
*/

ob_start('ob_gzhandler');

require_once '../_Bugzilla/bugs_fnc.php';
require_once '../_Bugzilla/bugs_start_end_dates.php';
require_once '../bugzilla_base/connect_to_bugzilla_db.php';
require_once 'quarter_developers.php';
require_once 'developer_filters_class.php';

$product_filter;
function filter_by_product($bug)
{
	global $product_filter;
	return $bug->m_product->m_id == $product_filter;
}

function bugs_get_developer_month_bugs(&$dbh, &$users, &$products, $developer_id, $month)
{
    $month_beg; $month_end;
    get_month_begin_end($month, $month_beg, $month_end);
    $bugs = get_worked_developer_bugs_by_dates($dbh, $developer_id, $month_beg, $month_end, $users, $products);
    return $bugs;
}

function bugs_get_developer_year_bugs(&$dbh, &$users, &$products, $developer_id, $year)
{
    $year_beg; $year_end;
    get_year_begin_end($year, $year_beg, $year_end);
    $bugs = get_worked_developer_bugs_by_dates($dbh, $developer_id, $year_beg, $year_end, $users, $products);
    return $bugs;
}

function bugs_by_developer_echo_table(&$dbh, $developer_id, $filter)
{
	$users        = get_user_profiles($dbh); // <userid><login_name>
	$products     = products_get($dbh);
	$bugs;
    
	if ( $filter == DeveloperFilters::Assigned ) {
		$bugs = bugs_get_assigned_by_developer($dbh, $users, $products, $developer_id);
	}
    else if ( $filter == DeveloperFilters::ThisYear ) {
        $year = current_year();
        $bugs = bugs_get_developer_year_bugs($dbh, $users, $products, $developer_id, $year);
        developer_bugs_to_table_by_product($bugs);
    }
    else if ( $filter == DeveloperFilters::PrevYear ) {
        $year = current_year() - 1;
        $bugs = bugs_get_developer_year_bugs($dbh, $users, $products, $developer_id, $year);
        developer_bugs_to_table_by_product($bugs);
    }
    else if ( $filter == DeveloperFilters::ThisMonth ) {
        $month = current_month();
        $bugs = bugs_get_developer_month_bugs($dbh, $users, $products, $developer_id, $month);
        developer_bugs_to_table_by_product($bugs);
        return;
    }
    else if ( $filter == DeveloperFilters::PrevMonth ) {
        $month = current_month() - 1;
        $bugs = bugs_get_developer_month_bugs($dbh, $users, $products, $developer_id, $month);
        developer_bugs_to_table_by_product($bugs);
        return;
    }
	else if ( $filter == DeveloperFilters::PrevQuaterProd ) {
		echo "<br>";
		$bugs = prev_bugs_get_developer_quarter_bugs($dbh, $users, $products, $developer_id);
		developer_bugs_to_table_by_product($bugs);
		return;
	}
	else if ( $filter == DeveloperFilters::PrevQuaterMile ) {
		echo "<br>";
		$bugs = prev_bugs_get_developer_quarter_bugs($dbh, $users, $products, $developer_id);
		quarter_developer_milestone_bugs_to_table($bugs);
		return;
	}
    else if ( $filter == DeveloperFilters::ThisQuaterProd ) {
		echo "<br>";
		$bugs = this_bugs_get_developer_quarter_bugs($dbh, $users, $products, $developer_id);
		developer_bugs_to_table_by_product($bugs);
		return;
	}
	else if ( $filter == DeveloperFilters::ThisQuaterMile ) {
		echo "<br>";
		$bugs = this_bugs_get_developer_quarter_bugs($dbh, $users, $products, $developer_id);
		quarter_developer_milestone_bugs_to_table($bugs);
		return;
	}
	else if ( $filter == DeveloperFilters::Open ) {
		$bugs = bugs_get_by_developer($dbh, $users, $products, $developer_id);
	}
	else if ( strlen($filter) > 0 ) {
		$bugs = bugs_get_by_developer($dbh, $users, $products, $developer_id);
		global $product_filter;
		$product_filter = $filter;
		$bugs = array_filter($bugs, "filter_by_product"); 
	}
	else {
		$bugs = bugs_get_by_developer($dbh, $users, $products, $developer_id);
	}
	 		
	if ( !$bugs || count($bugs) == 0 )
	{
		echo "<h3>There is no bugs fixed.</h3>";
		return;
	}
	
	bugs_update_worked_time($dbh, $bugs);
	bugs_init_start_end_dates($bugs);
	
	$cnt          = count($bugs);
	$work_time    = get_bugs_work_time($bugs);
	
	echo "<br>\n";
	echo "<p><span>Opened bugs count: $cnt</span><span>&nbsp;&nbsp;&nbsp;&nbsp;Remaining time: $work_time&nbsp;h</span></p>";
	
	bugs_echo_table($bugs, " ", "openTable tablesorter");
}

if ( !isset($_GET['Developer']) ) {
	return;
}

$developer_id = $_GET['Developer'];
$filter       = isset($_GET['Filter']) ? $_GET['Filter'] : "open_bugs";

$dbh = connect_to_bugzilla_db();
if ( $dbh == NULL ) {
	return;
}	

bugs_by_developer_echo_table($dbh, $developer_id, $filter);
?>