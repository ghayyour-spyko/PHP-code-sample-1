<?php

/**
 * MVC Model for accessing Comedian information for the site
 *
 * 
 */
class Comedians_model extends CI_Model {
   public $db_user;
   public function __construct() {
      $this->load->database();
	  $this->db_user = $this->session->userdata('db_user');
   }

   // Method:        get_links_for_comedian
   // Scope:         PUBLIC
   // Description:   Returns an array of comedianlink_url, comedianlink_sitename, comedianlink_iconurl
   //                and comedianlink_iconurlgreyed records for the given comedian in the right sort order.
   // Used By:       Pages that show the user profile for a comedian and show their social media icond list.
   public function get_links_for_comedian($in_comedian_id) {
      $sql = "SELECT comedianlink_url, linktype_name AS comedianlink_sitename, linktype_iconurl AS comedianlink_iconurl, linktype_iconurlgreyed AS comedianlink_iconurlgreyed FROM ComedianLinks AS cl INNER JOIN LinkTypes AS lt ON cl.linktype_id = lt.linktype_id WHERE comedian_id = ? ORDER BY comedianlink_sortorder";
      $queryresult = $this->db_user->query($sql, array($in_comedian_id));
      return $queryresult->row_array();
   }
   
   // Method:        get_full_info_for_comedian
   // Scope:         PUBLIC
   // Description:   Returns an array of all the fields from the Comedians table that would be used on the bio page
   // Used By:       Pages that show the user profile for a comedian
   public function get_full_info_for_comedian($in_comedian_id) {
      $sql = "SELECT comedian_id, comedian_fullname, comedian_lastname, comedian_firstname, comedian_biotext, comedian_fullimageurl, comedian_thumbimageurl, comedian_isfeatured, comedian_metatag, comedian_pagetitle,comedian_fbfanpageid, comedian_bioavailable,comedian_croping_cord,comedian_metakeywords,comedian_blogenabled,comedian_seoname FROM Comedians WHERE comedian_id = ?";
      $queryresult = $this->db_user->query($sql, array($in_comedian_id));
      return $queryresult->row();
   }
   
   public function get_full_info_for_comedian_by_name($comedian_name) {
      $sql = "SELECT comedian_id, comedian_fullname, comedian_lastname, comedian_firstname, comedian_biotext, comedian_fullimageurl, comedian_thumbimageurl, comedian_isfeatured, comedian_metatag, comedian_pagetitle,comedian_fbfanpageid, comedian_bioavailable,comedian_croping_cord,comedian_metakeywords,comedian_blogenabled FROM Comedians WHERE comedian_fullname = ?";
      $queryresult = $this->db_user->query($sql, array($comedian_name));
      return $queryresult->row();
   }
   
   public function get_full_info_for_comedian_by_seo_name($comedian_name) {
      $sql = "SELECT comedian_id, comedian_fullname, comedian_lastname, comedian_firstname, comedian_biotext, comedian_fullimageurl, comedian_thumbimageurl, comedian_isfeatured, comedian_metatag, comedian_pagetitle,comedian_fbfanpageid, comedian_bioavailable,comedian_croping_cord,comedian_metakeywords,comedian_blogenabled,comedian_seoname,comedian_enabletourdates,comedian_enablephotos,comedian_tourdatesimageurl,comedian_tourdatesheadline,comedian_tourdatesheadlinesource,comedianchannelskin_id,comedian_skinfontsize,comedian_channelid FROM Comedians WHERE comedian_seoname = ?";
      $queryresult = $this->db_user->query($sql, array($comedian_name));
      return $queryresult->row();
   }
   // Method:        get_comedian_list_info
   // Scope:         PUBLIC
   // Description:   Returns an array of comedian_id, comedian_fullname, & comedian_thumbimageurl for all
   //                comedians that actually have bio information available.
   // Used By:       User profile form for restricting specific comedians, comedian browse page, or any place that
   //                just wants a list of comedian names and small thumbnail images.
   public function get_comedian_list_info() {
      $sql = "SELECT comedian_id, comedian_fullname, comedian_thumbimageurl FROM Comedians WHERE comedian_bioavailable=1";
      $queryresult = $this->db_user->query($sql);
      return $queryresult->row_array();
   }

   // Method:        get_comedian_fbfanpageid_list
   // Scope:         PUBLIC
   // Description:   Returns an array of comedian_id & comedian_fbfanpageid for all comedians who have a facebook fan page ID
   //                in the database.  
   // Used By:       Back-end engine will use this function to reconcile the user like list and user subscription list to find
   //                comedians the user likes.
   public function get_comedian_fbfanpageid_list() {
      $sql = "SELECT comedian_id, comedian_fbfanpageid FROM Comedians WHERE comedian_fbfanpageid IS NOT NULL";
      $queryresult = $this->db_user->query($sql);
      return $queryresult->row_array();
   }

  
   // Method:        get_avoid_comedians_for_user
   // Scope:         PUBLIC
   // Description:   Returns an array of comedian_ids the user wishes to avoid
   // Used By:       The user profile editing of avoid comedians
   public function get_avoid_comedians_for_user($in_user_id) {
      $sql = "SELECT comedian_id FROM UserAvoidComedians WHERE user_id = ?";
      $queryresult = $this->db_user->query($sql, array($in_user_id));
	  $ids = array();
	  foreach($queryresult->result_array() as $row)
	  {
		  $ids[] = $row['comedian_id'];
	  }
	  return $ids;
      //return $queryresult->row_array();
   }
   
   
   
   // Method:        get_all_comedians_not_avoided
   // Scope:         PUBLIC
   // Description:   Returns an array of comedians the user not added in avoid list
   // Used By:       The user profile editing of avoid comedians
   public function get_all_comedians_not_avoided($in_user_id){
	   $this->db->select('comedian_id,comedian_fullname,comedian_thumbimageurl,comedian_bioavailable');
	   $this->db->from('Comedians');
	   $avoid_comedians = implode(',',$this->get_avoid_comedians_for_user($in_user_id));
	   if($avoid_comedians!='')
	   $this->db->where("comedian_id NOT IN ($avoid_comedians)");
	   $this->db->order_by('comedian_fullname');
	   $comedians = $this->db->get();
	   $result = $comedians->result();
	   return $result;
   }
   
   
   // Method:        get_all_avoid_comedians
   // Scope:         PUBLIC
   // Description:   Returns an array of comedians the user added in avoid list
   // Used By:       The user profile editing of avoid comedians
   public function get_all_avoid_comedians($in_user_id){
	   $this->db_user->select('comed.comedian_id,comed.comedian_fullname,comed.comedian_thumbimageurl,comed.comedian_bioavailable');
	   $this->db_user->from('Comedians AS comed');
	   $this->db_user->join('UserAvoidComedians AS avoid_comed','comed.comedian_id = avoid_comed.comedian_id','left');
	   $this->db_user->where("avoid_comed.user_id = '$in_user_id'");
	   $this->db_user->order_by('comed.comedian_fullname');
	   $comedians = $this->db_user->get();
	   $result = $comedians->result();
	   return $result;
   }
    public function get_all_comedians(){
	   $this->db->select('comed.comedian_id,comed.comedian_fullname,comed.comedian_thumbimageurl,comed.comedian_bioavailable');
	   $this->db->from('Comedians AS comed');
	   $this->db->order_by('comed.comedian_fullname');
	   $comedians = $this->db->get();
	   $result = $comedians->result();
	   return $result;
   }
   public function update_user_vidlist($new_userid){
	   $ch = curl_init();
		$cookie2user_nnip = $this->get_user_nnip($new_userid);
		$cookie2user_nnip = long2ip($cookie2user_nnip);
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://".$cookie2user_nnip.":8237/api/updatevidlist?user_id=".$new_userid);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		// grab URL and pass it to the browser
		curl_exec($ch);
		
		// close cURL resource, and free up system resources
		curl_close($ch);
   }
   public function get_user_nnip($user_id){
	   $this->db_user->select('cookie2user_nnip');
	   $this->db_user->from('Cookie2Users');
	   $this->db_user->where('user_id',$user_id);
	   $query = $this->db_user->get();
	   $result = $query->result_array();
	   if(isset($result[0])){
		   return $result[0]['cookie2user_nnip'];
	   }
   }
   public function add_to_avoid($comedian_id)
   {
	   $user_id = $this->session->userdata('id');
	   $insert_array = array('user_id'=>$user_id,'comedian_id'=>$comedian_id);
	   $is_added = $this->db_user->insert('UserAvoidComedians',$insert_array);
	   $this->update_user_vidlist($user_id);
	   return $is_added;
   }
   public function remove_from_avoid($comedian_id)
   {
	   $user_id = $this->session->userdata('id');
	   $this->db_user->where('comedian_id',$comedian_id);
	   $this->db_user->where('user_id',$user_id);
	   $is_deleted = $this->db_user->delete('UserAvoidComedians');
	   $this->update_user_vidlist($user_id);
	   return $is_deleted;
   }
   public function get_comedian_info($comedian_id)
   {
	   $this->db->select('comedian_id,comedian_fullname,comedian_thumbimageurl,comedian_bioavailable,comedian_biotext,comedian_fullimageurl');
	   $this->db->from('Comedians');
	   $this->db->where("comedian_id = '$comedian_id'");
	   $comedians = $this->db->get();
	   $result = $comedians->result();
	   return $result;
   }
   public function get_user_comedians($in_user_id){
	   $this->db->select('comedian_id');
	   $this->db->from('UserIsComedians');
	   $this->db->where('user_id',$in_user_id);
	   $query = $this->db->get();
	   $result = $query->result();
	   return $result;
   }
   public function delete_all_user_comedians($user_id){
	   $this->db->delete('UserIsComedians',"user_id = '$user_id'");
   }
   //get comedian array and user id
   public function add_user_comedians($comedians,$user_id){
	   if($comedians){
		   $ids = implode(',',$comedians);
		   $this->db->where("comedian_id NOT IN($ids)");
		   $this->db->where("user_id",$user_id);
		   $this->db->delete('UserIsComedians');
		   foreach($comedians as $cid){
			   if($cid!=''){
				   $is_already = $this->is_already_in_user_comedian($cid,$user_id);
				   if(!$is_already){
					   $insert = array('comedian_id'=>$cid,'user_id'=>$user_id);
					   $this->db->insert('UserIsComedians',$insert);
				   }
			   }
		   }
	   }
   }
   public function is_already_in_user_comedian($comedian_id,$user_id){
	   $this->db->from('UserIsComedians');
	   $this->db->where("comedian_id = '$comedian_id'");
	   $this->db->where("user_id = '$user_id'");
	   return $this->db->count_all_results();
   }
   public function get_featured_comedians(){
	   $this->db_user->select('com.comedian_id,com.comedian_fullname,com.comedian_thumbimageurl,com.comedian_bioavailable,com.comedian_biotext,com.comedian_firstname,c.channel_seoname,com.comedian_seoname');
	   $this->db_user->from('FeaturedComedians AS fc');
	   $this->db_user->join('Comedians AS com','fc.comedian_id=com.comedian_id','left');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   
	   $this->db_user->order_by("comedian_fullname");
	   $query = $this->db_user->get();
	  
	   $result = $query->result();
	   return $result;
   }
   public function filter_comedian($character,$limit = 20){
	   $character = mysql_real_escape_string($character);
	   $this->db_user->select('com.comedian_id,com.comedian_fullname,com.comedian_thumbimageurl,com.comedian_bioavailable,com.comedian_biotext,com.comedian_firstname,c.channel_seoname,com.comedian_seoname');
	   $this->db_user->from('Comedians AS com');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   if($character!='all'){
	      $this->db_user->where("comedian_firstname LIKE '$character%' ");
	   }
	   $this->db_user->order_by("comedian_fullname");
	   $this->db_user->limit($limit,0);
	   $query = $this->db_user->get();
	  
	   $result = $query->result();
	   return $result;
   }
   public function get_total_filter_comedian($character){
	   $this->db_user->from('Comedians');
	   if($character!='all'){
	      $this->db_user->where("comedian_firstname LIKE '$character%' ");
	   }
	   return $this->db_user->count_all_results();
   }
   public function get_comedian_info_for_popup($comedian_id){
	   $this->db_user->select('com.comedian_id,com.comedian_fullname,com.comedian_fullimageurl,com.comedian_bioavailable,com.comedian_biotext,com.comedian_firstname,c.channel_seoname,com.comedian_seoname');
	   $this->db_user->from('Comedians AS com');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   $this->db_user->where('com.comedian_id',$comedian_id);
	   $query = $this->db_user->get(); 		
	   $result = $query->result();
	   return $result;
   }
   //load more comedians ajax call
   public function load_more_comedians($character,$last_record,$limit = 20){
	   $character = mysql_real_escape_string($character);
	   $this->db_user->select('com.comedian_id,com.comedian_fullname,com.comedian_thumbimageurl,com.comedian_bioavailable,com.comedian_biotext,com.comedian_firstname,c.channel_seoname,com.comedian_seoname');
	  $this->db_user->from('Comedians AS com');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   if($character!='all'){
	      $this->db_user->where("comedian_firstname LIKE '$character%' ");
	   }
	   $this->db_user->order_by("comedian_fullname");
	   $this->db_user->limit($limit,$last_record);
	   $query = $this->db_user->get();
 
	   $result = $query->result();
	   return $result;
   }
   public function search_comedian_by_name($kw,$limit = ''){
	   $kw = mysql_real_escape_string($kw);
	   $this->db_user->select('com.comedian_id,com.comedian_fullname,com.comedian_thumbimageurl,com.comedian_bioavailable,com.comedian_biotext,com.comedian_firstname,c.channel_seoname,com.comedian_biotext,com.comedian_seoname');
	   $this->db_user->from('Comedians AS com');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   $this->db_user->where("lcase(comedian_fullname) LIKE '%$kw%'");
	   $this->db_user->order_by("comedian_fullname");
	   if($limit!=''){
		   $this->db_user->limit($limit,0);
	   }
	   $query = $this->db_user->get();
 
	   $result = $query->result();
	   return $result;
	   
   }
   public function get_comedian_social_links($comedian_id){
	   $this->db_user->select('cl.comedianlink_url,lt.linktype_name,lt.linktype_iconurl,lt.linktype_iconurlgreyed,cl.linktype_id');
	   $this->db_user->from('ComedianLinks AS cl');
	   $this->db_user->join('LinkTypes AS lt','cl.linktype_id=lt.linktype_id','left');
	   $this->db_user->where('cl.comedian_id',$comedian_id);
	   $query = $this->db_user->get();
	   $result = $query->result();
	   return $result;
   }
   public function user_like_comedian($like,$comedian_id,$user_id){
		$is_already_liked = $this->is_user_like_comedian($comedian_id,$user_id);
		$data = array('user_id'=>$user_id,'comedian_id'=>$comedian_id,'likedislikescore'=>$like);
		if($is_already_liked=='0'){
			$saved = $this->db_user->insert('UserComedianLikes',$data);
		}else{
			$this->db_user->where('user_id',$user_id);
			$this->db_user->where('comedian_id',$comedian_id);
			$saved = $this->db_user->update('UserComedianLikes',$data);
		}
		return $saved;
		
   }
   public function is_user_like_comedian($comedian_id,$user_id){
	   $this->db_user->select('likedislikescore');
	   $this->db_user->from('UserComedianLikes');
	   $this->db_user->where('user_id',$user_id);
	   $this->db_user->where('comedian_id',$comedian_id);
	   $query = $this->db_user->get();
	   $result = $query->result_array();
	   if(isset($result[0])){
		   return $result[0]['likedislikescore'];
	   }else{
		   return '0';
	   }
   }
   public function is_favorite_comedian($comedian_id,$user_id){
	   $this->db_user->select('user_id');
	   $this->db_user->from('UserComedianFavorites');
	   $this->db_user->where('user_id',$user_id);
	   $this->db_user->where('comedian_id',$comedian_id);
	   $query = $this->db_user->get();
	   $result = $query->result_array();
	   if(isset($result[0]))
	   		return '1';
	   else
			return '0';
   }
   public function add_remove_favorite($comedian_id,$user_id){
	   $is_already = $this->is_favorite_comedian($comedian_id,$user_id);
	   if($is_already==1){
		   $this->db_user->where('user_id',$user_id);
		   $this->db_user->where('comedian_id',$comedian_id);
		   $this->db_user->delete('UserComedianFavorites');
		   return '0';
	   }else{
		   $data = array('user_id'=>$user_id,'comedian_id'=>$comedian_id);
		   $this->db_user->insert('UserComedianFavorites',$data);
		   return '1';
	   }
	   
   }
   public function get_all_comedians_not_in_channel($comedians){
	   $this->db_user->select('comed.comedian_id,comed.comedian_fullname,comed.comedian_thumbimageurl,comed.comedian_bioavailable');
	   $this->db_user->from('Comedians AS comed');
	   if($comedians!=''){
		   $this->db_user->where("comed.comedian_id NOT IN($comedians)");
	   }
	   $this->db_user->order_by('comed.comedian_fullname');
	   $comedians = $this->db_user->get();
	   $result = $comedians->result();
	   return $result;
   }
   public function get_channel_comedians($comedians){
	   if($comedians!=''){
		   $this->db_user->select('comed.comedian_id,comed.comedian_fullname,comed.comedian_thumbimageurl,comed.comedian_bioavailable');
		   $this->db_user->from('Comedians AS comed');
		   $this->db_user->where("comed.comedian_id IN($comedians)");
		   
		   $this->db_user->order_by('comed.comedian_fullname');
		   $comedians = $this->db_user->get();
		   $result = $comedians->result();
		   return $result;
		   
	   }else{
		   return false;
	   }
   }
   public function get_comedian_skin($skin_id){
	   $this->db->select('comedianchannelskin_name,comedianchannelskin_topimage,comedianchannelskin_fontcolor,comedianchannelskin_fontshadowcolor,comedianchannelskin_showonlistingpage,comedianchannelskin_channelfontcolor,comedianchannelskin_channelfontshadowcolor,comedianchannelskin_novideooffsetpixels');
	   $this->db->from('ComedianChannelSkins');
	   $this->db->where('comedianchannelskin_id',$skin_id);
	   $query = $this->db->get();
	   $result = $query->result_array();
	   if(isset($result[0])){
		   return $result[0];
	   }else{
		   return false;
	   }
   }
   public function get_comedian_skins_for_listing(){
	   $this->db->select('comedianchannelskin_id,comedianchannelskin_name,comedianchannelskin_topimage,comedianchannelskin_fontcolor,comedianchannelskin_fontshadowcolor,comedianchannelskin_showonlistingpage,comedianchannelskin_channelfontcolor,comedianchannelskin_channelfontshadowcolor');
	   $this->db->from('ComedianChannelSkins');
	   $this->db->where('comedianchannelskin_showonlistingpage','1');
	   $query = $this->db->get();
	   $result = $query->result();
	   return $result;
   }
   public function get_recent_blog_entries($comedian_id,$limit=0){
	   $this->db->select('comedianblogentry_id,comedian_id,comedianblogentry_releasedate,comedianblogentry_isapproved,comedianblogentry_entryhtml,comedianblogentry_headline,comedianblogentry_authorname');
	   $this->db->from('ComedianBlogEntries');
	   $this->db->where('comedian_id',$comedian_id);
	   $this->db->where('comedianblogentry_releasedate <= NOW()');
	   $this->db->where('comedianblogentry_isapproved','1');
	   $this->db->order_by('comedianblogentry_releasedate','DESC');
	   if($limit!=0){
	   	$this->db->limit($limit,0);
	   }
	   $query = $this->db->get();
	   $result = $query->result();
	   return $result;
   }
   public function get_blog_entry_by_id($entry_id){
	   $this->db->select('comedianblogentry_id,comedian_id,comedianblogentry_releasedate,comedianblogentry_isapproved,comedianblogentry_entryhtml,comedianblogentry_headline,comedianblogentry_authorname');
	   $this->db->from('ComedianBlogEntries');
	   $this->db->where('comedianblogentry_id',$entry_id);
	   $this->db->where('comedianblogentry_releasedate <= NOW()');
	   $this->db->where('comedianblogentry_isapproved','1');
	   $query = $this->db->get();
	   $result = $query->result_array();
	   if(isset($result[0])){
	   		return $result[0];
	   }
   }
   public function get_posts_archives($year,$month,$comedian_id){
	   $this->db->select('comedianblogentry_id,comedian_id,comedianblogentry_releasedate,comedianblogentry_isapproved,comedianblogentry_entryhtml,comedianblogentry_headline,comedianblogentry_authorname');
	   $this->db->from('ComedianBlogEntries');
	   $this->db->where('DATE_FORMAT(comedianblogentry_releasedate, "%Y-%m") = "'.$year.'-'.$month.'"');
	   $this->db->where('comedianblogentry_isapproved','1');
	   $this->db->where('comedian_id',$comedian_id);
	   $this->db->order_by('comedianblogentry_releasedate','DESC');
	  
	   $query = $this->db->get();
	   $result = $query->result();
	   return $result;
   }
   public function get_comedian_channels($user_id,$comedian_id){
	   if($user_id){
	   		$sql = "SELECT c.channel_id, c.channel_name FROM 
				(SELECT channel_id FROM UserChannelFavorites WHERE user_id = '".$user_id."'
				UNION DISTINCT
				SELECT channel_id FROM ChannelSuggestions) AS cl INNER JOIN Channels AS c ON c.channel_id = cl.channel_id
				ORDER BY c.channel_name";
	   }else{
		   $sql = "SELECT DISTINCT c.channel_id, c.channel_name
			FROM ChannelSuggestions AS cl INNER JOIN Channels AS c ON c.channel_id = cl.channel_id
			ORDER BY c.channel_name";
	   }
	   $query = $this->db_user->query($sql);
	   $result = $query->result();
	   
	   $this->db_user->select('c.channel_id,c.channel_name');
	   $this->db_user->from('Comedians AS com');
	   $this->db_user->join('Channels AS c','com.comedian_channelid=c.channel_id','left');
	   $this->db_user->where('com.comedian_id',$comedian_id);
	   $query = $this->db_user->get();
	   $result_com = $query->result();
	   if(isset($result_com[0])){
		   $result[count($result)] = $result_com[0];
	   }
	   
	   return $result;
   }
   public function get_comedian_seo_name($channel_id){
	   $sql = 'SELECT comedian_id, comedian_seoname FROM Comedians WHERE comedian_channelid = '.$channel_id;
	   $query = $this->db_user->query($sql);
	   $result = $query->result_array();
	   if(isset($result[0])){
	   		return $result[0];
	   }else{
		   return false;
	   }
   }
}

?>
