<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../globals.php';


if (!empty($terms)) {

    $sql = "SELECT post_id FROM posts";
    $sql .= " WHERE source = 'TW'";
    $sql .= " ORDER BY post_id DESC";
    $sql .= " LIMIT 1";

    $since_id = 0;
    $stmt = $DBH->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) { 
           $since_id = $row->post_id;
        }
    }

    $settings = array(
        'oauth_access_token' => TWITTER_ACCESS_TOKEN,
        'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET,
        'consumer_key' => TWITTER_CONSUMER_KEY,
        'consumer_secret' => TWITTER_CONSUMER_SECRET
    );
   
            echo "\n\n" . SITE_TITLE . " - Twitter EXTRACT \n\n ";            

            $totalcount = 0;
            $count = 0;
            
            //foreach ($terms as $term) {

                $url = 'https://api.twitter.com/1.1/search/tweets.json';
                $requestMethod = 'GET';
                $getfield = "?" . TWITTER_PREFIX . TERM1;
                $getfield .= "+%23" . TERM2;
                //$getfield = "?q=%23[HASHTAG_GOES_HERE]%20%40[MENTION_OR_@]";
                //$getfield .= "%20filter:links";
                $getfield .= "&count=100";
                $getfield .= "&result_type=recent";

                if ($since_id > 0) {
                    $getfield .= "&since_id=$since_id";
                }

                $twitter = new TwitterAPIExchange($settings);
                $results = $twitter->setGetfield($getfield)
                        ->buildOauth($url, $requestMethod)
                        ->performRequest();
                $obj = json_decode($results);

                foreach ($obj->statuses as $tweet) {
                                        
                    $post_id = $Func->sanitize($tweet->id);
                    $created_at = date("Y-m-d H:i:s", strtotime($tweet->created_at));
                    $created_date = date("Y-m-d", strtotime($tweet->created_at));
                    $created_time = date("H:i:s", strtotime($tweet->created_at));
                    $text = $Func->sanitize($tweet->text);                    
                    $user_id = $Func->sanitize($tweet->user->id);
                    $name = $Func->sanitize($tweet->user->name);
                    $screen_name = $Func->sanitize($tweet->user->screen_name);
                    $profile_image_url = $Func->sanitize($tweet->user->profile_image_url);
                    $source = "TW";
                    $link = "https://twitter.com/$screen_name/status/$post_id";
                    
                    if ($Func->is_blocked_user($screen_name)) {
                        $not_valid = TRUE;
                        echo $screen_name . " - is blocked user. \n";
                    }
                    
                    if ($Func->alreadyEntered($DBH, $post_id, $user_id, 'TW')) {
                        $not_valid = TRUE;
                        echo $post_id . " - already entered \n";
                    }

                    // assuming retweets are not valid
                    if (isset($tweet->retweeted_status)){
                        $not_valid = TRUE;
                        echo $post_id . " - is a retweet \n";
                    }
                
                    // entities->media is only set when there is a photo uploaded with the tweet
                    if (isset($tweet->entities->media[0]->media_url)) {
                        if ($tweet->entities->media[0]->type == 'photo') {
                            $source_url = $Func->sanitize($tweet->entities->media[0]->media_url);
                            $thumb_url = $source_url . ":thumb";
                            $type = "Image";
                        }
                    } else {
                        // if it contains the hashtag but no photo
                        $not_valid = TRUE;
                        echo $post_id . " - images only \n";
                    }                  


                    if (!isset($not_valid)) {                        
                        
                        $count++;
                        $totalcount++;
                        
                        
                        $sql = "INSERT INTO posts set";
                        $sql .= " post_id = :post_id";
                        $sql .= ", screen_name = :screen_name";
                        $sql .= ", user_id = :user_id";
                        $sql .= ", text = :text";
                        $sql .= ", profile_img_url = :profile_image_url";
                        $sql .= ", created_at = :created_at";
                        $sql .= ", created_date = :created_date";
                        $sql .= ", created_time = :created_time";
                        $sql .= ", source_url = :source_url";
                        $sql .= ", thumb_url = :thumb_url";
                        $sql .= ", link = :link";
                        $sql .= ", type = :type";
                        $sql .= ", source = :source";
               
                        try {

                            $stmt = $DBH->prepare($sql);
                            
                            $stmt->bindValue(':post_id', $post_id, PDO::PARAM_STR);
                            $stmt->bindValue(':screen_name', $screen_name, PDO::PARAM_STR);
                            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                            $stmt->bindValue(':text', $text, PDO::PARAM_STR);
                            $stmt->bindValue(':profile_image_url', $profile_image_url, PDO::PARAM_STR);
                            $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
                            $stmt->bindValue(':created_date', $created_date, PDO::PARAM_STR);
                            $stmt->bindValue(':created_time', $created_time, PDO::PARAM_STR);
                            $stmt->bindValue(':source_url', $source_url, PDO::PARAM_STR);
                            $stmt->bindValue(':thumb_url', $thumb_url, PDO::PARAM_STR);
                            $stmt->bindValue(':link', $link, PDO::PARAM_STR);
                            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                            $stmt->bindValue(':source', $source, PDO::PARAM_STR);
                            
                            $stmt->execute();
                        } catch (Exception $ex) {
                            echo "There was an issue - " . $ex->getMessage();
                            exit();
                        } catch (PDOException $e) {
                            echo "There was a problem with the database - " . $e->getMessage();
                            exit();
                        }
                        
                        echo "$totalcount done - @$screen_name - $created_at \n";

                    }
                }
            //}
            
            if ($count == 0) {
                echo "No entries to import \n";
            } else {
                echo "ALL DONE! \n";
            }
        }
       
