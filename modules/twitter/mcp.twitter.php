<?php

/*
  =====================================================  
   File: mcp.twitter.php
  -----------------------------------------------------
   Purpose: Twitter class - CP
  =====================================================
*/
 
 if ( ! defined('EXT'))
 {
    exit('Invalid file request');
 }
 
 class Twitter_CP{
 
      var $version      = '1.0';
      var	$row_limit		= 15; // Used for pagination
      var	$horizontal_nav	= TRUE;
      
      /** -------------------------
      /**  Constructor
      /** -------------------------*/
      
      function Twitter_CP( $switch = TRUE )
      {
          global $IN, $DB, $DSP, $LANG;
          
          /** -------------------------------
          /**  Is the module installed?
          /** -------------------------------*/
          
          $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Twitter'");
          
          if ($query->row['count'] == 0)
          {
          	return;
          }
          
          /** -------------------------------
          /**  Assign Base Crumb
          /** -------------------------------*/
          
          $DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=Twitter', $LANG->line('twitter_module_name'));        
          
          if ($switch)
          {
              switch($IN->GBL('P'))
              {
                  case 'entry_form'                : $this->entry_form();
                        break;
                  case 'insert_new_entry'          : $this->insert_new_entry();
                        break;                        
                  case 'update_entry'   		       :  $this->update_entry();
                        break;
                  case 'multi_edit_entries'        :  $this->multi_edit_entries();
                        break;            
                  case 'view_entries'   			     :  $this->view_entries();
                        break;
                  case 'view_history'   			     :  $this->view_history();
                        break;
                  case 'delete_entry'			         :  $this->delete_entry();
                	      break;                                                    
                	case 'twitter_prefs_form'        :  $this->twitter_prefs_form();
                	      break;
                  case 'prefs_submit_handler'      :  $this->prefs_submit_handler();
                        break;
                  case 'update_status'             :  $this->update_twitter_status();
                        break;       
                  case 'twitt_home'                :  $this->twitter_home();
                        break;                                    
                  default       			             :  $this->twitter_home();
                        break;
              }
          }
          
      }
      /* END */
      
      /**------------------------------
      /**   twitter status update form 
      /**------------------------------*/
                  
      function twitter_home($msg='')
      {        
        global $DSP, $DB, $IN, $SESS, $FNS, $LANG, $PREFS;
      
        $DSP->title  = $LANG->line('twitter_module_name');
        $DSP->crumb  = $LANG->line('twitter_module_name');
        
        
        // Build the output		
		    $nav = $this->nav(	array(
									'twitter_home'			       => array('P' => 'twitt_home'),
									'twitter_new_entry'			   => array('P' => 'entry_form'),									
									'twitter_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view'),
									'twitter_view_history'		 => array('P' => 'view_history'),									
									'twitter_preferences'		   => array('P' => 'twitter_prefs_form')
								)
				);

    		if ($nav != '')
    		{
    			$DSP->body .= $nav;
    		}
    		
    		if(isset($_POST['message'])){
          $message = $_POST['message'];
        }else{
          $message = '';
          //get status for that current momentusing api and show to input box
        }

      
        $DSP->body .= $DSP->form_open(                        
        								array('action' => 'C=modules'.AMP.'M=twitter'.AMP.'P=update_status')
                        );      
                        
        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('update_twitter_status'));

        if ($msg != '')
        {
			    $DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
        }

        $DSP->body .= $DSP->div('box');


		//Cron url
		$query = $DB->query("SELECT action_id FROM exp_actions WHERE class = 'Twitter' AND method='update'");
		$action_id = $query->row['action_id'];
		$cron_url_txt = "URL for cron: {$PREFS->ini('site_url')}index.php?ACT=$action_id";
		$DSP->body .= $DSP->qdiv('itemWrapper', $cron_url_txt);
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $LANG->line('twitter_update_inst'));
            
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_textarea('message', $message, '4','','550px'));
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('submit')));
        $DSP->body .= $DSP->div_c();                        
        $DSP->body .= $DSP->form_close();   
      
      }
      /* END */

      /**------------------------------
      /**   twitter status update form 
      /**------------------------------*/
                  
      function view_history()
      {        

		    global $DSP, $IN, $DB, $LANG, $FNS, $LOC, $PREFS;

    		/** ------------------------------------
    		/**  Page heading/crumb/title
    		/** ------------------------------------*/
    		
        $title  = $LANG->line('twitter_module_name');
    		$crumb = $LANG->line('twitter_history');
        
        //fetch entries
        $sql = "SELECT message_id, message, entry_date  FROM exp_twitter_history ORDER BY entry_date desc";
        $query = $DB->query($sql);

    		/** -----------------------------
    		/**  Do we need pagination?
    		/** -----------------------------*/
		
    		$paginate = '';
    		
    		if ($query->num_rows > $this->row_limit)
    		{ 
    			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');
    						
    			$base_url = BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=view_history';
    						
    			$paginate = $DSP->pager(  $base_url,
    									  $query->num_rows, 
    									  $this->row_limit,
    									  $row_count,
    									  'row'
    									);
    			 
    			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
    			
    			$query = $DB->query($sql);    
    		}

    		/** ------------------------------
    		/**  Build the output
    		/** ------------------------------*/
    		        
        if ($PREFS->ini('time_format') == 'us')
    		{
    			$datestr = '%m/%d/%y %h:%i %a';
    		}
    		else
    		{
    			$datestr = '%Y-%m-%d %H:%i';
    		}
                    

        // Build the output		
        $r = '';
		    $nav = $this->nav(	array(
									'twitter_home'			       => array('P' => 'twitt_home'),
									'twitter_new_entry'			   => array('P' => 'entry_form'),									
									'twitter_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view'),
									'twitter_view_history'		 => array('P' => 'view_history'),									
									'twitter_preferences'		   => array('P' => 'twitter_prefs_form')
								)
				);
				

    		if ($nav != '')
    		{
    			$r .= $nav;
    		}
        
        
    		// If there are no entries yet we'll show an error message    
        if ($query->num_rows == 0)
        {          
    			$r  .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_entries')));			    
			    return $this->content_wrapper($title, $crumb, $r);              			
        }
            

        /** ------------------------------
        /**  Table Header
        /** ------------------------------*/
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
                  	  $DSP->tr().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('twitter_msg').$DSP->td_c().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('sent_date').$DSP->td_c().
                  	  $DSP->tr_c();

    		/** ------------------------------
    		/**  Table Rows
    		/** ------------------------------*/
    
    		$i = 0;
    	
    		foreach ($query->result as $row)
    		{		
    			
    			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
    			
    			$r .=  $DSP->tr();
    			
          if($row['message_id']==0)      
    			   $r .=  $DSP->table_qcell($style,  NBS.NBS.'<b>'.$row['message'].'</b>' , '50%');
    			else
             $r .=  $DSP->table_qcell($style, NBS.NBS.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=entry_form'.AMP.'message_id='.$row['message_id'], '<b>'.$row['message'].'</b>'), '50%');  
                
    			$timestamp = strtotime($row['entry_date']);
    			
          $r .=  $DSP->table_qcell($style, $LOC->decode_date($datestr, $timestamp, TRUE), '15%');
    			$r .=  $DSP->tr_c();
    		}

        $r .=  $DSP->table_c();
		
        $r .= $DSP->table('', '0', '', '65%');
        $r .= $DSP->tr().$DSP->td();
            
        // Pagination
            
        if ($paginate != '')
        {
        	$r .= $DSP->qdiv('crumblinks', $DSP->qdiv('itemWrapper', $paginate));
        }
        
        $r .= $DSP->td_c().$DSP->td('defaultRight');
        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();


        return $this->content_wrapper($title, $crumb, $r);
           
      
      }
      /* END */
      
      /** ------------------------------------------------
      /**  Content Wrapper
      /** ------------------------------------------------*/
      
      function content_wrapper($title = '', $crumb = '', $content = '')
      {
          global $DSP, $DB, $IN, $SESS, $FNS, $LANG;
                                    
          // Default page title if not supplied  
                          
          if ($title == '')
          {
              $title = $LANG->line('twitter_history');
          }
                  
          // Default bread crumb if not supplied
          
          if ($crumb == '')
          {
      	     $crumb = '';        
          }
                  
          // Set breadcrumb and title
          
          $DSP->title  = $title;
          $DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=twitt_home')).$crumb;
      
          // Default content if not supplied
      
          if ($content == '')
          {
              $content .= $this->twitter_home();
          }
                  
          $DSP->body	.=	$DSP->td('', '', '', '', 'top');
          
          $DSP->body	.=	$DSP->qdiv('itemWrapper', $content);
          
      }
      /* END */
          
      
      /**------------------------------------------
      /**   Update twitter status, uses API Methods
      /** ----------------------------------------*/
      function update_twitter_status()
      {

          global $IN, $DSP, $LANG, $LOC, $DB, $REGX;          
          
          if ($this->validate_msg_length($_POST['message'],1,140)==false){
			       return $DSP->error_message($LANG->line('invalid_length'));
          }
          
          //get twitter settings
          $query = $DB->query("SELECT * FROM exp_twitter_settings LIMIT 1");        
          if ($query->num_rows == 1)
        	{        		
        		$twitter_username =  $query->row['twitter_username'];
        		$twitter_password =  base64_decode($query->row['twitter_password']);
        		$system_status    =  $query->row['system_status'];
        		
        		//check system is on and twitter info provided
        		if($system_status==0){
              return $DSP->error_message($LANG->line('system_off'));
            }
        		
        	}else{
            return $DSP->error_message($LANG->line('setting_not_done'));
          }
		  
        //include API class file that post messages using CURL
        require PATH_MOD.'twitter/lib/class.twitter.php';
        
        $twitter = new TwitterAPI($twitter_username, $twitter_password);
        $response = $twitter->updateStatus(urlencode($_POST['message']));
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
					case '413':
						$msg = "Error: " . $response;
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

        $date = date("Y-m-d", ($LOC->server_now) );
        //store this on history table         
        $DB->query("INSERT INTO exp_twitter_history(message,sent_date) values ('".$DB->escape_str($_POST['message'])."', '".$date."' )");
      	      	
      	return $this->twitter_home($msg);
      	
      }
      /*  END */            

      /** ----------------------------------
      /** Entry form to store twitter message
      /** ---------------------------------*/
      function entry_form($msg = '')
      {
		      global $IN, $DSP, $LANG, $DB;

    		/** ------------------------------------
    		/**  Are we editing an existing entry?
    		/** ------------------------------------*/		      

		    $message_id = ( ! $IN->GBL('message_id')) ? FALSE : $IN->GBL('message_id');

    		if ($message_id !== FALSE)
    		{
    			$query = $DB->query("SELECT message_id, message, send_day, send_time, message_type, status FROM exp_twitter_messages WHERE message_id = '".$DB->escape_str($message_id)."' ");

          if ($query->num_rows == 1)
        	{
              $message        =  $query->row['message'];
              $send_day       =  $query->row['send_day'];
              $send_time      =  substr($query->row['send_time'],0,5);

              $message_type   =  $query->row['message_type'];
              $status         =  $query->row['status'];
                      	  
          }
    		
    		}else{
    		
              $message        =  '';
              $send_day       =  '';
              $send_time      =  '';
              $message_type   =  'twitter';
              $status         =  '1';        
        }
		
    		/** ------------------------------------
    		/**  Page heading/crumb/title
    		/** ------------------------------------*/
    		
        $DSP->title  = $LANG->line('twitter_module_name');
    		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter', $LANG->line('twitter_module_name'));
    		$DSP->crumb .= $DSP->crumb_item($LANG->line('twitter_new_entry'));       	
        

        // Build the output		
		    $nav = $this->nav(	array(
									'twitter_home'			       => array('P' => 'twitt_home'),
									'twitter_new_entry'			   => array('P' => 'entry_form'),									
									'twitter_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view'),
									'twitter_view_history'		 => array('P' => 'view_history'),									
									'twitter_preferences'		   => array('P' => 'twitter_prefs_form')
								)
				);
				

    		if ($nav != '')
    		{
    			$DSP->body .= $nav;
    		}
        
        $DSP->body  .= $DSP->qdiv('tableHeading', $LANG->line('twitter_new_entry'));

    		if ($message_id !== FALSE)
    		{
        
          $DSP->body .= $DSP->form_open(                        
          								array('action' => 'C=modules'.AMP.'M=twitter'.AMP.'P=update_entry')
                                  								
                          );
          $DSP->body .= $DSP->input_hidden('message_id', $message_id);
                                    
                          
        }else{

          $DSP->body .= $DSP->form_open(                        
          								array('action' => 'C=modules'.AMP.'M=twitter'.AMP.'P=insert_new_entry')                                  								
                          );
        }
                          
        
        $DSP->body .= $DSP->input_hidden('message_type', $message_type);
        
        if ($msg != '')
        {
			    $DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
        }
        
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%');
		
		    $style ='tableCellOne';

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('twitter_msg')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_textarea('message', $message, '4','','550px'))). $DSP->qdiv('itemWrapper', $LANG->line('twitter_msg_info')) , '50%');
    		$DSP->body .= $DSP->tr_c();

    		//build the day dropdown
        $day_dropdown = '';
    		$day_dropdown .= $DSP->input_select_header('send_day');

    		$day_dropdown .= $DSP->input_select_option('Monday', 'Monday', (($send_day=='Monday')?1:'') );
    		$day_dropdown .= $DSP->input_select_option('Tuesday', 'Tuesday', (($send_day=='Tuesday')?1:'') );    			
    		$day_dropdown .= $DSP->input_select_option('Wednesday', 'Wednesday', (($send_day=='Wednesday')?1:'') );
    		$day_dropdown .= $DSP->input_select_option('Thursday', 'Thursday', (($send_day=='Thursday')?1:'') );    			
    		$day_dropdown .= $DSP->input_select_option('Friday', 'Friday', (($send_day=='Friday')?1:'') );
    		$day_dropdown .= $DSP->input_select_option('Saturday', 'Saturday', (($send_day=='Saturday')?1:'') );
    		$day_dropdown .= $DSP->input_select_option('Sunday', 'Sunday', (($send_day=='Sunday')?1:'') );    			

    		$day_dropdown .= $DSP->input_select_footer();	
    		
    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('send_on')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $day_dropdown )), '50%');
    		$DSP->body .= $DSP->tr_c();

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('send_on')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text('send_time', $send_time, '5', '5', 'input', '40px'))).$DSP->qdiv('itemWrapper', $LANG->line('sample_time')), '50%');
    		$DSP->body .= $DSP->tr_c();

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('status')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_checkbox('status','1', (($status==1)?'y':'') ).$LANG->line('open') )), '50%');
    		$DSP->body .= $DSP->tr_c();
            		
    		$DSP->body .= $DSP->table_c();
    		
		    $DSP->body	.=	$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));       
		
        $DSP->body .= $DSP->form_close();   
      
      }      
      /* END */
      

      /** ----------------------------------
      /** Store Messages to DB
      /** ---------------------------------*/                      
      function insert_new_entry()
      {
          global $IN, $DSP, $SESS, $LOC, $LANG, $DB, $REGX;

          if ($this->validate_msg_length($_POST['message'],1,140)==false){
			       return $DSP->error_message($LANG->line('invalid_length'));
          }
          
          $send_time=$_POST['send_time'];
          $send_time_data = @explode(":",$send_time);
          $send_hour = (int) $send_time_data[0];
          $send_minute = @(int) $send_time_data[1];
          
          //formatted time
          $send_time  = str_pad($send_hour,2,'0',STR_PAD_LEFT).':'.str_pad($send_minute,2,'0',STR_PAD_LEFT).':00';  
          
          $status = isset($_POST['status'])?1:0;
          
          if($send_hour<0 OR $send_hour>23 OR $send_minute<0 OR $send_minute>59 ){
              return $DSP->error_message($LANG->line('invalid_send_time'));
          } 
         
          $DB->query("INSERT INTO exp_twitter_messages(author_id, message, send_day, send_time, message_type, status, entry_date) values (".$SESS->userdata('member_id').", '".$DB->escape_str($_POST['message'])."','".$_POST['send_day'] ."', '".$_POST['send_time'] ."','".$_POST['message_type']."' , ".$status." ,".$LOC->now." )");
          
          //$msg = $LANG->line('twitt_message_inserted');          
          return $this->view_entries('insert');          
      
      }
      /* END */

      /** ----------------------------------
      /** Update Stored Messages to DB
      /** ---------------------------------*/                      
      function update_entry()
      {
          global $IN, $DSP, $SESS, $LOC, $LANG, $DB, $REGX;

          if ($this->validate_msg_length($_POST['message'],1,140)==false){
			       return $DSP->error_message($LANG->line('invalid_length'));
          }
          
          $send_time      =$_POST['send_time'];
          $send_time_data = @explode(":",$send_time);
          $send_hour      = (int) $send_time_data[0];
          $send_minute    = @(int) $send_time_data[1];
          $status         = isset($_POST['status'])?1:0;
          $message_id     = $_POST['message_id'];

          //formatted time
          $send_time  = str_pad($send_hour,2,'0',STR_PAD_LEFT).':'.str_pad($send_minute,2,'0',STR_PAD_LEFT).':00';  
          
          
          if($send_hour<0 OR $send_hour>23 OR $send_minute<0 OR $send_minute>59 ){
              return $DSP->error_message($LANG->line('invalid_send_time'));
          } 
          
          $sql = "UPDATE exp_twitter_messages  SET message='".$DB->escape_str($_POST['message'])."', send_day='".$_POST['send_day'] ."', send_time='".$_POST['send_time'] ."', message_type='".$_POST['message_type']."' , status=".$status."  WHERE message_id=".$message_id; 
          $DB->query($sql);          
                    
          return $this->view_entries('update');          
      
      }
      /* END */
      
      /** ----------------------------------
      /** View Store Messages
      /** ---------------------------------*/
      function view_entries($action = '')
      {
		    global $DSP, $IN, $DB, $LANG, $FNS, $LOC, $PREFS;

    		/** ------------------------------------
    		/**  Page heading/crumb/title
    		/** ------------------------------------*/
    		
        $title  = $LANG->line('twitter_module_name');
    		//$crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter', $LANG->line('twitter_module_name'));
    		$crumb = $LANG->line('twitter_view_entries');
    		
    		$r = '';
        if ($action == 'update')
        {
        		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.NBS.$LANG->line('twitt_message_updated') )));
        }
        elseif ($action == 'insert')
        {
        		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.NBS.$LANG->line('twitt_message_inserted') )));
        }               	
        
        //fetch entries
        $sql = "SELECT message_id, message, send_day, send_time, entry_date, status FROM exp_twitter_messages ORDER BY entry_date desc";
        $query = $DB->query($sql);

    		/** -----------------------------
    		/**  Do we need pagination?
    		/** -----------------------------*/
		
    		$paginate = '';
    		
    		if ($query->num_rows > $this->row_limit)
    		{ 
    			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');
    						
    			$base_url = BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=view_entries';
    						
    			$paginate = $DSP->pager(  $base_url,
    									  $query->num_rows, 
    									  $this->row_limit,
    									  $row_count,
    									  'row'
    									);
    			 
    			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
    			
    			$query = $DB->query($sql);    
    		}

    		/** ------------------------------
    		/**  Build the output
    		/** ------------------------------*/
    		
    		
    		// This message is shown when entries are deleted
    		
    		if ($IN->GBL('action') == 'delete')
    		{
    			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('entry_deleted'))));                         
    		}
    		elseif (isset($LANG->language['action_'.$IN->GBL('action')]))
    		{
    			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('action_'.$IN->GBL('action')))));
    		}
    		        
        if ($PREFS->ini('time_format') == 'us')
    		{
    			$datestr = '%m/%d/%y %h:%i %a';
    		}
    		else
    		{
    			$datestr = '%Y-%m-%d %H:%i';
    		}
                    

        // Build the output		
		    $nav = $this->nav(	array(
									'twitter_home'			       => array('P' => 'twitt_home'),
									'twitter_new_entry'			   => array('P' => 'entry_form'),									
									'twitter_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view'),
									'twitter_view_history'		 => array('P' => 'view_history'),									
									'twitter_preferences'		   => array('P' => 'twitter_prefs_form')
								)
				);
				

    		if ($nav != '')
    		{
    			$r .= $nav;
    		}
        
        
    		// If there are no categories yet we'll show an error message    
        if ($query->num_rows == 0)
        {          
    			$r  .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_entries')));			    
			    return $this->content_wrapper($title, $crumb, $r);              			
        }

        /** ------------------------------
        /**  Form to Deletes entries
        /** ------------------------------*/
            
        $r .=	  $DSP->toggle().   
        		    $DSP->form_open(
        		    
                						array(
                								'action' => 'C=modules'.AMP.'M=twitter'.AMP.'P=multi_edit_entries', 
                								'name'	=> 'target',
                								'id'	=> 'target',
                							)
            					  );

        /** ------------------------------
        /**  Table Header
        /** ------------------------------*/
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
                  	  $DSP->tr().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('twitter_msg').$DSP->td_c().                  	  
                      $DSP->td('tableHeading', '', '').$LANG->line('send_day').$DSP->td_c().
                      $DSP->td('tableHeading', '', '').$LANG->line('send_time').$DSP->td_c().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('entry_date').$DSP->td_c().                  	  
                  	  $DSP->td('tableHeading', '', '').$LANG->line('status').$DSP->td_c().
                  	  $DSP->td('tableHeading', '', '').$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$DSP->td_c().
                  	  $DSP->tr_c();

    		/** ------------------------------
    		/**  Table Rows
    		/** ------------------------------*/
    
    		$i = 0;
    	
    		foreach ($query->result as $row)
    		{		
    			
    			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
    			
    			$r .=  $DSP->tr();      
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=entry_form'.AMP.'message_id='.$row['message_id'], '<b>'.$row['message'].'</b>'), '27%');      
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['send_day'], '10%');
          $r .=  $DSP->table_qcell($style, NBS.NBS.$row['send_time'], '10%');      
    			$r .=  $DSP->table_qcell($style, $LOC->decode_date($datestr, $row['entry_date'], TRUE), '15%');
    			$r .=  $DSP->table_qcell($style, ( ($row['status'] == 1) ? NBS.NBS.$LANG->line('open') : $DSP->qdiv('highlight', $LANG->line('closed')) ), '8%' );			
    			$r .=  $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['message_id'], '' , ' id="delete_box_'.$row['message_id'].'"'), '10%');      
    			$r .=  $DSP->tr_c();
    		}

        $r .=  $DSP->table_c();
		
        $r .= $DSP->table('', '0', '', '100%');
        $r .= $DSP->tr().$DSP->td();
            
        // Pagination
            
        if ($paginate != '')
        {
        	$r .= $DSP->qdiv('crumblinks', $DSP->qdiv('itemWrapper', $paginate));
        }
        
        $r .= $DSP->td_c().$DSP->td('defaultRight');
        
            
        // Actions and submit button
        
        $r .= $DSP->div('itemWrapper');
        
        $r .= $DSP->input_submit($LANG->line('submit'));
        
        $r .= NBS.$DSP->input_select_header('action').
              $DSP->input_select_option('delete', $LANG->line('delete_selected')).
              $DSP->input_select_option('null', '--').
              $DSP->input_select_option('close', $LANG->line('close_selected')).
              $DSP->input_select_option('open', $LANG->line('open_selected')).
              $DSP->input_select_footer();
              
        $r .= $DSP->div_c();
        
        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
        
        $r .= $DSP->form_close();
        
        return $this->content_wrapper($title, $crumb, $r);
      
      }      
      /* END */
      
    	/** -----------------------------
    	/**  Edit Multiple entries
    	/** -----------------------------*/
    	
    	function multi_edit_entries()
    	{
        global $IN, $DB, $DSP, $FNS;
            
                
        $entries = array();
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
    		      $entries[] = $DB->escape_str($val);
            }
        }
            
        if (sizeof($entries) == 0)
        {
          $FNS->redirect(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=view_entries');
          exit;
        }
            
        $action = $IN->GBL('action');
        
        if ($IN->GBL('action') == 'open')
        {
        	$DB->query("UPDATE exp_twitter_messages SET status = 1	WHERE message_id IN ('".implode("','", $entries)."') ");
        }
        elseif ($IN->GBL('action') == 'close')
        {
        	$DB->query("UPDATE exp_twitter_messages SET status = 0 WHERE message_id IN ('".implode("','", $entries)."') ");
        }
        elseif ($IN->GBL('action') == 'delete')
        {
        	$DB->query("DELETE FROM  exp_twitter_messages WHERE message_id IN ('".implode("','", $entries)."') ");
        }
        else
        {
        	$action = '';
        }
        
        $FNS->redirect(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=view_entries'.AMP.'action='.$action);
        exit;
    	}
    	/* END */
        
      /** ----------------------------------
      /** Preferences Form
      /** ---------------------------------*/
      function twitter_prefs_form($msg = '')
      {        
//        global $DSP, $DB, $IN, $SESS, $FNS, $LANG;
		      global $IN, $DSP, $LANG, $DB;

    		/** ------------------------------------
    		/**  Page heading/crumb/title
    		/** ------------------------------------*/
    		
        $DSP->title  = $LANG->line('twitter_module_name');
    		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter', $LANG->line('twitter_module_name'));
    		$DSP->crumb .= $DSP->crumb_item($LANG->line('twitter_preferences'));       	
        
        //get the values
        
        $query = $DB->query("SELECT * FROM exp_twitter_settings LIMIT 1");        
        if ($query->num_rows == 1)
        	{
        		$setting_id = $query->row['setting_id'];
        		$twitter_username =  $query->row['twitter_username'];
        		$system_status = $query->row['system_status'];
        		$admin_email =  $query->row['admin_email'];
        		$notification =  $query->row['notification'];
        	}else{
            $setting_id = '';
        		$twitter_username = '';
        		$system_status = '';
        		$admin_email='';
        		$notification = '';          
          }

        // Build the output		
		    $nav = $this->nav(	array(
									'twitter_home'			       => array('P' => 'twitt_home'),
									'twitter_new_entry'			   => array('P' => 'entry_form'),									
									'twitter_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view'),
									'twitter_view_history'		 => array('P' => 'view_history'),									
									'twitter_preferences'		   => array('P' => 'twitter_prefs_form')
								)
				);
				

    		if ($nav != '')
    		{
    			$DSP->body .= $nav;
    		}
        
        $DSP->body  .= $DSP->qdiv('tableHeading', $LANG->line('twitter_preferences'));
        
        $DSP->body .= $DSP->form_open(                        
        								array('action' => 'C=modules'.AMP.'M=twitter'.AMP.'P=prefs_submit_handler'),
        								array('setting_id' => $setting_id)
                        );
                        
        //if setting already updated, pass the setting id                       
        if($setting_id!=''){
          $DSP->body .= $DSP->tr($DSP->input_hidden('setting_id', $setting_id));
        }
        
        if ($msg != '')
        {
			    $DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
        }
        
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%');
		
		    $style ='tableCellOne';

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('twitter_username')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text('twitter_username', $twitter_username, '35', '40', 'input', '30%'))), '50%');
    		$DSP->body .= $DSP->tr_c();
    		
    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('twitter_password')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_pass('twitter_password', null, '35', '40', 'input', '30%'))).$DSP->qdiv('default', $LANG->line('allow_leave_blank')), '50%');
    		$DSP->body .= $DSP->tr_c();

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('system_status')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_radio('system_status','1', $system_status).$LANG->line('on').$DSP->input_radio('system_status','0',($system_status==0?1:'') ).$LANG->line('off') )), '50%');
    		$DSP->body .= $DSP->tr_c();

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('admin_email')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text('admin_email', $admin_email, '35', '40', 'input', '30%'))), '50%');
    		$DSP->body .= $DSP->tr_c();

    		$DSP->body .= $DSP->tr();
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('notification')), '10%');				
    		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_radio('notification','1', $notification).$LANG->line('yes').$DSP->input_radio('notification','0', ($notification==0?1:'') ).$LANG->line('no') )), '50%');
    		$DSP->body .= $DSP->tr_c();
    		
    		$DSP->body .= $DSP->table_c();
    		
		    $DSP->body	.=	$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('update')));       
		
        $DSP->body .= $DSP->form_close();   
      
      }      
      /* END */

      /** ----------------------------------
      /** Store Preferences to DB
      /** ---------------------------------*/                      
      function prefs_submit_handler()
      {
          global $IN, $DSP, $LANG, $DB, $REGX;
          $setting_id = (is_numeric($IN->GBL('setting_id')) AND $IN->GBL('setting_id') != 0) ? $IN->GBL('setting_id') : FALSE;
          
          if ($_POST['twitter_username'] == '' OR $_POST['twitter_username'] == ''){
			       return $DSP->error_message($LANG->line('marked_fields_required'));
          }

          if ($setting_id === FALSE)
          {
            if ($_POST['twitter_password'] == '' OR $_POST['twitter_password'] == ''){
  			       return $DSP->error_message($LANG->line('marked_fields_required'));
            }
          }  

         if ($REGX->valid_email($_POST['admin_email'])!=1)
		     {
		          return $DSP->error_message($LANG->line('twitt_invalid_email'));
         }
         
        if ($setting_id === FALSE)
        {
        	$DB->query("INSERT INTO exp_twitter_settings(setting_id, twitter_username, twitter_password, system_status, admin_email, notification) values ('', '".$DB->escape_str($_POST['twitter_username'])."', '".base64_encode($_POST['twitter_password'])."',".$_POST['system_status'].",'".$DB->escape_str($_POST['admin_email'])."', '".$_POST['notification']."')");
        }
        else
        {
            if ($_POST['twitter_password'] == '' OR $_POST['twitter_password'] == ''){
        	       $DB->query("UPDATE exp_twitter_settings SET twitter_username = '".$DB->escape_str($_POST['twitter_username'])."', system_status=".$_POST['system_status'].", admin_email='".$_POST['admin_email']."', notification=".$_POST['notification']." WHERE setting_id = '{$setting_id}'");
            }else{
        	       $DB->query("UPDATE exp_twitter_settings SET twitter_username = '".$DB->escape_str($_POST['twitter_username'])."', twitter_password = '".base64_encode($_POST['twitter_password'])."', system_status=".$_POST['system_status'].", admin_email='".$_POST['admin_email']."', notification=".$_POST['notification']." WHERE setting_id = '{$setting_id}'");            
            }
        }

      	$msg = $LANG->line('twitt_preferences_updated');
      	
      	return $this->twitter_prefs_form($msg);         
          
      
      }
      /* END */

      
      /** ----------------------------------
      /** Validate message length
      /** ----------------------------------*/
      
      function validate_msg_length($msg='', $min=1, $max=140){
        global $REGX, $FNS, $LANG;
        
        $msg = trim($msg);
        if(strlen($msg)<$min OR strlen($msg)>$max)
          return false; 
        
        return true;
      
      }
      /* END */             

      /** -----------------------------------
      /**  Navigation Tabs
      /** -----------------------------------*/
  
      // Takes an array as input and creates the navigation tabs from it.
      // This functiion is called by the one above.
  
      function nav($nav_array)
      {
        global $IN, $DSP, $PREFS, $REGX, $FNS, $LANG;
        
                
    		/** -------------------------------
    		/**  Build the menus
    		/** -------------------------------*/
    		// Equalize the text length.
    		// We do this so that the tabs will all be the same length.
		
    		$temp = array();
    		foreach ($nav_array as $k => $v)
    		{
    			$temp[$k] = $LANG->line($k);
    		}
    		$temp = $DSP->equalize_text($temp);

    		//-------------------------------
                                
        $highlight = array(
        					'twitt_home'			    => 'twitter_home',
        					'entry_form'			    => 'twitter_new_entry',        				
        					'view_entries'			  => 'twitter_view_entries',
        					'view_history'			  => 'twitter_view_history',
							    'twitter_prefs_form'	=> 'twitter_preferences'
        					);
        					
        $page = $IN->GBL('P');					
        					
        if (isset($highlight[$page]))
        {
        	$page = $highlight[$page];
        }
        
        $r = <<<EOT
        <script type="text/javascript"> 
        <!--

    		function styleswitch(link)
    		{                 
    			if (document.getElementById(link).className == 'altTabs')
    			{
    				document.getElementById(link).className = 'altTabsHover';
    			}
    		}
    	
    		function stylereset(link)
    		{                 
    			if (document.getElementById(link).className == 'altTabsHover')
    			{
    				document.getElementById(link).className = 'altTabs';
    			}
    		}
    		
    		-->
    		</script>		

EOT;
    
		    $r .= $DSP->table_open(array('width' => '100%'));

    		$nav = array();
    		foreach ($nav_array as $key => $val)
    		{
    			$url = '';
    		
    			if (is_array($val))
    			{
    				$url = BASE.AMP.'C=modules'.AMP.'M=twitter';		
    			
    				foreach ($val as $k => $v)
    				{
    					$url .= AMP.$k.'='.$v;
    				}					
    				$title = $temp[$key];
    			}
    			else
    			{
    				$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
    				$url = $REGX->prep_query_string($FNS->fetch_site_index()).$qs.'URL='.$REGX->prep_query_string($this->prefs['twitter_url']);
    				
    				$title = $LANG->line('twitter_module_name');
    			}
    			
    
    			$url = ($url == '') ? $val : $url;
    
    			$div = ($page == $key) ? 'altTabSelected' : 'altTabs';
    			$linko = '<div class="'.$div.'" id="'.$key.'"  onclick="navjump(\''.$url.'\');" onmouseover="styleswitch(\''.$key.'\');" onmouseout="stylereset(\''.$key.'\');">'.$title.'</div>';
    			
    			$nav[] = array('text' => $DSP->anchor($url, $linko));
    		}

    		$r .= $DSP->table_row($nav);		
    		$r .= $DSP->table_close();

  		  return $r;          
      }
      /* END */
      
      /** -------------------------
      /** Module Installer
      /** -------------------------*/
      
      function twitter_module_install()
      {
          global $DB;
            
          $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Twitter','$this->version', 'y')";

		$sql[] = "INSERT INTO exp_actions (action_id, class, method) 
									VALUES    ('', 'Twitter', 'update')";
									          
          $sql[] = "CREATE TABLE IF NOT EXISTS exp_twitter_settings(
            					setting_id int(4) unsigned NOT NULL auto_increment,
            					twitter_username varchar(20) NOT NULL,
            					twitter_password varchar(128) NOT NULL,            					
            					system_status int(1) NOT NULL default 1,
            					admin_email varchar(128) NOT NULL,
            					notification int(1) NOT NULL default 0,
            					PRIMARY KEY (setting_id)                   
                   )";

          $sql[] = "CREATE TABLE IF NOT EXISTS exp_twitter_messages(
            					message_id int(11) unsigned NOT NULL auto_increment,
					            author_id int(10) unsigned NOT NULL default '0',
                      message text NOT NULL,
                      send_day varchar(20) NOT NULL,
            					send_time time NOT NULL,
            					message_type varchar(10) NOT NULL default 'twitter',
            					status int(1) NOT NULL default 1,
                      entry_date int(11) NOT NULL,
            					edit_date timestamp(14) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,            					
            					PRIMARY KEY (message_id),
                      KEY (author_id)                   
                   )";

          $sql[] = "CREATE TABLE IF NOT EXISTS exp_twitter_history(
            					history_id int(11) unsigned NOT NULL auto_increment,
					            message_id int(10) unsigned NOT NULL default '0',
					            message text NOT NULL,
					            sent_date date NOT NULL,
            					entry_date timestamp(14) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,            					
            					PRIMARY KEY (history_id),
                      KEY (message_id)       
                   )";

            
          foreach ($sql as $query)
          {
              $DB->query($query);
          }
          
          return true;
      }
      
    /** ----------------------------------------
    /**  Module de-installer
    /** ----------------------------------------*/

    function twitter_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Twitter'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Twitter'";
//        $sql[] = "DELETE FROM exp_actions WHERE class = 'Twitt'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Twitter'";
        $sql[] = "DROP TABLE IF EXISTS exp_twitter_settings";
        $sql[] = "DROP TABLE IF EXISTS exp_twitter_messages";
        $sql[] = "DROP TABLE IF EXISTS exp_twitter_history";        

        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */
                       
                
 }
 
 
?>
