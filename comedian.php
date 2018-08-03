<?php
if (!defined('BASEPATH'))
   exit('No direct script access allowed');

class Comedian extends CI_Controller {

 
	public $data = array();
   public function __construct() {
      parent::__construct();
	  $this->load->model('User_model');
	  $this->load->model('Comedians_model');
	 $fb_rd_page = array('fb_red_page'=>$this->uri->uri_string());
	 if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				//ajax called do nothing
		  }elseif(!isset($_GET['callback'])){
			$this->session->set_userdata($fb_rd_page);
		  }
	  
   }

// Construct 

   public function index() {
	   $this->load->model('Liveshows_model');
	   
	   
	   $character = 'featured';//$this->uri->segment(2,'a');
	   //$this->data['comedians'] = $this->Comedians_model->filter_comedian($character);
	   $this->data['comedians'] = $this->Comedians_model->get_featured_comedians();
	   
	   $this->data['comedians']  = $this->check_comedian_has_videos($this->data['comedians']);
	   $this->data['comedians'] = $this->get_comedian_videos($this->data['comedians']);
	   /*echo '<pre>';
	   print_r($this->data['comedians']);exit;*/
	   $this->data['header_ad_code'] = $this->User_model->get_code_header_ad('comedian_listing');
	   $this->data['total_comedians'] = $this->Comedians_model->get_total_filter_comedian($character);
	   $this->data['character'] = $character;	   
	   $this->load->view('blocks/header.php',$this->data);
	   $this->load->view('browse_comedians.php');
	   $this->load->view('blocks/footer.php');
	   
   
   }
   //load more results using ajax call
   public function get_comedian_videos($comedians){
	   if($comedians){
		   foreach($comedians as $com){
			   if($com->has_videos=='1'){
				   $this->load->model('Channels_model');
				   $this->load->model('Videos_model');
				   $user_id = $this->session->userdata('id');
				   $allowuncensored = $this->session->userdata('allowuncensored');
				   $is_logedin = TRUE;
				   if(!$user_id){
					   $user_id = 0;
					   $allowuncensored = 0;
					   $is_logedin = FALSE;
				   }
				   $comedian_channelid = $this->Channels_model->get_comedian_channel_id($com->comedian_id);
				   if($this->session->userdata('countryaccesslist_allowadsupport')=='1'){
						$com->comedian_videos = $this->Channels_model->get_channelplaylist_foruser($comedian_channelid,$user_id,4);
						
						if($com->comedian_videos){
							foreach($com->comedian_videos as $v){
								$v->video_thumb = $this->Videos_model->get_thumbnail_url_new($v->video_id,'74','42');
								$v->vlink = base_url($com->comedian_seoname.'/'.$this->User_model->toAscii($v->video_shorttitle).'/'.$v->video_id);
							}
						}
				   }
			   }
			   
			   
			   
		   }
	   }
	   return $comedians;
   }
   public function display_bio_text($comedians){
	   if($comedians){
		   foreach($comedians as $comedian){
	   			$bio_text = '';
				if(!$comedian->latest_shows && !isset($comedian->comedian_videos)){
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,400);
					if(strlen(strip_tags($comedian->comedian_biotext))>400) 
						$bio_text .= '...';
				} 
				else if(isset($comedian->comedian_videos) && !$comedian->latest_shows){												
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,220);
					if(strlen(strip_tags($comedian->comedian_biotext))>220) 
						$bio_text .= '...';
				}elseif(!isset($comedian->comedian_videos) && $comedian->latest_shows){
					$bio_lines = 300-(count($comedian->latest_shows)*50);
					
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,$bio_lines);
					if(strlen(strip_tags($comedian->comedian_biotext))>$bio_lines) 
						$bio_text .= '...';
				}
				if((!$comedian->latest_shows || !isset($comedian->comedian_videos)) && $bio_text=='' ){
					$bio_text = 'Bio coming soon.';
				}
				$comedian->comedian_biotext = $bio_text;
		   }
	   }
	   return $comedians;
   }
   public function display_bio_text_popup($comedians){
	   if($comedians){
		   foreach($comedians as $comedian){
	   			$bio_text = '';
				if(!$comedian->latest_shows && !isset($comedian->comedian_videos)){
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,500);
					if(strlen(strip_tags($comedian->comedian_biotext))>500) 
						$bio_text .= '...';
				} 
				else if(isset($comedian->comedian_videos) && !$comedian->latest_shows){												
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,320);
					if(strlen(strip_tags($comedian->comedian_biotext))>320) 
						$bio_text .= '...';
				}elseif(!isset($comedian->comedian_videos) && $comedian->latest_shows){
					$bio_lines = 300-(count($comedian->latest_shows)*20);
					
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,$bio_lines);
					if(strlen(strip_tags($comedian->comedian_biotext))>$bio_lines) 
						$bio_text .= '...';
				}elseif($comedian->latest_shows && isset($comedian->comedian_videos)){
					$bio_lines = 300-(count($comedian->latest_shows)*50);
					
					$bio_text = substr(strip_tags($comedian->comedian_biotext),0,$bio_lines);
					if(strlen(strip_tags($comedian->comedian_biotext))>$bio_lines) 
						$bio_text .= '...';
				}
				
				if((!$comedian->latest_shows || !isset($comedian->comedian_videos)) && $bio_text=='' ){
					$bio_text = 'Bio coming soon.';
				}
				$comedian->comedian_biotext = $bio_text;
		   }
	   }
	   return $comedians;
   }
   public function load_more_comedians(){
	   
	   $character = $this->input->post('character',TRUE);
	   $last_record = (int)$this->input->post('last_record',TRUE);
	   if($character!='' && is_int($last_record)){
		   $new_comedians['comedians'] = $this->Comedians_model->load_more_comedians($character,$last_record);
		   $new_comedians['comedians'] = $this->get_comedian_latest_show($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->check_comedian_has_videos($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->get_comedian_videos($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->display_bio_text($new_comedians['comedians']);
		   echo json_encode($new_comedians);		   
	   }
   }
   public function check_comedian_has_videos($comedians){
	   $user_id = $this->session->userdata('id');
		   $allowuncensored = $this->session->userdata('allowuncensored');
		   $is_logedin = TRUE;
		   if(!$user_id){
			   $user_id = 0;
			   $allowuncensored = 0;
			   $is_logedin = FALSE;
		   }
		   $this->load->model('Channels_model');
		   if($comedians){
			   foreach($comedians as $com){
			 	  $com->has_videos = 0;
			 	  $this->data['has_videos'] = 0;
			 	   $comedian_channelid = $this->Channels_model->get_comedian_channel_id($com->comedian_id);
			 	   if($this->session->userdata('countryaccesslist_allowadsupport')=='1'){
			 		   $this->data['comedian_videos'] = $this->Channels_model->get_channelplaylist_foruser($comedian_channelid,$user_id,100);
			 		   if($this->data['comedian_videos']){
			 			   $com->has_videos = 1;
			 		   }
			 	   }
			   }
		   }
		   return $comedians;
   }
   //load comedian by character ajax
   public function load_comedians_by_character(){
	   $character = $this->input->post('character',TRUE);
	   $last_record = 0;
	   if($character=='featured'){
		   $new_comedians['comedians'] = $this->Comedians_model->get_featured_comedians();
		   $new_comedians['comedians'] = $this->get_comedian_latest_show($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->check_comedian_has_videos($new_comedians['comedians']);	
		   $new_comedians['comedians'] = $this->get_comedian_videos($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->display_bio_text($new_comedians['comedians']);  
		   $new_comedians['total_comedians'] = count($new_comedians['comedians']);
		   echo json_encode($new_comedians);		   
	   }
	   elseif($character!='' && is_int($last_record)){
		   $new_comedians['comedians'] = $this->Comedians_model->filter_comedian($character);
		   $new_comedians['comedians'] = $this->get_comedian_latest_show($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->check_comedian_has_videos($new_comedians['comedians']);	
		   $new_comedians['comedians'] = $this->get_comedian_videos($new_comedians['comedians']);
		   $new_comedians['comedians'] = $this->display_bio_text($new_comedians['comedians']);  
		   $new_comedians['total_comedians'] = $this->Comedians_model->get_total_filter_comedian($character);
		   echo json_encode($new_comedians);		   
	   }
   }
   public function get_comedian_for_popup(){
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if($comedian_id){
		   //
		   $info['comedian'] = $this->Comedians_model->get_comedian_info_for_popup($comedian_id);
		   $info['comedian'] = $this->get_comedian_latest_show($info['comedian']);
		   $info['comedian'] = $this->check_comedian_has_videos($info['comedian']);	
		   $info['comedian'] = $this->get_comedian_videos($info['comedian']);
		   $info['comedian'] = $this->display_bio_text_popup($info['comedian']); 
		   echo json_encode($info);	
	   }
   }
   public function search_comedian_by_name(){
	   $comedian_name = $this->input->post('kw',TRUE);
	   if(strlen(trim($comedian_name))>2){
		   $comedian_name = strtolower($comedian_name);
		   $search_comedian['comedians'] = $this->Comedians_model->search_comedian_by_name($comedian_name);
		   $search_comedian['comedians'] = $this->get_comedian_latest_show($search_comedian['comedians']);
		   $search_comedian['comedians'] = $this->check_comedian_has_videos($search_comedian['comedians']);
		   $search_comedian['comedians'] = $this->get_comedian_videos($search_comedian['comedians']);
		   $search_comedian['comedians'] = $this->display_bio_text($search_comedian['comedians']);
		   echo json_encode($search_comedian);
	   }
   }
   public function get_comedian_latest_show($comedians){
	   $this->load->model('Liveshows_model');
	   if($comedians){
		   foreach($comedians as $com){
			   $com->latest_shows = $this->Liveshows_model->get_liveshows_forcomedian($com->comedian_id,3);
			   if($com->latest_shows){
				   foreach($com->latest_shows as $ls){
					   $ls->liveshow_name = substr($ls->liveshowinstance_showname,0,35);
					   if(strlen($ls->liveshowinstance_showname)>35)
					   		$ls->liveshow_name .= ' ...';
					   $ls->show_date = date('D M d - g:i A',strtotime($ls->liveshowinstance_startdatetime));
					   $ls->link_date = date('Y-m-d',strtotime($ls->liveshowinstance_startdatetime));
				   }
			   }
			
		   }
	   }
	   return $comedians;
	   
   }
   public function comedian_profile(){
	   $is_four_zero_four = 0;
	   $this->load->model('Liveshows_model');
	   $this->load->model('Comedian_portal_model');
	   $check_blog = $this->uri->segment(2);
	   if($check_blog=='blog'){
		   $this->data['is_blog'] = 'blog';
		   $this->data['bpost_id'] = $this->uri->segment(3,'');
	   }else{
		   $this->data['is_blog'] = '';
	   }
	   $comedian_name = $this->uri->segment('1');
	   //exit;
	   $this->data['active_page'] = 'comedians';
	   //$comedian_name = str_replace('-',' ',$comedian_name);
	   $this->data['comedian'] = $this->Comedians_model->get_full_info_for_comedian_by_seo_name($comedian_name);
	   /*echo '<pre>';
	   print_r($this->data['comedian']);exit;*/
	   
	   if(!$this->data['comedian'] || (isset($this->data['comedian']->comedian_bioavailable) && $this->data['comedian']->comedian_bioavailable=='0')){
		  $this->check_for_old_url($this->uri->uri_string());
		  
		  //redirect(base_url('404.html'));
		  $this->output->set_status_header('404');
		  $this->load->view('404.php');
		  
		  $is_four_zero_four = 1;
		  
		  //exit;
	   }
	   if(!$is_four_zero_four){
		   $comedian_id = $this->data['comedian']->comedian_id;
		   if($this->data['comedian']->comedian_enablephotos=='1'){
			   $this->data['com_pictures'] = $this->Comedian_portal_model->get_all_pictures($comedian_id);
		   }
		   if($this->session->userdata('id')!="")
		   {
			   $user_id = $this->session->userdata('id');
			   $valid_payee = $this->Comedian_portal_model->validate_legalpayee($user_id,$comedian_id);
			   if($valid_payee)
			   {
				   $this->data['comedian_name'] = $comedian_name;
				   $this->data['legalpayee_id'] = $valid_payee["0"]->legalpayee_id;
			   }
		   }
		   $this->data['suggested_channels'] = $this->Comedians_model->get_comedian_channels($this->session->userdata('id'),$comedian_id);
		   
		   $this->data['shows'] = $this->Liveshows_model->get_tour_and_shows($comedian_id);
		   if($this->data['comedian']->comedian_blogenabled=='1') {
			   $this->data['blog'] = $this->Comedians_model->get_recent_blog_entries($comedian_id,8);
			   if($this->data['blog']){
				   foreach($this->data['blog'] as $b){
					   $b->comedianblogentry_entryhtml_less = $this->html_cut($b->comedianblogentry_entryhtml,800);
				   }
			   }
			   $archive = $this->Comedians_model->get_recent_blog_entries($comedian_id);
			   $archive_data = array();
			   if($archive){
				   foreach($archive as $arc){
					   $year = date('Y', strtotime($arc->comedianblogentry_releasedate));
					   $month = date('M', strtotime($arc->comedianblogentry_releasedate));
					   $archive_data[$year][$month] = '';
				   }
			   }
			   $this->data['archive'] = $archive_data;
		   }
		   
		   $this->data['comedian_skin'] = $this->Comedians_model->get_comedian_skin($this->data['comedian']->comedianchannelskin_id);
		   $comedian_skin = $this->data['comedian_skin'];
		   
		   if(isset($comedian_skin['comedianchannelskin_fontcolor']) && $comedian_skin['comedianchannelskin_fontcolor']!=''){
				$this->data['fcolor'] = $comedian_skin['comedianchannelskin_fontcolor'];
			}else{
				$this->data['fcolor'] = '#ffffff';
			}
			
			if(isset($comedian_skin['comedianchannelskin_fontshadowcolor']) && $comedian_skin['comedianchannelskin_fontshadowcolor']!=''){
				$this->data['scolor'] = $comedian_skin['comedianchannelskin_fontshadowcolor'];
			}else{
				$this->data['scolor'] = '';
			}
			
			//channel color
			if(isset($comedian_skin['comedianchannelskin_channelfontcolor']) && $comedian_skin['comedianchannelskin_channelfontcolor']!=''){
				$this->data['cfcolor'] = $comedian_skin['comedianchannelskin_channelfontcolor'];
			}else{
				$this->data['cfcolor'] = '#ffffff';
			}
			
			if(isset($comedian_skin['comedianchannelskin_channelfontshadowcolor']) && $comedian_skin['comedianchannelskin_channelfontshadowcolor']!=''){
				$this->data['cscolor'] = $comedian_skin['comedianchannelskin_channelfontshadowcolor'];
			}else{
				$this->data['cscolor'] = '';
			}
		   $this->data['comedian_social_links'] = $this->Comedians_model->get_comedian_social_links($comedian_id);
		   foreach($this->data['comedian_social_links'] as $link){
			   if($link->linktype_id==3){
				   if($link->comedianlink_url!=''){
					   $twitter_name = explode('/',$link->comedianlink_url);
					   $this->data['twitter_username'] = $twitter_name[count($twitter_name)-1];
				   }
			   }
		   }
		   //load video model
		   $this->load->model('Videos_model');
		   $this->load->model('Channels_model');
		   $user = $this->User_model->get_user_info();
		   $this->data['user'] = $user;
		   $user_id = $this->session->userdata('id');
		  $allowuncensored = $this->session->userdata('allowuncensored');
		  $is_logedin = TRUE;
		  if(!$user_id){
			  $user_id = 0;
			  $allowuncensored = 0;
			  $is_logedin = FALSE;
		  }
		   $comedian_channelid = $this->Channels_model->get_comedian_channel_id($comedian_id);
		   if($this->session->userdata('countryaccesslist_allowadsupport')=='1'){
				$this->data['comedian_videos'] = $this->Channels_model->get_channelplaylist_foruser($comedian_channelid,$user_id,100);
		   }
		   else{
				$this->data['comedian_videos'] = '';
				
		   }
		  
		   //video page
		   if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$this->data['user_ip'] = $_SERVER["HTTP_X_FORWARDED_FOR"];
		  }else{
			$this->data['user_ip'] = $_SERVER['REMOTE_ADDR'];
		  }
		   
		   
		   if($this->data['comedian_videos']){
			   $video_ids = array();
			  
				  foreach($this->data['comedian_videos'] as $com){
					  $com->video_thumb = $this->Videos_model->get_thumbnail_url_new($com->video_id,'110','62');
					  $com->video_comedians = $this->Videos_model->get_video_comedians($com->video_id);
					  $video_ids[] = $com->video_id;
				  }
				  $play_video = $this->uri->segment(3);//get video id for direct play
				  if($play_video){
					  
					  $selected_video =  $this->Channels_model->get_directedvideo_foruser($play_video, $user_id, $allowuncensored);
					  if($selected_video && in_array($play_video,$video_ids)){//change now playing if direct video exist
						  $selected_video[0]->video_thumb = $this->Videos_model->get_thumbnail_url_new($selected_video[0]->video_id,'110','62');
						  $selected_video[0]->video_comedians = $this->Videos_model->get_video_comedians($selected_video[0]->video_id);
						  $this->data['now_playing'] = $selected_video[0];  
					  }else{//if direct video not found show an error and play first video in the list
						  if($this->data['is_blog']!='blog')	
						  $this->data['not_found'] = 1;
						  $this->data['now_playing'] = $this->data['comedian_videos'][0];
					  }
				  }else{
					  $this->data['now_playing'] = $this->data['comedian_videos'][0];
				  }
				  $this->data['is_direct_video'] = '1';
				  $this->data['play_type'] = 'channels';
				  $this->data['now_playing_array'] = $this->data['comedian_videos'];
				  $this->load->library('user_agent');
				  $this->data['user_agent'] = $this->agent->agent_string();
				  $this->data['current_url'] = base_url(uri_string());
				  $browser = strtolower($this->agent->browser());//Firefox..Chrome
				  $is_mobile = $this->agent->is_mobile();
				  if($is_mobile){
					  $this->data['device'] = 'mobile';
						$transcode_type = '3,6';
				  }else{
					  $this->data['device'] = 'desktop';
					  $transcode_type = '3,7';
					  if($browser=='chrome' || $browser=='firefox'){
						  $transcode_type = '3,4,7';
					  }
				  }
				  
				  if($user_id>0 && $allowuncensored){
					$censored_type = '1,2,3';
				  }else{
					  $censored_type = '1,2';
				  }
				  $bitrate = '1064';
				  if($is_mobile){
					  $bitrate = '664';
				  }
				  $this->data['video_transcodes'] = $this->Videos_model->get_video_transcode($this->data['now_playing']->video_id,$transcode_type,$censored_type,$bitrate);
				  
						//******check video size*******//
				 $video_size = $this->session->userdata('video_size');
				 if($this->session->userdata('cookie2user_id')==''){
					$user_info = $this->User_model->get_user_info();
				  }else{
					  $vsize = $this->session->userdata('video_size');
					  if($vsize==''){
						  $vsize = '-1';
					  }
					  $id = $this->session->userdata('cookie2user_id');
					  $user_info->cookie2user_id = $id;
					  $user_info->video_size = $vsize;
					  
				  }
				  $this->data['user_video_size'] = $user_info;
			
				  if($video_size==0){
					  $this->data['comp_ad_width'] = '300';
		  			  $this->data['comp_ad_height'] = '250';
					  $this->data['banner_size'] = '300:250';
					  $this->data['player_width'] = '640';
		  $this->data['player_height'] = '360';
					  $this->data['ui_conf_id'] = '13167552';
				  }else{
					  $this->data['banner_size'] = '300:60';
					  $this->data['ui_conf_id'] = '13167562';
					  $this->data['comp_ad_width'] = '300';
				  	  $this->data['comp_ad_height'] = '60';
					  $this->data['player_width'] = '900';
		  			  $this->data['player_height'] = '506';
				  }
				  $this->data['video_size'] = $video_size;  
				 
				 //****** end video size *********//
				 $this->data['current_video_liked'] = $this->Videos_model->is_video_liked($user->cookie2user_id,$this->data['comedian_videos'][0]->video_id);
				  
				 $channel_detail = $this->Channels_model->get_channel_by_id($comedian_channelid); 
				 
				 if($channel_detail){
					 $this->data['current_channel_name'] = addslashes($channel_detail[0]->channel_name);
					 $this->data['channel_name_seo'] = addslashes($channel_detail[0]->channel_seoname);
				 }else{
					 $this->data['current_channel_name'] = '';
					 $this->data['channel_name_seo'] = '';
				 }
				  
			  }else{
				  $this->data['invalid_video'] = 1;		  
				  $this->data['header_ad_code'] = $this->User_model->get_code_header_ad('comedian_no_video');
			  }
			  
		   if($this->session->userdata('id')!=''){
			 $this->data['is_favorite'] = $this->Comedians_model->is_favorite_comedian($comedian_id,$this->session->userdata('id'));
			 $this->data['is_liked_dislike'] = $this->Comedians_model->is_user_like_comedian($comedian_id,$this->session->userdata('id'));
		   }else{
			   $this->data['is_favorite'] = 0;
			   $this->data['is_liked_dislike'] = 0;
		   }
		   $this->data['page_title'] = 'The '.$this->data['comedian']->comedian_fullname.' Channel | Comedy Network';//$this->data['comedian']->comedian_pagetitle;
		   $comedian_kw = rtrim($this->data['comedian']->comedian_fullname.',comedian, stand-up, stand up, comedy,'.$this->data['comedian']->comedian_metakeywords,',');
		   $this->data['page_metatag'] = '<meta name="title" content= "'.$this->data['comedian']->comedian_pagetitle.'"/>
										  <meta name="description" content="'.$this->data['comedian']->comedian_fullname.'\'s Biography" /> 
										  <meta name="keywords" content= "'.$comedian_kw.'" />
										  <meta name="robot" content="index, follow"/>';
		   
		   
		   
		   $this->data['is_favorite_comedian'] = $this->Comedians_model->is_favorite_comedian($comedian_id,$this->session->userdata('id'));
			if($this->session->userdata('id')){
				   $this->data['is_liked_dislike'] = $this->Comedians_model->is_user_like_comedian($comedian_id,$this->session->userdata('id'));
			   }else{
				   $this->data['is_liked_dislike'] = 0;
			   }
		   
		   
		   //////////////////////fb for admin postin ///////////////
			$is_fb_connected = $this->session->userdata('fb_access_token');
			$is_user_login = $this->session->userdata('id');
			if($is_fb_connected && $is_user_login){
				$fb_config = array(
					  'appId'  => $this->config->item('fb_appId'),
					  'secret' => $this->config->item('fb_secret')
				  );
				$this->load->library('facebook', $fb_config);
				$this->facebook->setAccessToken($this->session->userdata('fb_access_token'));
				try {
				  $page_id = $this->config->item('fb_page_id');//'125023041032606';
				  $page_info = $this->facebook->api("/$page_id?fields=access_token");
				  if( !empty($page_info['access_token']) ) {
					  $this->data['has_permissions'] = '1';
				  } else {
					  $permissions = $this->facebook->api("/me/permissions");
					  if( !array_key_exists('publish_stream', $permissions['data'][0]) || 
						  !array_key_exists('manage_pages', $permissions['data'][0])) {
						  // We don't have one of the permissions
						  // Alert the admin or ask for the permission!
						  $this->data['permissions_url'] = $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
						  //echo $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
					  }
			   
				  }
				} catch (FacebookApiException $e) {
				  $this->data['permissions_url'] = $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
				}
			}
			
			/////////////////////////end ////////////////////////////
			if(isset($video_size) && $video_size==0){
				$this->data['header_ad_code'] = $this->User_model->get_code_header_com_video_ad('comedian_video_small');
			}else if(isset($video_size) && $video_size==1){
				$this->data['header_ad_code'] = $this->User_model->get_code_header_com_video_ad('comedian_video_big');
			}else{
				$this->data['header_ad_code'] = $this->User_model->get_code_header_com_video_ad('comedian_no_videos');
			}
		   
		   
		   $this->load->view('blocks/header.php',$this->data);
		   $this->load->view('comedian_profile_top_jwp.php');
		   if($this->data['comedian_videos']){
			   if($video_size==0){
					$this->load->view('comedian_video_med_jwp.php');
			   }else{
					$this->load->view('comedian_video_big_jwp.php');
			   }
		   }
		   $this->load->view('comedian_profile.php');
		   if($this->data['comedian_videos']){
			$this->load->view('video/video-body-bottom-player-settings-jwp.php');
		   }
		   $this->load->view('blocks/footer.php');
		 
   		}
   }
   public function check_for_old_url($url){
	   $this->load->model('Videos_model');
	   $exp_dash_video = explode("-",$url);
	   if($exp_dash_video[0]=='video' && is_numeric($exp_dash_video[1])){
		   $old_vid = $exp_dash_video[1];		   
	   }else{
		   $exp_slash_video = explode("/",$url);
		   if($exp_slash_video[0]=='cat' && $exp_slash_video[2]=='video' && is_numeric($exp_slash_video[3])){
			   $old_vid = $exp_slash_video[3];
		   }
	   }
	   if(isset($old_vid)){
		   $video_detail = $this->Videos_model->get_video_by_old_id($old_vid);
		   if($video_detail){
			   $url_long_title = $this->User_model->toAscii($video_detail['video_longtitle']);
			   header('location:'.base_url('videos/'.$url_long_title.'/'.$video_detail['video_id']), true ,301);
			   exit;
		   }
	   }else{
		   $exp_tags = explode('/',$url);
		   $is_tag = explode('-',$exp_tags[0]);
		   if(is_array($is_tag) && $is_tag[0]=='tag'){			   
			    $kw = $this->User_model->toAscii($is_tag[1]);//
				if($kw){
			    	header('location:'.base_url('search?kw='.$kw), true ,301);
			    	exit;
				}
		   }
	   }
   }
   public function comedian_skins(){
	   if(isset($_POST['skin_name'])){
		   $skin_name = $this->input->post('skin_name',TRUE);
		   $comedian_name = $this->input->post('comedian_name',TRUE);
		   $comedian_email = $this->input->post('comedian_email',TRUE);
		   $errors = array();
		   if($skin_name==''){
			   $errors[] = 'Please select a skin';
		   }
		   if($comedian_name==''){
			   $errors[] = 'Please provide your name';
		   }
		   if(!$errors){
			   $skin_request_email_to = $this->User_model->get_skin_request_email();
			   $smtp_config = array(
				   'protocol'    => $this->config->item('protocol'),
				   'smtp_crypto' => $this->config->item('smtp_crypto'),
				   'smtp_host'   => $this->config->item('smtp_host'),
				   'smtp_port'   => $this->config->item('smtp_port'),
				   'smtp_user'   => $this->config->item('smtp_user'),
				   'smtp_pass'   => $this->config->item('smtp_pass'),
				   'priority'	   => 1,
				   'mailtype'    => 'html'
				);
			   $this->load->library('email', $smtp_config);
			   $this->email->set_newline("\r\n");
			   $email_from = '';
			   $this->email->from($email_from, 'Comedian Skin Request');
			   $this->email->to($skin_request_email_to);/*$contact_detail['admincontact_email']*/
			   $subject = 'Comedian Skin Request';
			   $this->email->subject($subject);
			   $this->email->reply_to($comedian_email,$comedian_name);
			   $email_message = "** Comedian Skin Request from  ***<br><br>
			   Name:&nbsp;&nbsp;&nbsp;".$comedian_name."<br><br>
			   E-mail:&nbsp;&nbsp;&nbsp;".$comedian_email."<br><br>
			   Skin Name:&nbsp;&nbsp;&nbsp;".$skin_name."<br><br>
			   
			   ";
			   $this->email->message($email_message);
			  
			  
			  
			   if ($this->email->send()) {
				   $this->data['sent'] = '1';
			   }else{
				   $msg = $this->email->print_debugger();
				   $error_data = array('emailfailure_dateattempted'=>date('Y-m-d H:i:s'),'emailfailure_from'=>$email_from,'emailfailure_to'=>$skin_request_email_to,'emailfailure_subject'=>$subject,'emailfailure_sesresponse'=>$msg[8]);
				   $this->User_model->email_failure_report($error_data);
			   }
		   }else{
			   $this->data['errors']  = $errors;
		   }
	   }
	   $this->data['active_page'] = 'comedians';
	   
	   $this->data['page_title']='Comedians – Skins | ';
	   $this->data['page_meta_description']='';
	   $this->data['page_meta_keyword']='';
	   
	   $this->data['skins'] = $this->Comedians_model->get_comedian_skins_for_listing();
	   $this->load->view('blocks/header.php',$this->data);
	   $this->load->view('comedians_skins.php');
	   $this->load->view('blocks/footer.php');
   }
   public function comedian_liked_by_user(){
	   $like = $this->input->post('like',TRUE);
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if(($like=='' && $comedian_id=='') || $this->session->userdata('id')==''){
		   return false;
	   }
	   $user_id = $this->session->userdata('id');
	   $added = $this->Comedians_model->user_like_comedian($like,$comedian_id,$user_id);
	   if($added){
		   echo '1';
	   }
   }
   public function comedian_add_remove_favorite(){
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if(!$comedian_id || $this->session->userdata('id')==''){
		   return false;
	   }
	   echo $this->Comedians_model->add_remove_favorite($comedian_id,$this->session->userdata('id'));
	   
   }
   public function get_comedian_bio(){
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if($comedian_id){
		   $this->data['comedian'] = $this->Comedians_model->get_full_info_for_comedian($comedian_id);
		   $this->data['bio_text_lengt'] = strlen($this->data['comedian']->comedian_biotext);
		   $this->data['comedian']->comedian_biotext = $this->data['comedian']->comedian_biotext;//$this->html_cut($this->data['comedian']->comedian_biotext,2048);
		   $this->data['comedian_social_links'] = $this->Comedians_model->get_comedian_social_links($comedian_id);
		    $this->data['user_login'] = $this->session->userdata('id');
			if($this->data['user_login']){
				$user_id = $this->data['user_login'];
			}else{
				$user_id = '0';
			}
			
			$this->data['is_favorite_comedian'] = $this->Comedians_model->is_favorite_comedian($comedian_id,$user_id);
			if($this->session->userdata('id')){
				   $this->data['is_liked_dislike'] = $this->Comedians_model->is_user_like_comedian($comedian_id,$this->session->userdata('id'));
			   }else{
				   $this->data['is_liked_dislike'] = 0;
			   }
		   if($this->data['comedian_social_links']){
			   $this->data['has_links'] = '1';		   
		   }else{
			   $this->data['has_links'] = '0';
		   }
		   echo json_encode($this->data);
	   }
   }
   public function get_blog_entry_by_id(){
	   $entry_id = $this->input->post('entry_id',TRUE);
	   if($entry_id){
		   $this->data['entry'] = $this->Comedians_model->get_blog_entry_by_id($entry_id);
		   if($this->data['entry']){
			   $this->data['entry']['comedianblogentry_releasedate'] = date('F d, Y',strtotime($this->data['entry']['comedianblogentry_releasedate']));
			   
		   }
		   echo json_encode($this->data);
	   }
   }
   public function get_blog_entries_archive(){
	   $year = $this->input->post('year',TRUE);
	   $month = $this->input->post('month',TRUE);
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if($year && $month && $comedian_id){
		   $entries = $this->Comedians_model->get_posts_archives($year,$month,$comedian_id);
		   if($entries){
			   foreach($entries as $ent){
				   $ent->comedianblogentry_headline = mb_check_encoding($ent->comedianblogentry_headline, 'UTF-8') ? $ent->comedianblogentry_headline : utf8_encode($ent->comedianblogentry_headline);
				   $ent->comedianblogentry_releasedate = date('F d, Y',strtotime($ent->comedianblogentry_releasedate));
				   preg_match_all('/<img[^>]+>/i',$ent->comedianblogentry_entryhtml, $result);
					if(isset($result[0][0])){
						$image = $result[0][0];
						$img = array();
						preg_match_all('/(alt|title|src)=("[^"]*")/i',$image, $img[$image]);
					}
					if(isset($image)){
						$ent->image = $img[$image][0][0];
					}else{
						$ent->image = '';
					}
					$ent->comedianblogentry_entryhtml = $this->html_cut($ent->comedianblogentry_entryhtml,800);
					$ent->comedianblogentry_entryhtml = mb_check_encoding($ent->comedianblogentry_entryhtml, 'UTF-8') ? $ent->comedianblogentry_entryhtml : utf8_encode($ent->comedianblogentry_entryhtml);
			   }
		   }
		   $this->data['entries'] = $entries;
		   echo json_encode($this->data);
	   }
   }
   public function get_blog_entries_recent(){
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   		$entries = $this->Comedians_model->get_recent_blog_entries($comedian_id,3);
		   if($entries){
			   foreach($entries as $ent){
				   $ent->comedianblogentry_headline = mb_check_encoding($ent->comedianblogentry_headline, 'UTF-8') ? $ent->comedianblogentry_headline : utf8_encode($ent->comedianblogentry_headline);
				   $ent->comedianblogentry_releasedate = date('F d, Y',strtotime($ent->comedianblogentry_releasedate));
				   preg_match_all('/<img[^>]+>/i',$ent->comedianblogentry_entryhtml, $result);
					if(isset($result[0][0])){
						$image = $result[0][0];
						$img = array();
						preg_match_all('/(alt|title|src)=("[^"]*")/i',$image, $img[$image]);
					}
					if(isset($image)){
						$ent->image = $img[$image][0][0];
					}else{
						$ent->image = '';
					}
					$ent->comedianblogentry_entryhtml = $this->html_cut($ent->comedianblogentry_entryhtml,800);
					$ent->comedianblogentry_entryhtml = mb_check_encoding($ent->comedianblogentry_entryhtml, 'UTF-8') ? $ent->comedianblogentry_entryhtml : utf8_encode($ent->comedianblogentry_entryhtml);
			   }
		   }
		   $this->data['entries'] = $entries;
		   echo json_encode($this->data);
   }
   public function get_comedian_by_id(){
	   $comedian_id = $this->input->post('comedian_id',TRUE);
	   if($comedian_id){
		   $this->data['comedian'] = $this->Comedians_model->get_comedian_info($comedian_id);
		   $this->data['comedian'] = $this->data['comedian'][0];
		   echo json_encode($this->data);
	   }
   }
   public function get_channel_detail(){
	   $channel_id = $this->input->post('channel_id',TRUE);
	   if($channel_id){
		   $comedian = $this->Comedians_model->get_comedian_seo_name($channel_id);
		   if($comedian){
			   $this->data['url'] = base_url().$comedian['comedian_seoname'];
		   }else{
			   $this->load->model('Channels_model');
			   $channel = $this->Channels_model->get_channel_by_id($channel_id);
			   if($channel){
				   $this->data['url'] = base_url().'channels/'.$channel[0]->channel_seoname;
			   }
		   }
		   echo json_encode($this->data);
	   }
   }
   public function check_for_comedian_with_id(){
	   $comedian_id = $this->uri->segment('2');
	   $comedian = $this->Comedians_model->get_full_info_for_comedian($comedian_id);
	   
	   if($comedian && $comedian->comedian_seoname){
		   redirect(base_url($comedian->comedian_seoname));
	   }else{
		   $this->output->set_status_header('404');
			$this->load->view('404.php');
		   //redirect(base_url('404.html'));
		  // header('location:'.base_url('404.html'), true ,404);
		 // exit;
	   }
   }
    public function post_video_on_lf_page(){
	   $data['body_flag'] = 'Yes';
      $data['active_page'] = 'video';
	  $comedian_seo_name = $this->uri->segment(3);
	   $video_to_post = $this->uri->segment(4);
	   $video_channel_id = $this->uri->segment(5);
	   if(!$video_to_post || $this->session->userdata('isadmin')!='1'){
		   redirect(base_url($comedian_seo_name));
	   }
	   $this->load->model('Videos_model');
	   $this->load->model('Channels_model');
	   if(isset($_POST['video_comment'])){
	   		
	   $video_kid = $this->input->post('video_kid',TRUE);
	   $channel_id = $this->input->post('channel_id',TRUE);
	   if($video_kid && $channel_id){
		   $channel_detail = $this->Channels_model->get_channel_by_id($channel_id);
		   
		   $video_detail = $this->Videos_model->get_video_detail($video_kid);
		   if($video_detail){
			   $video_thumb = $this->Videos_model->get_thumbnail_url($video_kid);
			   $fb_config = array(
					'appId'  => $this->config->item('fb_appId'),
					'secret' => $this->config->item('fb_secret')
				);
				
				$post_array = array(
					'message' => $this->input->post('video_comment',TRUE),
					'name' => ''.$channel_detail[0]->channel_name.' | '.$video_detail['video_longtitle'],
					'description' => $video_detail['video_synopsis'],
					'picture' => $video_thumb,
					'link' => base_url($comedian_seo_name.'/'.str_replace(' ','-',strtolower($video_detail['video_shorttitle'])).'/'.$video_detail['video_id'])
				);
				
				
				
				$this->load->library('facebook', $fb_config);
				$this->facebook->setAccessToken($this->session->userdata('fb_access_token'));
					try {
					  $page_id = $this->config->item('fb_page_id');//'125023041032606';
					  $page_info = $this->facebook->api("/$page_id?fields=access_token");
					  if( !empty($page_info['access_token']) ) {
						  
						  $post_array['access_token'] = $page_info['access_token'];
						  $post_id = $this->facebook->api("/$page_id/feed","post",$post_array);
						  redirect(base_url($comedian_seo_name));
					  } else {
						  $permissions = $this->facebook->api("/me/permissions");
						  if( !array_key_exists('publish_stream', $permissions['data'][0]) || 
							  !array_key_exists('manage_pages', $permissions['data'][0])) {
							  // We don't have one of the permissions
							  // Alert the admin or ask for the permission!
							  //header( "Location: " . $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages")) );
							  echo $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
							  //echo $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
						  }
				   
					  }
					} catch (FacebookApiException $e) {
					  echo $this->facebook->getLoginUrl(array("scope" => "publish_stream, manage_pages"));
					}
				
			 
		   }
	   }
   
	   }
	   $data['channel_detail'] = $this->Channels_model->get_channel_by_id($video_channel_id);
	   $data['video_thumb'] = $this->Videos_model->get_thumbnail_url($video_to_post);	   
	   $data['video_detail'] = $this->Videos_model->get_video_detail($video_to_post);
	   $data['video_id'] = $video_to_post;
	   $data['channel_id'] = $video_channel_id;
	   $data['video_link'] = base_url($comedian_seo_name.'/'.str_replace(' ','-',strtolower($data['video_detail']['video_shorttitle'])).'/'.$data['video_detail']['video_id']);
	   $this->load->view('blocks/header.php', $data);
       $this->load->view('post_on_facebook_wall.php');
       $this->load->view('blocks/footer.php');
	   
   }
   public function post_comedian_on_tw(){
	   $tweet_text = $this->input->post('text');
	   try{	
	   		
		   $tweet_text = str_replace('<br>',' ',$tweet_text);
		   $complete_url = '';
		   $this->load->model('User_model');  
		   $this->User_model->post_comedian_to_twitter($tweet_text);
		   echo '{"posted":"1","no_tw":"0"}';
	   }catch(Exception $ex){
		   echo '{"posted":"0","no_fb":"0","message":"'.$ex->getMessage().'"}';
	   }
   }
   public function get_tweets(){
	   if(isset($_GET['screen_name'])){
		   $this->load->library('Twitteroauth');
		   $tokens = array();
		   $this->twitteroauth->create($this->config->item('twitter_consumer_token'), $this->config->item('twitter_consumer_secret'));
		  //echo $tokens['oauth_token'] = $this->config->item('oauth_token_disp');//$this->session->userdata('tw_access_token');
		   //echo $tokens['oauth_token_secret'] = $this->config->item('oauth_token_sec_disp');//$this->session->userdata('tw_access_secret');
		   //$this->tweet->setAccessTokens($tokens);
		   $res['tweets'] = $this->twitteroauth->get('statuses/user_timeline', array('screen_name' => $this->input->get('screen_name',TRUE), 'include_entities' => 'true', 'count' => $this->input->get('count'),'include_rts'=>'true'));
		   echo json_encode($res);
	   }
   }
   public function html_cut($text, $max_length)
	{
		$tags   = array();
		$result = "";
	
		$is_open   = false;
		$grab_open = false;
		$is_close  = false;
		$in_double_quotes = false;
		$in_single_quotes = false;
		$tag = "";
	
		$i = 0;
		$stripped = 0;
	
		$stripped_text = strip_tags($text);
	
		while ($i < strlen($text) && $stripped < strlen($stripped_text) && $stripped < $max_length)
		{
			$symbol  = $text[$i];
			$result .= $symbol;
	
			switch ($symbol)
			{
			   case '<':
					$is_open   = true;
					$grab_open = true;
					break;
	
			   case '"':
				   if ($in_double_quotes)
					   $in_double_quotes = false;
				   else
					   $in_double_quotes = true;
	
				break;
	
				case "'":
				  if ($in_single_quotes)
					  $in_single_quotes = false;
				  else
					  $in_single_quotes = true;
	
				break;
	
				case '/':
					if ($is_open && !$in_double_quotes && !$in_single_quotes)
					{
						$is_close  = true;
						$is_open   = false;
						$grab_open = false;
					}
	
					break;
	
				case ' ':
					if ($is_open)
						$grab_open = false;
					else
						$stripped++;
	
					break;
	
				case '>':
					if ($is_open)
					{
						$is_open   = false;
						$grab_open = false;
						array_push($tags, $tag);
						$tag = "";
					}
					else if ($is_close)
					{
						$is_close = false;
						array_pop($tags);
						$tag = "";
					}
	
					break;
	
				default:
					if ($grab_open || $is_close)
						$tag .= $symbol;
	
					if (!$is_open && !$is_close)
						$stripped++;
			}
	
			$i++;
		}
	
		while ($tags)
			$result .= "</".array_pop($tags).">";
	
		return $result;
	}
}

