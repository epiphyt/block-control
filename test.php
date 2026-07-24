<?php
// current > start > end
$current_time = 1701767621;
$hide_date_start = 1698228193;
$hide_date_end = 1698055396;
$should_be_hidden = false;

// current > end > start
$current_time = 1701767621;
$hide_date_start = 1698055396;
$hide_date_end = 1698228193;
$should_be_hidden = false;

// start > current > end
$current_time = 1698228193;
$hide_date_start = 1701767621;
$hide_date_end = 1698055396;
$should_be_hidden = false;

// start > end > current
$current_time = 1698055396;
$hide_date_start = 1701767621;
$hide_date_end = 1698228193;
$should_be_hidden = false;

// end > current > start
$current_time = 1698228193;
$hide_date_start = 1698055396;
$hide_date_end = 1701767621;
$should_be_hidden = true;

// end > start > current
$current_time = 1698055396;
$hide_date_start = 1698228193;
$hide_date_end = 1701767621;
$should_be_hidden = false;

$is_hidden = false;

if ( $current_time > $hide_date_start ) {
	$is_hidden = true;
	
	if (
		( $hide_date_start > $hide_date_end && $current_time > $hide_date_end )
		|| ( $hide_date_start <= $hide_date_end && $current_time < $hide_date_end )
	) {
		echo 'hit1' . PHP_EOL;
		// break;
	}
	else {
		echo 'hit2' . PHP_EOL;
		$is_hidden = false;
	}
}

if ( $current_time <= $hide_date_end ) {
	$is_hidden = true;
	
	if (
		( $hide_date_end > $hide_date_start && $current_time > $hide_date_start )
		|| ( $hide_date_end <= $hide_date_start && $current_time > $hide_date_start )
	) {
		echo 'hit3' . PHP_EOL;
		// break;
	}
	else {
		echo 'hit4' . PHP_EOL;
		$is_hidden = false;
	}
}

var_dump( $should_be_hidden, $is_hidden );
