<?php
# Copyright (c) 2003-2005, Jannis Hermanns (on behalf the Serendipity Developer Team)
# All rights reserved.  See LICENSE file for licensing details

/*****************************************************************
 *  SMF  Importer,     by Garvin Hicking *
 * ****************************************************************/

class Serendipity_Import_smf extends Serendipity_Import {
    var $info        = array('software' => 'SMF');
    var $data        = array();
    var $inputFields = array();
    var $categories  = array();

    function getImportNotes() {
        return '<p>SMF uses salted MD5 passwords that Serendipity cannot import. Thus, those passwords are incompatible with the MD5 hashing of Serendipity. The passwords for all users have been set to a random string. <strong>You need to modify the passwords manually for each user</strong>, we are sorry for that inconvenience.</p>';
    }

    function __construct($data) {
        $this->data = $data;
        $this->inputFields = array(array('text' => INSTALL_DBHOST,
                                         'type' => 'input',
                                         'name' => 'host'),

                                   array('text' => INSTALL_DBUSER,
                                         'type' => 'input',
                                         'name' => 'user'),

                                   array('text' => INSTALL_DBPASS,
                                         'type' => 'protected',
                                         'name' => 'pass'),

                                   array('text' => INSTALL_DBNAME,
                                         'type' => 'input',
                                         'name' => 'name'),

                                   array('text' => INSTALL_DBPREFIX,
                                         'type' => 'input',
                                         'name' => 'prefix',
                                         'default' => 'smf_'),

                                   array('text'    => CHARSET,
                                         'type'    => 'list',
                                         'name'    => 'charset',
                                         'value'   => 'native',
                                         'default' => $this->getCharsets(false)),

                                   array('text'    => CONVERT_HTMLENTITIES,
                                         'type'    => 'bool',
                                         'name'    => 'use_strtr',
                                         'default' => 'true'),

                                   array('text'    => ACTIVATE_AUTODISCOVERY,
                                         'type'    => 'bool',
                                         'name'    => 'autodiscovery',
                                         'default' => 'false')
                            );
    }

    function validateData() {
        return sizeof($this->data);
    }

    function getInputFields() {
        return $this->inputFields;
    }

    function import() {
        global $serendipity;

        // Save this so we can return it to its original value at the end of this method.
        $noautodiscovery = isset($serendipity['noautodiscovery']) ? $serendipity['noautodiscovery'] : false;

        if ($this->data['autodiscovery'] == 'false') {
            $serendipity['noautodiscovery'] = 1;
        }

        $this->getTransTable();

        $this->data['prefix'] = serendipity_db_escape_string($this->data['prefix']);
        $users = array();
        $entries = array();

        if (!extension_loaded('mysqli')) {
            return MYSQL_REQUIRED;
        }

        $gdb = @mysqli_connect($this->data['host'], $this->data['user'], $this->data['pass']);
        if (!$gdb || mysqli_connect_error()) {
            return sprintf(COULDNT_CONNECT, serendipity_specialchars($this->data['host']));
        }

        if (!@mysqli_select_db($gdb, $this->data['name'])) {
            return sprintf(COULDNT_SELECT_DB, mysqli_error($gdb));
        }
        
        /* Users */
        $res = @$this->nativeQuery("SELECT ID_MEMBER       AS ID,
                                    memberName      AS user_login,
                                    passwd AS user_pass,
                                    emailAddress    AS user_email,
                                    ID_GROUP AS user_level
                               FROM {$this->data['prefix']}members
                              WHERE is_activated = 1", $gdb);
        if (!$res) {
            return sprintf(COULDNT_SELECT_USER_INFO, mysqli_error($gdb));
        }

        for ($x=0, $max_x = mysqli_num_rows($res); $x < $max_x ; $x++ ) {
            $users[$x] = mysqli_fetch_assoc($res);

            $data = array('right_publish' => 1,
                          'realname'      => $users[$x]['user_login'],
                          'username'      => $users[$x]['user_login'],
                          'email'         => $users[$x]['user_email'],
                          'userlevel'     => ($users[$x]['user_level'] == 1 ? USERLEVEL_ADMIN : USERLEVEL_EDITO),
                          'password'      => $users[$x]['user_pass']); // MD5 compatible

            if ($serendipity['serendipityUserlevel'] < $data['userlevel']) {
                $data['userlevel'] = $serendipity['serendipityUserlevel'];
            }

            serendipity_db_insert('authors', $this->strtrRecursive($data));
            echo mysqli_error();
            $users[$x]['authorid'] = serendipity_db_insert_id('authors', 'authorid');
        }

        /* Categories */
        $res = @$this->nativeQuery("SELECT ID_CAT AS cat_ID,
                                    name AS cat_name
                               FROM {$this->data['prefix']}categories", $gdb);
        if (!$res) {
            return sprintf(COULDNT_SELECT_CATEGORY_INFO, mysqli_error($gdb));
        }

        // Get all the info we need
        for ($x=0, $max_x = mysqli_num_rows($res) ; $x < $max_x ; $x++) {
            $parent_categories[] = mysqli_fetch_assoc($res);
        }

        for ($x=0, $max_x = sizeof($parent_categories) ; $x < $max_x ; $x++ ) {
            $cat = array('category_name'        => $parent_categories[$x]['cat_name'],
                         'category_description' => '',
                         'parentid'             => 0, // <---
                         'category_left'        => 0,
                         'category_right'       => 0);

            serendipity_db_insert('category', $this->strtrRecursive($cat));
            $parent_categories[$x]['categoryid'] = serendipity_db_insert_id('category', 'categoryid');
        }

        /* Categories */
        $res = @$this->nativeQuery("SELECT ID_BOARD AS cat_ID,
                                    ID_CAT   AS parent_cat_id,
                                    name AS cat_name,
                                    description AS category_description
                               FROM {$this->data['prefix']}boards ORDER BY boardOrder;", $gdb);
        if (!$res) {
            return sprintf(COULDNT_SELECT_CATEGORY_INFO, mysqli_error($gdb));
        }

        // Get all the info we need
        for ($x=0, $max_x = mysqli_num_rows($res) ; $x < $max_x ; $x++) {
            $categories[] = mysqli_fetch_assoc($res);
        }

        // Insert all categories as top level (we need to know everyone's ID before we can represent the hierarchy).
        for ($x=0, $max_x = sizeof($categories) ; $x < $max_x ; $x++ ) {
            $pcatid = 0;
            foreach($parent_categories AS $pcat) {
                if ($pcat['cat_ID'] == $categories[$x]['parent_cat_id']) {
                    $pcatid = $pcat['cat_ID'];
                    break;
                }
            }

            $cat = array('category_name'        => $categories[$x]['cat_name'],
                         'category_description' => $categories[$x]['category_description'],
                         'parentid'             => $pcatid, // <---
                         'category_left'        => 0,
                         'category_right'       => 0);

            serendipity_db_insert('category', $this->strtrRecursive($cat));
            $categories[$x]['categoryid'] = serendipity_db_insert_id('category', 'categoryid');
        }

        serendipity_rebuildCategoryTree();

        /* Entries */
        $res = @$this->nativeQuery("SELECT

        tm.subject AS post_subject,
        t.ID_MEMBER_STARTED AS topic_poster,
        t.ID_BOARD AS forum_id,
        tm.posterTime AS post_time,
        tm.body AS post_text,
        t.ID_TOPIC AS topic_id,
        t.ID_FIRST_MSG AS post_id,
        t.numReplies AS ccount

        FROM {$this->data['prefix']}topics AS t
        JOIN {$this->data['prefix']}messages AS tm
          ON tm.ID_MSG = t.ID_FIRST_MSG

        GROUP BY t.ID_TOPIC", $gdb);
        if (!$res) {
            return sprintf(COULDNT_SELECT_ENTRY_INFO, mysqli_error($gdb));
        }

        for ($x=0, $max_x = mysqli_num_rows($res) ; $x < $max_x ; $x++ ) {
            $entries[$x] = mysqli_fetch_assoc($res);

            $entry = array('title'          => $this->decode($entries[$x]['post_subject']),
                           'isdraft'        => 'false',
                           'allow_comments' => 'true',
                           'timestamp'      => $entries[$x]['post_time'],
                           'body'           => $this->strtr($entries[$x]['post_text']),
                           'extended'       => ''
                           );

            $entry['authorid'] = '';
            $entry['author']   = '';
            foreach ($users as $user) {
                if ($user['ID'] == $entries[$x]['topic_poster']) {
                    $entry['authorid'] = $user['authorid'];
                    $entry['author']   = $user['user_login'];
                    break;
                }
            }

            if (!is_int($entries[$x]['entryid'] = serendipity_updertEntry($entry))) {
                return $entries[$x]['entryid'];
            }

            /* Entry/category */
            foreach ($categories as $category) {
                if ($category['cat_ID'] == $entries[$x]['forum_id'] ) {
                    $data = array('entryid'    => $entries[$x]['entryid'],
                                  'categoryid' => $category['categoryid']);
                    serendipity_db_insert('entrycat', $this->strtrRecursive($data));
                    break;
                }
            }
            
            $topic_id = $entries[$x]['topic_id'];

            // Store original ID, we might need it at some point.
            serendipity_db_insert('entryproperties', array('entryid' => $entries[$x]['entryid'], 'property' => 'foreign_import_id', 'value' => $entries[$x]['topic_id']));

            // Convert SMF tags
            $t_res = @$this->nativeQuery("SELECT t.tag
                                            FROM {$this->data['prefix']}tags_log AS tl
                                            JOIN {$this->data['prefix']}tags AS t
                                              ON tl.ID_TAG = t.ID_TAG
                                           WHERE tl.ID_TOPIC = {$topic_id}
                                             AND t.approved = 1", $gdb);
            if (mysqli_num_rows($t_res) > 0) {
                while ($a = mysqli_fetch_assoc($t_res)) {
                    serendipity_db_insert('entrytags', array('entryid' => $entries[$x]['entryid'], 'tag' => $t_res['tag']));
                }
            }

            /* Comments */
            $c_res = @$this->nativeQuery("SELECT
                tm.subject AS post_subject,
                tm.body AS post_text,
                tm.ID_MSG AS post_id,
                tm.posterTime AS post_time,
                tm.ID_BOARD AS forum_id,
                tm.posterName AS poster_name,
                tm.posterEmail AS poster_email

                FROM {$this->data['prefix']}topics AS t
                JOIN {$this->data['prefix']}messages AS tm
                  ON tm.ID_TOPIC = t.ID_TOPIC
               WHERE t.ID_TOPIC = {$topic_id}
            ", $gdb);

            if (!$c_res) {
                return sprintf(COULDNT_SELECT_COMMENT_INFO, mysqli_error($gdb));
            }

            while ($a = mysqli_fetch_assoc($c_res)) {
                if ($a['post_id'] == $entries[$x]['post_id']) {
                    continue;
                }
                $author   = $a['poster_name'];
                $mail     = $a['poster_email'];
                $url      = '';

                foreach($users AS $user) {
                    if ($user['ID'] == $a['poster_id']) {
                        $author = $user['user_login'];
                        $mail   = $user['user_email'];
                        $url    = $user['user_url'];
                        break;
                    }
                }
                $a['post_text'] = serendipity_entity_decode($a['post_text']);

                $comment = array('entry_id ' => $entries[$x]['entryid'],
                                 'parent_id' => 0,
                                 'timestamp' => $a['post_time'],
                                 'author'    => $author,
                                 'email'     => $mail,
                                 'url'       => $url,
                                 'ip'        => '',
                                 'status'    => 'approved',
                                 'body'      => $a['post_text'],
                                 'subscribed'=> 'false',
                                 'type'      => 'NORMAL');

                serendipity_db_insert('comments', $this->strtrRecursive($comment));
                $cid = serendipity_db_insert_id('comments', 'id');
                serendipity_approveComment($cid, $entries[$x]['entryid'], true);
            }
        }

        $serendipity['noautodiscovery'] = $noautodiscovery;

        // That was fun.
        return true;
    }
}

return 'Serendipity_Import_smf';

/* vim: set sts=4 ts=4 expandtab : */
