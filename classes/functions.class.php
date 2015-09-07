<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of functions
 *
 * @author jhackett <hackjob83@gmail.com>
 */
class functions {
    //put your code here

    /**
     * Function that queries the database to determine if currently in an entry period, 
     * and what that range of start and end dates is
     * Returns them in an array $arr['start'] and $arr['end']
     * 
     * @param PDO Handle $dbh
     * @param datetime $now
     * @return array
     */
    public function get_current_range($dbh, $now) {
        $query = "SELECT * FROM entry_periods WHERE start <= :now AND end >= :now2";

        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':now', $now, PDO::PARAM_STR);
        $stmt->bindValue(':now2', $now, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $return = array();

        if (!empty($result)) {
            foreach ($result as $row) {
                $return['start'] = $row->start;
                $return['end'] = $row->end;
            }
        }

        return $return;
    }
    
    
    public function get_all_ranges($dbh) {
        $query = "SELECT * FROM entry_periods ORDER BY id asc";
        
        try {
            $stmt = $dbh->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $range = array();
        
            if (!empty($result)) {
                foreach ($result as $row) {
                    $range[$row->id]['start'] = $row->start;
                    $range[$row->id]['end'] = $row->end;
                }
            }
        } catch (Exception $ex) {
            echo "There was an exception - " . $ex->getMessage();
        } catch (PDOException $e) {
            echo "There was a db exception - " . $e->getMessage();
        }        
        
        return $range;
    }

    /**
     * Queries the db to see if this person has entered today
     * Does this based on the current day and the user id
     * May have to mod depending on what the rules state for entry limitations
     * 
     * 
     * @param PDO Handle $dbh
     * @param int $user_id
     * @return boolean
     */
    public function alreadyEntered($dbh, $user_id, $post_id, $source) {
        // this will have to be modified to cover the entry period? Or one per day?
        $today = date("Y-m-d");

        try {
            $checkEntrySql = 'SELECT id';
            $checkEntrySql .= ' FROM posts';
            $checkEntrySql .= ' where post_id = :post';
            $checkEntrySql .= ' AND source = :source';

            $stmt = $dbh->prepare($checkEntrySql);
            $stmt->bindValue(':post', $post_id, PDO::PARAM_STR);
            $stmt->bindValue(':source', $source, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return TRUE;
            } else {
                return FALSE;
                
            }
        } catch (Exception $ex) {
            echo "There was a problem checking alreadyEntered() - " . $ex->getMessage();
            exit();
        } catch (PDOException $e) {
            echo "There was a problem with the database - " . $e->getMessage();
            exit();
        }
    }
    
        
    
    public function add_tags($dbh, $post_id, $tag) {
        $query = "INSERT INTO tags set post_id = :post_id, tag = :tag";
        
        try {
            $stmt = $dbh->prepare($query);
            $stmt->bindValue(':post_id', $post_id, PDO::PARAM_STR);
            $stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $ex) {
            echo "Exception thrown! - " . $ex->getMessage();
        } catch (PDOException $e) {
            echo "PDO Exception - " . $e->getMessage();
        }
    }

    /**
     * Pulls the video url from the shortened vine url
     * 
     * @param type $url
     * @return type
     */
    public function getVineVideoFromUrl($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        preg_match('/twitter:player:stream.*content="(.*)"/', $res, $output);
        return $output[1];
    }

    /**
     * Pulls the thumbnail url from vine videos
     * 
     * @param type $url
     * @return type
     */
    public function get_vine_thumbnail($url) {

        $vine = file_get_contents($url);
        preg_match('/property="og:image" content="(.*?)"/', $vine, $matches);

        return ($matches[1]) ? $matches[1] : false;
    }

    /**
     * Does a search to see if a string contains bad words listed in db
     * Set up for Mysqli connection but have updated to PDO
     * Need to update function as well
     * 
     * @global Mysql $database
     * @param string $text
     * @return boolean
     */
    public function has_bad_words($text) {
        global $database;

        $esctext = addslashes($text);

        $sql = "SELECT * FROM bad_words";
        $sql .= " WHERE '$esctext' like concat('%', word, '%')";

        $result = $database->doQuery($sql);
        $num_results = $database->getRowCount();

        if ($num_results > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Queries db to see if current screen name is blocked
     * Set up to use mysqli connection, not PDO
     * Need to update
     * 
     * @global Mysql $database
     * @param string $text
     * @return boolean
     */
    public function is_blocked_user($name) {
        $blocked = array('disq_username');
        
        if (in_array($name, $blocked)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Set up to search post text for additional tag terms for IG posts
     * 
     * @param string $term
     * @param string $text
     * @return boolean
     */
    public function IG_second_check($term, $text) {
        $matches = array();
        $matchFound = preg_match_all("/($term)/i", $text, $matches);

        if ($matchFound) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * part of the sanitize function
     * This part is the blacklist section where we can specify things to be taken out
     * Also purifies() and htmlspecialchars() the input
     * 
     * @global HTMLPurifier $purifier
     * @param type $input
     * @return type
     */
    public function cleanInput($input) {
        $search = array(
            '@<script[^>]*?>.*?</script>@si', // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@', // Strip multi-line comments
            '/onEvent\=+/i', // Strip onEvent calls
            '/\"|<|>|{|}|\[|\]*/'                               // Strip double quotes, left and right brackets, etc
        );

        //$input = trim($input);                                  // trim to remove leading and trailing spaces if not already removed
        $blacklist_input = preg_replace($search, '', trim($input));   // perform preg_replace to strip out blacklisted items
        $output = htmlspecialchars($blacklist_input);      // htmlspecialchars as an added precaution
        //$output = $purifier->purify($strip_input);              // run trhough purifier
        return $output;                                         // return cleaned input
    }

    /**
     * Second half of the sanitize function
     * This one stripslashes() and escapes the input
     * 
     * @global Mysql $database
     * @param type $input
     * @return type
     */
    public function sanitize($input) {
        //global $database;

        if (is_array($input)) {
            foreach ($input as $var => $val) {
                $output[$var] = $this->sanitize($val);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $input = stripslashes($input);
            }
            $output = $this->cleanInput($input);
            //$output = $database->escape($input);
        }
        return $output;
    }

}
