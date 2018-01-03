<?php

ini_set('Display_errors', true); 
// php youtube main connector class
require_once ('./google-api-php-client-2.1.3_PHP54/vendor/autoload.php');
// function to return youtube api object to call method to get data		
function returnYoutubeClientProfileObject(){

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
$connection=mysqli_connect('','','','');

         // if connection is null then exit
         if(!$connection){

          die ("unble to connect to database");


         }

return $connection;
	
}



// function to called if connection was interrupted while fetching videos info
function Resume_From_this_Token_Channel_And_PlayListID($channelID,$Playlistid,$token){
	// intializing the youtbe main object
	$youtube=returnYoutubeClientProfileObject();
	// getting database connection to store data
	$connection1=getDbConnection();
	//parepare statements 

	$stamt=$connection1->prepare("insert into youtubeApiData Values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
	$stamt2=$connection1->prepare("update PlayListIds set nextToken=? where channelid=? and playlistid=?");

	//then token which was last visited when connection interrupted
	$nexttoken=$token;
	// checking if youtube result returned a non-null nextpage token
	while($nexttoken!=NULL){
	  echo "nextPageToken is: ".$nexttoken.PHP_EOL;

	  sleep(2);
	// querying the playlistitem by providing the token number and playlistid
	$nextPageResults=$youtube->playlistItems->listPlaylistItems('snippet',array(
		'playlistId'=>$Playlistid,
    		'pageToken'=>$nexttoken,
   
	));


//var_dump($nextPageResults);
//exit();
$counter=1;
// looping through all result return by the above query
foreach ($nextPageResults['items'] as $playitem) {
	
     echo "fetched result ".$counter.PHP_EOL;

              
                $etag=$playitem['etag'];
                $etag = str_replace('"', "", $etag);
                $id=$playitem['id'];

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
            // calling the function to get the duration ,viewcount etc
              $returnedArray=get_Video_details_OR_Meta_data($playitem['snippet']['resourceId']['videoId']);


            

			$tags=	$returnedArray['ttag'];

			$CatogryId=	$returnedArray['CcatogryId'];
		    $dlang=	$returnedArray['DdefulatLanguage'];
			$duration=	$returnedArray['Vduration'];
			$VviewCount=	$returnedArray['VviewCount'];
			$likecount=	$returnedArray['VlikeCount'];
			$dislike=	$returnedArray['VdislikeCount'];
			$comment=	$returnedArray['VcommentCount'];




        

// bind db statement paramter to insert
			$VID=$playitem['snippet']['resourceId']['videoId'];
$stamt->bind_param("ssssssssssssissiiiis",$etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$highurl,$standardurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$VID);
// inserting into the database
$stamt->execute();

echo "error is ".$stamt->error.PHP_EOL;
if($stamt->execute()){


echo "statement execute successfull".PHP_EOL;
// checking to see if the result has next page token
    $nexttoken=$nextPageResults['nextPageToken'];
// checking if null then setting the db parm to null means done with this playid
   if($nexttoken===NULL){
    $NULL=null;
   


   $stamt2->bind_param("sss",$NULL,$channelID,$Playlistid);
	$stamt2->execute();

   }
   // if above not true then setting the db param to next token
   else{


   

$stamt2->bind_param("sss",$nexttoken,$channelID,$Playlistid);
	$stamt2->execute();


   }






}






     $counter+=1;


}



}




$stamt->close();
$stamt2->close();

$connection1->close();


echo "operation success".PHP_EOL;
}



// function to get meta data of video's by videoid
function get_Video_details_OR_Meta_data($VideoID){


   // Define an object that will be used to make all API requests.
  $youtube = returnYoutubeClientProfileObject();


// gettting the meta data of a video by its video ID
$videoResonse=$youtube->videos->listVideos('snippet,contentDetails,recordingDetails,statistics',array(


      'id'=>$VideoID,



	));

$localArray=array();

// looping through all the results
foreach ($videoResonse['items'] as $videoMetadata) {
	
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



// getting only playlist id where staus is 0 and nextToken is not null

function getPlayListID_with_status_Unvisited(){
//connectin object
$connection=getDbConnection();

// preapre statemmnt
$stamt=$connection->prepare("select channelid,playlistid,nextToken from PlayListIds where nextToken is not null");


//binding result parameters

$stamt->bind_result($channelid,$Playlistid,$nextToken);

//executing the statemnet
$stamt->execute();


$chanidNO=1;
// looping until statement hasNext result
while($stamt->fetch()){

// checking if nexttoken is a400 means new playlist id
if($nextToken==="a400"){

echo "playlist  NO  ".$chanidNO.PHP_EOL;
echo "playid is   ".$Playlistid.PHP_EOL;
$OperationStatus=get_PlayListItems($channelid,$Playlistid);

echo "operation status ".$OperationStatus.PHP_EOL;


if($OperationStatus==="true"){

echo "operation was successfull".PHP_EOL;

set_PlayList_ID_Status_Done($channelid,$Playlistid);


}
}
// if id is other then a400 means that connection was interrupted so resume
else if($nextToken!="a400"){

echo "nettoken is".$nextToken.PHP_EOL;
echo "resume playlist ".PHP_EOL;
// resumen function to resume the interepted playlistid
Resume_From_this_Token_Channel_And_PlayListID($channelid,$Playlistid,$nextToken);


}

$chanidNO+=1;


}






$stamt->close();
$connection->close();



}


// setting the status of playid done when everything works fine

function set_PlayList_ID_Status_Done($channelID,$Playlistid){



$connection =getDbConnection();


$stamt=$connection->prepare("update PlayListIds set status=1 where channelID=? and playlistid=?");


$stamt->bind_param("ss",$channelID,$Playlistid);

$stamt->execute();


$stamt->close();

$connection->close();

}











// getting all the information about the playlistid items

function get_PlayListItems($channelID,$Playlistid){

$isCompletedSuccesfully="false";


// connection 
$connection=getDbConnection();

$stamt=$connection->prepare("insert into youtubeApiData Values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stamt2=$connection->prepare("update PlayListIds set nextToken=? where channelid=? and playlistid=?");


$youtube=returnYoutubeClientProfileObject();



// getting all playlist items result by playlist id
$playlistitems=$youtube->playlistItems->listPlaylistItems('snippet',array(


'playlistId'=>$Playlistid,


	));

$counter=1;
$masterArray=array();

// looping through the result to obtain info
foreach ($playlistitems as $playitem) {
	
$localArray=array();

	echo "fetch first page result ".PHP_EOL;
             
                $etag          =	$playitem['etag'];

                $id            =	$playitem['id'];

				$Publish   	   =	$playitem['snippet']['publishedAt'];


				$chanid 	   =	$playitem['snippet']['channelId'];

				$title         =	$playitem['snippet']['title'];

				$description   =	$playitem['snippet']['description'];


				$defaulturl    =	$playitem['snippet']['thumbnails']['default']['url'];

				$mediumurl     =	$playitem['snippet']['thumbnails']['medium']['url'];
				$highurl       =	$playitem['snippet']['thumbnails']['high']['url'];

				if(trim($playitem['snippet']['thumbnails']['standard']['url'])=="")
				  $standardurl ="NA";
				$standardurl   =	$playitem['snippet']['thumbnails']['standard']['url'];

				$chanTitle     =	$playitem['snippet']['channelTitle'];

              $returnedArray=get_Video_details_OR_Meta_data($playitem['snippet']['resourceId']['videoId']);

            


			    $tags 		   =	$returnedArray['ttag'];
			    $CatogryId 	   =	$returnedArray['CcatogryId'];
		        $dlang 		   =	$returnedArray['DdefulatLanguage'];
			    $duration	   =	$returnedArray['Vduration'];
			    $VviewCount    =	$returnedArray['VviewCount'];
			    $likecount 	   =	$returnedArray['VlikeCount'];
			    $dislike 	   =	$returnedArray['VdislikeCount'];
			    $comment 	   =	$returnedArray['VcommentCount'];

$VID=$playitem['snippet']['resourceId']['videoId'];

$stamt->bind_param("ssssssssssssissiiiis",$etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$highurl,$standardurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$VID);




$stamt->execute();


echo "executing error is ".$stamt->error.PHP_EOL;

if($stamt->execute()===false){

return;

}





$counter+=1;
}
// check to see if nextpage is given,if yes then continue until nextpage=null

$nexttoken=$playlistitems['nextPageToken'];


if($nexttoken===NULL){
	$isCompletedSuccesfully="true";
	$NULL=null;
	$stamt2->bind_param("sss",$NULL,$channelID,$Playlistid);
	$stamt2->execute();
}


while($nexttoken!=NULL){

$stamt2->bind_param("sss",$nexttoken,$channelID,$Playlistid);
	$stamt2->execute();

$isCompletedSuccesfully="flase";
echo "nextPageToken is: ".$nexttoken.PHP_EOL;
sleep(0.0000000000000000000002);


// getting information of the nextToken page 
$nextPageResults=$youtube->playlistItems->listPlaylistItems('snippet',array(

    'playlistId'=>$Playlistid,
    'pageToken'=>$nexttoken,
   
	));



$counter=1;
foreach ($nextPageResults['items'] as $moreplayListitems) {
	
     echo "fetched result ".$counter.PHP_EOL;

               
                $etag       =$playitem['etag'];

                $id         =$playitem['id'];

				$Publish    =$playitem['snippet']['publishedAt'];


				$chanid     =$playitem['snippet']['channelId'];

				$title      =$playitem['snippet']['title'];

				$description=$playitem['snippet']['description'];


				$defaulturl =$playitem['snippet']['thumbnails']['default']['url'];

				$mediumurl  =$playitem['snippet']['thumbnails']['medium']['url'];

				$highurl    =$playitem['snippet']['thumbnails']['high']['url'];

				if(trim($playitem['snippet']['thumbnails']['standard']['url'])=="")
				  $standardurl="NA";
				$standardurl =$playitem['snippet']['thumbnails']['standard']['url'];

				$chanTitle   =$playitem['snippet']['channelTitle'];

              	$returnedArray=get_Video_details_OR_Meta_data($playitem['snippet']['resourceId']['videoId']);

				$tags   		=$returnedArray['ttag'];
				$CatogryId 		=$returnedArray['CcatogryId'];
		    	$dlang 			=$returnedArray['DdefulatLanguage'];
				$duration		=$returnedArray['Vduration'];
				$VviewCount 	=$returnedArray['VviewCount'];
				$likecount     	=$returnedArray['VlikeCount'];
				$dislike 		=$returnedArray['VdislikeCount'];
				$comment 		=$returnedArray['VcommentCount'];


$VID=$playitem['snippet']['resourceId']['videoId'];

   $stamt->bind_param("ssssssssssssissiiiis",$etag,$id,$Publish,$chanid,$title,$description,$defaulturl,$mediumurl,$highurl,$standardurl,$chanTitle,$tags,$CatogryId,$dlang,$duration,$VviewCount,$likecount,$dislike,$comment,$VID);

   $stamt->execute();

echo "error occured is exe".$stamt->error.PHP_EOL;

if($stamt->execute()){

    $nexttoken=$nextPageResults['nextPageToken'];

   if($nexttoken===NULL){
    $NULL=null;
   $stamt2->bind_param("sss",$NULL,$channelID,$Playlistid);
	$stamt2->execute();

   }


     $counter+=1;


}
}

$isCompletedSuccesfully="true";

}



$stamt->close();
$stamt2->close();
$connection->close();

return $isCompletedSuccesfully;

}



// calling the main function to get crawl the data from youtube channel playlist

getPlayListID_with_status_Unvisited();



?>
