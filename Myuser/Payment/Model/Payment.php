<?php
namespace Myuser\Payment\Model;
use \MyUser\MyUserPay;

include dirname(__FILE__).'/MyUserPay/init.php'; 
class Payment extends \Magento\Payment\Model\Method\Cc 
{
    const CODE = 'myuserpayment';
    protected $_code = self::CODE;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_isGateway = true;
    protected $_countryFactory;
    protected $cart = null;
	protected $scopeConfig;
	
    public function __construct( \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry, 
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData, 
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
    \Magento\Payment\Model\Method\Logger $logger, 
    \Magento\Framework\Module\ModuleListInterface $moduleList,
    \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Checkout\Model\Cart $cart,

    array $data = array() 
  	 ) {
     
		
		parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data );
        $this->cart = $cart; $this->_countryFactory = $countryFactory;
		$this->scopeConfig = $scopeConfig;
   }
   public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
	   $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	   $key = $this->scopeConfig->getValue('payment/myuser/api_key_secret', $storeScope);
	   MyUserPay::setPrivateKey($key);
		$process = MyUserPay::charge(
			array(
			'amount'=> $amount*100,
			//If request was post
			'token'=>$payment->getCCType(),
			)
			);	
			//var_dump($process);
			if($process['status']){
				//success
				$charge_id = $process['id'];
				$payment->setTransactionId($charge_id)->setIsTransactionClosed(0);
				
			}
			else
			{
				//error
				
				$this->_logger->error($process['status']);
      			throw new \Magento\Framework\Validator\Exception(__('Payment capturing error. '.$process['status']));
			}
			return $this;
		
	}
	/*This function is defined to set the Payment Action Type that is - - Authorize - Authorize and Capture Whatever has been set under Configuration of this Payment Method in Admin Panel, that will be fetched and set for this Payment Method by passing that into getConfigPaymentAction() function. */
	public function getConfigPaymentAction() {
	   return $this->getConfigData('payment_action');
	}

	public function authRequest($request) {
	   //Process Request and receive the response from Payment Gateway---
	  $response = ['tid' => rand(100000, 99999999)];
	   //Here, check response and process accordingly---
	   if(!$response)
	   {
	     throw new \Magento\Framework\Exception\LocalizedException(__('Failed authorize request.'));
	   }
	     return $response;
	  } 
	   /**
	    * Test method to handle an API call for capture request. 
	    *
	    * @param $request 
	    * @return array 
	    * @throws \Magento\Framework\Exception\LocalizedException
	    */
	    public function captureRequest($request) {
	       //Process Request and receive the response from Payment Gateway---                    $response = ['tid' => rand(100000, 99999999)];
	        //Here, check response and process accordingly---
	       if(!$response)
	       {
	         throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
	       }
	         return $response;
	    }

		public function validate()
		{
			/*
			* calling parent validate function
			*/
		//  parent::validate();  // command this parent validation
			return $this;
		}
	  }