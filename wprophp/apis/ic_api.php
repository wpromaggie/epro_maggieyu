<?php
require('ic_api/IcApi.php');
require('ic_api/IcResource.php');

define('IC_KEY', 'zlA3VnhFLFEp7SHKLdYpeyfAjeCk5lou');
define('IC_SECRET', 'xPni2vOE1iUY1ryDvnSzMY0V3t5YEkj2');
define('IC_LOGIN', 'wpromote');
define('IC_PASS', 'moth1201');

function ic_init_api(&$api)
{
	$api = new IcApi("http://api.intellicontact.com/icp");
	$api->setVersion("1.0");
	$api->setKey(IC_KEY);
	$api->setSecret(IC_SECRET);
	$api->setLogin(IC_LOGIN);
	$api->setPassword(IC_PASS);
	#$api->setDebug(true);
}

function ic_set_node_info(&$elem, $nodes)
{
	$node = $nodes->item(0);
	$elem = array();
	$elem['id'] = $node->getAttribute('id');
	foreach ($node->childNodes as $child_node)
		$elem[$child_node->nodeName] = trim($child_node->nodeValue);
}

function ic_get_lists(&$list_ids)
{
	require_once('ic_api/IcResource_Lists.php');
	ic_init_api($api);

	$ic_lists = new IcResource_Lists();
	$api->get($ic_lists);
	$xml = $ic_lists->getXML();
	
	$list_nodes = $xml->getElementsByTagName('list');
	$list_ids = array();
	//for ($i = 0; $i < $list_nodes->length; $i++)
	foreach ($list_nodes as $list_node)
	{
		//$list_node = $list_nodes->item($i);
		$list_ids[] = $list_node->getAttribute('id');
	}
}

function ic_get_list(&$list, $list_id)
{
	require_once('ic_api/IcResource_List.php');
	ic_init_api($api);

	$ic_list = new IcResource_List();
	$ic_list->setListId($list_id);
	$api->get($ic_list);
	$xml = $ic_list->getXML();
	
	ic_set_node_info($list, $xml->getElementsByTagName('list'));
}

function ic_new_contact(&$contact, $email, $fname)
{
	require_once('ic_api/IcResponse.php');
	require_once('ic_api/IcResource_Contact.php');
	ic_init_api($api);

	$ic_contact = new IcResource_Contact();
	$ic_contact->newContact($email, $fname);
	$response = $api->put($ic_contact);
	
	$xml = $response->getXML();
	ic_set_node_info($contact, $xml->getElementsByTagName('contact'));
}

function ic_get_contact(&$contact, $contact_id)
{
	require_once('ic_api/IcResource_Contact.php');
	ic_init_api($api);

	$ic_contact = new IcResource_Contact();
	$ic_contact->setContactId($contact_id);
	$api->get($ic_contact);
	$xml = $ic_contact->getXML();
	ic_set_node_info($contact, $xml->getElementsByTagName('contact'));
}

function ic_get_subscriptions(&$subscriptions, $contact_id)
{
	require_once('ic_api/IcResource_Contact.php');
	ic_init_api($api);

	$ic_contact = new IcResource_Contact();
	$ic_contact->setContactId($contact_id);
	$ic_contact->getSubscriptions();
	$api->get($ic_contact);
}

function ic_set_subscription($list_id, $contact_id, $subscription)
{
	require_once('ic_api/IcResponse.php');
	require_once('ic_api/IcResource_Contact.php');
	ic_init_api($api);

	$ic_contact = new IcResource_Contact();
	$ic_contact->setContactId($contact_id);
	$ic_contact->newSubscription($list_id, $subscription);
	$ic_contact->putSubscription();
	$response = $api->put($ic_contact);
}

?>