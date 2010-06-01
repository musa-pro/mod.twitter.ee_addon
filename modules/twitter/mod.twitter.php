<?php

/*
=====================================================
 File: mod.twitter.php
-----------------------------------------------------
 Purpose: twitter status updating class for scheduled 
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class Twitter {
	
	var $debug = FALSE;
    
    /**--------------------------------------------------------
    /** This function update twitter status from stored message
    /**--------------------------------------------------------*/    
 
    function update()
    {        
        global $IN, $DB, $REGX, $LANG, $TMPL, $FNS, $PREFS, $LOC;

/*
    		if ($TMPL->fetch_param('debug') == 'yes')
    		{
    			$this->debug = TRUE;
    		}*/

    		
        //get the preferences
        $query = $DB->query("SELECT * FROM exp_twitter_settings LIMIT 1");
        if ($query->num_rows == 1)
        {
        		$twitter_username =  $query->row['twitter_username'];
        		$twitter_password =  base64_decode($query->row['twitter_password']);        		
        		$system_status = $query->row['system_status'];
        		$admin_email =  $query->row['admin_email'];
        		$notification =  $query->row['notification'];
        }
        
        if($system_status){

      		//get current setting time and day
      		$datetime = date("M d, Y H:i", ($LOC->server_now) );
      		$date = date("Y-m-d", ($LOC->server_now) );
      		$day = date("l",$LOC->server_now); 
          
          $minTime = date("H:i", ($LOC->server_now-5*60) );
          $minTimeA = explode(":",$minTime);
          $minTime = str_pad($minTimeA[0],2,'0',STR_PAD_LEFT).':'.str_pad($minTimeA[1],2,'0',STR_PAD_LEFT).':00';
          
          $maxTime = date("H:i", ($LOC->server_now+5*60) );
          $maxTimeA = explode(":",$maxTime);
          $maxTime = str_pad($maxTimeA[0],2,'0',STR_PAD_LEFT).':'.str_pad($maxTimeA[1],2,'0',STR_PAD_LEFT).':00';
          
          //get the entries with open, date time etc
          $sql =        "SELECT m.message_id, m.message, m.send_time, m.send_day, h.message_id as sent_message_id, h.sent_date as sent_date
                        FROM exp_twitter_messages AS m
                        LEFT JOIN exp_twitter_history as h on m.message_id=h.message_id  
                        WHERE m.status=1 AND m.send_day='".$day."' AND send_time>='".$minTime."' AND send_time<='".$maxTime."'
                        GROUP BY m.message_id ORDER BY m.send_time ASC,h.sent_date DESC  
                      "; 

          $query = $DB->query($sql);

          if ($query->num_rows > 0 )
          {
                  $message = '';
               		foreach ($query->result as $row)
                  {
                  
               		 if($row['sent_date']!=$date)
               		 {
                    $message_id = $row['message_id'];
                    $message = $row['message'];                    
                    break;
                   }                   
                  }
 
                  if($message)
                  {
                        //update twitter status
                        //include API class file that post messages using CURL
                        require PATH_MOD.'twitter/lib/class.twitter.php';
                        
                        $twitter = new TwitterAPI($twitter_username, $twitter_password);
                        $response = $twitter->updateStatus(urlencode( $message ));
                        
                        $LANG->fetch_language_file('twitter');
                        $msg = '';
                        
                				switch ($twitter->lastStatusCode()) {
                					case '200':
                						$response['success'] = TRUE;
                						$msg = $LANG->line('successful_tweet');
                						break;
                					case '400':
                						$msg = $LANG->line('rate_limit_exceeded');
                						break;
                					case '401':
                						$msg = $LANG->line('no_account');
                						break;
                					case '403':
                						$msg = "Error: " . $response;
                						break;
                					case '404':
                						$msg = "Error: " . $LANG->line('not_authorised');
                						break;
                					case '500':
                						$msg = "Error: " . $LANG->line('twiiter_outage');
                						break;
                					case '502':
                						$msg = "Error: " . $LANG->line('twiiter_outage');
                						break;
                					case '503':
                						$msg = "Error: " . $LANG->line('twiiter_outage');
                						break;
                					case '0':
                						$response['success'] = TRUE;
                						$msg = $LANG->line('successful_tweet');
                						break;
                						
                				} 
      
                        //store this on history table         
                        $DB->query("INSERT INTO exp_twitter_history(message_id, message,sent_date) values (".$message_id.", '".$DB->escape_str($message)."', '".$date."' )");
                 
                        //if notification on, send notification to admin
                        if($notification AND $admin_email!=''){
              
                    				if ( ! class_exists('EEmail'))
                    				{
                    					require PATH_CORE.'core.email'.EXT;
                    				}
                    				
                    				$email_msg = $LANG->line('API_notification');
                            
                            $email_msg = str_replace("%x",$datetime, $email_msg);
                            $email_msg = str_replace("%y",$message, $email_msg);
                            $email_msg = @str_replace("%z",$msg, $email_msg);
                                                                        
                    				$email = new EEmail;
                    
                  					$email->initialize();	
                  					$email->wordwrap = true;
                  					$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
                  					$email->to($admin_email);
                  					$email->reply_to($PREFS->ini('webmaster_email'));					 
                  					$email->subject($LANG->line('API_notification_subject'));	
                  					$email->message($REGX->entities_to_ascii($email_msg));		
                  					$email->Send();
                        }
                        
                        return $message;
                  }else
                  {
                   $LANG->fetch_language_file('twitter');
                   return $LANG->line('no_entry_scheduled');
                  }
          
          }else
          {
           $LANG->fetch_language_file('twitter');
           return $LANG->line('no_entry_scheduled');          
          }
        
        }else{
         $LANG->fetch_language_file('twitter');
         return $LANG->line('system_offline');
        }
        
        
            
    }
    /*  END */
    
    /**--------------------------------------------
    /** function to check if email works/cron works
    /*--------------------------------------------*/
        
    function test_email()
    {        
        global $IN, $DB, $REGX, $LANG, $TMPL, $FNS, $PREFS, $LOC;

              
				if ( ! class_exists('EEmail'))
				{
					require PATH_CORE.'core.email'.EXT;
				}
				
				$email_msg = 'Cron executed at '. date("M d, Y H:i", ($LOC->server_now) );;
        
                                                    
				$email = new EEmail;

				$email->initialize();	
				$email->wordwrap = true;
				$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));	
				$email->to('musa@bglobalsourcing.com');
				$email->reply_to($PREFS->ini('webmaster_email'));					 
				$email->subject('cron works');	
				$email->message($REGX->entities_to_ascii($email_msg));		
				$email->Send();
    }
    /*  END */
}
?>
