<?php 

require_once ('/home/orbit/google-api-php-client-2.1.3_PHP54/vendor/autoload.php');
		
		function returnYoutubeClientProfileObject(){

		// This code will execute if the user entered a search query in the form
	// and submitted the form. Otherwise, the page displays the form above.

  /*
   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
   * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
   * Please ensure that you have enabled the YouTube Data API for your project.
   */
  $DEVELOPER_KEY = 'your-developer-key'; 
  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);

  
  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  
	return $youtube;

}



function getDbConnection(){

$connection=mysqli_connect('host','user','password','db-name');

         // if connection is null then exit
         if(!$connection){

          die ("unable to connect to database");


         }

return $connection;
	
}



function set_Chanlle_ID_tatus_Done($channelID){
  $connection =getDbConnection();
  $stamt=$connection->prepare("update ChannelIDs set status=1 where channelID=?");
  $stamt->bind_param("s",$channelID);
  $stamt->execute();

}




function getAllChannleList($channlID){
  $connection=getDbConnection();
  $stamt=$connection->prepare("insert into PlayListIds Values(?,?,?)");
  $youtube=returnYoutubeClientProfileObject();



  $PlayLists=$youtube->playlists->listPlaylists('snippet',array(

	     'channelId'=>$channlID,

		));

    

   if(sizeof($playlists['items'])===0)return;
   foreach ($PlayLists['items'] as $playlist) {
    $staus=0;
    echo "Playlist id".$playlist['id'].PHP_EOL;
    $playid=$playlist['id'];
    $stamt->bind_param("ssi",$channlID,$playid,$staus);
    $stamt->execute();
}



}



function MainFunction(){
  $connection=getDbConnection();
  $stamt=$connection->prepare("select channelID from ChannelIDs where status=0");
  $stamt->bind_result($channelID);
  $stamt->execute();
  while($stamt->fetch()){
    getAllChannleList($channelID);
    set_Chanlle_ID_tatus_Done($channelID);
  }


}

MainFunction();


?>

