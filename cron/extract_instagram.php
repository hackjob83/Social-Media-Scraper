<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once '../globals.php';

if (!empty($terms)) {

    // get the last inserted date for Instagram posts
    $sql = "SELECT post_id FROM posts";
    $sql .= " WHERE source = 'IG'";
    $sql .= " ORDER BY created_at desc";
    $sql .= " LIMIT 1";

    $since_id = '0';

    $stmt = $DBH->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $since_id = (int) $row->post_id;
        }
    }

    echo "\n\n" . SITE_TITLE . " Instagram EXTRACT \n\n";


    $totalcount = 0;

    //foreach ($terms as $term) {

        // Instagram object
        $instagram = new Instagram(IG_CLIENT_ID);
        $result = $instagram->getTagMedia(TERM1, $since_id);
        
        
        // $count = 0;
        // cycle through data
        foreach ($result->data as $media) {            

            $not_valid = FALSE;
            //$blocked = FALSE;

            $tags = $media->tags;
            $link = $media->link;
            $text = (isset($media->caption->text) ? $Func->sanitize($media->caption->text) : '');
            $created_at = date('Y-m-d H:i:s', $media->created_time);
            $created_date = date('Y-m-d', $media->created_time);
            $created_time = date('H:i:s', $media->created_time);

            $screen_name = $Func->sanitize($media->user->username);
            $post_id = $Func->sanitize($media->id);
            $user_id = $Func->sanitize($media->user->id);
            
            if (!in_array(TERM2, $tags)) {
                $not_valid = TRUE;
                echo TERM2 . " - not in tags. \n";
            }
            
            if ($Func->is_blocked_user($screen_name)) {
                $not_valid = TRUE;
                echo $screen_name . " - is blocked user. \n";
            }

            if ($Func->alreadyEntered($DBH, $user_id, $post_id, 'IG')) {
                $not_valid = TRUE;
                echo $user_id . " already entered \n";
            }

	    // Instagram pulls everything "recent" so kick out anything before today            
            if ($created_date < date('Y-m-d')) {
                $not_valid = true;
                echo $user_id . " not today's \n";
            }

            if ($created_at < $range['start'] || $created_at > $range['end']) {
                $not_valid = true;
                echo $user_id . " since date \n";
            }

            if (($not_valid === FALSE)) {

                $profile_image_url = $Func->sanitize($media->user->profile_picture);
                $source = "IG";

                // output media
                if ($media->type == 'image') {

		      // optional to keep track of other tags used in posts
//                    foreach ($tags as $t) {
//                        $Func->add_tags($DBH, $post_id, $t);
//                    }

                    $totalcount++;

                    // no video allowed?
                    $source_url = $Func->sanitize($media->images->standard_resolution->url);
                    $thumb_url = $Func->sanitize($media->images->low_resolution->url);
                    $type = "Image";

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
                } // else {
                //    echo "Videos aren't allowed $screen_name!<br />";
                // }
            }
        }
        //} while ($result = $instagram->pagination($images));
    //}

    if ($totalcount == 0) {
        echo "No entries to import \n";
    } else {
        echo "ALL DONE! \n";
    }
}
       