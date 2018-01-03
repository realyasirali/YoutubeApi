<?php

require_once './google-api-php-client-2.1.3_PHP54/vendor/autoload.php';

define("DB_HOST","localhost");

define("DB_USER","");
define("DB_PASS","");
define("DB_NAME","");


function returnYoutubeClientProfileObject()
{

		// This code will execute if the user entered a search query in the form
	           // and submitted the form. Otherwise, the page displays the form above.

                /*
                * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
                * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
                * Please ensure that you have enabled the YouTube Data API for your project.
                */
    // google console developer key
     	$DEVELOPER_KEY = ''; 
     	$client = new Google_Client();
    	$client->setDeveloperKey($DEVELOPER_KEY);

  
  	// Define an object that will be used to make all API requests.
  		$youtube = new Google_Service_YouTube($client);

  
		return $youtube;

}

// function to get the db connection 
function getDbConnection(){
	// using mysqli_connect to get the connection 
	$connection=mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

         // if connection is null then exit
         if(!$connection){

          die ("unable to connect to database");


         }

return $connection;
  
}




function resume($plyid,$nexttoken)
{

				// intializing the youtbe main object
				$youtube=returnYoutubeClientProfileObject();
				// getting database connection to store data
				
				//parepare statements 

				$nextpagetoken=$nexttoken;


	while($nextpagetoken!=NULL)
	{

			$status=0;

			set_playList_Id_done_OR_Set_nextPage_Token($status,$nextpagetoken,$plyid);

			$playlistitemsResponse=$youtube->playlistItems->listPlaylistItems('snippet',array(

			    'playlistId' =>$plyid,
			    'maxResults'=>'50',
			    'pageToken'=>$nexttoken,

				));

				if($playlistitemsResponse['pageInfo']['totalResults']==0)
				{
		  				$null=NULL;
					  $status=1;
					 set_playList_Id_done($status,$null,$plyid);
					 return;
				}




		foreach ($playlistitemsResponse as $playitem) 
		{
    	
 			    $etag=$playitem['etag'];
                $etag = str_replace('"', "", $etag);
               
                $id=$playitem['snippet']['resourceId']['videoId'];
                
                $Publish=$playitem['snippet']['publishedAt'];
				
				$chanid=$playitem['snippet']['channelId'];

				$title=$playitem['snippet']['title'];

				$description=$playitem['snippet']['description'];

				$defaulturl=$playitem['snippet']['thumbnails']['default']['url'];

				$mediumurl=$playitem['snippet']['thumbnails']['medium']['url'];

				$highurl=$playitem['snippet']['thumbnails']['high']['url'];
               
				if(trim($playitem['snippet']['thumbnails']['standard']['url'])=="")
				  $standardurl="NA";
				$standardurl=$playitem['snippet']['thumbnails']['standard']['url'];

                $chanTitle=$playitem['snippet']['channelTitle'];

                $returnedArray=get_Video_details_OR_Meta_data($id);
				$tags=	$returnedArray['ttag'];

				$CatogryId=	$returnedArray['CcatogryId'];

			    $dlang=	$returnedArray['DdefulatLanguage'];
			    
				$duration=	$returnedArray['Vduration'];

				$VviewCount=$returnedArray['VviewCount'];

				$likecount=	$returnedArray['VlikeCount'];

				$dislike=	$returnedArray['VdislikeCount'];
				$comment=	$returnedArray['VcommentCount'];


	            $maxResUrl;
	            $maxResWidth;
	            $maxResHeigth;
             if($playitem['snippet']['thumbnails']['maxres']['url']==NULL)
             {
				  $maxResUrl="NA";
                  $maxResWidth=0;
                  $maxResHeigth=0;
              }else{
					$maxResUrl=$playitem['snippet']['thumbnails']['maxres']['url'];

                  	//echo "max res".$maxResUrl.PHP_EOL;
                	$maxResWidth=$playitem['snippet']['thumbnails']['maxres']['width'];
                	$maxResHeigth=$playitem['snippet']['thumbnails']['maxres']['height'];
                }





			insert_All_Data_To_Db($etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$standardurl,$highurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$maxResUrl,$maxResWidth,$maxResHeigth);
		}



			$nextpagetoken=$playlistitemsResponse['nextPageToken'];

	}
			$staus=1;
			$null=NULL;
			set_playList_Id_done_OR_Set_nextPage_Token($status,$null,$plyid);
}









function get_Video_details_OR_Meta_data($VideoID)
{


			   // Define an object that will be used to make all API requests.
			  $youtube = returnYoutubeClientProfileObject();


			// gettting the meta data of a video by its video ID
			$videoResonse=$youtube->videos->listVideos('snippet,contentDetails,recordingDetails,statistics,',array(


			      'id'=>$VideoID,



				));


			$localArray=array();

			// looping through all the results
			foreach ($videoResonse['items'] as $videoMetadata) 
			{
				
				//echo "video meta data".PHP_EOL;
				if(sizeof($videoMetadata['snippet']['tags'])!=0)
				$localArray['ttag']=implode('#',$videoMetadata['snippet']['tags']);

				$localArray['ttag']="NA";


				$localArray['CcatogryId']=$videoMetadata['snippet']['categoryId'];

				if (trim($videoMetadata['snippet']['defaultLanguage']) == "") 
				   $localArray['DdefulatLanguage'] = "NA";    
				   $localArray['DdefulatLanguage']=$videoMetadata['snippet']['defaultLanguage'];
				   
				   $localArray ['Vduration']=$videoMetadata['contentDetails']['duration'];
				   $localArray ['VviewCount']=$videoMetadata['statistics']['viewCount'];
				   $localArray['VlikeCount']=$videoMetadata['statistics']['likeCount'];
				   $localArray['VdislikeCount']=$videoMetadata['statistics']['dislikeCount'];
				   $localArray ['VcommentCount']=$videoMetadata['statistics']['commentCount'];
					return $localArray;
			}
}


function set_playList_Id_done_OR_Set_nextPage_Token($status,$next,$playlistid)
{

      $connect=getDbConnection();
      $statment=$connect->prepare("call set_playList_Id_done(?,?,?)");
      $statment->bind_param("iss",$status,$next,$playlistid);

        if($statment->execute())
        {

            echo "Plylist ID status done".PHP_EOL;
        }else
        {

            echo "Error in Updating Playlist id status ".$statment->error.PHP_EOL;
        }
}

function insert_All_Data_To_Db($etag,$id,$publishedAt,$channelid,$title,$description,$defaulturl,$mediumurl,$standardurl,$highurl,$channelTitile,$tags,$categoryid,$defaultlanguage,$duration,$viewcount,$likecount,$dislikecount,$commentcount,$MaxResUrl,$MaxRWidth,$MaxRHeight)
{
 		$connect=getDbConnection();
      	$statment=$connect->prepare("call insert_raw_data(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $statment->bind_param("ssssssssssssissiiiisii",$etag,$id,$publishedAt,$channelid,$title,$description,$defaulturl,$mediumurl,$standardurl,$highurl,$channelTitile,$tags,$categoryid,$defaultlanguage,$duration,$viewcount,$likecount,$dislikecount,$commentCount,$MaxResUrl,$MaxRWidth,$MaxRHeight);

        if($statment->execute())
        {

            echo "Data inserted Successfully".PHP_EOL;
        }else
        {

            echo "Error in inserting Raw Data ".$statment->error.PHP_EOL;
        }
}

function get_PlayList_Items_From_PlayList_ID($playlistid){

$youtube=returnYoutubeClientProfileObject();


$playlistitemsResponse=$youtube->playlistItems->listPlaylistItems('snippet',array(

    'playlistId' =>$playlistid,
    'maxResults'=>'50',

	));

			if($playlistitemsResponse['pageInfo']['totalResults']==0)
				{
		  			$null=NULL;
					  $status=1;
					 //set_playList_Id_done($status,$null,$playlistid);
					 return;

				}


    foreach ($playlistitemsResponse as $playitem) {
    	
 			    $etag=$playitem['etag'];
                $etag = str_replace('"', "", $etag);
               
                $id=$playitem['snippet']['resourceId']['videoId'];
                
                $Publish=$playitem['snippet']['publishedAt'];
				
				$chanid=$playitem['snippet']['channelId'];

				$title=$playitem['snippet']['title'];

				$description=$playitem['snippet']['description'];

				$defaulturl=$playitem['snippet']['thumbnails']['default']['url'];

				$mediumurl=$playitem['snippet']['thumbnails']['medium']['url'];

				$highurl=$playitem['snippet']['thumbnails']['high']['url'];
               
				if(trim($playitem['snippet']['thumbnails']['standard']['url'])=="")
				  $standardurl="NA";
				$standardurl=$playitem['snippet']['thumbnails']['standard']['url'];

                $chanTitle=$playitem['snippet']['channelTitle'];

                $returnedArray=get_Video_details_OR_Meta_data($id);
				$tags=	$returnedArray['ttag'];

				$CatogryId=	$returnedArray['CcatogryId'];

			    $dlang=	$returnedArray['DdefulatLanguage'];
			    
				$duration=	$returnedArray['Vduration'];

				$VviewCount=$returnedArray['VviewCount'];

				$likecount=	$returnedArray['VlikeCount'];

				$dislike=	$returnedArray['VdislikeCount'];
				$comment=	$returnedArray['VcommentCount'];


            $maxResUrl;
            $maxResWidth;
            $maxResHeigth;
             if($playitem['snippet']['thumbnails']['maxres']['url']==NULL){
				  $maxResUrl="NA";
                  $maxResWidth=0;
                  $maxResHeigth=0;

             }
			  else{
			$maxResUrl=$playitem['snippet']['thumbnails']['maxres']['url'];

                 // echo "max res".$maxResUrl.PHP_EOL;
                $maxResWidth=$playitem['snippet']['thumbnails']['maxres']['width'];
                $maxResHeigth=$playitem['snippet']['thumbnails']['maxres']['height'];

				}





insert_All_Data_To_Db($etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$standardurl,$highurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$maxResUrl,$maxResWidth,$maxResHeigth);
}



$nextpage=$playlistitemsResponse['nextPageToken'];

		if($nextpage==NULL)
		{
 		 	$null=NULL;
  			$status=1;
 	
 	set_playList_Id_done_OR_Set_nextPage_Token($status,$null,$playlistid);


			}




while($nextpage!=NULL){

$status=0;
echo "nextpage".$nextpage.PHP_EOL;
set_playList_Id_done_OR_Set_nextPage_Token($status,$nextpage,$playlistid);

$playlistitemsResponse=$youtube->playlistItems->listPlaylistItems('snippet',array(

    'playlistId' =>$playlistid,
    'maxResults'=>'50',
    'pageToken'=>$nextpage,

	));

			if($playlistitemsResponse['pageInfo']['totalResults']==0)
				{
		  			$null=NULL;
					  $status=1;
					 //set_playList_Id_done($status,$null,$playlistid);
					 return;

				}


    foreach ($playlistitemsResponse as $playitem) {
    	
 			    $etag=$playitem['etag'];
                $etag = str_replace('"', "", $etag);
               
                $id=$playitem['snippet']['resourceId']['videoId'];
                
                $Publish=$playitem['snippet']['publishedAt'];
				
				$chanid=$playitem['snippet']['channelId'];

				$title=$playitem['snippet']['title'];

				$description=$playitem['snippet']['description'];

				$defaulturl=$playitem['snippet']['thumbnails']['default']['url'];

				$mediumurl=$playitem['snippet']['thumbnails']['medium']['url'];

				$highurl=$playitem['snippet']['thumbnails']['high']['url'];
               
				if(trim($playitem['snippet']['thumbnails']['standard']['url'])=="")
				  $standardurl="NA";
				$standardurl=$playitem['snippet']['thumbnails']['standard']['url'];

                $chanTitle=$playitem['snippet']['channelTitle'];

                $returnedArray=get_Video_details_OR_Meta_data($id);
				$tags=	$returnedArray['ttag'];

				$CatogryId=	$returnedArray['CcatogryId'];

			    $dlang=	$returnedArray['DdefulatLanguage'];
			    
				$duration=	$returnedArray['Vduration'];

				$VviewCount=$returnedArray['VviewCount'];

				$likecount=	$returnedArray['VlikeCount'];

				$dislike=	$returnedArray['VdislikeCount'];
				$comment=	$returnedArray['VcommentCount'];


            $maxResUrl;
            $maxResWidth;
            $maxResHeigth;
             if($playitem['snippet']['thumbnails']['maxres']['url']==NULL){
				  $maxResUrl="NA";
                  $maxResWidth=0;
                  $maxResHeigth=0;

             }
			  else{
			$maxResUrl=$playitem['snippet']['thumbnails']['maxres']['url'];

                  //echo "max res".$maxResUrl.PHP_EOL;
                $maxResWidth=$playitem['snippet']['thumbnails']['maxres']['width'];
                $maxResHeigth=$playitem['snippet']['thumbnails']['maxres']['height'];

				}





		insert_All_Data_To_Db($etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$standardurl,$highurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$maxResUrl,$maxResWidth,$maxResHeigth);


    }



$nextpage=$playlistitemsResponse['nextPageToken'];


}



}

function get_Playlistid_with_nexttoken_null(){

$connection=getDbConnection();

$stamt=$connection->prepare("select playlistid,nextToken from catsPlaylistids where nextToken is not null");

$stamt->bind_result($playlisitid,$next);

$stamt->execute();
$status=1;
$nexttoken=NULL;
while($stamt->fetch()){

echo "plal".$playlisitid."nexttoken ".$next.PHP_EOL;

if($next!="a400"){

resume($playlisitid,$next);

}

else if($next=="a400"){

get_PlayList_Items_From_PlayList_ID($playlisitid);
set_playList_Id_done_OR_Set_nextPage_Token($status,$nexttoken,$playlisitid);
}


break;


}

}



// calling the one function 
get_Playlistid_with_nexttoken_null();




?>
