<?php 

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id$
//
// ---------------------------------------------------------------------------

/**
 * Implements the blogger and MetaWeblog APIs.
 *
 * @package pivotx
 */
/* 
 *  Blogger and metaWeblog implementation for PivotX
 *
 *  Based on phpMyWeblog's api.php
 *
 *  Initially modified by Connor Carney to work with Pivot
 *  Revamped, corrected and extended by the PivotX Team.
 *
 *  Originally Created by Dougal Campbell <dougal@gunters.org>, <emc3@users.sf.net>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */


/**
 * Including PivotX core functions and XML RPC functions/server.
 */
require_once(dirname(__FILE__).'/lib.php');
require_once($pivotx_path.'includes/xmlrpc/xmlrpc.inc');
require_once($pivotx_path.'includes/xmlrpc/xmlrpcs.inc');;
initializePivotX(false);

/**
 * Selecting UTF-8 as default encoding.
 */
$xmlrpc_internalencoding = 'UTF-8';

$conversion_method=0;
$VerboseGenerate = false;


// PIVOTX FUNCTIONS
// These functions update the PivotX database using the data
// from XMLRPC:

/**
 * Separator for introduction and body parts of an entry
 */
$body_separator = "PIVOTX_BODY";

/** 
 * Checks username and password, and return an array with
 * the userid (uid) and potentially an error (err).
 *
 * @param string $user
 * @param string $pass
 * @return array
 */
function pivotx_get_userid($user,$pass) {
    global $PIVOTX;

    // Being as relaxed about the user name as the Session login function.
    $user = strtolower($user);

    if (!$PIVOTX['config']->get('xmlrpc')) {
        debug("XML-RPC is disabled. Method call from ".$_SERVER['REMOTE_ADDR']." ignored.");
        return array(
            'uid'=>-1,
            'err'=>"XML-RPC is disabled."
        );
    }

    if ($PIVOTX['session']->login($user,$pass,'false')) {
        debug("User $user approved");
        return array('uid'=>$user);
    } else {
        debug("Wrong password/username ($user)");
        return array(
            'uid'=>-1,
            'err'=>"Your password/username is incorrect."
        );
    }
}
/**
 * Creates a list of categories (with description and more properties) for 
 * the given blog ID.
 * 
 * @param string $blogid
 * @return array
 */
function pivotx_get_blog_cats($blogid) {
    global $PIVOTX;
    $wcats = $PIVOTX['weblogs']->getCategories($blogid);
    $cats_info = array();
    foreach ($wcats as $cat) {
        // (categoryid is needed by Windows Live Writer.)
        $cats_info[] = array(
            'description'=>$cat,
            'title'=>$cat,
            'categoryid'=>$cat,
            'htmlUrl'=>$PIVOTX['paths']['host'].makeCategoryLink($cat,$blogid)
            // 'rssURL'=>$PIVOTX['paths']['host'].makeCategoryLink($cat,$blogid)
        );
    }
    return $cats_info;
}

/**
 * Finds the list of blogs (without checking the user permissions).
 *
 * @param string $uid
 * @return array
 */
function pivotx_get_user_blogs($uid) {
    global $PIVOTX;
    // $Current_weblog_orig = $Current_weblog;
    $blogs = array();
    foreach ($PIVOTX['weblogs']->getWeblogs() as $key => $value) {
        $PIVOTX['weblogs']->setCurrent($key);
        $blogs[] = array(
            "blogid"=>$key,
            "blogName"=>smarty_weblogtitle(),
            "url"=>$PIVOTX['paths']['host'] . $PIVOTX['weblogs']->get($key, 'link'),
        );
    }
    // $Current_weblog = $Current_weblog_orig;
    return $blogs;
}


/**
 * Determines if a user can post to a given blog (at all) by checking all 
 * permissions for all categories.
 *
 * @param string $uid
 * @param string $blogid
 * @return boolean
 */
function pivotx_user_blog_check($uid,$blogid) {
    global $PIVOTX;
    $wcats = $PIVOTX['weblogs']->getCategories($blogid);
    $allowed = $PIVOTX['categories']->allowedCategories($uid);
    if (count(array_intersect($allowed, $wcats)) > 0) {
        debug("User $uid allowed to post in blog $blogid");
        return true;
    }
    debug("User $uid NOT allowed to post to any category in blog $blogid");
    return false;
}


/** 
 * Determines if a post exists.
 *
 * @param string $postid
 * @return boolean
 */
function pivotx_post_exists($postid) {
    global $PIVOTX;
    $entry=$PIVOTX['db']->read_entry($postid);
    if (!isset($entry['code']) || empty($entry['code'])) {
        return false;
    } else {
        return true;
    }
}

/** 
 * Determines if a user has access to a given post.
 *
 * @todo Check if user is administrator or super-user.
 *
 * @param string $uid
 * @param string $postid
 * @return boolean
 */
function pivotx_user_post_check($uid,$postid) {
    global $PIVOTX;
    $entry=$PIVOTX['db']->read_entry($postid);
    if ($entry['user']!=$uid){
        return false;
    } else {
        return true;
    }
}

/**
 * Finds recent posts in the given blog.
 *
 * @param string $blogid
 * @param string $num
 * @return array
 */
function pivotx_recent($blogid, $num) {
    global $PIVOTX, $body_separator;

    $postlist = $PIVOTX['db']->read_entries(array(
        'show' => $num, 
        'offset' => 0, 
        'cats' => $PIVOTX['weblogs']->getCategories($blogid),
        'order' => 'desc',
    ));

    $retposts=array();
    foreach($postlist as $post){
        $thispost= $PIVOTX['db']->read_entry($post['code']);
        $content = $thispost['introduction'];
        if(!empty($thispost['body'])) {
            $content = $content."\n".$body_separator."\n".$thispost['body'];
        }

        $retposts[]=array(
            'date'=>$thispost['date'],
            'userid'=>$thispost['user'],
            'postid'=>$thispost['code'],
            'content'=>$content,
            'title'=>$thispost['title'],
            'description'=>$content,
            'link'=>makeFileURL($thispost['code'], $blogid, ''),
            'permalink'=>makeFileURL($thispost['code'], $blogid, ''),
            'categories'=>$thispost['category']
        );
    }

    return $retposts;
}


/**
 * Get a specific post.
 *
 * @todo Fix links (use functions). Add user check?
 *
 * @param string $postid
 * @return array
 */
function pivotx_get_post($postid) {
    global $PIVOTX, $body_separator;

    $post=$PIVOTX['db']->read_entry($postid);
    list($yr,$mo,$da,$ho,$mi) = explode("-",$post['date']);
    $unixtime = mktime($ho,$mi,0,$mo,$da,$yr);
    $isoString=iso8601_encode($unixtime);
    $content = $post['introduction'];
    if(!empty($post['body'])) {
        $content = $content."\n".$body_separator."\n".$post['body'];
    }
    $result = array(
        'dateCreated'=>new xmlrpcval($isoString,"dateTime.iso8601"),
        'userid'=>$post['user'],
        'postid'=>$post['code'],
        'content'=>$content,
        'title'=>$post['title'],
        'description'=>$post['introduction'],
        'link'=>makeFileURL($post['code'], '', ''),
        'permaLink'=>makeFileURL($post['code'], '', ''),
        'categories'=>$post['category']
    );

    return $result;
}

/**
 * Creates a new post and returns the code for that entry,.
 *
 * @param string $uid
 * @param string $title
 * @param string $body
 * @param string $categories
 * @return int
 */
function pivotx_new_post($uid,$title,$body,$categories) {
    global $PIVOTX, $conversion_method, $body_separator;

    $entry['code'] = ">";

    $entry['date'] = date("Y-m-d-H-i", getCurrentDate());

    list($pivotintro,$pivotbody) = explode($body_separator, stripTrailingSpace(stripslashes($body)));
    $entry['introduction'] = $pivotintro;
    $entry['body'] = $pivotbody;

    $entry['introduction'] = tidyHtml($entry['introduction'], TRUE);
    $entry['body'] = tidyHtml($entry['body'], TRUE);

    $entry['category'] = $categories;
    $entry['publish_date'] = date("Y-m-d-H-i", getCurrentDate());
    $entry['edit_date'] = date("Y-m-d-H-i", getCurrentDate());
    $entry['title'] = stripTrailingSpace(stripslashes($title));
    $entry['subtitle'] = "";
    $entry['user'] = $uid;
    $entry['convert_lb'] = $conversion_method;
    $entry['status'] =  "publish";
    $entry['allow_comments'] = 1;
    // Gather all tags from introduction and body in keywords.
    $tags = getTags(false, $entry['introduction'] . $entry['body']);
    $entry['keywords'] = implode(" ", $tags);
    $entry['vialink'] =  "";
    $entry['viatitle'] = "";

    $PIVOTX['db']->set_entry($entry);
    $PIVOTX['db']->save_entry(TRUE);

    //Return code
    return $PIVOTX['db']->entry['code'];
}


/**
 * Updates a post.
 *
 * @param string $uid
 * @param string $postid
 * @param string $title
 * @param string $content
 * @param array $categories
 * @return void
 */
function pivotx_update_post($uid,$postid,$title,$content,$categories='') {
    global $PIVOTX, $conversion_method, $body_separator;

    $oldentry = $PIVOTX['db']->read_entry($postid);

    $entry['code'] = $postid;

    $entry['date'] = $oldentry['date'];
    list($pivotintro,$pivotbody) = explode($body_separator, stripTrailingSpace(stripslashes($content)));
    $entry['introduction'] = $pivotintro;
    $entry['body'] = $pivotbody;
    $entry['introduction'] = tidyHtml($entry['introduction'], TRUE);
    $entry['body'] = tidyHtml($entry['body'], TRUE);
    if (empty($categories)) {
        $entry['category'] = $oldentry['category'];
    } else {
        $entry['category'] = $categories;
    }
    $entry['publish_date'] = $oldentry['publish_date'];
    $entry['edit_date'] = date("Y-m-d-H-i", getCurrentDate());
    $entry['title'] = stripTrailingSpace(stripslashes($title));
    $entry['subtitle'] = $oldentry['subtitle'];
    $entry['user'] = $uid;
    $entry['convert_lb'] = $conversion_method;
    $entry['status'] =  "publish";
    $entry['allow_comments'] = 1;
    $entry['keywords'] = $oldentry['keywords'];
    $entry['vialink'] =  $oldentry['vialink'];
    $entry['viatitle'] = $oldentry['viatitle'];

    $PIVOTX['db']->set_entry($entry);
    $PIVOTX['db']->save_entry(TRUE);
}

/**
 * Deletes a post.
 *
 * @param string $postid
 * @return void
 */
function pivotx_delete_post($postid) {
    global $PIVOTX;
    $entry = $PIVOTX['db']->read_entry($postid);
    $PIVOTX['db']->delete_entry();
}


/**
 * Returns info about user.
 *
 * @todo Fetch more info (if possible) from global $PIVOTX array.
 *
 * @param string $uid
 * @return array
 */
function pivotx_user_info($uid) {
    global $PIVOTX;

    $info = $PIVOTX['users']->getUser($uid);

    $user = array();
    $user['userid'] = $info['username'];
    $user['email'] = $info['email'];
    $user['nickname'] = $info['nickname'];
    $user['lastname'] = '';
    $user['firstname'] = '';
    $user['url'] = '';

    return $user;
}

/***********************************************************************************************
 * Blogger API
 *
 * MetaWeblog is based on Blogger, so we have to include these.
 * Posting via Blogger clients might work, but isn't recommended or tested.
 */

/**
 * blogger.newPost
 *
 * @param array $params Contains application key (ignored), blog id, username, password, content, and publish status (ignored). 
 */
function newPost ($params) {
    global $PIVOTX, $xmlrpcerruser;

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $blogid = $conv->scalarval();
    $conv = $params->getParam(2); $user = $conv->scalarval();
    $conv = $params->getParam(3); $pass = $conv->scalarval();
    $conv = $params->getParam(4); $content = $conv->scalarval();
    $conv = $params->getParam(5); $publish = $conv->scalarval();

    // Make Sure User Name and Password Match
    $login = pivotx_get_userid($user,$pass);

    //Get the User ID
    $uid = $login['uid'];

    if ($uid != -1) {
        //Check access rights
        if (!pivotx_user_blog_check($uid,$blogid)) {
            $err = "User " . $user . " does not have access to any category in blog " . $blogid;
        }

    } else {
        //Throw an error if the password was wrong.
        $err = $login['err'];
    }

    if ($err) {
        // Return an Error
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {

        // Let's try using <title> tags....
        // Blogger has no built-in title feature.  You should use MetaWeblog instead.
        $result = preg_match('/<title>(.+)<\/title>(.*)/is',$content,$arr);
        if ($result) {
            $title = $arr[1];
            $body = $arr[2];
        } else {
            $title = '';
            $body = $content;
        }

        // Finding all allowed categories in selected blog
        $cats = array();
        $wcats = $PIVOTX['weblogs']->getCategories($blogid);
        $allowed = $PIVOTX['categories']->allowedCategories($uid);
        $cats = array_intersect($allowed, $wcats);
        if (count($cats) == 0) {
            return new xmlrpcresp(0, $xmlrpcerruser+1,
            "User $user does not have access to any category in blog $blogid");
        } else { 
            $postid = pivotx_new_post($uid,$title,$body,$cats);
            $myResp = new xmlrpcval($postid,"string");
            return new xmlrpcresp($myResp);
        }
    }
}

/**
 * blogger.editPost
 *
 * @param array $params Contains application key (ignored), post id, username, password, content, and publish status (ignored)
 */
function editPost ($params) {
    global $xmlrpcerruser;


    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $postid = $conv->scalarval();
    $conv = $params->getParam(2); $user = $conv->scalarval();
    $conv = $params->getParam(3); $pass = $conv->scalarval();
    $conv = $params->getParam(4); $content = $conv->scalarval();
    $conv = $params->getParam(5); $publish = $conv->scalarval();

    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_post_exists($postid)) {
            $err = "Post $postid doesn't exist";
        } else if (!pivotx_user_post_check($uid,$postid)) {
            $err = "$user didn't write $postid";
        } 
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {
        // Let's try using <title> tags....
        $result = preg_match('/^<title>(.+)<\/title>(.*)/is',$content,$arr);
        if ($result) {
            $title = $arr[1];
            $body = $arr[2];
        } else {
            $title = '';
            $body = $content;
        }

        pivotx_update_post($uid,$postid,$title,$body);

        $myResp = new xmlrpcval(1,"boolean");

        // return
        return new xmlrpcresp($myResp);
    }
}

/**
 * blogger.getPost
 *
 * @param array $params Containsapplication key (ignored), post id, username, and password
 */
function getPost ($params) {

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $postid = $conv->scalarval();
    $conv = $params->getParam(2); $user = $conv->scalarval();
    $conv = $params->getParam(3); $pass = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_post_exists($postid)) {
            $err = "Post $postid doesn't exist";
        } else {
            $xmlrpcpost = pivotx_get_post($postid);
        }
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {
        //Return a response
        $myResp = new xmlrpcval($xmlrpcpost,"struct");
        return new xmlrpcresp($myResp);
    }

}


/**
 * blogger.deletePost
 *
 * @param array $params Contains application key (ignored), post id, username, password, and publish status (ignored)
 */
function deletePost ($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $postid = $conv->scalarval();
    $conv = $params->getParam(2); $user = $conv->scalarval();
    $conv = $params->getParam(3); $pass = $conv->scalarval();
    $conv = $params->getParam(4); $publish = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_post_exists($postid)) {
            $err = "Post $postid doesn't exist";
        } else if (!pivotx_user_post_check($uid,$postid)) {
            $err = "$user didn't write $postid";
        } else {
            $err = pivotx_delete_post($postid);
        }
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {
        $myResp = new xmlrpcval(1,"boolean");
        return new xmlrpcresp($myResp);
    }

}


/**
 * blogger.getRecentPosts
 *
 * @param array $params Contains application key (ignored), blog ID, username, password, and number of posts
 */
function getRecentPosts($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $blogid = $conv->scalarval();
    $conv = $params->getParam(2); $user = $conv->scalarval();
    $conv = $params->getParam(3); $pass = $conv->scalarval();
    $conv = $params->getParam(4); $num = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        //Check Permissions
        if (pivotx_user_blog_check($uid,$blogid)) {
            $postlist = pivotx_recent($blogid,$num);
        } else {
            $err = "$user does not have access to any category in blog $blogid";
        }

    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {
        // Encode each entry of the array.
        foreach($postlist as $entry) {
            // convert the date
            list($yr,$mo,$da,$ho,$mi) = explode("-",$entry['date']);
            $unixtime = mktime($ho,$mi,0,$mo,$da,$yr);
            $isoString=iso8601_encode($unixtime);
            $date = new xmlrpcval($isoString,"dateTime.iso8601");

            $userid = new xmlrpcval($entry['userid']);
            $postid = new xmlrpcval($entry['postid']);
            $content = new xmlrpcval($entry['content']);

            $encode_arr = array(
                'dateCreated' => $date,
                'userid' => $userid,
                'postid' => $postid,
                'content' => $content
            );

            $xmlrpcpostarr[] = new xmlrpcval($encode_arr,"struct");
        }	

        $myResp = new xmlrpcval($xmlrpcpostarr,"array");

        return new xmlrpcresp($myResp);
    }

}


/**
 * blogger.getUserInfo
 *
 * @param array $params Contains application key (ignored), username and password.
 */
function getUserInfo ($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid == -1) {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1,$err);
    } else {
        $xmlrpcuser = pivotx_user_info($uid);
        $myResp = php_xmlrpc_encode($xmlrpcuser);
        return new xmlrpcresp($myResp);
    }

}


/**
 * blogger.getUsersBlogs and metaWeblog.getUsersBlogs
 *
 * @param array $params Contains application key (ignored), username, and password
 */
function getUsersBlogs ($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $appkey = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();


    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        $bloglist = pivotx_get_user_blogs($uid);

        if (!is_array($bloglist)) {
            $err = "$user isn't allowed to post here.";
        }

    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        //Make an array of blogs
        foreach($bloglist as $entry) {
            $xmlrpcblogarr[] = php_xmlrpc_encode($entry);
        }	

        // Convert the array to XMLRPC
        $myResp = new xmlrpcval($xmlrpcblogarr,"array");

        // return
        return new xmlrpcresp($myResp);
    }
}


/**
 * blogger.getTemplate
 *
 * We don't support template editing, so we just return an error.
 *
 * @param array $params Contains application key, blog id, username, password
 */
function getTemplate ($params) {	 
    global $xmlrpcerruser;

    $err = "this endpoint doesn't support template editing.";

    return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
}

/**
 * blogger.getTemplate
 *
 * We don't support template editing, so we just return an error.
 *
 * @param array $params Contains application key, blog id, username, password, template, and type
 */
function setTemplate ($params) {
    global $xmlrpcerruser;

    $err = "this endpoing doesn't support template editing.";

    return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
}


/**********************************************************************************************
 * MetaWeblog Functions
 */

/**
 * metaWeblog.newPost
 *
 * @param array $params Contains blog id, username, password, content, and publish status.
 */
function metaweblog_newPost($params) {
    global $PIVOTX, $xmlrpcerruser;

    $conv = $params->getParam(0); $blogid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();
    $conv = $params->getParam(3); $contentstruct = php_xmlrpc_decode($conv);
    $conv = $params->getParam(4); $conv = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_user_blog_check($uid,$blogid)) {
            $err = "User " . $user . " does not have access to any category in blog " . $blogid;
        }
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        //Create post and respond

        $title = $contentstruct['title'];
        $content = $contentstruct['description'];
        if (!isset($contentstruct['categories']) || (count($contentstruct['categories']) == 0)) {
            // Using all allowed categories in selected blog
            $wcats = $PIVOTX['weblogs']->getCategories($blogid);
            $allowed = $PIVOTX['categories']->allowedCategories($uid);
            $cats = array_intersect($allowed, $wcats);
        } else {
            $cats = $contentstruct['categories'];
            // Checking if submitted categories really belong to selected blog.
            $wcats = $PIVOTX['weblogs']->getCategories($blogid);
            foreach ($cats as $key => $value) {
                if (!in_array($value,$wcats)) {
                    unset($cats[$key]);
                    debug("Category $value doesn't belong to weblog $blogid");
                }
            }
            if (count($cats) == 0) {
                $err = "None of the entry's categories belong to the selected blog";
                return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
            }
        }

        $postid = pivotx_new_post($uid,$title,$content,$cats);

        $myResp = new xmlrpcval($postid,"string");
        return new xmlrpcresp($myResp);
    }

}


/**
 * metaWeblog.editPost
 *
 * @param array $params Contains post id, user, password, content and publish
 */
function metaweblog_editPost ($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $postid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();
    $conv = $params->getParam(3); $contentstruct = php_xmlrpc_decode($conv);
    $conv = $params->getParam(4); $publish = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_post_exists($postid)) {
            $err = "Post $postid doesn't exist";
        } else if (!pivotx_user_post_check($uid,$postid)) {
            $err = "$user didn't write post $postid";
        }
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        //Update the post and return

        $title = $contentstruct['title'];
        $content = $contentstruct['description'];
        $categories = $contentstruct['categories'];
        pivotx_update_post($uid,$postid,$title,$content,$categories);

        $myResp = new xmlrpcval(1,"boolean");

        return new xmlrpcresp($myResp);
    }
}


/**
 * metaWeblog.getPost
 *
 * @param array $params Contains post id, username, password
 */
function metaweblog_getPost ($params) {
    $conv = $params->getParam(0); $postid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if (!pivotx_post_exists($postid)) {
            $err = "Post $postid doesn't exist";
        } else {
            $xmlrpcpost = pivotx_get_post($postid);
        }

    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        //Encode as an XMLRPC array
        $myResp = php_xmlrpc_encode($xmlrpcpost);

        return new xmlrpcresp($myResp);
    }

}


/**
 * metaWeblog.getRecentPosts
 *
 * @param array $params Contains blog id, username, password, and number of posts
 */
function metaweblog_getRecentPosts ($params) {
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $blogid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();
    $conv = $params->getParam(3); $num = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        // Check blog permissions.
        if (pivotx_user_blog_check($uid,$blogid)) {
            $postlist = pivotx_recent($blogid,$num);
        } else {
            $err = "User $user does not have access to any category in blog $blogid";
        }

    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        // Encode each entry of the array.
        foreach($postlist as $entry) {
            // convert the date
            list($yr,$mo,$da,$ho,$mi) = explode("-",$entry['date']);
            $unixtime = mktime($ho,$mi,0,$mo,$da,$yr);
            $isoString=iso8601_encode($unixtime);
            $date = new xmlrpcval($isoString,"dateTime.iso8601");
            $userid = new xmlrpcval($entry['userid']);
            $content = new xmlrpcval($entry['content']);

            $postid = new xmlrpcval($entry['postid']);
            $title = new xmlrpcval($entry['title']);
            $description = new xmlrpcval($entry['description']);
            $link = new xmlrpcval($entry['link']);
            $permalink = new xmlrpcval($entry['permalink']);
            $cat_arr = php_xmlrpc_encode($entry['categories']);

            $encode_arr = array(
                'dateCreated' => $date,
                'userid' => $userid,
                'postid' => $postid,
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'permaLink' => $permalink,
                'categories' => $cat_arr
            );

            $xmlrpcpostarr[] = new xmlrpcval($encode_arr,"struct");
            // $xmlrpcpostarr[] = php_xmlrpc_encode($encode_arr);
        }	
        $myResp = new xmlrpcval($xmlrpcpostarr,"array");
        return new xmlrpcresp($myResp);
    }

}


/**
 * metaWeblog.getCategories
 *
 * @param array $params Contains blog id, username, password
 */
function metaweblog_getCategories ($params) {	
    global $xmlrpcerruser;

    $conv = $params->getParam(0); $blogid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        $catlist = pivotx_get_blog_cats($blogid);
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        //Make an array of blogs
        foreach($catlist as $entry) {
            $xmlrpccatarr[] = php_xmlrpc_encode($entry);
        }

        // Convert the array to XMLRPC
        $myResp = new xmlrpcval($xmlrpccatarr,"array");

        return new xmlrpcresp($myResp);
    }
}

/**
 * metaWeblog.newMediaObject
 *
 * @param array $params Contains blog id, username, password and file
 */
function metaweblog_newMediaObject ($params) {	
    global $PIVOTX, $xmlrpcerruser;

    $conv = $params->getParam(0); $blogid = $conv->scalarval();
    $conv = $params->getParam(1); $user = $conv->scalarval();
    $conv = $params->getParam(2); $pass = $conv->scalarval();
    $conv = $params->getParam(3); $file = php_xmlrpc_decode($conv);

    // Check password
    $login = pivotx_get_userid($user,$pass);

    $uid = $login['uid'];

    if ($uid != -1) {
        if(!empty($file['name'])) {
            $filename = basename($file['name']);
            // Clean up file name (only lowercase letters, numbers, underscores and hyphens)
            $filename = preg_replace("/[^a-z0-9._-]/", "", str_replace(array(' ','%20'),'-', strtolower($filename)));
            $filename = makeUploadFolder($filename).$filename;
            // Always write to a new file if the name already exists
            if (file_exists($filename)) {
                $fileext = getExtension($filename);
                $tmp = substr($filename,0,-strlen(".$fileext"));
                while (file_exists($filename)) {
                    $i++;
                    $filename = $tmp . '_' . $i . ".$fileext";
                }
            }
            $url = str_replace($PIVOTX['paths']['upload_base_path'],$PIVOTX['paths']['upload_base_url'],$filename);
            writeFile($filename,$file['bits'],'wb');
        } else {
            debug("Media object has no name.");
            $err = "Media object has no name.";
        }
    } else {
        $err = $login['err'];
    }

    if ($err) {
        return new xmlrpcresp(0, $xmlrpcerruser+1, $err);
    } else {
        $myResp = php_xmlrpc_encode(array('url'=>$PIVOTX['paths']['host'].$url));
        return new xmlrpcresp($myResp);
    }
}


//***********************************************************************************************

//XMLRPC Code

// Set up the server
$s=new xmlrpc_server( 
	array(	"blogger.newPost" =>
			array("function" => "newPost",
				"signature" => array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcBoolean)),
				"doc" => "Create a new post using the Blogger API"
			),
		"blogger.editPost" =>
			array("function" => "editPost",
				"signature" => array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcBoolean)),
				"doc" => "Edit an existing post using the Blogger API"
			),
		"blogger.deletePost" =>
			array("function" => "deletePost",
				"signature" => array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcBoolean)),
				"doc" => "Delete an existing post"
			),
		"blogger.getUsersBlogs" =>
			array("function" => "getUsersBlogs",
				"signature" => array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get a list of PivotX categories the user is authorized to access."
			),
		"blogger.getUserInfo" =>
			array("function" => "getUserInfo",
				"signature" => array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get information for a given username"
			),
		"blogger.getTemplate" =>
			array("function" => "getTemplate",
				"signature" => array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "We don't support template editing."
			),
		"blogger.setTemplate" =>
			array("function" => "setTemplate",
				"signature" => array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "We don't support template editing."
			),
		"blogger.getPost" =>
			array("function" => "getPost",
				"signature" => array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get an existing post using the Blogger API"
			),
		"blogger.getRecentPosts" =>
			array("function" => "getRecentPosts",
				"signature" => array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt)),
				"doc" => "Get recent posts using the Blogger API"
			),
		"metaWeblog.newPost" =>
			array("function" => "metaweblog_newPost",
				"signature" => array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean)),
				"doc" => "Create a new post using metaWeblog"
			),
		"metaWeblog.editPost" =>
			array("function" => "metaweblog_editPost",
				"signature" => array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean)),
				"doc" => "Edit an existing post using metaWeblog"
			),
		"metaWeblog.getPost" =>
			array("function" => "metaweblog_getPost",
				"signature" => array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get an existing post using metaWeblog"
			),
		"metaWeblog.getRecentPosts" =>
			array("function" => "metaweblog_getRecentPosts",
				"signature" => array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt)),
				"doc" => "Get recent posts using metaWeblog"
			),
		"metaWeblog.getUsersBlogs" =>
			array("function" => "getUsersBlogs",
				"signature" => array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get a list of PivotX weblogs (currently without checking the user permissions)"
			),
		"metaWeblog.getCategories" =>
			array("function" => "metaweblog_getCategories",
				"signature" => array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString)),
				"doc" => "Get a list of PivotX categories"
			),
		"metaWeblog.newMediaObject" =>
			array("function" => "metaweblog_newMediaObject",
				"signature" => array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct)),
				"doc" => "Uploads a file to your PivotX image directory."
			)
	)
);

?>
