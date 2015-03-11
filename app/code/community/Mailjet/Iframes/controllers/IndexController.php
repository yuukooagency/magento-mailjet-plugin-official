<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Mailjet_Iframes_IndexController extends Mage_Adminhtml_Controller_Action
{
    
    /**
     *
     * @var string
     */
    protected $_apikey;
    
    /**
     *
     * @var string
     */
    protected $_secretKey;
    
    public function preDispatch() 
    {
        /*
         * Turns off security key check to make it able to open this action from outside magento
         */
        if ($this->getRequest()->getActionName() == 'events') {
            Mage::getSingleton('adminhtml/url')->turnOffSecretKey();
        } else  {
        parent::preDispatch();
        
        $this->_apikey = Mage::getStoreConfig(Mailjet_Iframes_Helper_Config::XML_PATH_SMTP_LOGIN);
        $this->_secretKey = Mage::getStoreConfig(Mailjet_Iframes_Helper_Config::XML_PATH_SMTP_PASSWORD);
        }
    }

    public function indexAction()
    {
        if (!$this->_apikey || !$this->_secretKey) {
            $this->_redirect('adminhtml/system_config/edit/section/mailjetiframes_options');
        } else {
            $this->_forward('iframe');
        }
    }
    
    /**
     * Includes mailjet iFrame
     */
    public function iframeAction()
    {
        $this->checkValidApiCredentials();
        $this->loadLayout();
        
        $iframesHelper = $this->_getIframesWrapperHelper();

        $block = $this->getLayout()
            ->createBlock('core/text', 'example-block')
            ->setText($iframesHelper->getHtml());

        $this->_addContent($block);
        
        $this->_setActiveMenu('mailjet/settings');
        $this->renderLayout();
    }
    /**
     * 
     */
    public function eventsAction()
    {
        try {
            
        $postinput = trim(file_get_contents('php://input'));
            $params = json_decode($postInput, 1);

            //Mage::getModel('core/log_adapter', 'iframes_setup.log')->log('$params'."\r\n".print_r($params, 1));

        switch ($params['event']) {
            case 'open':
                /* => do action */
                /* If an error occurs, tell Mailjet to retry later: header('HTTP/1.1 400 Error'); */
                /* If it works, tell Mailjet it's OK */
                header('HTTP/1.1 200 Ok');
                break;
            case 'click':
                /* => do action */
                break;
            case 'bounce':
                /* => do action */
                break;
            case 'spam':
                /* => do action */
                break;
            case 'blocked':
                /* => do action */
                break;
            case 'unsub':
                /* => do action */
                    if(isset($params['email']) && !empty($params['email'])) {
                        $syncManager = new Mailjet_Iframes_Helper_SyncManager();
                        $syncManager->usubscribeByEmail($params['email']);
                    }
                break;
            case 'typofix':
                /* => do action */
                break;
            /* # No handler */
            default:
                header('HTTP/1.1 423 No handler');
                /* => do action */
                break;
        }
        } catch (Exception $e) {
			//throw new Exception(Mage::helper('adminhtml')->__('Wrong event type'));
		}
    }
    
    /**
     * 
     * @return Mailjet_Iframes_Helper_IframesWrapper
     */
    protected function _getIframesWrapperHelper()
    {
        return new Mailjet_Iframes_Helper_IframesWrapper(
            $this->_apikey, $this->_secretKey
        );
    }
    
    
    protected function checkValidApiCredentials()
    {
        $mailjetApi = new Mailjet_Iframes_Helper_ApiWrapper(
            $this->_apikey, 
            $this->_secretKey
        );
        $response = $mailjetApi->sender(array('limit' => 1))->getResponse();
        if(!isset($response->Data)) {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('adminhtml')->__("Please verify that you have entered your API and secret key correctly. If this is the case and you have still this error message, please go to Account API keys (<a href='https://www.mailjet.com/account/api_keys'>https://www.mailjet.com/account/api_keys</a>) to regenerate a new Secret Key for the plug-in."));
            Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/mailjetiframes_options'));
            return false;
        }
        return true;
    }
}