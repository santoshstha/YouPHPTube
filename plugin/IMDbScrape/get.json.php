<?php
header('Content-Type: application/json');
require_once '../../videos/configuration.php';
//require_once $global['systemRootPath'] . 'plugin/Bookmark/Objects/BookmarkTable.php';
require_once $global['systemRootPath'] . 'plugin/IMDbScrape/imdb.class.php';

$plugin = YouPHPTubePlugin::getObjectData("IMDbScrape");

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
                                                
if(!User::isAdmin() && !Video::canEdit($_GET['videos_id'])){
    $obj->msg = "You cant do this";
    die(json_encode($obj));
}

$video = new Video('', '', $_GET['videos_id']);

$oIMDB = new IMDB($video->getTitle());
if ($oIMDB->isReady) {
    $videoFileName = $video->getFilename();
    $poster = $oIMDB->getPoster('big', true);
    $filename = "{$global['systemRootPath']}videos/{$videoFileName}_portrait.jpg";
    im_resizeV2($poster, $filename, $plugin->posterWidth, $plugin->posterHeight);
    
    $description = $oIMDB->getDescription();
    $rate = $oIMDB->getRating();
    $trailer = $oIMDB->getTrailerAsUrl(true);
    
    $video->setDescription($description);
    $video->setRate($rate);
    
    // trailer
    $encoderURL = $config->getEncoderURL()."youtubeDl.json?videoURL=".urlencode($trailer)."&webSiteRootURL=".urlencode($global['webSiteRootURL'])."&user=".urlencode(User::getUserName())."&pass=".urlencode(User::getUserPass());
    error_log("IMDB encoder URL {$encoderURL}");
    $json = url_get_contents($encoderURL);
    error_log("IMDB encoder answer {$json}");
    $json = json_decode($json);
    if(!empty($json->videos_id)){
        $trailerVideo = new Video('', '', $json->videos_id);
        $trailerVideo->setStatus('u');
        $video->setTrailer1(Video::getPermaLink($json->videos_id,true));
    }
    $video->save();
    
    $obj->error = false;
} else {
    $obj->msg = "Movie not found";
}

echo json_encode($obj);
