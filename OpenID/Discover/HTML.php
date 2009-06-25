<?php

require_once 'OpenID/Discover.php';
require_once 'OpenID/Discover/Interface.php';
require_once 'OpenID/ServiceEndpoint.php';
require_once 'OpenID/ServiceEndpoints.php';

class OpenID_Discover_HTML extends OpenID_Discover implements OpenID_Discover_Interface
{
    protected $identifier = null;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function discover()
    {
        $response = $this->sendRequest();

        $dom = new DOMDocument();
        $dom->loadHTML($response);

        $xPath = new DOMXPath($dom);
        $query = "/html/head/link[contains(@rel,'openid')]";
        $links = $xPath->query($query);

        $results = array(
            'openid2.provider' => array(),
            'openid2.local_id' => array(),
            'openid.server'    => array(),
            'openid.delegate'  => array()
        );

        foreach ($links as $link) {
            $rels  = explode(' ', $link->getAttribute('rel'));
            foreach ($rels as $rel) {
                if (array_key_exists($rel, $results)) {
                    $results[$rel][] = $link->getAttribute('href');
                }
            }
        }

        return $this->buildServiceEndpoint($results);
    }

    private function buildServiceEndpoint(array $results)
    {
        if (count($results['openid2.provider'])) {
            if (count($results['openid2.local_id'])) {
                $version = OpenID::SERVICE_2_0_SIGNON;
                $localID = $results['openid2.local_id'][0];
            } else {
                $version = OpenID::SERVICE_2_0_SERVER;
            }
            $endpointURIs = $results['openid2.provider'];
        } elseif (count($results['openid.server'])) {
            $version      = OpenID::SERVICE_1_1_SIGNON;
            $endpointURIs = $results['openid.server'];
            if (count($results['openid.delegate'])) {
                $localID = $results['openid.delegate'][0];
            }
        } else {
            throw new OpenID_Discover_Exception(
                'Discovered information does not conform to spec'
            );
        }

        $opEndpoint = new OpenID_ServiceEndpoint();
        $opEndpoint->setVersion($version);
        $opEndpoint->setTypes(array($version));
        $opEndpoint->setURIs($endpointURIs);
        $opEndpoint->setSource(OpenID_Discover::TYPE_HTML);

        if (isset($localID)) {
            $opEndpoint->setLocalID($localID);
        }

        return new OpenID_ServiceEndpoints($this->identifier, $opEndpoint);
    }

    private function sendRequest()
    {
        $request  = new HTTP_Request($this->identifier, $this->requestOptions);
        $request->sendRequest();
        $response = $request->getResponseBody();

        if ($request->getResponseCode() !== 200) {
            throw new OpenID_Discover_Exception(
                'Unable to connect to OpenID Provider.'
            );
        }

        return $response;
    }
}

?>