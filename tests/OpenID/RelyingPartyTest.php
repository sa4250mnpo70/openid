<?php
/**
 * OpenID_RelyingPartyTest 
 * 
 * PHP Version 5.2.0+
 * 
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pearopenid.googlecode.com
 */

require_once 'PHPUnit/Framework.php';
require_once 'OpenID/RelyingParty.php';
require_once 'OpenID/RelyingParty/Mock.php';
require_once 'OpenID/Store/Mock.php';
require_once 'OpenID/Discover.php';
require_once 'OpenID/Association.php';
require_once 'OpenID/Association/Request.php';
require_once 'OpenID.php';
require_once 'OpenID/Nonce.php';

/**
 * OpenID_RelyingPartyTest 
 * 
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org> 
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://pearopenid.googlecode.com
 */
class OpenID_RelyingPartyTest extends PHPUnit_Framework_TestCase
{
    protected $id            = 'http://user.example.com';
    protected $returnTo      = 'http://openid.examplerp.com';
    protected $realm         = 'http://examplerp.com';
    protected $rp            = null;
    protected $opEndpointURL = 'http://exampleop.com';
    protected $discover      = null;
    protected $store         = null;
    protected $association   = null;

    /**
     * setUp 
     * 
     * @return void
     */
    public function setUp()
    {
        $this->rp = $this->getMock('OpenID_RelyingParty',
                                   array('getAssociationRequestObject',
                                         'getAssertionObject'),
                                   array($this->id, $this->returnTo, $this->realm));

        $this->store = $this->getMock('OpenID_Store_Mock',
                                      array('getDiscover',
                                            'getAssociation',
                                            'getNonce'));

        OpenID::setStore($this->store);

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->id));

        $opEndpoint = new OpenID_ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new OpenID_ServiceEndpoints($this->id, $opEndpoint);

        $this->discover->expects($this->any())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $params = array(
            'uri'          => 'http://example.com',
            'expiresIn'    => 600,
            'created'      => 1240980848,
            'assocType'    => 'HMAC-SHA256',
            'assocHandle'  => 'foobar{}',
            'sharedSecret' => '12345qwerty'
        );

        $this->association = $this->getMock('OpenID_Association',
                                            array('checkMessageSignature'), 
                                            array($params));
    }

    /**
     * tearDown 
     * 
     * @return void
     */
    public function tearDown()
    {
        $this->rp          = null;
        $this->store       = null;
        $this->association = null;
    }

    /**
     * testEnableDisableAssociations 
     * 
     * @return void
     */
    public function testEnableDisableAssociations()
    {
        $this->rp->enableAssociations();
        $this->rp->disableAssociations();
    }

    /**
     * testSetClockSkew 
     * 
     * @return void
     */
    public function testSetClockSkew()
    {
        $this->rp->setClockSkew(50);
    }

    /**
     * testSetClockSkewFail 
     * 
     * @expectedException OpenID_Exception
     * @return void
     */
    public function testSetClockSkewFail()
    {
        $this->rp->setClockSkew('foo');
    }

    /**
     * testPrepare 
     * 
     * @return void
     */
    public function testPrepare()
    {
        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getAssociation')
                    ->will($this->returnValue($this->association));

        $auth = $this->rp->prepare();
        $this->assertType('OpenID_Auth_Request', $auth);
    }

    /**
     * testGetDiscoverFail 
     * 
     * @expectedException OpenID_Exception
     * @return void
     */
    public function testGetDiscoverFail()
    {
        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue(false));

        $auth = $this->rp->prepare();
    }

    /**
     * testGetAssociationFail 
     * 
     * @return void
     */
    public function testGetAssociationFail()
    {
        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getAssociation')
                    ->will($this->returnValue(false));

        $assocRequest = $this->getMock('OpenID_Association_Request',
                                       array('associate'),
                                       array($this->opEndpointURL,
                                             OpenID::SERVICE_2_0_SERVER));

        $assocRequest->expects($this->once())
                     ->method('associate')
                     ->will($this->returnValue($this->association));

        $this->rp->expects($this->once())
                 ->method('getAssociationRequestObject')
                 ->will($this->returnValue($assocRequest));


        $auth = $this->rp->prepare();
        $this->assertType('OpenID_Auth_Request', $auth);
    }

    /**
     * testGetAssociation 
     * 
     * @return void
     */
    public function testGetAssociation()
    {
        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getAssociation')
                    ->will($this->returnValue(false));

        $assocRequest = $this->getMock('OpenID_Association_Request',
                                       array('associate'),
                                       array($this->opEndpointURL,
                                             OpenID::SERVICE_2_0_SERVER));

        $assocRequest->expects($this->once())
                     ->method('associate')
                     ->will($this->returnValue(false));

        $this->rp->expects($this->once())
                 ->method('getAssociationRequestObject')
                 ->will($this->returnValue($assocRequest));

        $auth = $this->rp->prepare();
        $this->assertType('OpenID_Auth_Request', $auth);
    }

    /**
     * testGetAssociationRequestObject 
     * 
     * @return void
     */
    public function testGetAssociationRequestObject()
    {
        $rp = new OpenID_RelyingParty_Mock($this->id,
                                           $this->returnTo,
                                           $this->realm);

        $a = $rp->returnGetAssociationRequestObject($this->opEndpointURL,
                                                    OpenID::SERVICE_2_0_SERVER);
        $this->assertType('OpenID_Association_Request', $a);
    }

    /**
     * testVerifyCancel 
     * 
     * @return void
     */
    public function testVerifyCancel()
    {
        $message = new OpenID_Message();
        $message->set('openid.mode', OpenID::MODE_CANCEL);

        $result = $this->rp->verify($message);
        $this->assertType('OpenID_Assertion_Result', $result);
        $this->assertFalse($result->success());
        $this->assertSame(OpenID::MODE_CANCEL, $result->getAssertionMethod());
    }

    /**
     * testVerifyError 
     * 
     * @expectedException OpenID_Exception
     * @return void
     */
    public function testVerifyError()
    {
        $message = new OpenID_Message();
        $message->set('openid.mode', OpenID::MODE_ERROR);

        $result = $this->rp->verify($message);
    }

    /**
     * testVerifyInvalidMode 
     * 
     * @expectedException OpenID_Exception
     * @return void
     */
    public function testVerifyInvalidMode()
    {
        $message = new OpenID_Message();
        $message->set('openid.mode', 'foo');

        $result = $this->rp->verify($message);
    }

    /**
     * testVerifyAssociation 
     * 
     * @return void
     */
    public function testVerifyAssociation()
    {
        $this->store->expects($this->any())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getAssociation')
                    ->will($this->returnValue($this->association));

        $this->association->expects($this->once())
                          ->method('checkMessageSignature')
                          ->will($this->returnValue(true));

        $nonceObj = new OpenID_Nonce($this->opEndpointURL);
        $nonce    = $nonceObj->createNonce();

        $message = new OpenID_Message();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.assoc_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);


        $this->assertType('OpenID_Assertion_Result', $this->rp->verify($message));
    }

    /**
     * testVerifyCheckAuthentication 
     * 
     * @return void
     */
    public function testVerifyCheckAuthentication()
    {
        $this->store->expects($this->any())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(false));

        $nonceObj = new OpenID_Nonce($this->opEndpointURL);
        $nonce    = $nonceObj->createNonce();

        $message = new OpenID_Message();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.invalidate_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $assertion = $this->getMock('OpenID_Assertion',
                                    array('checkAuthentication'),
                                    array($message, $this->returnTo));

        $authMessage = new OpenID_Message;
        $authMessage->set('is_valid', 'true');

        $assertion->expects($this->once())
                  ->method('checkAuthentication')
                  ->will($this->returnValue($authMessage));

        $this->rp->expects($this->once())
                 ->method('getAssertionObject')
                 ->will($this->returnValue($assertion));

        $this->assertType('OpenID_Assertion_Result', $this->rp->verify($message));
    }

    /**
     * testGetAssertionObject 
     * 
     * @return void
     */
    public function testGetAssertionObject()
    {
        $this->store->expects($this->any())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(false));

        $nonceObj = new OpenID_Nonce($this->opEndpointURL);
        $nonce    = $nonceObj->createNonce();

        $message = new OpenID_Message();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.invalidate_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $rp = new OpenID_RelyingParty_Mock($this->id, $this->returnTo, $this->realm);
        $this->assertType('OpenID_Assertion',
                          $rp->returnGetAssertionObject($message));
    }
}
?>