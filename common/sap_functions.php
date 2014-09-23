<?php
        $GLOBALS['webdev_options'] = array(
            "Flash Animation",
            "Javascript",
            "Copywriting",
            "Logo Creation",
            "Content Management System (CMS)",
            "Blog Set-up or Integration",
            "Social Media Integration",
            "Contact Form",
            "Mailing List Signup",
            "Additional Forms",
            "Widget",
            "Shopping Cart",
            "Site Search Features",
            "Message Forum",
            "Click to Chat Integration",
            "Usability Testing",
            "Brand Consulting",
            "Additional Templates",
            "Ongoing Hosting/Maintenance"
        );
        

        function get_webdev_options($format="array"){
            
            return ($format=="array") ? $GLOBALS['webdev_options'] : implode(":", $GLOBALS['webdev_options']);

        }

        function excluded_webdev_options($selected){

            return array_diff($GLOBALS['webdev_options'], $selected);
        }

	function replace_vars($str, $order_details){
	
		preg_match_all("/\[(.*?)\]/", $str, $matches);
		$full_matches = $matches[0];
		$keys = $matches[1];
		for ($i = 0, $count = count($full_matches); $i < $count; ++$i)
		{
			$str = str_replace($full_matches[$i], $order_details[$keys[$i]], $str);
		}
		return $str;
	}

	function add_derived_vars(&$order_details) {
		
		//Make a list of services selected in an SAP
		$services = array();
		if($order_details['ppc_package']){
			$services[] = "Integrated Search Marketing";
		}
		if($order_details['seo_package']){
			$services[] = "Search Engine Optimization";
		}
		if($order_details['wd_package']){
			$services[] = "Web Development";
		}
		if($order_details['smo_package']){
			$services[] = "Social Media";
		}
		if($order_details['infographic']){
			$services[] = "Infographic Creation & Distribution";
		}
		if($order_details['fba_package']){
			$services[] = "Facebook Advertising";
		}
		if($order_details['slb_package']){
			$services[] = "Social Link Building";
		}

		//Build a string with commas and "and "
		$last_index = count($services) - 1;
		$services_stmt = $services[0];
		for ($i=1;$i<$last_index;$i++) {
			$services_stmt .= ', ' . $services[$i];
		}
		if ($last_index > 0) {
			$services_stmt .= ' and ' . $services[$last_index];
		}
		$order_details['services_stmt'] = $services_stmt;
	}
	
	function save_double_quotes($str){
	
		return str_replace('\"', '&#34;',$str);
		
	}
	
	function create_sap_field($content,$s1='',$s2='',$s3='',$list_order=0,$type='par',$default_checkbox='true'){
		echo "<textarea class=\"{$type}\" name=\"sap_var-{$s1}-{$s2}-{$s3}-{$list_order}\" id=\"{$s1}-{$s2}-{$s3}-{$list_order}\">";
		echo $content[$s1][$s2][$s3][$list_order]['text'];
		echo "</textarea>";
		
        echo "<br /><br />";
        echo "Back to default: <input type=\"checkbox\" name=\"default[]\" value=\"{$s1}-{$s2}-{$s3}-{$list_order}\" />";
		echo "<br /><br />";
	}
	
	function create_title($updated_text, $order_details, $s1='', $s2='', $s3='', $ta_class='title'){
		$text = $updated_text[$s1][$s2][$s3][0]['text'];
		$output = replace_vars($updated_text[$s1][$s2][$s3][0]['text'], $order_details);
		echo "<div id=\"edit_{$s1}-{$s2}-{$s3}\" class='edit_title' >";
        	echo "<h3 id=\"edit_{$s1}-{$s2}-{$s3}_text\">";  
				echo $output;
			echo "</h3>";
			echo  "<input type=\"button\" id=\"edit_{$s1}-{$s2}-{$s3}_btn\" onclick=\"editField('edit_{$s1}-{$s2}-{$s3}','{$s1}-{$s2}-{$s3}','".save_double_quotes(addslashes($text))."','title','{$ta_class}')\" value=\"Edit Title\" />";
		echo "</div>";
	}
	
	function output_title($updated_text, $order_details, $s1='', $s2='', $s3='', $ta_class='title'){
		$output = replace_vars($updated_text[$s1][$s2][$s3][0]['text'], $order_details);
		echo "<h3>".$output."</h3>";
	}
	
	function create_par($updated_text, $order_details, $s1='', $s2='', $s3='', $ta_class='par'){
	
		$text = $updated_text[$s1][$s2][$s3][0]['text'];
		$output = replace_vars($updated_text[$s1][$s2][$s3][0]['text'], $order_details);
		echo "<div id=\"edit_{$s1}-{$s2}-{$s3}\">";
        	echo "<p id=\"edit_{$s1}-{$s2}-{$s3}_text\">";  
				echo $output;
			echo "</p>";
			echo  "<input type=\"button\" id=\"edit_{$s1}-{$s2}-{$s3}_btn\" value=\"Edit Field\" onclick=\"editField('edit_{$s1}-{$s2}-{$s3}','{$s1}-{$s2}-{$s3}','".save_double_quotes(addslashes($text))."','par','{$ta_class}')\"/>";
		echo "</div>";
	
	}
	
	function output_par($updated_text, $order_details, $s1='', $s2='', $s3='', $ta_class='par'){
		$output = replace_vars($updated_text[$s1][$s2][$s3][0]['text'], $order_details);
		echo "<p>".$output."</p>";
	}
	
	function create_list($updated_text, $order_details, $s1='', $s2='', $s3='', $list_style='', $ta_class='list'){
		
		echo "<div id=\"edit_{$s1}-{$s2}-{$s3}\">";
        echo "    <div id=\"edit_{$s1}-{$s2}-{$s3}_text\">";
        echo "        <ul {$list_style}>";

                        foreach($updated_text[$s1][$s2][$s3] as $list_item => $list_info){

                            $list_text .= "{".$list_item."}"; // set the list item value

                            $list_text .= $list_info['text']."|"; // split up the list items for js to take apart

                            $output = replace_vars($list_info['text'], $order_details);
                            if($output!=""){
                                    
                                    echo "<li>";
                                    echo $output;
                                    echo "</li>";
                            }
                        }

                   
         echo "       </ul>";
         echo "</div>";
         echo  "<input type=\"button\" id=\"edit_{$s1}-{$s2}-{$s3}_btn\" value=\"Edit Field\" onclick=\"editField('edit_{$s1}-{$s2}-{$s3}','{$s1}-{$s2}-{$s3}-','".save_double_quotes(addslashes($list_text))."','list','{$ta_class}')\"/>";
        echo "</div>";

	}
	
	function create_contract_terms_list($updated_text, $order_details, $type=""){
		
		$s1 = "terms";
		$s2 = "contract";
		$s3 = "";
		
		echo "<div id=\"edit_{$s1}-{$s2}-{$s3}\">";
		echo "    <div id=\"edit_{$s1}-{$s2}-{$s3}_text\">";
		echo "        <ul>";

					foreach($updated_text[$s1][$s2][$s3] as $list_item => $list_info){
						
						$key3 = $s3;
						
						if($list_item == 9){
							//cancelation clause
							$key3 = $type;
							$list_info = $updated_text[$s1][$s2][$type][$list_item];
						}

						$output = replace_vars($list_info['text'], $order_details);
						if($output!=""){
								echo "<li id='{$s1}-{$s2}-{$key3}-{$list_item}' text='".htmlentities($list_info['text'],ENT_QUOTES)."' class='editable'>";
								echo $output;
								echo "</li>";
						}
					}

			   
		echo "       </ul>";
		echo "</div>";
		echo  "<input type=\"button\" value=\"Edit Field\" onclick=\"editContractTerms('edit_{$s1}-{$s2}-{$s3}')\"/>";
		echo "</div>";
	}
	
	function output_list($updated_text, $order_details, $s1='', $s2='', $s3='', $list_style='', $ta_class='list'){
	
		echo "<ul {$list_style}>";

                foreach($updated_text[$s1][$s2][$s3] as $list_item => $list_info){
                    $output = replace_vars($list_info['text'], $order_details);
                    if($output!=""){

                            echo "<li>";
                            if(strstr($list_style, 'class="dashes"')){
                                    echo "- ";
                            }
                            echo $output;
                            echo "</li>";
                    }
                }
                    
		
                   
         echo "</ul>";
	
	}
	
	function output_contract_terms($updated_text, $order_details, $type=""){
		
			echo "<ol>";
                    
			foreach($updated_text['terms']['contract'][''] as $list_item => $list_info){
						
						if($list_item == 9){
							//cancellation clause
							$list_info = $updated_text['terms']['contract'][$type][9];
						}
				
						$output = replace_vars($list_info['text'], $order_details);
						if($output!=""){
								echo "<li>";
								echo $output;
								echo "</li>";
						}
			}
			
			if($order_details['email']=="cgill@newspaperarchive.com"){
					?>
					<li>Email marketing provisions. In order to send emails, all assets must be received at least two working days 
					prior to the date of the blast. All assets are to be provided by the Client and must be fully complete and quality 
					assured at the time of delivery to the Company. Email addresses acquired over three years ago cannot be used for 
					any email marketing purposes with the Company. Email addresses acquired between six months and three years ago will 
					participate in select email marketing efforts in small sample sizes in an effort to test the viability of the list. 
					Email addresses acquired in the last six months will be considered current and can be entered into all email 
					marketing efforts. Any campaigns not specified in this document must be preapproved by the Company.
					</li>
					<?php
				}
                   
         echo "</ol>";
         
	}
	
	function output_ol_list($updated_text, $order_details, $s1='', $s2='', $s3='', $list_style='', $ta_class='list'){
	
		echo "<ol {$list_style}>";
                    
		foreach($updated_text[$s1][$s2][$s3] as $list_item => $list_info){
                    $output = replace_vars($list_info['text'], $order_details);
                    if($output!=""){
                            echo "<li>";
                            if(strstr($list_style, 'class="dashes"')){
                                    echo "- ";
                            }
                            echo $output;
                            echo "</li>";
                    }
		}
                   
         echo "</ol>";
	
	}

        function output_order_table($order_details){
            $discount_total = $order_details['ppc_discount']
			+ $order_details['seo_discount'] + $order_details['smo_discount']
			+ $order_details['seo_ig_discount'] + $order_details['fba_discount']
			+ $order_details['slb_discount'] + $order_details['wd_discount'];

            // table headres
            $t_header = array(
                "desc" => "Description",
                "monthly" => "Monthly",
                "discount" => "Discount",
                "total" => "Total"
            );

            // table data
            $t_data = array();

            // Express PPC Set-Up Fee
            if($order_details['ppc_package']==1) {
                $setup_fee = ($order_details['ppc_setup_fee']) ? $order_details['ppc_setup_fee'] : "Waived";
                $t_data[] = array(
                    "desc" => 'Express PPC Management Fee <span class="tabbed">Startup Fee - One Time Payment</span>',
                    "monthly" => "",
                    "discount" => "",
                    "total" => $setup_fee
                );
            }

            //PPC Monthly Management Fee
            if($order_details['ppc_mgmt'] && $order_details['ppc_package']) {
                $t_data[] = array(
                    "desc" => 'PPC Management Fee',
                    "monthly" => $order_details['ppc_mgmt'],
                    "discount" => $order_details['ppc_discount'],
                    "total" => $order_details['ppc_mgmt']-$order_details['ppc_discount']
                );
            }

            //PPC Budget
            if($order_details['ppc_package']) {
                $ppc_budget = ($order_details['ppc_clicks']) ? $order_details['ppc_budget'] : "(Paid Directly to Search Engine)";
                $t_data[] = array(
                    "desc" => 'PPC Budget',
                    "monthly" => $order_details['ppc_budget'],
                    "discount" => "",
                    "total" => $ppc_budget
                );
            }

            //SEO Package
            if($order_details['seo_package']) {
                $t_data[] = array(
                    "desc" => 'Search Engine Optimization Service',
                    "monthly" => $order_details['seo_amount'],
                    "discount" => $order_details['seo_discount'],
                    "total" => $order_details['seo_amount']-$order_details['seo_discount']
                );
            }

            //SMO Package
            if($order_details['smo_package']) {
                $t_data[] = array(
                    "desc" => 'Social Media Service',
                    "monthly" => $order_details['smo_amount'],
                    "discount" => $order_details['smo_discount'],
                    "total" => $order_details['smo_amount']-$order_details['smo_discount']
                );
            }
            
            //Infographic
            if($order_details['infographic']) {
				
				
				$ig_desc = "Infographic Creation & Distribution";
				if($order_details['ig_num'] > 1){
					$ig_desc .= " (x{$order_details['ig_num']})";
				}	
				
                $t_data[] = array(
                    "desc" => $ig_desc,
                    "monthly" => $order_details['seo_ig_amount'],
                    "discount" => $order_details['seo_ig_discount'],
                    "total" => $order_details['seo_ig_amount']-$order_details['seo_ig_discount']
                );
            }
			
			//Facebook Advertising
			if($order_details['fba_package']) {
				$t_data[] = array(
					"desc" => 'Facebook Advertising Management Fee',
					"monthly" => $order_details['fba_mgmt'],
					"discount" => $order_details['fba_discount'],
					"total" => $order_details['fba_mgmt']-$order_details['fba_discount']
				);
				$fba_budget = ($order_details['fba_clicks']) ? $order_details['fba_budget'] : "(Paid Directly to Facebook)";
				$t_data[] = array(
					"desc" => 'Facebook Advertising Budget',
					"monthly" => $order_details['fba_budget'],
					"discount" => "",
					"total" => $fba_budget
				);
				if ($order_details['fba_setup_fee']) {
					$t_data[] = array(
						"desc" => 'Facebook Advertising Setup Fee',
						"monthly" => "",
						"discount" => "",
						"total" => $order_details['fba_setup_fee']
					);
				}
			}
			
			//Social Link Building
			if($order_details['slb_package']) {
				$t_data[] = array(
					"desc" => 'Social Link Building Service',
					"monthly" => $order_details['slb_amount'],
					"discount" => $order_details['slb_discount'],
					"total" => $order_details['slb_amount']-$order_details['slb_discount']
				);
			}

            //Web Development
            if($order_details['wd_op'] || $order_details['wd_landing_page'] || $order_details['wd_package']) {
                //Format Web Dev Description
                $wd_dsec = "Web Development Services Total";
                if($order_details['wd_package']){
                    //$wd_dsec .= '<span class="tabbed">Web Dev Package '.$order_details['wd_package'].'</span>';
                }
                if($order_details['wd_op']){
                    $wd_dsec .= '<span class="tabbed">Landing Page Optimization</span>';
                }
                if($order_details['wd_landing_page_testing']){
                    $wd_dsec .= '<span class="tabbed">Landing Page Design & Testing</span>';
                } else if($order_details['wd_landing_page']){
                    $wd_dsec .= '<span class="tabbed">Landing Page Design</span>';
                }
                if($order_details['wd_pay_half']){
                    $wd_dsec .= '(1st month total is half the total amount owed at the end of the contract)';
                    $wd_total = $order_details['wd_first_month_amount'];
                } else {
                    $wd_total = $order_details['wd_amount'];
                }

                $t_data[] = array(
                    "desc" => $wd_dsec,
                    "monthly" => "",
                    "discount" => $order_details['wd_discount'],
                    "total" => $wd_total-$order_details['wd_discount']
                );
            }
            
            
            // custom
            if (array_key_exists('_custom', $order_details))
						{
							$t_data = array_merge($t_data, $order_details['_custom']);
						}

            //Format Output
           $t_out .= "<tr>";
           $t_out .=    "<th class='odd'>{$t_header["desc"]}</th>";
           $t_out .=    "<th>{$t_header["monthly"]}</th>";
           $last_class = "odd";
           if($discount_total){
            $t_out .=   "<th class='odd'>{$t_header["discount"]}</th>";
            $last_class = "";
           }
           $t_out .=    "<th class='{$last_class}'>{$t_header["total"]}</th>";
           $t_out .= "<tr>";
            

            foreach($t_data as $td){
               $monthly_total     += is_numeric($td["monthly"]) ? $td["monthly"] : 0;
               $first_month_total += is_numeric($td["total"]) ? $td["total"] : 0;

               $monthly  = is_numeric($td["monthly"]) ? "$".number_format($td["monthly"], 2) : $td["monthly"];
               $discount = is_numeric($td["discount"]) ? "$".number_format($td["discount"], 2) : $td["discount"];
               $total    = is_numeric($td["total"]) ? "$".number_format($td["total"], 2) : $td["total"];

               $t_out .= "<tr>";
               $t_out .=    "<td class='odd'>{$td["desc"]}</td>";
               $t_out .=    "<td>{$monthly}</td>";
               $last_class = "odd";
               if($discount_total){
					$t_out .= "<td class='odd'>";
					$t_out .= ($discount!="") ? "-{$discount}" : "";
					$t_out .= "</td>";
					$last_class = "";
               }
               $t_out .=    "<td class='{$last_class}'>{$total}</td>";
               $t_out .= "<tr>";
            }
            
            if(!$order_details['ppc_clicks']){
				$monthly_total -= $order_details['ppc_budget'];
			}
			if(!$order_details['fba_clicks']){
				$monthly_total -= $order_details['fba_budget'];
			}

        ?>
            <table width="100%" border="0" cellspacing="5" cellpadding="5">
                <caption align="top">Order Details</caption>

                <?php echo $t_out; ?>

                <tr style="font-weight: bold; font-size: 1.2em;">
                    <td class="odd">1st Month Total Due</td>
                    <td></td>
                    <?php
                        $last_class = "odd";
                        if($discount_total){
                            $last_class = "";
                            echo "<td class='odd'></td>";
                        }
                    ?>
                    <td class="<?php echo $last_class; ?>">$<?php echo number_format(($first_month_total), 2); ?></td>
                </tr>

            </table>
        <?php
            return $monthly_total;
        }
	
	function output_order_table_old($order_details){
		
		$check_discounts = $order_details['ppc_discount'] + $order_details['seo_discount'] + $order_details['smo_discount'] + $order_details['wd_discount'];
		
	?>
     
		<table width="100%" border="0" cellspacing="5" cellpadding="5">
            <caption align="top">
            	Order Details
            </caption>

            <!-- ------ TABLE HEADERS --------- -->
            <tr>
                <th scope="col" class="odd" width="50%">Description</th>
                <th scope="col">Monthly</th>
               <?php if($check_discounts): ?>
                <th scope="col">Discount</th>
               <?php endif; ?>
                <th scope="col" class="odd">1st Month Totals</th>
            </tr>

            <!------------ PPC ------------->
           <?php if($order_details[ppc_package]==1): ?>
            <tr>
                <td class="odd">Express PPC Management Fee <span class="tabbed">Startup Fee - One Time Payment</span></td>
                <td></td>
               <?php if($check_discounts): ?>
                <th scope="col">
                    <?php
                        if($order_details['ppc_discount']){
                            echo "$".number_format($order_details['ppc_discount'], 2);
                        }
                    ?>
                </th>
               <?php endif; ?>
                <td class="odd">
                    <?php if($order_details[ppc_setup_fee]){
                                 echo "$".number_format($order_details[ppc_setup_fee], 2);
                                 $first_month_total += $order_details[ppc_setup_fee];
                          } else {
                                 echo "Waived" ;
                          }
                    ?>
                </td>
           </tr>		
	  <?php endif; ?>
            
            
            <?php if($order_details[ppc_mgmt] && $order_details[ppc_package]){ ?>
            <tr>
                <td class="odd">PPC Management Fee</td>
                <td>$<?php echo number_format($order_details[ppc_mgmt],2); $monthly_total = $order_details[ppc_mgmt]; ?></td>
                <td class="odd">$<?php echo number_format($order_details[ppc_mgmt], 2); $first_month_total = $order_details[ppc_mgmt] + $order_details[ppc_setup_fee]; ?></td>
            </tr>
            <?php } ?>
            
            <?php if($order_details[ppc_package]) { ?>
             <tr>
                <td class="odd">PPC Budget</td>
                <td>
                	$<?php echo number_format($order_details[ppc_budget], 2);?>
                </td>
                <td class="odd"><?php
					if($order_details[ppc_clicks]){
						echo "$".number_format($order_details[ppc_budget], 2);
						$first_month_total += $order_details[ppc_budget];
					} else {
						echo '(Paid Directly to Search Engine)';
					}?>
                </td>
            </tr>
            <?php } ?>
             
            <?php if($order_details[seo_package]) { ?>
            <tr>
                <?php
                    if($order_details['email']=="ichen@loandepot.com"){
                        echo "<td class=\"odd\">Online Reputation Management</td>";
                    } else {
                        echo "<td class=\"odd\">Search Engine Optimization Service</td>";
                    }
                ?>
                <td>$<?php echo number_format($order_details[seo_amount], 2); $monthly_total += $order_details[seo_amount]; ?></td>
                <td class="odd">$<?php echo number_format($order_details[seo_amount], 2); $first_month_total += $order_details[seo_amount]; ?></td>
            </tr>
            <?php } 
			 if($order_details[smo_package]) { ?>
            <tr>
                <td class="odd">Social Media Service</td>
                <td>$<?php echo number_format($order_details[smo_amount], 2); $monthly_total += $order_details[smo_amount]; ?></td>
                <td class="odd" >$<?php echo number_format($order_details[smo_amount], 2); $first_month_total += $order_details[smo_amount]; ?></td>
            </tr>
             <?php }  
			 if($order_details[wd_op] || $order_details[wd_landing_page] || $order_details[wd_package]) { ?>
            <tr>
                <td class="odd">
                	Web Development Services Total
                    	<?php if($order_details[wd_package]){
                                        echo '<span class="tabbed">Web Dev Package '.$order_details[wd_package].'</span>';
                                }
                                if($order_details[wd_op]){
                                        echo '<span class="tabbed">Landing Page Optimization</span>';
                                }
                                if($order_details[wd_landing_page_testing]){
                                        echo '<span class="tabbed">Landing Page Design & Testing</span>';
                                } else if($order_details[wd_landing_page]){
                                        echo '<span class="tabbed">Landing Page Design</span>';
                                }
                        ?>
                         <?php
                            $wd_total = $order_details[wd_amount];

                            if($order_details[wd_pay_half]){
                                echo "(1st month total is half the total amount owed at the end of the contract)";
                                $wd_total = $order_details[wd_first_month_amount];
                            }
                        ?>
                </td>
                <td>
                   
                </td>
                <td class="odd" >$<?php echo number_format($wd_total, 2); ?></td>
            </tr>
             <?php } 
			 $first_month_total += $wd_total;
			 ?>
             
            <tr>
                <td class="odd">&nbsp;</td>
                <td></td>
                <td class="odd"></td>
            </tr>
            
            <tr style="font-weight: bold; font-size: 1.2em;">
                <td class="odd">1st Month Total Due</td>
                <td></td>
                <td class="odd" >$<?php echo number_format($first_month_total, 2); ?></td>
            </tr>
            
		</table>
        
	<?php
		return $monthly_total;
	}
	
	function get_smo_table($package){
			
	?>
		<table width="100%">
		  <tr>
			<th scope="col" class="odd">Social Networks</th>
			<th scope="col">Micro Blogging</th>
			<th scope="col" class="odd">Social Bookmarks</th>
			<th scope="col">Entertainment Networking</th>
		  </tr>
		  <tr>
			<td class="odd"><span>Facebook</span> Ning</td>
			<td>Twitter</td>
			<td class="odd">Stumble Upon</td>
			<td><span>Trig</span> Reverb Nation</td>
		  </tr>
		  <tr>
			<td class="odd"><span>MySpace</span> Ryze</td>
			<td>FriendFeed</td>
			<td class="odd">Digg</td>
			<td><span>Virb</span> iLike</td>
		  </tr>
		  <tr>
			<td class="odd">Bebo</td>
			<td>Plurk</td>
			<td class="odd">Delicious</td>
			<td><span>GarageBand</span> Imeem</td>
		  </tr>
		  <tr>
			<td class="odd">Plaxo</td>
			<td>Yammer</td>
			<td class="odd">Reddit</td>
			<td><span>Last.fm</span> YouTube</td>
		  </tr>
		  <tr>
			<td class="odd"><span>Hi5</span> Oruk</td>
			<td>Identi.ca</td>
			<td class="odd">Sphinn</td>
			<td><span>Indie 911</span> Break</td>
		  </tr>
		  <tr>
			<td class="odd"><span>Friendster</span> Spoke</td>
			<td>Jaiku</td>
			<td class="odd">Newsvine</td>
			<td><span>Pure Volume</span> Buzznet</td>
		  </tr>
		  <tr>
			<td class="odd"><span>LinkedIn</span> Sonico</td>
			<td>Brightkite</td>
			<td class="odd">&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		</table>
        
	<?php
	}

        function custom_order_table(){
        ?>


		<table width="100%" border="0" cellspacing="5" cellpadding="5">
            <caption align="top">
            	Order Details
            </caption>
            <tr>
                <th scope="col" class="odd" width="50%">Description</th>
                <th scope="col">Monthly</th>
                <th scope="col" class="odd">1st Month Totals</th>

            </tr>





                        <tr>
                <td class="odd">SEO services</td>
                <td>$5,000.00</td>
                <td class="odd">$5,000.00</td>
            </tr>

            <tr>
                <td class="odd">Monthly Linkbuilding Budget</td>
                <td>$25,000.00</td>
                <td class="odd">$25,000.00</td>
            </tr>

            <tr>

                <td class="odd">&nbsp;</td>
                <td></td>
                <td class="odd"></td>
            </tr>

            <tr style="font-weight: bold; font-size: 1.2em;">
                <td class="odd">1st Month Total Due</td>
                <td></td>
                <td class="odd" >$30,000.00</td>

            </tr>

		</table>

        <?php

        }
?>
