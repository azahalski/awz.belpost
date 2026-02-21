<?php
namespace Awz\Belpost;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;

class Checker {

    public static function runJob($points){
        if(!is_array($points)) return;
        foreach($points as $point){
            PvzTable::updatePvz($point);
        }
    }

    public static function agentGetPickpointsStep($nextPage=0, $maxTime = 0){

        if(!$maxTime) $maxTime = time()+10;

        $isUtf8 = Application::getInstance()->isUtfMode();

        $api = Helper::getApi();
        if (!$isUtf8){
            $api->setStandartJson(true);
        }
        $pointRes = $api->getPvz(['next_page'=>$nextPage]);
        if (!$isUtf8){
            $api->setStandartJson(false);
        }
        if($pointRes->isSuccess()){
            $pointsData = $pointRes->getData();
            if(isset($pointsData['result']['data']['points'])){
                foreach($pointsData['result']['data']['points'] as $point){
                    if (!$isUtf8){
                        $point = Json::decode(json_encode($point));
                    }
                    PvzTable::updatePvz($point);
                }
            }
            if(isset($pointsData['result']['data']['next_page']) && $pointsData['result']['data']['next_page']){
                $nextPage = (int)$pointsData['result']['data']['next_page'];
                usleep(500000);
                if($maxTime > time())
                    return self::agentGetPickpointsStep($nextPage, $maxTime);
            }else{
                return false;
            }
        }else{
            return false;
        }

        return "\\Awz\\Belpost\\Checker::agentGetPickpointsStep(".$nextPage.");";
    }
    public static function agentGetPickpoints(){

        try{
            $strAgent = self::agentGetPickpointsStep(0, time()+10);
            if($strAgent){
                \CAgent::AddAgent(
                    $strAgent,
                    "awz.belpost",
                    "N",
                    60);
            }
        }catch (\Exception $e){

        }

        return "\\Awz\\Belpost\\Checker::agentGetPickpoints();";

    }

    public static function agentGetTarifs(){

        $api = Helper::getApi();
        $tarifRes = $api->getDefTarifs('fl');
        if($tarifRes->isSuccess()){
            $data = $tarifRes->getData();
            $tafirs = $data['result']['data']['tarifs'] ?? [];
            Option::set("awz.belpost", "belpost_tarifs_fl", serialize($tafirs), "");
        }
        $tarifRes2 = $api->getDefTarifs('ul');
        if($tarifRes2->isSuccess()){
            $data = $tarifRes2->getData();
            $tafirs = $data['result']['data']['tarifs'] ?? [];
            Option::set("awz.belpost", "belpost_tarifs_ul", serialize($tafirs), "");
        }

        return "\\Awz\\Belpost\\Checker::agentGetTarifs();";
    }

}