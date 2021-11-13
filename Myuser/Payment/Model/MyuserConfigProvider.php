<?php
    namespace Myuser\Payment\Model;
    use Magento\Checkout\Model\ConfigProviderInterface;
    use Magento\Payment\Helper\Data as PaymentHelper;
    use Magento\Framework\UrlInterface as UrlInterface;
    use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

    class MyuserConfigProvider implements ConfigProviderInterface {
        protected $methodCode = "myuserpayment";
        protected $method;
        protected $urlBuilder;

        public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder, ScopeConfig $scopeConfig ) {
            $this->method = $paymentHelper->getMethodInstance($this->methodCode);
            $this->urlBuilder = $urlBuilder;
            $this->scopeConfig = $scopeConfig;
        }

        public function getConfig() {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            return $this->method->isAvailable() ? [
                'payment' => [
                    'myuser' => [
                        'apikeypublic' => $this->scopeConfig->getValue('payment/myuser/api_key_public', $storeScope),                    
                    ]
                ]
            ] : [];
        }
    }