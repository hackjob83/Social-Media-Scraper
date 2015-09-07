<?php
// during dev and testing
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// for production
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set("log_errors", 1);
// ini_set("error_log", "/path/to/error_log.log");

require_once '../globals.php';

function get_images($dbh) {
    $query = "SELECT post_id,source_url FROM posts WHERE disq = 0 AND downloaded = 0";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    if (!empty($result)) {
        foreach ($result as $row) {
            $link = $row->source_url;
            $post_id = $row->post_id;
            
            // get image and save to dir
            file_put_contents('/path/to/where/images/will/live/'.$post_id.'.jpg', file_get_contents($link));
            
            $query2 = "UPDATE posts SET downloaded = 1 WHERE post_id = '" . $post_id . "'";
            $stmt2 = $dbh->prepare($query2);
            $stmt2->execute();
            
            echo $post_id . " - image downloaded \n";
        }
    } else {
        echo "No images to import. \n";
    }
    
}

get_images($DBH);



