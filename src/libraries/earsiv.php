<?php

namespace EFINANS\Libraries;

use EFINANS\Config\config;

class earsiv extends config
{
    /** @var mixed */
    protected $data;

    /** @var mixed */
    protected $parametre;

    /** @var mixed */
    protected $xml;

    /** @var mixed */
    protected $xmlData;

    /** @var mixed */
    protected $input;

    private $seriNo = "TR";
    private $sube = "MERKEZ";
    private $kasa = "MERKEZ";

    private $faturaUuid = "";
    private $faturaNo = "";
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

    public function setSeriNo($sn = "TN")
    {
        /*
         * $sn -> tercih edilen seri no
         * */
        $this->seriNo = $sn;
        return $this;
    }

    public function setUuid($uid = "")
    {
        /*
         * $uid -> getUuid() methoduyla otomatik de oluşturulabilir.
         * */
        $this->data["cbc:UUID"] = $uid;
        return $this;
    }

    public function setSube($data = "MERKEZ")
    {
        /*
         * $data -> e arşiv yapılandırma panel ayarlarından tanımlanır
         * */
        $this->sube = $data;
        return $this;
    }

    public function setKasa($data = "MERKEZ")
    {
        /*
         * $data -> e arşiv yapılandırma panel ayarlarından tanımlanır
         * */
        $this->kasa = $data;
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

    public function setfaturaUuid($data = "")
    {
        $this->faturaUuid = $data;
        return $this;
    }

    public function setfaturaNo($data = "")
    {
        $this->faturaNo = $data;
        return $this;
    }

    public function setbelgeFormati($data = "")
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

    private function setEkData()
    {
        /*e arşivde profil id "EARSIVFATURA" olmalı */
        $this->data["cbc:ProfileID"] = "EARSIVFATURA";

        return $this;
    }

    private function setDataXml()
    {
        $element = 'Invoice xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 ../xsdrt/maindoc/UBL-Invoice-2.1.xsd" xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:n4="http://www.altova.com/samplexml/other-namespace" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"';
        $this->xml = new \EFINANS\Component\xml($element);

        $this->setPrefix()->setEkData();

        $this->xmlData = $this->xml->setParams($this->data, $this->prefix)->getFaturaSablonXml();

        return $this;
    }

    public function getFaturaNo($prefix = "")
    {
        try {
            $this->input = array(
                "faturaSeri" => $prefix,
                "islemId" => $this->data["cbc:UUID"],
                "vkn" => $this->vergiTcKimlikNo,
            );

            $this->parametre = array(
                "input" => json_encode($this->input)
            );

            $r = $this->api->faturaNoUret($this->parametre);
            $this->return = $r->output;
        } catch (\Exception  $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function setEArsiv()
    {
        try {
            $this->input = array(
                "donenBelgeFormati" => 9,
                "islemId" => $this->data["cbc:UUID"],
                "vkn" => $this->vergiTcKimlikNo,
                "sube" => $this->sube,
                "kasa" => $this->kasa,
            );

            if (!$this->data["cbc:ID"]) { /* eğer fatura no gönderilmemişse otomatik üretilmesi için kullanıyoruz */
                $this->input["numaraVerilsinMi"] = 1;
                $this->input["faturaSeri"] = ($this->seriNo ? $this->seriNo : 'EA');
            }

            $this->parametre = array(
                "input" => json_encode($this->input),
                "fatura" => array(
                    "belgeFormati" => 'UBL',
                    "belgeIcerigi" => $this->xmlData)
            );

            $r = $this->api->faturaOlustur($this->parametre);
            $this->return = $r->return;
        } catch (\Exception  $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function getFaturaSorgula()
    {
        try {
            $this->input = array(
                "vkn" => $this->vergiTcKimlikNo,
                "faturaUuid" => $this->faturaUuid,
                "donenBelgeFormati" => $this->belgeFormati,
            );

            $this->parametre = array(
                "input" => json_encode($this->input),
            );

            $r = $this->api->faturaSorgula($this->parametre);
            $this->return=$r;
        } catch (\Exception  $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function gidenBelgeleriIndir($uuid="")
    {
        try {
            $this->parametre = array(
                "uuidList" => [$uuid],
                "belgeFormati" => $this->belgeFormati,
                "tasinanFaturalar" => 0

            );
            $r = $this->api->faturaZipiAl($this->parametre);
            $this->return = $r;
        } catch (\Exception $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
    }

    public function callFaturaIptalEt()
    {
        try {
            $this->input = array(
                "vkn" => $this->vergiTcKimlikNo,
                "faturaUuid" => $this->faturaUuid,
                "faturaNo" => $this->faturaNo,
            );

            $this->parametre = array(
                "input" => json_encode($this->input),
            );

            $r = $this->api->faturaIptalEt($this->parametre);
            $this->return=$r->return;
        } catch (\Exception  $e) {
            $this->errors[__FUNCTION__][0] = $e;
        }
        return $this->return;
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

    public function getErrors($function)
    {
        return $this->errors[$function];
    }
}
