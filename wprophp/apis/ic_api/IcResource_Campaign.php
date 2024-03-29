<?php
/**
 * Copyright (C) 2006 by Broadwick.
 * 
 * This would be some really fancy legalese if we were concerned about that sort of stuff right now.  Mainly,
 * we guarantee nothing.  You should use this at your own risk.  
 * 
 * @version $Id: IcResource_Campaign.php 4926 2006-08-17 22:37:59Z acox $
 * @package IcApi
 */

/**
 * This class represents the campaign resource in the IntelliContact API.
 *
 * @author jtravis@broadwick.com
 */
class IcResource_Campaign extends IcResource {
    
    private $campaign_id;
    
    public function getCampaignId() {
        return $this->campaign_id;
    }
    
    public function getLocation() {
        $campaign_id = $this->getCampaignId();
        if($campaign_id > 0) {
            return "{$this->getName()}/$campaign_id";
        }
    }
    
    public function getName() {
        return "campaign";
    }
    
    public function setCampaignId($campaign_id) {
        $this->campaign_id = $campaign_id;
    }
    
}

?>
