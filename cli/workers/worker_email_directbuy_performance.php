<?php
/**
 */

class worker_email_directbuy_performance extends worker{
	private $filename;
	private $dur_offset,$start_date,$end_date;

	public function run(){
		$this->dur_offset = 60;
		$this->start_date = date('Y-m-d',strtotime("- {$this->dur_offset} days"));
		$this->end_date = date('Y-m-d');

		$this->filename = sprintf("/tmp/directbuy_performance_%s.csv",date('Ymd'));
		util::create_file_from_db($this->filename, $this->get_data(),',');
		$this->send_email();
	}

	private function send_email(){
		$email = util::get_phpmailer();
		$email->addAddress('chimdi@wpromote.com','Chimdi Azubuike');
		$email->Subject = "Daily DirectBuy Keyword Performance Report";
		$email->Body = sprintf("Attached is the weekly Directbuy Keywords Perforamance Report<br>
								Start Date: %s<br>
								End Date: %s<br><br>",
								$this->start_date,
								$this->end_date);
		$email->isHTML(true);
		$email->addAttachment($this->filename);

		if(!$email->send()){
			e($email->ErrorInfo);
		}else{
			e('Message Sent!');
		}
	}

    public function get_data(){
            $kws = $this->get_all_keyword_performance_by_date($this->start_date,$this->end_date);
            $kw_stats = array();
            $progress = 0;
            foreach($kws as $kw){
                    $progress++;
                    $keyword_word = (preg_match("/\+\W/",$kw['keyword']))? "`{$kw['keyword']}" : $kw['keyword'];
                    if(!isset($kw_stats[$keyword_word]))
                            $kw_stats[$keyword_word] = new stats();

                    $kwstat =& $kw_stats[$keyword_word];
                    $kwstat->keyword = $keyword_word;
                    $kwstat->total_spend += (is_numeric($kw['total_spend']))? $kw['total_spend'] : 0;
                    $kwstat->total_clicks += (is_numeric($kw['total_clicks']))? $kw['total_clicks'] : 0;
                    $kwstat->total_imps += (is_numeric($kw['total_clicks']))? $kw['total_clicks'] : 0;

                    $convs = $this->get_conversion_data($keyword_word,$this->start_date,$this->end_date);
                    echo ".";
                    if(!$convs)
                    	continue;

                    foreach($convs as $conv){
                            if(preg_match("/Member/i",$conv['_leads_status']) || $conv['_leads_status'] === 'NI _ Not Interested'){
                                    $kwstat->pres_count += $conv['_count'];
                                    if($conv['_leads_status'] !== 'NI _ Not Interested')
                                            $kwstat->total_mmbr += $conv['_count'];
                            }
                            $kwstat->total_rev += $conv['revenue'];
                    }
                    if($progress % 100 == 1){
                    }
            }
            
            //post process convert to array
            $export = array();
            foreach($kw_stats as $obj){
                    $export[] = (array)$obj;
            }
            e($export);
            util::create_file_from_db($this->filename,$export);
    }

    public function get_keywords($keyword_prase,$from,$to){
            $q = "select text AS keyword 
                    from `g_objects`.`keyword_A430104416`
                    where text LIKE '%{$keyword_prase}%'
                    group by text";
            return db::select($q,'ASSOC');
    }

    private function get_all_keyword_performance_by_date($from,$to){
            $q = "SELECT 
                            keyword_id,
                            total_clicks,
                            total_imps,
                            total_spend,
                            text AS `keyword`
                    FROM (
                    select 
                            keyword_id,
                            SUM(clicks) AS total_clicks,
                            SUM(imps) AS total_imps,
                            ROUND(SUM(`cost`),2) AS total_spend
                    from `g_objects`.`all_data_A430104416` a
                    where data_date between '{$from}' and '{$to}'
                    group by keyword_id
                    ) AS data
                    LEFT JOIN (select DISTINCT id, text from `g_objects`.`keyword_A430104416` order by id) kw ON kw.id = data.keyword_id";

            return db::select($q,'ASSOC');
    }

    private function get_keyword_performance($keyword){
            $q = "SELECT 
                            c.text as campaign_name,
                            data.keyword,
                            data.cid,
                            SUM(data.total_spend) AS total_spend,
                            SUM(data.clicks) AS total_clicks
                    FROM (
                    select 
                            k.text AS keyword,
                            k.campaign_id AS cid,
                            SUM(cd.clicks) AS clicks,
                            ROUND(SUM(cost),2) AS total_spend
                    from `g_objects`.`keyword_A430104416` k
                    left join `g_objects`.`campaign_data_A430104416` cd on cd.campaign_id = k.campaign_id
                    where text = '{$keyword}'
                    and data_date between '{$this->start_date}' and '{$this->end_date}'
                    group by k.campaign_id
                    ) AS data
                    LEFT JOIN `g_objects`.`campaign_A430104416` c ON c.id = data.cid
                    group by keyword";
            $r = db::select($q,'ASSOC');
            return $r[0];
    }

    private function get_conversion_data($keyword,$from,$to){
            $q = "SELECT 
                            utm_term, 
                            COUNT(directbuy_id) AS _count, 
                            _leads_status,
                            SUM(mv_negotiated_price) AS revenue
                    FROM (
                    select 
                            directbuy_id,
                            utm_term,
                            db.lead_source,
                            dls.name as _leads_status,
                            campaign_code,
                            mv_negotiated_price
                    from `client_db_directbuy`.`directbuy` db
                    left join `client_db_directbuy`.`directbuy_lead_status` dls on dls.id = db.lead_status
                    where date(creation_date) BETWEEN '{$from}' and '{$to}'
                    AND length(utm_term) > 1
                    ) AS utm
                    where utm_term = '{$keyword}'
                    GROUP BY utm_term, _leads_status";

            return db::select($q,'ASSOC');
    }

}

class stats{
        public $pres_count,$total_rev,$total_spend,$total_clicks,$total_mmbr,$total_imps,$keyword;
        public function __construct(){
                $this->pres_count = $this->total_rev = $this->total_spend = $this->total_clicks = $this->total_mmbr = $this->total_imps = 0;
        }
}
?>