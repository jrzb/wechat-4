<?php
/**
 * @file wechat/hiboston/index.php 
 */

// includes
include "./utils/geoUtilities.php";  // geo
include "./utils/msgUtilities.php";  // commen used
include "./weather/weatherCondition.php";  // weather
include "./mbta/mbtaSubwayStop.php";  // mbta


//define your token
define("TOKEN", "hiboston");

$Obj = new Callback();
$Obj->responseMsg();



// mbta callback
class Callback
{
    public function responseMsg() {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
                
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $MsgType = $postObj->MsgType;

            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();

         

            if(!empty( $keyword )) {
                if ($keyword == "Hello2BizUser") { // new user 
                    $contentStr = "Hi Bostonian!";
                    setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                } else {

                    // pre process
                    // split
                    $keywords = splitStringToTwo($keyword);
              
                    if (count($keywords) > 2) {
                        $contentStr = "Wrong Format!\n[category]: [keywords]\nor\n[category]\ni.e.weather\nor\nweather: Boston";
                        setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                        exit;
                    } else if (count($keywords) == 1) { // [category]

                        // remove spaces
                        $param = trim($keywords[0]);

                        // weather
                        if (strtolower($param) == "weather") {
                            $weather = new weatherCondition();
                            $contentStr = $weather->getWeather();
                    
                            setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                    
                        } else {
                            $contentStr = $this->getHelp();
                            setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                        }

                    } else if (count($keywords) == 2) {  // [category]: [keyword]
                        // remove spaces
                        $param = array();
                        $param[] = trim($keywords[0], " ");
                        $param[] = trim($keywords[1], " ");
            
                        // weather
                        if (strtolower($param[0]) == "weather") {
                            $weather = new weatherCondition();
                            $contentStr = $weather->getWeather($param[1]);
                    
                            setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                        } else {
                            $contentStr = $this->getHelp();
                            setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                        }                
                    }                 
                } 
            } else if ($MsgType == "location") {

                $Location_X = $postObj->Location_X;
                $Location_Y = $postObj->Location_Y;

                $stops = new mbtaSubwayStop;

                $stopArray = $stops->getStops($Location_X, $Location_Y);
                $stopNumber = count($stopArray);

                if ($stopNumber == 1) {
                    $contentStr = "Sorry, there is no subway station around...";
                    setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                } else {
                    
                    setRichMediaResponse($fromUsername, $toUsername, $time, $stopArray);
                }
            }else {
                echo "input something";
            }

        }else {
            echo "";
            exit;
        }
    }



    // help
    private function getHelp() {
        $help = "1. For nearby subway stops, share your current location with me.\n2. Sent 'weather' to get Boston weather condition.\n3. Other info is coming soon...";
        return $help;
    }
}  // callback end




?>
