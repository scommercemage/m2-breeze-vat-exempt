<?php

namespace Scommerce\BreezeVatExempt\Controller\Action;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Scommerce\VatExempt\Helper\Data as Helper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\TaxRateManagement;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Data\Form\FormKey\Validator;

class Breeze extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Validator
     */
    protected $_validator;

    /**
     * Constructor function for class Apply
     *
     * @param Context                    $context
     * @param Helper                     $helper
     * @param ProductFactory             $productFactory
     * @param JsonFactory                $resultJsonFactory
     * @param CheckoutSession            $checkoutSession
     * @param Validator                  $formKeyValidator
     */
    public function __construct(
        Context $context,
        Helper $helper,
        ProductFactory $productFactory,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        Validator $formKeyValidator
    ) {
        $this->_helper = $helper;
        $this->_productFactory = $productFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_validator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Execute Function for class Apply
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        $response = ['success' => 'true', 'amount' => 0];
        if ($this->_validator->validate($this->getRequest())) {
            $params = $this->getRequest()->getParams();
            $currentQuote = $this->_checkoutSession->getQuote();
            $name = isset($params['name']) ? $params['name']: "";
            $reason = isset($params['reason']) ? $params['reason']: "";
            $address = isset($params['address']) ? $params['address']: "";
            if ($params['action'] == 'apply') {
                $totalVatToBeExempted = $this->_helper->getTotalVatExemption($currentQuote);
                $currentQuote->setIsVatExempt(1)
                    ->setVatExempted($totalVatToBeExempted)
                    ->setName($name)
                    ->setAddress($address)
                    ->setReason($reason)
                    ->save();
                $response['amount'] = $totalVatToBeExempted;
                $resultJson->setData($response);
                return $resultJson;
            } elseif ($params['action'] == 'remove') {
                $currentQuote->setIsVatExempt(0)
                    ->setVatExempted(0)
                    ->setname('')
                    ->setReason('')
                    ->save();
            } else {
                $response = ['success' => 'false'];
                $resultJson->setData($response);
                return $resultJson;
            }
            $resultJson->setData($response);
            return $resultJson;

        } else {
            $response = ['success' => 'false', 'message'=>__("Invalid Request")];
            $resultJson->setData($response);
            return $resultJson;
        }
    }
}
