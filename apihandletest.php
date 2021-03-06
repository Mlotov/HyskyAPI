<?php

header("Access-Control-Allow-Origin: *");
ini_set('memory_limit', '8192M');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require("composer/vendor/autoload.php");


    $access = true;



if(isset($_GET["req"]) && $access){
    if($_GET["req"] == "top"){

        $AuctionsArray = json_decode(file_get_contents("cache/allauctions.json"), true);
        if(array_multisort(array_column($AuctionsArray, 'highest'), SORT_DESC, $AuctionsArray)){
            $JsonOutput = array_slice($AuctionsArray, 0, 200);
            $AuctionsJson = json_encode($JsonOutput);
            echo $AuctionsJson;
        }
        else{
            echo "Failed";
        }
        
    }
    
    elseif($_GET["req"] == "search"){
    
        if(isset($_GET["query"])){

            $query = strtolower($_GET["query"]);

            if(!strpos(" " . $query, "/ah ") && !strpos(" " . $query, "/ebs ")){
                include("smart_search.php");
            }

            if(strpos(" " . $query, "/ah" )){
                $history = false;
                if(strpos(" " . $query, "-h" )){
                    $history = true;
                }
                $playerAuctions = array();
                $playerName = strtolower(trim(str_replace("/ah", "", $query)));
                $playerName = str_replace("-h", "", $playerName);
                $player = json_decode(file_get_contents("https://api.hypixel.net/player?key=09828659-42c5-4360-9203-d93bcb5df79d&name=" . $playerName), true);
                $playerUUID = $player["player"]["uuid"];
                $playerProfiles = $player["player"]["stats"]["SkyBlock"]["profiles"];
                echo "___Player profiles.____";
                var_dump($playerProfiles);
                echo "___END___";

                foreach($playerProfiles as $profile){
                    
                    $profile = json_decode(file_get_contents("https://api.hypixel.net/skyblock/auction?key=09828659-42c5-4360-9203-d93bcb5df79d&profile=" . $profile["profile_id"]), true);
                    $profileAucs = $profile["auctions"];
                    echo "<br>___sec___  <br>";
                    var_dump($profile);
                    echo "<br>___sec___ <br>";
                    if(sizeOf($profileAucs)){
                        
                        foreach($profileAucs as $Auction){

                            $Auction["bid_num"] = sizeof($Auction["bids"]);

                            $Auction["latest_bid"] = end($Auction["bids"]);

                            $Auction["bids"] = [];

                            $itemBytes = $Auction["item_bytes"]["data"];

                            $d = $itemBytes;
        
                            // Extracting Item Count from the item bytes.
        
                            $nbtString = gzdecode(base64_decode($d));
                            $nbtService = new \Nbt\Service(new \Nbt\DataHandler());
                            $tree = $nbtService->readString($nbtString);
                            $val = strtolower(print_r($tree, true));
                            $start = strpos($val, "count");
                            $finalRaw = substr($val, $start, 100);
                            $itemCount = preg_replace('/[^0-9]/', '', $finalRaw);

                            $Anvils = "No Anvil Uses";
                            if(strpos($val, "anvil_uses")){
                                $startAnvils = strpos($val, "anvil_uses");
                    
                                $finalAnvils = substr($val, $startAnvils, 250);
                                $Anvils = str_replace("1", "" , preg_replace('/[^0-9]/', '', $finalAnvils));
                            }

                            $Auction["anvils"] = $Anvils;

                            $Auction["item_count"] = $itemCount;

                            $Auction["item_bytes"] = "";
                            
                            if($Auction["end"] > strtotime("now") * 1000 || $history == true){
                                array_push($playerAuctions, $Auction); 
                            }

                        }
                    
                    }
                
                }

                // if(sizeof($playerAuctions)){

                //     echo json_encode($playerAuctions);

                // }

                // else{

                //     echo json_encode("false");

                // }
            }


            elseif(strpos(" " . strtolower($query), "/ebs")){

                $query = trim(str_replace("/ebs", "", $query));
                $query = str_replace("1", "i", $query);
                $query = str_replace("2", "ii", $query);
                $query = str_replace("3", "iii", $query);
                $query = str_replace("4", "iv", $query);
                $query = str_replace("5", "v", $query);
                $query = str_replace("6", "vi", $query);
                if(strpos(" " . $query, "god")){
                    $query = str_replace("god", "sharpnessv critical cubism enderslayer impaling execute experience fireaspect firststrike giantkiller lethality lifesteal looting luck scavenger sharpness smite vampirism", $query);
                }
                

                $type = true;
                if(strpos(" ". $query, "book")){
                    $type = "enchantedbook";
                }
                elseif(strpos(" ". $query, "potion")){
                    $type = "potion";
                }
            
                $searchAucArray = json_decode(file_get_contents("cache/allauctions.json"), true);
                $resultArray = array();
                foreach($searchAucArray as $Auction){

                    $item_lore = str_replace(" ", "",strtolower($Auction["item_lore"]));
                    $item_name = str_replace(" ", "", strtolower($Auction["item_name"]));
               
                    $search_array = explode(" ", strtolower($query));
                    $num_prec = 0;

                    foreach($search_array as $search_part){
                        if(strpos(" " . $item_lore, trim($search_part)) && $item_name == $type){
                            $num_prec += 1;
                        }
                    }

                    if( $num_prec >= 1 ){
                        $Auction["num_prec"] = $num_prec;
                        array_push($resultArray, $Auction);
                    }
                }  

                array_multisort(
                    
                    array_column($resultArray, "num_prec"), SORT_DESC,
                    $resultArray
                );

                $JsonOutput = array_slice($resultArray, 0 ,200);
                $AuctionsJson = json_encode($JsonOutput);
                echo $AuctionsJson;

            }

            else{
                $searchAucArray = json_decode(file_get_contents("cache/allauctions.json"), true);
                $resultArray = array();

                foreach($searchAucArray as $Auction){

                    $item_name = " " . str_replace(" ", "", strtolower($Auction["item_name"]));
                    $search_array = explode(" ", strtolower($query));
                    $num_prec = 0;

                    if(sizeOf($search_array) > 0){

                        for($z = 0; $z < sizeOf($search_array); $z++){

                            $search_part = str_replace( " ", "", $search_array[$z]);

                            if(isset($search_array[$z]))
                            {

                                $armor_array= array("helmet", "chestplate", "legging", "boot");

                                if(strpos(" " . $search_part, "armor")){
                                    foreach($armor_array as $armor_piece){
                                        $e_search_part = trim(str_replace("armor", $armor_piece, $search_part));
                                        if(strpos($item_name, $e_search_part)){
                                            $num_prec += 1;
                                        }
                                    }
                                }

                                elseif(strpos($item_name, $search_part))
                                {

                                    $num_prec += 1;

                                }

                            }
                            else
                            {

                                $trigger_precision = false;

                            }
                        }
                    }

                    if( $num_prec >= sizeof($search_array) ){
                        array_push($resultArray, $Auction);
                    }
                }  
                
                array_multisort(array_column($resultArray, 'endingtime'), SORT_ASC, $resultArray);
                $JsonOutput = array_slice($resultArray, 0 ,200);
                $AuctionsJson = json_encode($JsonOutput);
                echo $AuctionsJson;
            }
    
        }
        else{
            echo "NO SEARCH QUERIES";
        }
    }

}

else{
    echo "This API key is invalid.";
}
?>