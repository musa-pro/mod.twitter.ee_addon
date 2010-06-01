<?php

/**
  * Twitter interface class
  * Nov 26 2007 Nick Beam
  * Bugs, comments, questions: winkerbeam@gmail.com
  * http://rbrw.net -- http://tinydinosaur.com
  *
  * This is a simple interface to the Twitter API.
  * I've tried to keep as close as possible to the real API
  *   calls (some had to be changed due to ambiguity), but all
  *   of the arguments are as they are in the official docs.
  *
  * Usage:
  *  $twitter = new Twitter("username", "password");
  *  $public_timeline_xml = $twitter->getPublicTimeline("xml");
  *
  * Methods:
  *  getPublicTimeline($format [, $since_id])
  *  getFriendsTimeline($format [, $id [, $since ]])
  *  getUserTimeline($format [, $id [, $count [, $since ]]])
  *  showStatus($format, $id)
  *  updateStatus($status)
  *  destroyStatus($format, $id)
  *  getReplies($format [, $page ])
  *  getFriends($format [, $id ])
  *  getFollowers($format [, $lite ])
  *  getFeatured($format)
  *  showUser($format [, $id [, $email ]])
  *  getMessages($format [, $since [, $since_id [, $page ]]])
  *  getSentMessages($format [, $since [, $since_id [, $page ]]])
  *  newMessage($format, $user, $text)
  *  destroyMessage($format, $id)
  *  createFriendship($format, $id)
  *  destroyFriendship($format, $id)
  *  verifyCredentials([$format])
  *  endSession()
  *  getArchive($format [, $page ])
  *  getFavorites($format [, $id [, $page ]])
  *  createFavorite($format, $id)
  *  destroyFavorite($format, $id)
  *  lastStatusCode()
  *  lastAPICall()
  */

class TwitterAPI {
	/* Username:password format string */
	var $credentials;
	
	/* Contains the last HTTP status code returned */
	var $http_status;
	
	/* Contains the last API call */
	var $last_api_call;
	
	/* Twitter class constructor */
	function TwitterAPI($username, $password) {
		$this->credentials = sprintf("%s:%s", $username, $password);
	}
	
	function getPublicTimeline($format, $since_id = 0) {
		$api_call = sprintf("http://twitter.com/statuses/public_timeline.%s", $format);
		if ($since_id > 0) {
			$api_call .= sprintf("?since_id=%d", $since_id);
		}
		return $this->APICall($api_call);
	}
	
	function getFriendsTimeline($format, $id = NULL, $since = NULL) {
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/friends_timeline/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/friends_timeline.%s", $format);
		}
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		return $this->APICall($api_call, true);
	}
	
	function getUserTimeline($format, $id = NULL, $count = 20, $since = NULL) {
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/user_timeline/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/user_timeline.%s", $format);
		}
		if ($count != 20) {
			$api_call .= sprintf("?count=%d", $count);
		}
		if ($since != NULL) {
			$api_call .= sprintf("%ssince=%s", (strpos($api_call, "?count=") === false) ? "?" : "&", urlencode($since));
		}
		return $this->APICall($api_call, true);
	}
	
	function showStatus($format, $id) {
		$api_call = sprintf("http://twitter.com/statuses/show/%d.%s", $id, $format);
		return $this->APICall($api_call);
	}
	
	function updateStatus($status) {
		$status = urlencode(stripslashes(urldecode($status)));
		$api_call = sprintf("http://twitter.com/statuses/update.xml?status=%s", $status);
		return $this->APICall($api_call, true, true);
	}
	
	function getReplies($format, $page = 0) {
		$api_call = sprintf("http://twitter.com/statuses/replies.%s", $format);
		if ($page) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function destroyStatus($format, $id) {
		$api_call = sprintf("http://twitter.com/statuses/destroy/%d.%s", $id, $format);
		return $this->APICall($api_call, true);
	}
	
	function getFriends($format, $id = NULL) {
		// take care of the id parameter
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/friends/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/friends.%s", $format);
		}
		return $this->APICall($api_call, true);
	}
	
	function getFollowers($format, $lite = NULL) {
		$api_call = sprintf("http://twitter.com/statuses/followers.%s%s", $format, ($lite) ? "?lite=true" : NULL);
		return $this->APICall($api_call, true);
	}
	
	function getFeatured($format) {
		$api_call = sprintf("http://twitter.com/statuses/featured.%s", $format);
		return $this->APICall($api_call);
	}
	
	function showUser($format, $id, $email = NULL) {
		if ($email == NULL) {
			$api_call = sprintf("http://twitter.com/users/show/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/users/show.xml?email=%s", $email);
		}
		return $this->APICall($api_call, true);
	}
	
	function getMessages($format, $since = NULL, $since_id = 0, $page = 1) {
		$api_call = sprintf("http://twitter.com/direct_messages.%s", $format);
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		if ($since_id > 0) {
			$api_call .= sprintf("%ssince_id=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $since_id);
		}
		if ($page > 1) {
			$api_call .= sprintf("%spage=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function getSentMessages($format, $since = NULL, $since_id = 0, $page = 1) {
		$api_call = sprintf("http://twitter.com/direct_messages/sent.%s", $format);
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		if ($since_id > 0) {
			$api_call .= sprintf("%ssince_id=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $since_id);
		}
		if ($page > 1) {
			$api_call .= sprintf("%spage=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function newMessage($format, $user, $text) {
		$text = urlencode(stripslashes(urldecode($text)));
		$api_call = sprintf("http://twitter.com/direct_messages/new.%s?user=%s&text=%s", $format, $user, $text);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyMessage($format, $id) {
		$api_call = sprintf("http://twitter.com/direct_messages/destroy/%s.%s", $id, $format);
		return $this->APICall($api_call, true);
	}
	
	function createFriendship($format, $id) {
		$api_call = sprintf("http://twitter.com/friendships/create/%s.%s", $format, $id);
		return $this->APICall($api_call, true);
	}
	
	function destroyFriendship($format, $id) {
		$api_call = sprintf("http://twitter.com/friendships/destroy/%s.%s", $format, $id);
		return $this->APICall($api_call, true);
	}
	
	function verifyCredentials($format = NULL) {
		$api_call = sprintf("http://twitter.com/account/verify_credentials%s", ($format != NULL) ? sprintf(".%s", $format) : NULL);
		return $this->APICall($api_call, true);
	}
	
	function endSession() {
		$api_call = "http://twitter.com/account/end_session";
		return $this->APICall($api_call, true);
	}
	
	function getArchive($format, $page = 1) {
		$api_call = sprintf("http://twitter.com/account/archive.%s", $format);
		if ($page > 1) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function getFavorites($format, $id = NULL, $page = 1) {
		if ($id == NULL) {
			$api_call = sprintf("http://twitter.com/favourings.%s", $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/favourings/%s.%s", $id, $format);
		}
		if ($page > 1) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function createFavorite($format, $id) {
		$api_call = sprintf("http://twitter.com/favourings/create/%d.%s", $id, $format);
		return $this->APICall($api_call, true);
	}
	
	function destroyFavorite($format, $id) {
		$api_call = sprintf("http://twitter.com/favourings/destroy/%d.%s", $id, $format);
		return $this->APICall($api_call, true);
	}
	
	function APICall($api_url, $require_credentials = false, $http_post = false) {
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $api_url);

		$entry = '<content type="text/html; charset=iso-8859-1">';
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS , $entry);
		
		if ($require_credentials) {
			curl_setopt($curl_handle, CURLOPT_USERPWD, $this->credentials);
		}
		
    if ($http_post) {
			curl_setopt($curl_handle, CURLOPT_POST, true);
		//  curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "source=simonmoyes");
		}
		
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
		$twitter_data = curl_exec($curl_handle);
		$this->http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		
		$this->last_api_call = $api_url;
		curl_close($curl_handle);
		return $twitter_data;
	}
	
	function lastStatusCode() {
		return $this->http_status;
	}
	
	function lastAPICall() {
		return $this->last_api_call;
	}
}

?>
