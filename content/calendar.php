<?php

require_once 'Zend/Feed.php';
require_once 'Zend/Cache.php';

function format_atom_datetime($atom_date,$format='m/d/Y h:i:s a'){
    $pattern = "/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/";
    preg_match($pattern,$atom_date,$match);
    return date($format,gmmktime($match[4],$match[5],$match[6],$match[2],$match[3],$match[1]));
}

$query = $service->newEventQuery();
$query->setUser('default');
// Set to $query->setVisibility('private-magicCookieValue') if using
// MagicCookie auth
$query->setVisibility('private');
$query->setProjection('full');
$query->setOrderby('starttime');
$query->setFutureevents('true');

// Retrieve the event list from the calendar server
try {
    $eventFeed = $service->getCalendarEventFeed($query);
} catch (Zend_Gdata_App_Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Iterate through the list of events, outputting them as an HTML list
echo "<ul>";
foreach ($eventFeed as $event) {
    echo "<li>" . $event->title . " (Event ID: " . $event->id . ")</li>";
}
echo "</ul>";

//your calendar feed URL
$feed_url = 'http://www.google.com/calendar/feeds/20b3mrhuml4qlhsjo48uhkndtk%40group.calendar.google.com/public/full';

//set cache options
$frontendOptions = array(
    'lifeTime' => 3600 // cache lifetime of 1 hour
);
$backendOptions = array(
    'cacheDir' => 'cache'
);

//grab the file from cache, or fetch it from Google and cache it
$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
if (!($xml = $cache->load('GoogleCalendar'))) {
    if (!($xml = @file_get_contents($feed_url))){
        echo "Oops. Can't grab this Calendar from Google. Check your feed URL and calendar permissions";
        exit;
    }
    $cache->save($xml);
}

//read the xml into a Zend_Feed object
try {
    $cal = Zend_Feed::importString($xml);
} catch (Zend_Feed_Exception $e) {
    echo "Oops. Can't import this feed: {$e->getMessage()}\n";
    exit;
}

//display each event
echo "<dl>\n";
foreach ($cal as $item) {
    echo "<dt>".$item->title()."</dt>\n";
    echo "<dd>Posted By ".$item->author->name()."</dd>\n";
    echo "<dd>".$item->content()."</dd>\n";
    echo "<dd>Published: ".format_atom_datetime($item->published())."</dd>\n";
    echo "<dd>Last Updated: ".format_atom_datetime($item->updated())."</dd>\n";
}
echo "</dl>\n";