<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../globals.php';

$settings = array(
    'oauth_access_token' => TWITTER_ACCESS_TOKEN,
    'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET,
    'consumer_key' => TWITTER_CONSUMER_KEY,
    'consumer_secret' => TWITTER_CONSUMER_SECRET
);


//$url = 'https://api.twitter.com/1.1/search/tweets.json';
$url = "https://api.twitter.com/1.1/statuses/update.json";
$requestMethod = 'POST';
$postfields = array(
    'status' => "#test tweet response"
);

$twitter = new TwitterAPIExchange($settings);

echo $twitter->buildOauth($url, $requestMethod)
             ->setPostfields($postfields)
             ->performRequest();

