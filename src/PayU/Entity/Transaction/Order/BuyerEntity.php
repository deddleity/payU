<?php
/**
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */

namespace PayU\Entity\Transaction\Order;

use \PayU\Entity\EntityAbstract;
use \PayU\Entity\EntityException;
use \PayU\Entity\Transaction\ShippingAddressEntity;

/**
 * Order buyer entity class.
 *
 * @package PayU\Entity
 * @author Leonardo Thibes <leonardothibes@gmail.com>
 * @copyright Copyright (c) The Authors
 */
class BuyerEntity extends EntityAbstract
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->shippingAddress = new ShippingAddressEntity();
    }

    /**
     * Payer full name.
     * @var string
     */
    protected $fullName = null;

    /**
     * Set payer full name.
     *
     * @param  string $fullName
     * @return BuyerEntity
     */
    public function setFullName($fullName)
    {
        $this->fullName = (string)$fullName;
        return $this;
    }

    /**
     * Get payer full name.
     * @return string
     */
    public function getFullName()
    {
        return (string)$this->fullName;
    }

    /**
     * Payer e-mail address.
     * @var string
     */
    protected $emailAddress = null;

    /**
     * Set payer e-mail address.
     *
     * @param  string $emailAddress
     * @return BuyerEntity
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = (string)$emailAddress;
        return $this;
    }

    /**
     * Get payer e-mail address.
     * @return string
     */
    public function getEmailAddress()
    {
        return (string)$this->emailAddress;
    }

    /**
     * DNI number
     * @var string
     */
    protected $dniNumber = null;

    /**
     * Set DNI number
     *
     * @param  string $dniNumber
     * @return BuyerEntity
     */
    public function setDniNumber($dniNumber)
    {
        $this->dniNumber = (string)$dniNumber;
        return $this;
    }

    /**
     * Get DNI number
     * @return string
     */
    public function getDniNumber()
    {
        return (string)$this->dniNumber;
    }

    /**
     * Shipping address.
     * @var ShippingAddressEntity
     */
    protected $shippingAddress = null;

    /**
     * Set shipping address.
     *
     * @param  ShippingAddressEntity $shippingAddress
     * @return BuyerEntity
     */
    public function setShippingAddress(ShippingAddressEntity $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    /**
     * Get shipping address.
     * @return ShippingAddressEntity
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * CNPJ (National Registry of Legal Entities in Brazil)
     * @var string $cnpj
     */
    protected $cnpj;

    /**
     * @return string
     */
    public function getCnpj()
    {
        return $this->cnpj;
    }

    /**
     * @param string $cnpj
     * @return $this
     */
    public function setCnpj($cnpj)
    {
        $this->cnpj = $cnpj;
        return $this;
    }

    /**
     * Returns if object is empty
     * @return bool
     */
    public function isEmpty()
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (
                $value    !== null  and
                $value    !== false and
                $property !== 'shippingAddress'
            ) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Generate arry order.
     * @return array
     */
    public function toArray()
    {
        return array(
            'fullName'        => $this->fullName,
            'emailAddress'    => $this->emailAddress,
            'dniNumber'       => $this->dniNumber,
            'shippingAddress' => $this->shippingAddress->toArray(),
        );
    }
}
