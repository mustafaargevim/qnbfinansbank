<?php

namespace EFINANS\Libraries;

use EFINANS\Config\config;

class efatura extends config
{
    /** @var mixed */
    protected $parametre;

    /** @var mixed */
    protected $xml;

    /** @var mixed */
    protected $xmlData;

    /** @var mixed */
    protected $input;

    /** @var mixed */
    protected $belgeNo;

    private $data = array();
    private $belgeFormati = "";

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($config=array()){
        $this->vergiTcKimlikNo = $config["vergiTcKimlikNo"];
        $this->username = $config["username"];
        $this->password = $config["password"];
        $this->url = $config["url"];
        return $this;
    }

    public function setStart()
    {
        $this->soapUp(); /* soap başlatılıyor */

        return $this;
    }

    public function setBelgeNo($bn = 0)
    {
        /*
         * $bn -> belge noyu set ediyoruz uniq bir id olmalı
         * */
        $this->belgeNo = $bn;

        return $this;
    }


    public function setData($data = array())
    {
        /*
         * $data -> dxml datasını array olarar set ediyoruz
         * */
        $this->data = $data;

        $this->setDataXml(); /* xxml datası oluşturuluyor.*/

        return $this;
    }

    public function setbelgeFormati($data = "PDF")
    {
        $this->belgeFormati = $data;
        return $this;
    }

    private function setPrefix()
    {
        $vergino = $this->data["cac:AccountingCustomerParty"]["cac:Party"]["cac:PartyIdentification"]["cbc:ID"];
        $this->prefix["cac:AccountingCustomerParty"]["cac:Party"]["cac:PartyIdentification"]["cbc:ID"]["value"] = (strlen($vergino) > 10 ? 'TCKN' : 'VKN');
        return $this;
    }

    private function setDataXml()
    {
        $element = 'Invoice xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 ../xsdrt/maindoc/UBL-Invoice-2.1.xsd" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:n4="http://www.altova.com/samplexml/other-namespace" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"';
        $this->xml = new \EFINANS\Component\xml($element);

        $this->setPrefix();

        $this->xmlData = $this->xml->setParams($this->data, $this->prefix)->getFaturaSablonXml();
        return $this;
    }


    public function getFaturaNo($prefix="")
    {
        try {
            $this->parametre = array(
                "vknTckn" => $this->vergiTcKimlikNo,
                "faturaKodu" => $prefix,
            );

            $r = $this->api->faturaNoUret($this->parametre);
            $this->return = $r->return;
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function setEFatura()
    {
        try {
            $this->parametre = array(
                "vergiTcKimlikNo" => $this->vergiTcKimlikNo,
                "belgeTuru" => "FATURA_UBL",
                "belgeNo" => $this->belgeNo,
                "veri" => $this->xmlData,
                "belgeHash" => md5($this->xmlData),
                "mimeType" => "application/xml",
                "belgeVersiyon" => "3.0",
            );

            $this->return = $this->api->belgeGonder($this->parametre);

        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }


    public function gidenBelgeDurumSorgula($belgeOid="")
    {
        try {
            $this->parametre = array(
                "vergiTcKimlikNo" => $this->vergiTcKimlikNo,
                "belgeOid" => $belgeOid,
            );
            $r = $this->api->gidenBelgeDurumSorgula($this->parametre);
            $this->return = $r->return;
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function gidenBelgeleriIndir($belgeOid="")
    {
        try {
            $this->parametre = array(
                "vergiTcKimlikNo" => $this->vergiTcKimlikNo,
                "belgeOidListesi" => $belgeOid,
                "belgeTuru" => "FATURA",
                "belgeFormati" => $this->belgeFormati,

            );
            $r = $this->api->gidenBelgeleriIndir($this->parametre);
            $this->return = $r;
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function gidenBelgeleriListele()
    {
        try {
            $this->parametre = array(
                "parametreler" => array(
                    "baslangicGonderimTarihi" => "20200601",
                    "bitisGonderimTarihi" => "20200630",
                    "belgeTuru" => "FATURA",
                    "vkn" => $this->vergiTcKimlikNo,
                ),
            );
            $r = $this->api->gidenBelgeleriListele($this->parametre);
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $r;
    }

    public function gelenBelgeleriListele()
    {
        /* example response
         [0] => stdClass Object
                        (
                            [belgeNo] => TMA2020000000002
                            [belgeSiraNo] => 2
                            [belgeTarihi] => 20200610
                            [belgeTuru] => FATURA
                            [ettn] => 4BAF0887-FF0F-4093-9B65-CB6FBE348A72
                            [gonderenEtiket] => urn:mail:efinansgb@cs.com.tr
                            [gonderenVknTckn] => 8720616074
                        )

        */

        try {
            $this->parametre = array(
                "vergiTcKimlikNo" => $this->vergiTcKimlikNo,
                "sonAlinanBelgeSiraNumarasi" => 0,
                "belgeTuru" => "FATURA",
            );
            $r = $this->api->gelenBelgeleriListele($this->parametre);
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }

        return $r;
    }

    public function getEfaturaKullanicisi($vkn="")
    {
        try {
            $this->parametre = array(
                "vergiTcKimlikNo" => $vkn,
            );
            $r = $this->api->efaturaKullanicisi($this->parametre);

        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }

        return (int)$r->return;
    }

    public function getXmlData()
    {
        return $this->xmlData;
    }

    public function viewXmlData()
    {
        header("content-type:application/xml");
        print $this->xmlData;
        exit;
    }

    public function getErrors()
    {
        /* çalıştırılan methoddaki hataları döner*/
        return $this->errors;
    }
}
