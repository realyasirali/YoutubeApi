<?php 
require_once './google-api-php-client-2.1.3_PHP54/vendor/autoload.php';
define("DB_HOST","localhost");

define("DB_USER","db-user");
define("DB_PASS","db-pass");
define("DB_NAME","db-name");
function returnYoutubeClientProfileObject()
{

	
     $DEVELOPER_KEY = 'your-developer-key'; 
     $client = new Google_Client();
    $client->setDeveloperKey($DEVELOPER_KEY);

  
  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  
	return $youtube;

}

$youtube=returnYoutubeClientProfileObject();
$connect=mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$stmt=$connect->prepare("insert into youtubecatsVideoID values(?,?,?)");
$params = array(
                      //'type'=>'playlist',
                      'q' => '%22funny+cat%22"',
                      'relevanceLanguage'=>"en",
                      //'order'=>"videoCount",
                      'maxResults'=>50,
                      'regionCode'=>'US',
                    );

$year = 2013;
$years = 4;
for($i = 1; $i <= $years; $i++)
{	
	for($j = 9; $j <= 12; $j++)//loop month
	{
		$Month = ($j < 10) ? "0$j" : $j;
		
		for($k = 1; $k <= 29; $k++)//loop days // 30
		{	
			$Startdate = ($k < 10) ? "0$k" : $k;
			$EndDate = $k+1; 
			$EndDate = ($EndDate < 10) ? "0$EndDate" : $EndDate ;

			$QstartDate = $year."-".$Month."-".$Startdate."T11:54:58.000Z";
			$QendtDate = $year."-".$Month."-".$EndDate."T11:54:58.000Z";

			$params['publishedAfter'] = $QstartDate;
			$params['publishedBefore'] = $QendtDate; print_r($params); //exit();
			$searchResponse = $youtube->search->listSearch('snippet', $params);

			foreach ($searchResponse as $searchResult) 
			{
				echo "*******************************************". PHP_EOL. PHP_EOL;
				echo $searchResult['id']['kind'];
				echo  PHP_EOL;
				echo $searchResult['snippet']['publishedAt'];
				echo  PHP_EOL;
				echo $searchResult['snippet']['title'];
				echo  PHP_EOL;
				echo $videoId=(isset($searchResult['id']['channelId'])) ? $searchResult['id']['channelId']: (isset($searchResult['id']['playlistId']) ? $searchResult['id']['playlistId'] : $searchResult['id']['videoId']);
				//echo "video id ". $videoId.PHP_EOL;
				$status=0;

				$title=$searchResult['snippet']['title'];

                $stmt->bind_param("sis",$videoId,$status,$title);
               if( $stmt->execute())echo "added successfuly".PHP_EOL;
               else echo "error occured ".$stmt->error.PHP_EOL;
				//print_r($searchResult);
				//echo "*******************************************". PHP_EOL. PHP_EOL. PHP_EOL;

				
			}
			
			//echo $QstartDate . " ==> ". $QendtDate .PHP_EOL;
		}//loop days END
	}//loop month END
	$year++;
}//loop year END



