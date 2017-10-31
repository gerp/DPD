<?php namespace MCS;

use Exception;
use Soapclient;
use SoapFault;
use SOAPHeader;

class DPDAuthorisation{

    public $authorisation = [
        'staging' => false,
        'delisId' => null,
        'password' => null,
        'messageLanguage' => 'en_EN',
        'customerNumber' => null,
        'token' => null,
        'zone' => null,
    ];

    const TEST_LOGIN_WSDL = 'https://public-ws-stage.dpd.com/services/LoginService/V2_0/?wsdl';
    const LOGIN_WSDL = 'https://public-ws.dpd.com/services/LoginService/V2_0?wsdl';

    /**
     * Get an authorisationtoken from the DPD webservice
     * @param array   $array
     * @param boolean $wsdlCache, cache the wsdl
     */
    public function __construct($array, $wsdlCache = false)
    {
        $this->authorisation = array_merge($this->authorisation, $array);
        $this->environment = [
            'wsdlCache' => $wsdlCache,
            'loginWsdl' => $this->getWsdl(),
        ];

        if($this->environment['wsdlCache']){
            $soapParams = [
                'cache_wsdl' => WSDL_CACHE_BOTH
            ];
        }
        else{
            $soapParams = [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'exceptions' => true
            ];
        }

        try{
            $client = new Soapclient($this->environment['loginWsdl'], $soapParams);

            $auth = $client->getAuth([
                'delisId' => $this->authorisation['delisId'],
                'password' => $this->authorisation['password'],
                'messageLanguage' => $this->authorisation['messageLanguage'],
            ]);

            $auth->return->messageLanguage = $this->authorisation['messageLanguage'];
            $this->authorisation['token'] = $auth->return;

        }
        catch (SoapFault $e){
            throw new Exception($e->detail->authenticationFault->errorMessage);
        }
    }

    private function getWsdl()
    {
        if (!isset($this->authorisation['zone'])) {
            return $this->authorisation['staging'] ? 'https://public-dis-stage.dpd.nl/Services/LoginService.svc?singlewsdl' : 'https://public-dis.dpd.nl/Services/LoginService.svc?singlewsdl';
        }
        return $this->authorisation['staging'] ? self::TEST_LOGIN_WSDL : self::LOGIN_WSDL;
    }
}