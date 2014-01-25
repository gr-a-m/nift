<?php
ob_start('OB_GZHANDLER');
// The format of the url accessing this script is important. The first
// variable is the command. 0 accesses initial data, 1 retrieves dates,
// 2 retrieves keywords, and 3 retrieves coordinates. After that, a flag
// is passed that states the first filter. Following that is a list of
// terms to filter by until the next flag is reached. Following the last
// flag is the list of the rest of the filter terms.

// This include is needed for the porter stemming algorithm to be implemented.
include ('class.stemmer.inc');
date_default_timezone_set('UTC');

// Change these to calibrate how many dates and keywords you want to echo in 
// response.
$NUM_DATES = 16;
$NUM_WORDS = 50;

// Set the URL of the stopword file
$STOPWORD_URL = "../data/stopwords.txt";

// Initialize some default values
set_time_limit(120);
$command = 0;
$param_length = 0;
$filter_keywords = false;
$filter_geography = false;
$filter_date = false;
$keywords = array();
$dates = array();
$areas = array();
$bounds = array();

// Database information
$database = "../data/ows_grant.db";

foreach ($_GET as $i) {
  $param_length++;
}

$breakpoint = 1;

// The first parameter determines how the url is interpreted.
$command = (int)$_GET[0];

// The command must be between 1 and 3 to be a valid command.
if ($command >= 1 && $command <= 3) {
  // This is the command to get dates
  if ($command == 1) {
    $filter_geography = (bool)$_GET[1];
    // if the second parameter is "0" this will be false

    if ($filter_geography) {
      for ($i = 2; $i < $param_length; $i++) {
        if ($_GET[$i] != "0" && $_GET[$i] != "1") {
          array_push($bounds, $_GET[$i]);
        } else {
          $breakpoint = $i;
          break;
        }
      }

      $filter_keywords = (bool)$_GET[$breakpoint];

      if ($filter_keywords) {
        for ($i = $breakpoint + 1; $i < $param_length; $i++) {
          array_push($keywords, $_GET[$i]);
        }
      }
    } else {
      $filter_keywords = (bool)$_GET[2];

      if ($filter_keywords) {
        for ($i = 3; $i < $param_length; $i++) {
          array_push($keywords, $_GET[$i]);
        }
      }
    }

    retrieve_dates($filter_keywords, $keywords, $bounds);
  } else if ($command == 2) {
    // This is the command for retrieving keywords
    // if the second parameter is "0" this will be false
    $filter_geography = (bool)$_GET[1];
    $breakpoint = 2;

    if ($filter_geography) {
      for ($i = 2; $i < $param_length; $i++) {
        if ($_GET[$i] != "0" && $_GET[$i] != "1") {
          array_push($bounds, $_GET[$i]);
        } else {
          $breakpoint = $i;
          break;
        }
      }

      $filter_date = (bool)$_GET[$breakpoint];

      if ($filter_date) {
        for ($i = $breakpoint + 1; $i < $param_length; $i++) {
          array_push($dates, $_GET[$i]);
        }
      }
    } else {
      $filter_date = (bool)$_GET[2];

      if ($filter_date) {
        for ($i = 3; $i < $param_length; $i++) {
          array_push($dates, $_GET[$i]);
        }
      }
    }

    retrieve_keywords($filter_date, $dates, $bounds);
  } else if ($command == 3) {
    // This is the command to retrieve locations.
    // if the second parameter is "0" this will be false
    $filter_keywords = (bool)$_GET[1];

    if ($filter_keywords) {
      for ($i = 2; $i < $param_length; $i++) {
        if ($_GET[$i] != "0" && $_GET[$i] != "1") {
          array_push($keywords, $_GET[$i]);
        } else {
          $breakpoint = $i;
          break;
        }
      }

      $filter_date = (bool)$_GET[$breakpoint];

      if ($filter_date) {
        for ($i = $breakpoint + 1; $i < $param_length; $i++) {
          array_push($dates, $_GET[$i]);
        }
      }
    } else {
      $filter_date = (bool)$_GET[2];

      if ($filter_date) {
        for ($i = 3; $i < $param_length; $i++) {
          array_push($dates, $_GET[$i]);
        }
      }
    }

    retrieve_points($filter_keywords, $keywords, $filter_date, $dates);
  }
}

//------------------------------------------------------------------------------
// This function retrieves the latitude and longitude values of the tweets that 
// contain the text specified by filter_keywords and occured on one of the dates
// specified by filter_date. These points are returned in the format "lat,lon\n"
//------------------------------------------------------------------------------
function retrieve_points($filter_keywords, $keywords, $filter_date, $dates) {
  global $database;
  $connection = new SQLite3($database, SQLITE3_OPEN_READONLY);
	$connection->busyTimeout(100);
  
  $query = "SELECT lat, lon FROM tweetProperties2 JOIN(SELECT docid as id, tweetText FROM tweetTexts2) USING(id) where lat <> 0.0 AND lon <> 0.0 ";

  if ($filter_date) {
    $query .= " AND (";

    for ($i = 0; $i < count($dates); $i++) {
      $date = new DateTime($dates[$i]);
      $query .= "time BETWEEN " . $date->getTimestamp() .
				" AND " . ($date->getTimestamp() + 86400);
      if ($i < count($dates) - 1) {
        $query .= " OR ";
      }
    }

    $query .= ")";
  }

  if ($filter_keywords) {
    $query .= " AND tweetText MATCH '";
    for ($i = 0; $i < count($keywords); $i++) {
      $query .= $keywords[$i];
      if ($i < count($keywords) - 1) {
        $query .= " OR ";
      } else {
        $query .= "';";
      }
    }
  }

  $result = $connection->query($query);
  
  if (!$result) {
    $message = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
  }

  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo $row['lat'] . "," . $row['lon'] . '\n';
  }
	$result->finalize();
}

//------------------------------------------------------------------------------
// Retrieve keywords gets the top keywords from the database based on the filter
// dates and locations. These values are then echoed in the format 
// "keyword,frequency\n" so that it can be accessed using AJAX and the return 
// can be parsed and used in an application.
//------------------------------------------------------------------------------
function retrieve_keywords($filter_date, $dates, $bounds) {
  // So that the function can access the constant declared at the top of the file.
  global $database;
  global $NUM_WORDS;
	global $STOPWORD_URL;
  global $filter_geography;
  $connection = new SQLite3($database, SQLITE3_OPEN_READONLY);
  $connection->busyTimeout(100);

  $query = "SELECT tweetText FROM tweetTexts2 JOIN (SELECT tweetProperties2.id as docid FROM tweetProperties2";

  $stopwords = array();

  $file = fopen($STOPWORD_URL, "r");

  while (!feof($file)) {
    $line = explode(",", fgets($file));

    for ($i = 0; $i < count($line); $i++) {
      $stopwords[trim($line[$i])] = 1;
    }
  }

  if ($filter_geography || $filter_date) {
    $query .= " WHERE ";
  }

  if ($filter_geography) {
    $query .= "(";
    for ($i = 0; $i < count($bounds); $i+=4) {
      if ($i > 0) {
        $query .= "OR ";
      }
      $query .=  "(lat BETWEEN " . $bounds[$i+2] . " AND " . $bounds[$i+0] . " AND lon BETWEEN " . $bounds[$i+1] . " AND " . $bounds[$i+3] . ") ";
    }
    $query .= ")";
  }

  if ($filter_date) {
    $query .= " AND (";

    for ($i = 0; $i < count($dates); $i++) {
			$date = new DateTime($dates[$i]);
      $query .= "time BETWEEN " . $date->getTimestamp() .
				" AND " . ($date->getTimestamp() + 86400);
      if ($i < count($dates) - 1) {
        $query .= " OR ";
      }
    }
    $query .= ")";
  }
  $query .= ") USING(docid);";

  $result = $connection->query($query);

  if (!$result) {
    $message = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
  }

  $values = array();
  $top_words = array();

  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $words = explode(" ", $row['tweetText']);
    for ($i = 0; $i < count($words); $i++) {
      $word = trim($words[$i], " ~\"/,:-._><\'");
      $word = strtolower($word);

      if (!array_key_exists($word, $stopwords) && (substr($word, 0, 4) != "http")
          && ($word{0} != '@') && ($word{0} != '#') && $word != "") {
        if (array_key_exists($word, $values)) {
          $values[$word]++;
        } else {
          $values[$word] = 1;
        }
      }
    }
  }
	$result->finalize();


  asort($values);

  $values = array_splice($values, -1 * $NUM_WORDS);

  foreach ($values as $key => $value) {    
    // If the key is empty or present in the stopwords associative array, skip
    if ($key == "" || array_key_exists($key, $stopwords)) {
      continue;
    }
    
    if (!array_key_exists($key, $top_words)) {
      $top_words[$key] = $value;
    } else {
      $top_words[$key] = $value + $top_words[$key];
    }
  }

  // Put the top_words in alphabetic order
  asort($top_words);

  foreach ($top_words as $key => $value) {
    echo $key . "," . $value . "\n";
  }
}

//------------------------------------------------------------------------------
// This function retrieves the top dates from the database based on the filter 
// keywords and bounds. This particular function retrieves a number of dates 
// equal to the NUM_DATES variable. These dates are returned in the format 
// "YYYY-MM-DD,frequency\n".
//------------------------------------------------------------------------------
function retrieve_dates($filter_keywords, $keywords, $bounds) {
  global $database;
  global $NUM_DATES;
  global $filter_geography;
  $connection = new SQLite3($database, SQLITE3_OPEN_READONLY);
  $connection->busyTimeout(100);

  $query = "SELECT day, count(*) as ct FROM tweetProperties2 JOIN(SELECT docid as id, tweetText FROM tweetTexts2) USING(id)";

  if ($filter_geography || $filter_keywords) {
    $query .= " WHERE ";
  }

  if ($filter_geography) {
    $query .= "(";
    for ($i = 0; $i < count($bounds); $i+=4) {
      if ($i > 0) {
        $query .= "OR ";
      }
      $query .=  "(lat BETWEEN " . $bounds[$i+2] . " AND " . $bounds[$i+0] . " AND lon BETWEEN " . $bounds[$i+1] . " AND " . $bounds[$i+3] . ") ";
    }
    $query .= ")";
  }

  if ($filter_keywords) {
    $query .= " AND tweetText MATCH '";
    for ($i = 0; $i < count($keywords); $i++) {
      $query .= $keywords[$i];
      if ($i < count($keywords) - 1) {
        $query .= " OR ";
      } else {
				$query .= "'";
			}
    }
  }
  $query .= " GROUP BY day ORDER BY ct DESC LIMIT " . $NUM_DATES . ";";
  
  $results = $connection->query($query);

  if (!$results) {
    $message = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
  }

  $date_map = array();
  while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    $date_map[$row['day']] = $row['ct'];
  }
	$results->finalize();

  foreach ($date_map as $key => $value) {
    echo $key . "," . $value . "\n";
  }
}
?>