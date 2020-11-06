<?php
//require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Mobile app API
 * Description:       All functions which is used in mobile app with JWT Auth.
 * Version:           1.0
 * Author:            Knoxweb
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

add_action( 'rest_api_init', function() {
    
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', function( $value ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );

		return $value;
		
	});
}, 15 );




function test_jwt_auth_expire($issuedAt)
{
    return $issuedAt + (62732 * 10000);
}
add_filter('jwt_auth_expire', 'test_jwt_auth_expire');

add_action('rest_api_init', function () {
    register_rest_route('mobileapi/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'MobileApiMakeNewAuthor',
    ));

    register_rest_route('mobileapi/v1', '/retrieve_password', array(
        'methods' => 'POST',
        'callback' => 'RetrivePassword',
    ));

    //GetUserImage

    register_rest_route('mobileapi/v1', '/GetUserImage', array(
        'methods' => 'POST',
        'callback' => 'GetUserImage',
    ));


    register_rest_route('mobileapi/v1', '/validate_token', array(
        'methods' => 'POST',
        'callback' => 'validate_token',
    ));

    register_rest_route('mobileapi/v1', '/facebook_login', array(
        'methods' => 'POST',
        'callback' => 'facebook_login',
    ));
    
     register_rest_route('mobileapi', '/update_profile', array(
        'methods' => 'POST',
        'callback' => 'updateProfile'
    ));
    
    register_rest_route('mobileapi', '/get_comment', array(
        'methods' => 'POST',
        'callback' => 'getComment'
    ));
    
    register_rest_route('mobileapi', '/submit_comment', array(
        'methods' => 'POST',
        'callback' => 'submitComment'
    ));

    register_rest_route('mobileapi', '/reply_comment', array(
        'methods' => 'POST',
        'callback' => 'replyComment'
    ));
    
     register_rest_route('mobileapi/v1', '/create_feed', array(
        'methods' => 'POST',
        'callback' => 'createFeed'
    ));
    
     register_rest_route('mobileapi/v1', '/create_product', array(
        'methods' => 'POST',
        'callback' => 'create_product'
    ));
    
     register_rest_route('mobileapi/v1', '/create_contact', array(
        'methods' => 'POST',
        'callback' => 'create_contact'
    ));
    
    register_rest_route('mobileapi/v1', '/getProfile/', array(
        'methods' => 'GET',
        'callback' => 'getProfile'
    ));
        register_rest_route('mobileapi/v1', '/subscribeMembershipPlan', array(
        'methods' => 'POST', 
        'callback' => 'subscribeMembershipPlan'
    ));
    //fetch_cat
    register_rest_route('mobileapi/v1', '/fetch_cat/', array(
        'methods' => 'POST',
        'callback' => 'fetch_cat'
    ));
    
    //getNearBy
     register_rest_route('mobileapi/v1', '/getNearBy/', array(
        'methods' => 'POST',
        'callback' => 'getNearBy'
    ));

});


function getProfile($request){
     $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );
    $param = $request->get_params();
     $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    if($user_id){
        
         $useravatar = get_user_meta($user_id,'wp_user_avatar',true); 
           if($useravatar){
            $img = wp_get_attachment_image_src($useravatar, array('150','150'), true );
              $user_avatar=$img[0];
              
              //$response->data['author_avatar']=$user_avatar;
           }else{
             
               $user_avatar='http://1.gravatar.com/avatar/1aedb8d9dc4751e229a335e371db8058?s=96&d=mm&r=g';
           }
        
        $user=get_userdata($user_id);   
        $role = 'subscriber';
        if (in_array('beacon', (array) $user->roles)) {
            $role = 'beacon';
        } 
        
         $args = array(
        	'posts_per_page'   => -1,
        	'author'	   => $user_id,
          );
          
          $count=0;
          $posts_array = get_posts( $args );
          if(count($posts_array)>0){
              $count = count($posts_array);
          }

         $data=array();
         $data['userImage']=$user_avatar;
         $data['name']=get_user_meta($user_id,'first_name',true)." ".get_user_meta($user_id,'last_name',true);
         $data['membership']='';
         $data['job']=$role;
         $data['school']="N/A";
         $data['city']="Verinia";
         $data['state']="NY";
         $data['city_state']=$data['city'].", ".$data['state'];
         $data['likes']=$user_id;
         $data['followers']=(string)$count;
         $data['following']='0';
         //description
         $data['about']=get_user_meta($user_id,'description',true);
         

         return new WP_REST_Response($data, 200);
    }else{
         $data  = array(
        "status" => "error",
        "errormsg" => "user token expired",
        'error_code' => "user_expire");
        return new WP_REST_Response($data, 403);
    }
}

function createFeed($request){
    
     $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );
    $param = $request->get_params();
    	
    $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    if($user_id){
        $new_post = array(
        'post_title' =>$param['post_title'],
        'post_content' =>$param['post_content'],
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_type' => 'beacon_services',
        'post_category' => array(0)
        );
        
        $post_id = wp_insert_post($new_post);
        
        if($post_id){
        $data['post']=$post_id;
        
                global $wpdb;
                $address = trim($param['city']).", ".trim($param['state'])." ".trim($param['zipcode']).", USA"; // Google HQ
                $row_ln = $wpdb->get_row("SELECT * FROM `wp_latlon` WHERE `zipcode` ='".$address."'");
                if(count($row_ln)==0){
                //$key = 'AIzaSyAJSffEDfIkpgatiOvePj_db4BFfqHAYNk';
                    
                    $prepAddr = str_replace(' ','+',$address);
                    $key = 'AIzaSyAJSffEDfIkpgatiOvePj_db4BFfqHAYNk';
                    $geo = wp_remote_fopen('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key='.urlencode($key));
               
                     // We convert the JSON to an array
                    $geo = json_decode($geo, true);
               
            
                   // If everything is cool
                   if ($geo['status'] = 'OK') {
                       
                      $lat = $geo['results'][0]['geometry']['location']['lat'];
                      $lon = $geo['results'][0]['geometry']['location']['lng'];
                       $wpdb->insert("wp_latlon",array('zipcode'=>$address,'lat'=>$lat,'lon'=>$lon));
                   }
                }else{
                   $lat = $row_ln->lat;
                   $lon = $row_ln->lon;  
                }
        
         update_post_meta($post_id,"map_lat",$lat);
         update_post_meta($post_id,"map_lng",$lon);
        
        update_post_meta($post_id,"category",$param['category']);
        update_post_meta($post_id,"zipcode",$param['zipcode']);
        update_post_meta($post_id,"price",$param['price']);
        update_post_meta($post_id,"city",$param['city']);
        update_post_meta($post_id,"state",$param['state']);
        return new WP_REST_Response($data, 200);
        }else{
          $data['post']=$new_post;    
          $data['errormsg']="post not created, something went wrong.";    
          return new WP_REST_Response($data, 403);   
        }
    }else{
        $data  = array(
        "status" => "error",
        "errormsg" => "user token expired",
        'error_code' => "user_expire"
    );
    }
    return new WP_REST_Response($data, 403);
    
  }
  
  
  
  function getNearBy($request) {
      
    global $wpdb;
    $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );
    $param = $request->get_params();
    
     $lat = $param['lat'];
     $lng=$param['lng'];
     $distance=50; 

    // Radius of the earth 3959 miles or 6371 kilometers.
    $earth_radius = 3959;
    if($param['type']==1){
        $q='';
        
        if(isset($param['name']) && trim($param['name'])!=''){
             $q=" AND p.post_title LIKE '%".$param['name']."%'";
        }
        
        if(isset($param['zipcode']) && trim($param['zipcode'])!=''){
            $q.=" AND m.meta_key = 'zipcode' AND m.meta_value='".$param['zipcode']."'";
        }
        
         $sql = "
        SELECT DISTINCT
            p.ID,
            p.post_title,(select meta_value from $wpdb->postmeta where post_id=p.ID AND meta_key='map_lat') as locLat ,
            (select meta_value from $wpdb->postmeta where post_id=p.ID AND meta_key='map_lng') as locLong 
        FROM $wpdb->posts p
        INNER JOIN $wpdb->postmeta m ON p.ID = m.post_id
        WHERE 1 = 1
        AND p.post_type = 'beacon_services'
        AND p.post_status = 'publish'
        $q";
        
    }else{
        
    $sql = $wpdb->prepare( "
        SELECT DISTINCT
            p.ID,
            p.post_title,
            map_lat.meta_value as locLat,
            map_lng.meta_value as locLong,
            ( %d * acos(
            cos( radians( %s ) )
            * cos( radians( map_lat.meta_value ) )
            * cos( radians( map_lng.meta_value ) - radians( %s ) )
            + sin( radians( %s ) )
            * sin( radians( map_lat.meta_value ) )
            ) )
            AS distance
        FROM $wpdb->posts p
        INNER JOIN $wpdb->postmeta map_lat ON p.ID = map_lat.post_id
        INNER JOIN $wpdb->postmeta map_lng ON p.ID = map_lng.post_id
        WHERE 1 = 1
        AND p.post_type = 'beacon_services'
        AND p.post_status = 'publish'
        AND map_lat.meta_key = 'map_lat'
        AND map_lng.meta_key = 'map_lng'
        HAVING distance < %s
        ORDER BY distance ASC",
        $earth_radius,
        $lat,
        $lng,
        $lat,
        $distance
    );
    }

    // Uncomment and paste into phpMyAdmin to debug.
    // echo $sql;

    $nearbyLocations = $wpdb->get_results( $sql );
    if(count($nearbyLocations)>0){
        $data['services']=$nearbyLocations;
    }else{
       $data['services']=false;
    }
    
    return new WP_REST_Response($data, 200); 
}
  
 function create_product($request){
     $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );
    $param = $request->get_params();
    	
    $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    if($user_id){
        $new_post = array(
        'post_title' =>$param['post_title'],
        'post_content' =>$param['post_content'],
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_type' => 'products',
        'post_category' => array(0)
        );
        
        $post_id = wp_insert_post($new_post);
        
        if($post_id){
        $data['post']=$post_id;
        update_post_meta($post_id,"category",$param['category']);
        update_post_meta($post_id,"price",$param['price']);
        return new WP_REST_Response($data, 200);
        }else{
          $data['post']=$new_post;    
          $data['errormsg']="post not created, something went wrong.";    
          return new WP_REST_Response($data, 403);   
        }
    }else{
        $data  = array(
        "status" => "error",
        "errormsg" => "user token expired",
        'error_code' => "user_expire"
    );
    }
    return new WP_REST_Response($data, 403); 
 }
 
 function create_contact($request){
     
     $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );
    $param = $request->get_params();
    	
    $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    if($user_id){
        $new_post = array(
        'post_title' =>"Request From ".$param['name'],
        'post_content' =>$param['post_content'],
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_type' => 'services_request',
        'post_category' => array(0)
        );
        
        $post_id = wp_insert_post($new_post);
        
        if($post_id){
        $data['post']=$post_id;
         update_post_meta($post_id,"name",$param['name']);
          update_post_meta($post_id,"email",$param['email']);
           update_post_meta($post_id,"contact_number",$param['contact_number']);
            update_post_meta($post_id,"message",$param['post_content']);
             update_post_meta($post_id,"service",$param['service']);
        return new WP_REST_Response($data, 200);
        }else{
          $data['post']=$new_post;    
          $data['errormsg']="Conatct not created, something went wrong.";    
          return new WP_REST_Response($data, 403);   
        }
    }else{
        $data  = array(
        "status" => "error",
        "errormsg" => "user token expired",
        'error_code' => "user_expire"
    );
    }
    return new WP_REST_Response($data, 403); 
 }

function submitComment($request){
   global $wpdb;
    $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );

   
    $param = $request->get_params();

    $token = $param['token'];
    $post_id = $param['post_id'];
    $comment = $param['comment'];
    
    $user_id = GetMobileAPIUserByIdToken($token);

    if(!$user_id){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid token');
       $data['error_code'] = "invalid_token";
       return new WP_REST_Response($data, 403);	
    }

    // get user by user id
    $user_temp= get_user_by('ID', $user_id);
    $user= $user_temp->data;

    if(empty($user)){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid user');
       $data['error_code'] = "invalid_user";
       return new WP_REST_Response($data, 403);	
    }

    // check if comment and post id exist
    if($comment=='' || $post_id==''){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid request');
       $data['error_code'] = "invalid_request";
       return new WP_REST_Response($data, 403);
    }

	  $args = array(
	    'comment_post_ID' => $post_id,
	    'comment_author' => $user->user_login,
	    'comment_author_email' => $user->user_email,
	    'comment_author_url' => 'http://',
	    'comment_content' => $comment,
	    'comment_type' => '',
	    //'comment_parent' => 0,
	    'user_id' => $user->ID,
	    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
	    'comment_date' => current_time('mysql'),
	    'comment_approved' => 1,
	  );

	  //print_r($args); exit;
    if(wp_insert_comment($args)){
    	$data  = array(
	        "status" => "ok",
	        "msg"    => "comment submitted successfully",
	        "errormsg" => "",
	        'error_code' => ""
       );

       return new WP_REST_Response($data, 200);
    }else{
       $data['status']     = "error";
       $data['errormsg']   = __('Comment could not be submitted');
       $data['error_code'] = "invalid_request";
       return new WP_REST_Response($data, 403);	
    }
}

function getComment($request){
   
   $data = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    ); 

   $param = $request->get_params();
   $post_id  = $param['post_id'];

   // get all comment of current post
   $comments=get_comments( array('post_id' => $post_id, 'order' => 'ASC'));
   //print_r($comments); exit;
   
   $comments_arr= array();
   foreach($comments as $comment){
      if($comment->comment_parent==0){
          $temp= array();
          $temp['id'] = $comment->comment_ID;
          $temp['comment_author'] = $comment->comment_author;
          $temp['comment_date'] = $comment->comment_date;
          $temp['content'] = $comment->comment_content;
          $child= get_child($temp);
          if($child){
             $temp['child']= $child;
          }

          $comments_arr[]= $temp;
      }
   }

   //print_r($comments_arr); exit;

   return new WP_REST_Response($comments_arr, 200);
}


function replyComment($request){
    $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );

   
    $param = $request->get_params();

    $token = $param['token'];
    $post_id = $param['post_id'];
    $parent_cid = $param['parent_cid'];
    $reply = $param['reply'];
    
    $user_id = GetMobileAPIUserByIdToken($token);

    if(!$user_id){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid token');
       $data['error_code'] = "invalid_token";
       return new WP_REST_Response($data, 403); 
    }

    // get user by user id
    $user_temp= get_user_by('ID', $user_id);
    $user= $user_temp->data;

    if(empty($user)){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid user');
       $data['error_code'] = "invalid_user";
       return new WP_REST_Response($data, 403); 
    }

    // check if comment and post id exist
    if($parent_cid=='' || $post_id=='' || $reply==''){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid request');
       $data['error_code'] = "invalid_request";
       return new WP_REST_Response($data, 403);
    }

    $args = array(
      'comment_post_ID' => $post_id,
      'comment_author' => $user->user_login,
      'comment_author_email' => $user->user_email,
      'comment_author_url' => 'http://',
      'comment_content' => $reply,
      'comment_type' => '',
      'comment_parent' => $parent_cid,
      'user_id' => $user->ID,
      'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
      'comment_date' => current_time('mysql'),
      'comment_approved' => 1,
    );

    //print_r($args); exit;
    if(wp_insert_comment($args)){
       $data  = array(
          "status" => "ok",
          "msg"    => "comment submitted successfully",
          "errormsg" => "",
          'error_code' => ""
       );

       return new WP_REST_Response($data, 200);
    }else{
       $data['status']     = "error";
       $data['errormsg']   = __('Comment could not be submitted');
       $data['error_code'] = "invalid_request";
       return new WP_REST_Response($data, 403); 
    }
}

function get_child($comment){
    // child comment
    $args = array('parent' => $comment['id'], 'order' => 'ASC');
    $child_cmts = get_comments($args);
    
    $child_arr= array();
    if($child_cmts){
        foreach($child_cmts as $child_cmt){
            $temp_child= array();
            $temp_child['id'] = $child_cmt->comment_ID;
            $temp_child['comment_author'] = $child_cmt->comment_author;
            $temp_child['comment_date'] = $child_cmt->comment_date;
            $temp_child['content'] = $child_cmt->comment_content;
            $child_arr[]= $temp_child;
        }
    }

    return $child_arr;
}

function updateProfile($request){
    global $wpdb;
    $data  = array(
        "status" => "ok",
        "errormsg" => "",
        'error_code' => ""
    );

   
    $param = $request->get_params();

    $token            = $param['token'];
    $email            = $param['email'];
    $username         = $param['username'];
    
   
    $user_id = GetMobileAPIUserByIdToken($token);

    if(!$user_id){
       $data['status']     = "error";
       $data['errormsg']   = __('Invalid token');
       $data['error_code'] = "invalid_token";
       return new WP_REST_Response($data, 403);	
    }
    
    if ($email != '' && $username != '') {

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
           $data['status']     = "error";
           $data['errormsg']   = __('This is not a Valid Email.');
           $data['error_code'] = "invalid_email";
           return new WP_REST_Response($data, 403);
        }

        // if email already exist
        $already_exist=email_exists($email);
        if($already_exist && $already_exist!=$user_id){
           $data['status']     = "error";
           $data['errormsg']   = __('Email already exist');
           $data['error_code'] = "invalid_email";
           return new WP_REST_Response($data, 403);
        }

        // if username already exist
        $already_exist=username_exists($username);
        if($already_exist && $already_exist!=$user_id){
           $data['status']     = "error";
           $data['errormsg']   = __('Username already exist');
           $data['error_code'] = "invalid_username";
           return new WP_REST_Response($data, 403);
        }
        
        $userdata= array();
        $userdata['ID']            = $user_id;
        $userdata['user_email']    = $email;
        $userdata['user_nicename'] = $username;
        //$userdata['user_login'] =    $username;
        $userdata['display_name']  = $username;
        $userdata['first_name']    = $username;


        
    }
}


function facebook_login($request)
{

    $username = $request->get_param('username');
    $email = $request->get_param('email');
    $fbname = $request->get_param('fbname');
    $facebook_id = $request->get_param('facebook_id');

    if (!is_email($email)) {
        $email = $facebook_id . "_facebook_random@gmail.com";
    }

    $userloginFlag = true;
    $user_id = username_exists($username);
    if (!$user_id and email_exists($email) == false) {
        $userloginFlag = false;
    }
    // check if facebookID exists
    $users_check_facebookID = get_users(
        array(
            'meta_key' => 'facebook_id',
            'meta_value' => $facebook_id,
        )
    );

    if (count($users_check_facebookID) == 0) {
        $userloginFlag = false;
    } else {
        $user_id = $users_check_facebookID[0]->data->ID;
    }

    if ($userloginFlag == true) {
        $data = fb_check_login($user_id, $facebook_id);
    } else {
        $user_id = FBSignup($email, $username, $fbname, $facebook_id);
        $data = fb_check_login($user_id);
    }

    if (count($data) > 0) {
        return new WP_REST_Response($data, 200);
    } else {
        $res = array("status" => 'error');
        return new WP_REST_Response($res, 403);
    }

}

// Facebook Signup function
function FBSignup($user_email, $user_name, $first_name, $facebook_id)
{

    $user_id = username_exists($user_name);
    if (!$user_id and email_exists($user_email) == false) {
        $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
        $user_id = wp_create_user($user_name, $password, $user_email);
        $user = new WP_User($user_id);
        $user->set_role('author');
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'nickname', $first_name);
        update_user_meta($user_id, 'facebook_id', $facebook_id);
        return $user_id;
    } else {
        return $user_id;
    }

}

// Facebook Login function
function fb_check_login($user_id, $facebook_id = null)
{
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;

    /** Try to authenticate the user with the passed credentials*/
    $user = get_userdata($user_id);
    if (count($user) > 0) {
        if ($facebook_id) {
            update_user_meta($user_id, 'facebook_id', $facebook_id);
        }

        /** Valid credentials, the user exists create the according Token */
        $issuedAt = time();
        $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'data' => array(
                'user' => array(
                    'id' => $user->data->ID,
                ),
            ),
        );

        /** Let the user modify the token data before the sign. */
        $token = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), $secret_key);

        /** The token is signed, now create the object with no sensible user data to the client*/
        $data = array(
            'token' => $token,
            'user_email' => $user->data->user_email,
            'user_nicename' => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
        );

        /** Let the user modify the data before send it back */
        $data = apply_filters('jwt_auth_token_before_dispatch', $data, $user);
        return $data;
    }

}

function validate_token($request)
{
    $param = $request->get_params();
    $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    if ($user_id) {
        $res['status'] = "ok";
        return new WP_REST_Response($res, 200);
    } else {
        $res['status'] = "error";
        $res['msg'] = "Your session expired, please login again";
        return new WP_REST_Response($res, 200);
    }

}

// Create new user
function MobileApiMakeNewAuthor($request)
{

    $data = array("status" => "ok", "errormsg" => "", 'error_code' => "");
    $param = $request->get_params();
    $user_name = $param['email'];
    $user_email = $param['email'];
    $password = $param['password'];
    $type = $param['role'];
    $first_name=$param['name'];

    // JWT_AUTH_SECRET_KEY define in wp-config
    if ($param['jw_auth_sec'] != JWT_AUTH_SECRET_KEY) {
        $data['status'] = "error";
        $data['errormsg'] = __('cheating----.');
        $data['error_code'] = "token_error";
        return new WP_REST_Response($data, 403);
    }

    if (!is_email($user_email)) {
        $data['status'] = "error";
        $data['errormsg'] = __('This is not a Valid Email.');
        $data['error_code'] = "invalid_email";
        return new WP_REST_Response($data, 403);
    }

    $user_id = username_exists($user_name);

    if ($passowrd == " ") {
        $data['status'] = "error";
        $data['errormsg'] = __('Please provide password.');
        $data['error_code'] = "password_blank";
        return new WP_REST_Response($data, 403);
    }
    if (!$user_id and email_exists($user_email) == false) {
        //$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $user_id = wp_create_user($user_name, $password, $user_email);
        $user = new WP_User($user_id);
        if ($type == "player") {
            $user->set_role('player');
        }

        if ($type == "collage_coach" || $type == "high_collage_coach" || $type == "middle_collage_coach") {
            $user->set_role('coach');
        }

        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'nickname', $first_name);
        update_user_meta($user_id, 'type', $type);
        return new WP_REST_Response($data, 200);
    } else {
        $data['status'] = "error";
        $data['errormsg'] = __('Account exists with this email.');
        $data['error_code'] = "user_already";
        return new WP_REST_Response($data, 403);
    }
}

function user_id_exists($user)
{
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));

    if ($count == 1) {return true;} else {return false;}
}

// Get User ID by token
function GetMobileAPIUserByIdToken($token)
{
    $decoded_array = array();
    $user_id = 0;
    if ($token) {
        $decoded = JWT::decode($token, JWT_AUTH_SECRET_KEY, array('HS256'));

        $decoded_array = (array) $decoded;

    }
    if (count($decoded) > 0) {
        $user_id = $decoded_array['data']->user->id;
    }

    if (user_id_exists($user_id)) {
        return $user_id;
    } else {
        return false;

    }
}

// forgot password
function RetrivePassword($request)
{
    global $wpdb, $current_site;

    $data = array("status" => "ok", "msg" => "you will be recive login instructions.");
   
    $param = $request->get_params();
    $user_login = sanitize_text_field($param['user_login']);

    if (!is_email($user_login)) {
        $data = array("status" => "error", "msg" => "Please provide valid email.");
        return new WP_REST_Response($data, 403);
    }

    if (empty($user_login)) {
        $data = array("status" => "error", "msg" => "User email is empty.");
        return new WP_REST_Response($data, 403);

    } elseif (strpos($user_login, '@')) {

        $user_data = get_user_by('email', trim($user_login));

    } else {
        $login = trim($user_login);
        $user_data = get_user_by('login', $login);
    }

    if (!$user_data) {
        $data = array("status" => "error", "msg" => "User not found using email.");
        return new WP_REST_Response($data, 403);
    }

    // redefining user_login ensures we return the right case in the email
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_email;

    $allow = apply_filters('allow_password_reset', true, $user_data->ID);

    if (!$allow) {
        $data = array("status" => "error", "msg" => "Password reset not allowed.");
        return new WP_REST_Response($data, 403);
    } elseif (is_wp_error($allow)) {
        $data = array("status" => "error", "msg" => "Something went wrong");
        return new WP_REST_Response($data, 403);
    }

    //$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
    // if ( empty($key) ) {
    // Generate something random for a key...
    $key = get_password_reset_key($user_data);
    $password = wp_generate_password(6, false);
    wp_set_password($password, $user_data->ID);

    // do_action('retrieve_password_key', $user_login, $key);
    // Now insert the new md5 key into the db
    //$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
    // }

    $message = __('Hello ,') . "\r\n\r\n";

    $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
    //$message .= network_home_url( '/' ) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
    $message .= sprintf(__('New Password : %s'), $password) . "\r\n\r\n";

    //$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('Thank you') . "\r\n\r\n";
    // $message .= network_site_url("resetpass/?key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n";
    /* <http://vipeel.testplanets.com/resetpass/?key=wDDY0rDxwfaWPOFZrrmf&login=ajaytest%40gmail.com> */
    if (is_multisite()) {
        $blogname = $GLOBALS['current_site']->site_name;
    } else
    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    {
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }

    $title = sprintf(__('[%s] Password Reset'), $blogname);

    $title = apply_filters('retrieve_password_title', $title);
    $message = apply_filters('retrieve_password_message', $message, $key);

    if ($message && !wp_mail($user_email, $title, $message)) {
        $data = array("status" => "error", "msg" => "The e-mail could not be sent..");
        return new WP_REST_Response($data, 403);
    }
    // wp_die( __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...') );

    return new WP_REST_Response($data, 200);
}

//apply_filters('jwt_auth_token_before_dispatch', $data, $user);
add_filter('jwt_auth_token_before_dispatch', 'mobileapi_jwt_auth_token_before_dispatch', 10, 2);
function mobileapi_jwt_auth_token_before_dispatch($data, $user)
{

    $role = 'player';
    if (in_array('coach', (array) $user->roles)) {
        $role = 'coach';
    }


    $data['role'] = $role;
    $first_name = get_user_meta($user->ID, "first_name", true);
    if (!empty($first_name)) {
        $data['user_display_name'] = ucfirst($first_name);
    } else {
        $data['user_display_name'] = ucfirst($data['user_display_name']);
    }
    $useravatar = get_user_meta($user->ID, 'wp_user_avatar', true);
    if ($useravatar) {
        $img = wp_get_attachment_image_src($useravatar, array('150', '150'), true);
        $data['user_avatar'] = $img[0];
    } else {
        $data['user_avatar'] = 'http://1.gravatar.com/avatar/1aedb8d9dc4751e229a335e371db8058?s=96&d=mm&r=g';
    }
    $data['user_id'] = $user->ID;
   

    return $data;
}

function GetUserImage($request)
{
    $param = $request->get_params();
    $token = $param['token'];
    $user_id = GetMobileAPIUserByIdToken($token);
    $useravatar = get_user_meta($user_id, 'wp_user_avatar', true);
    if ($useravatar) {
        $img = wp_get_attachment_image_src($useravatar, array('150', '150'), true);
        $data['user_avatar'] = $img[0];
    } else {
        $data['user_avatar'] = 'https://beaconapp.betaplanets.com/wp-content/uploads/2019/05/1aedb8d9dc4751e229a335e371db8058.jpg';
    }
    return new WP_REST_Response($data, 200);
}




add_filter('rest_prepare_beacon_services', 'func_rest_prepare_beacon_services', 10, 3 );
add_filter('rest_prepare_products', 'func_rest_prepare_beacon_services', 10, 3 );

function func_rest_prepare_beacon_services( $data, $post, $request ) {
    $commentCount = wp_count_comments($post->ID);
    $data->data['comment_count'] = $commentCount->approved;
    global $wpdb;
   if($data->data['featured_media']>0){
       $image_attributes = wp_get_attachment_image_src($data->data['featured_media'],'large');
       $data->data['media_url']=$image_attributes[0];
   }else{
       $data->data['media_url']='https://via.placeholder.com/150';
   }
   
  $data->data['price'] = "$".get_post_meta($post->ID,'price',true);
  $data->data['city'] = get_post_meta($post->ID,'city',true);
  $data->data['state'] = get_post_meta($post->ID,'state',true);
  $data->data['category'] = get_post_meta($post->ID,'category',true);
  $term = get_term($data->data['category'],'service_category');
  $data->data['category_name'] = $term->name;
  
  $first_name = get_user_meta($data->data['author'],'first_name',true);
  $last_name = get_user_meta($data->data['author'],'last_name',true);
  
  $name = $first_name." ".$last_name;
  $nameL = $first_name.$last_name;
  if($nameL==''){
      $data->data['author_name'] = get_user_meta($data->data['author'],'nickname',true);
  }else{
     $data->data['author_name'] = $name ;
  }
  

   
    $useravatar = get_user_meta($data->data['author'],'wp_user_avatar',true); 
           if($useravatar){
            $img = wp_get_attachment_image_src($useravatar, array('150','150'), true );
              $user_avatar=$img[0];
              $data->data['author_avatar_urls']=$user_avatar;
              //$response->data['author_avatar']=$user_avatar;
           }else{
             
               $data->data['author_avatar_urls']='https://beaconapp.betaplanets.com/wp-content/uploads/2019/05/1aedb8d9dc4751e229a335e371db8058.jpg';
           }
    return $data;
}

//do_action( 'rest_insert_attachment', $attachment, $request, true );
add_action('rest_insert_attachment','func_rest_insert_attachment',10,3);
function func_rest_insert_attachment($attachment, $request,$is_create){
  
  if(isset($request['post']) && $request['post']!=''){
      set_post_thumbnail($request['post'],$attachment->ID);
  }
  if(isset($request['type']) && $request['type']=="edit"){
      if(isset($request['old_image']) && $request['old_image']!=""){
           wp_delete_attachment($request['old_image'],true); 
      }
  }
  //_wp_attachment_wp_user_avatar
  
  if(isset($request['_wp_attachment_wp_user_avatar']) && $request['_wp_attachment_wp_user_avatar']!=''){
      //set_post_thumbnail($request['post'],$attachment->ID);
      update_post_meta($attachment->ID, '_wp_attachment_wp_user_avatar', $request['_wp_attachment_wp_user_avatar']);
      //wp_user_avatar
      update_user_meta($request['_wp_attachment_wp_user_avatar'], 'wp_user_avatar', $attachment->ID);
  }
}

////apply_filters( "rest_{$this->post_type}_query", $args, $request ); 
add_filter('rest_beacon_services_query', 'func_rest_beacon_services_query', 10, 2 );
add_filter('rest_products_query', 'func_rest_beacon_services_query', 10, 2 );
function func_rest_beacon_services_query($args, $request)
{
    $param = $request->get_params();
    $token = $param['token'];
    if($token!='' && $param['mypost']==1){
       $user_id = GetMobileAPIUserByIdToken($token); 
       if($user_id){
         $args['author']=$user_id;
       }else{
          $args['author']=72348237483278274827482374; 
       }
       
    }
    
    
    return $args;
}


function fetch_cat($request){
    
  $param = $request->get_params();
  
  $terms = get_terms( array(
    'taxonomy' => 'service_category',
    'hide_empty' => false,
   ) );
   
   $kits = array();
   $activeKitName='';
   $res = array("status"=>"error","cat"=>'');
   
         $active_profile =false;
         foreach($terms as $tm){
               $kit_item = array();
               $kit_item['name']=$tm->name;
               $kit_item['id']=$tm->term_id;
               $term_image = get_term_meta($tm->term_id, 'image', true);
               if($term_image){
                $img = wp_get_attachment_image_src($term_image, array('150','150'), true );
               $kit_item['image']=$img[0];    
               }
               
               $kits[]=$kit_item;
           }
        $res['status']="ok";
        return new WP_REST_Response($kits, 200);
       

    return new WP_REST_Response($res, 403);
}



