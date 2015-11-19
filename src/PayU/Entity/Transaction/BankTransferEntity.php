<?php
/**
 * Created by PhpStorm.
 * User: diana
 * Date: 19/11/15
 * Time: 04:21 PM
 */

namespace PayU\Entity\Transaction;


use PayU\Entity\EntityAbstract;

class BankTransferEntity extends EntityAbstract
{
    /**
     * FINANCIAL_INSTITUTION_CODE
     * @var string
     */
    protected $bank;

    /**
     * USER_TYPE
     * @var string
     */
    protected $personType;

    /**
     * PSE_REFERENCE2
     * @var string
     */
    protected $documentType;

    /**
     * PSE_REFERENCE3
     * @var string
     */
    protected $document;
    /**
     * RESPONSE_URL
     * @var string
     */
    protected $responseUrl;

    /**
     * PSE_REFERENCE1
     * @var string
     */
    protected $ip;

    /**
     * @return string
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param string $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getPersonType()
    {
        return $this->personType;
    }

    /**
     * @param string $personType
     */
    public function setPersonType($personType)
    {
        $this->personType = $personType;
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param string $bank
     */
    public function setBank($bank)
    {
        $this->bank = $bank;
    }

    /**
     * @return string
     */
    public function getResponseUrl()
    {
        return $this->responseUrl;
    }

    /**
     * @param string $responseUrl
     */
    public function setResponseUrl($responseUrl)
    {
        $this->responseUrl = $responseUrl;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }
    /**
     * Generate array order.
     * @return array
     */
    public function toArray()
    {
        return array(
            'RESPONSE_URL'                  => $this->responseUrl,
            'PSE_REFERENCE1'                => $this->ip,
            'FINANCIAL_INSTITUTION_CODE'    => $this->bank,
            'USER_TYPE'                     => $this->personType,
            'PSE_REFERENCE2'                => $this->documentType,
            'PSE_REFERENCE3'                => $this->document

        );
    }

}