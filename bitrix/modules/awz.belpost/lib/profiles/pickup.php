<?php
namespace Awz\Belpost\Profiles;

use Awz\Belpost\Handler;
use Awz\Belpost\Helper;
use Awz\Belpost\PvzTable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security;

Loc::loadMessages(__FILE__);

class Pickup extends \Bitrix\Sale\Delivery\Services\Base
{
    protected static $isProfile = true;
    protected $parent = null;

    const DEF_COUNTRY_CODE = 'BY';

    const WEIGHTS = [
        [0.01, 1],
        [1.01, 2],
        [2.01, 3],
        [3.01, 4],
        [4.01, 5],
        [5.01, 6],
        [6.01, 7],
        [7.01, 8],
        [8.01, 9],
        [9.01, 10],
        [10.01, 15],
        [15.01, 20],
        [20.01, 25],
        [25.01, 30],
        [30.01, 35],
        [35.01, 40],
        [40.01, 45],
        [45.01, 50],
        [50.01, 1000],
    ];

    public function __construct(array $initParams)
    {
        if(empty($initParams["PARENT_ID"]))
            throw new \Bitrix\Main\ArgumentNullException('initParams[PARENT_ID]');
        parent::__construct($initParams);
        $this->parent = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($this->parentId);
        if(!($this->parent instanceof Handler))
            throw new ArgumentNullException('parent is not instance of \Awz\Belpost\Handler');
        if(isset($initParams['PROFILE_ID']) && intval($initParams['PROFILE_ID']) > 0)
            $this->serviceType = intval($initParams['PROFILE_ID']);
    }

    public static function getClassTitle()
    {
        return Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_NAME');
    }

    public static function getClassDescription()
    {
        return Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_DESC');
    }

    public function getParentService()
    {
        return $this->parent;
    }

    public function isCalculatePriceImmediately()
    {
        return $this->getParentService()->isCalculatePriceImmediately();
    }

    public static function isProfile()
    {
        return self::$isProfile;
    }

    public function isCompatible(\Bitrix\Sale\Shipment $shipment)
    {
        $calcResult = self::calculateConcrete($shipment);
        return $calcResult->isSuccess();
    }

    protected function getConfigStructure()
    {
        $result = array(
            "MAIN" => array(
                'TITLE' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_SETT_INTG'),
                'DESCRIPTION' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_SETT_INTG_DESC'),
                'ITEMS' => array(
                    'BTN_CLASS' => array(
                        'TYPE' => 'STRING',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_SETT_BTN_CLASS'),
                        "DEFAULT" => 'btn btn-primary'
                    ),
                    'WEIGHT_DEFAULT' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_SETT_WEIGHT_DEF'),
                        "DEFAULT" => '3000'
                    ),
                    'SHOW_ALL' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_SHOW_ALL'),
                        "DEFAULT" => 'Y'
                    ),
                    'API_COST' => array(
                        'TYPE' => 'ENUM',
                        'OPTIONS'=>[
                            'N'=>Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_N'),
                            'UL'=>Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_UL'),
                            'FL'=>Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_FL')
                        ],
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST'),
                        "DEFAULT" => 'N'
                    ),
                )
            ),
            "TARIFS_WEIGHT"=>array(
                'TITLE' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS_WEIGHT'),
                'DESCRIPTION' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS_WEIGHT_DESC'),
                'ITEMS' => array(

                )
            ),
            "TARIFS"=>array(
                'TITLE' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS'),
                'DESCRIPTION' => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS_DESC'),
                'ITEMS' => array(
                    'TARIF_MAXW' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_MAXW'),
                        "DEFAULT" => '50'
                    ),
                    'TARIF_NDS1' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_NDS1'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_NP' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_NP'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_NDS2' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_NDS2'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_ADD' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_ADD'),
                        "DEFAULT" => '0.00'
                    ),
                )
            )
        );

        $defTarifs = Helper::getDefTarifs('fl');
        $defTarifsUl = Helper::getDefTarifs('ul');

        asort($defTarifs);
        asort($defTarifsUl);

        $result['MAIN']['ITEMS']['API_COST_FL'] = [
            'TYPE' => 'Y/N',
            "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_Y').' ',
            "DEFAULT" => 'N'
        ];
        $result['MAIN']['ITEMS']['API_COST_UL'] = [
            'TYPE' => 'Y/N',
            "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_Y').' ',
            "DEFAULT" => 'N'
        ];

        $result['MAIN']['ITEMS']['API_COST_FL']['NAME'] .= "\n".Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_FL').': ';
        $result['MAIN']['ITEMS']['API_COST_FL']['NAME'] .= '['.implode(", ",$defTarifs)."]";
        $result['MAIN']['ITEMS']['API_COST_UL']['NAME'] .= "\n".Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_MAIN_API_COST_UL').': ';
        $result['MAIN']['ITEMS']['API_COST_UL']['NAME'] .= '['.implode(", ",$defTarifsUl).']';
        //print_r($defTarifs);

        foreach(self::WEIGHTS as $weight){
            $k = preg_replace('/([^0-9])/is','',$weight[0]).
                '_'.preg_replace('/([^0-9])/is','',$weight[1]);
            $result['TARIFS_WEIGHT']['ITEMS'][$k] = array(
                'TYPE' => 'NUMBER',
                "NAME" => Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIF_NUMS', [
                    '#FROM#'=>$weight[0],
                    '#TO#'=>$weight[1]
                ]),
                "DEFAULT" => $defTarifs[$k]
            );
        }

        return $result;
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
    {

        $config = $this->getConfigValues();

        $result = new \Bitrix\Sale\Delivery\CalculationResult();

        $weight = $shipment->getWeight();
        if(!$weight) $weight = $config['MAIN']['WEIGHT_DEFAULT'];

        $maxWeight = $config['TARIFS']['TARIF_MAXW'];

        if($weight > $maxWeight){
            $result->addError(new \Bitrix\Main\Error(
                Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS_ERR_MAXW')
            ));
            return $result;
        }

        /* @var \Bitrix\Sale\Order $order */
        $order = $shipment->getCollection()->getOrder();
        $props = $order->getPropertyCollection();
        $locationProp = $props->getDeliveryLocation();
        $locationName = '';
        if(!$locationProp){
            $locationName = static::DEF_COUNTRY_CODE;
        }else{
            $locationCode = $locationProp->getValue();
            if(strlen($locationCode) == strlen(intval($locationCode))){
                if ($loc = \Bitrix\Sale\Location\LocationTable::getRowById($locationCode)) {
                    $locationCode = $loc['CODE'];
                }
            }
        }

        if($locationCode){
            $res = \Bitrix\Sale\Location\LocationTable::getList(array(
                'filter' => array(
                    '=CODE' => $locationCode,
                    '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '=PARENTS.TYPE.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                ),
                'select' => array(
                    'I_ID' => 'PARENTS.ID',
                    'I_NAME_LANG' => 'PARENTS.NAME.NAME',
                    'I_TYPE_CODE' => 'PARENTS.TYPE.CODE',
                    'I_TYPE_NAME_LANG' => 'PARENTS.TYPE.NAME.NAME',
                ),
                'order' => array(
                    'PARENTS.DEPTH_LEVEL' => 'asc'
                )
            ));
            while($item = $res->fetch())
            {
                if($item['I_TYPE_CODE'] == 'CITY'){
                    $locationName = $item['I_NAME_LANG'];
                }
            }
        }
        if(!$locationName) $locationName = static::DEF_COUNTRY_CODE;


        if(!$locationName){
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_ERR_REGION')));
            return $result;
        }

        $rCheck = PvzTable::checkPvzFromTown($locationName);
        if(!$rCheck->isSuccess() && $config['MAIN']['SHOW_ALL']=='Y'){
            $rCheck = new Result();
            $locationName = static::DEF_COUNTRY_CODE;
        }
        if($rCheck->isSuccess()){

            $price = false;

            $defTarifs = [];
            if($config['MAIN']['API_COST'] == 'FL' && $config['MAIN']['API_COST_FL'] == 'Y'){
                $defTarifs = Helper::getDefTarifs('fl');
            }

            if($config['MAIN']['API_COST'] == 'UL' && $config['MAIN']['API_COST_UL'] == 'Y'){
                $defTarifs = Helper::getDefTarifs('ul');
            }

            foreach (static::WEIGHTS as $weightConfig){
                $k = preg_replace('/([^0-9])/is','',$weight[0]).
                    '_'.preg_replace('/([^0-9])/is','',$weight[1]);
                if(
                    ($weight > $weightConfig[0]*1000)
                    && ($weight <= $weightConfig[1]*1000)
                ){
                    $tarif_detail = 0;
                    if($config['MAIN']['API_COST'] == 'N'){
                        $tarif_detail = $config['TARIFS_WEIGHT'][$k];
                    }elseif($defTarifs){
                        $tarif_detail = $defTarifs[$k];
                    }
                    if(!$tarif_detail) continue;

                    $price = round((float)$config['TARIFS_WEIGHT'][$k],2);
                }
            }

            if($config['TARIFS']['TARIF_NDS1']==='Y' && $price){
                $price = round($price + $price*0.2, 2);
            }

            if($config['TARIFS']['TARIF_NP']==='Y' && $price){
                $priceOrder = $order->getPrice();
                $price2 = round($priceOrder*3/100, 2);
                if($config['TARIFS']['TARIF_NDS2']==='Y'){
                    $price2 = round($price2 + $price2*0.2, 2);
                }
                $price = $price + $price2;
            }

            if($config['TARIFS']['TARIF_ADD'] && $price){
                $price = $price + $config['TARIFS']['TARIF_ADD'];
            }

            if($price === false){
                $result->addError(new \Bitrix\Main\Error(
                    Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_TARIFS_ERR')
                ));
                return $result;
            }

            $result->setDeliveryPrice(
                roundEx(
                    $price,
                    SALE_VALUE_PRECISION
                )
            );

            $pointId = false;
			
			foreach($props as $prop){
				if($prop->getField('CODE') == Helper::getPropPvzCode($this->getId())){
					if($prop->getValue()){
						$pointId = $prop->getValue();
					}
				}
			}
			
            $pointHtml = '';
            $request = Context::getCurrent()->getRequest();
            if($request->get('AWZ_BPPOINT_ID')){
                $pointId = preg_replace('/([^0-9A-z\-])/is', '', $request->get('AWZ_BPPOINT_ID'));
            }
            if($pointId){
                $blnRes = Helper::getBaloonHtml($pointId, true);
                if($blnRes->isSuccess()){
                    $blnData = $blnRes->getData();
                    $pointHtml = $blnData['html'];
                }
            }

            $signer = new Security\Sign\Signer();

            $signedParameters = $signer->sign(base64_encode(serialize(array(
                'address'=>$locationName,
                'profile_id'=>$this->getId(),
                's_id'=>bitrix_sessid()
            ))));

            $buttonHtml = '<a id="AWZ_BPPOINT_LINK" class="'.$config['MAIN']['BTN_CLASS'].'" href="#" onclick="window.awz_bp_modal.show(\''.Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_BTN_OPEN').'\',\''.$signedParameters.'\');return false;">'.Loc::getMessage('AWZ_BELPOST_PROFILE_PICKUP_BTN_OPEN').'</a><div id="AWZ_BPPOINT_INFO">'.$pointHtml.'</div>';
            $result->setDescription($result->getDescription().
                '<!--btn-awz-ed-start-->'.
                $buttonHtml
                .'<!--btn-awz-ed-end-->'
            );

        }else{
            foreach ($rCheck->getErrors() as $error) {
                $result->addError($error);
            }
        }


        return $result;

    }

    public static function onBeforeAdd(array &$fields = array()): \Bitrix\Main\Result
    {
        if(!$fields['LOGOTIP']){
            $fields['LOGOTIP'] = Handler::getLogo();
        }
        return new \Bitrix\Main\Result();
    }

    public static function onAfterAdd($serviceId, array $fields = array())
    {
        \Awz\Belpost\Checker::agentGetTarifs();
        $strAgent = \Awz\Belpost\Checker::agentGetPickpointsStep(0, time()+10);
        if($strAgent){
            \CAgent::AddAgent(
                $strAgent,
                "awz.belpost",
                "N",
                60);
        }
        return true;
    }
}