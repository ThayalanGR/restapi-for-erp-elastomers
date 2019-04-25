<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../config/dbconnection.php");

//defaults
    // eMail Settings
    $sender_user		=	"lk@wriston.co.in";
    $sender_add			=	"lk@wriston.co.in";
    $sender_pass		=	"madras#2";
    $mgmt_grp_email		=	array("lk@wriston.co.in");
    $dev_grp_email		=	array("lk@wriston.co.in");
    $prod_grp_email		=	array("lk@wriston.co.in");
    $cpd_grp_email		=	array("lk@wriston.co.in");
    $quality_grp_email	=	array("lk@wriston.co.in");
    $mktg_grp_email		=	array("lk@wriston.co.in");
    $maint_grp_email	=	array("lk@wriston.co.in");
    $admin_grp_email	=	array("lk@wriston.co.in");

    // Component Rejection Alert Settings
    $compRejPer			=	5;



function str2num($string)
{
    //$string		=	'3,345.67';
    $num		=	0;
    $temp		=	split('[,]', $string);
    $number		=	"";
    foreach ($temp as $value) {
        $number		.=  $value;
    }
    return (float)$number;
}

function getMySQLData($sql, $type=null, $startNode=null, $nodeCase=null, $queryType=null)
{
    global $DB;
    $db_output		=	array();
    $SN				=	($startNode)?$startNode:"mysql";
    $xml_output		=	"<$SN>";
    if ($sql) {
        $db_output['sql']	=	$sql;
        $xml_output			.=	"<sql><![CDATA[".$sql."]]></sql>";
        if ($query = mysqli_query($DB, $sql)) {
            if ($query) {
                if ($queryType == "select" && mysqli_num_rows($query) > 0) {
                    $db_output['count']		=	mysqli_num_rows($query);
                    $db_output['data']		=	array();
                    $xml_output				.=	"<count>".$db_output['count']."</count>";
                    $xml_output				.=	"<data>";
                        
                    while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                        $keys			=	array_keys($data);
                        $ndata			=	array();
                        if (count($keys) > 0) {
                            for ($k=0; $k<count($keys); $k++) {
                                if (preg_match("/\(/", $keys[$k])) {
                                    $data_key	=	split("\(", $keys[$k]);
                                    $data_key	=	split("\)", $data_key[count($data_key)-1]);
                                    $data_key	=	$data_key[0];
                                            
                                    if (preg_match("/\./", $data_key)) {
                                        $data_key	=	split("\.", $data_key);
                                        $data_key	=	$data_key[count($data_key)-1];
                                    }
                                } else {
                                    $data_key	=	$keys[$k];
                                }
                                        
                                switch (strtolower($nodeCase)) {
                                            case "lower":
                                            case "lowercase":
                                            $data_key	=	@strtolower($data_key);
                                            break;
                                            case "upper":
                                            case "uppercase":
                                            $data_key	=	@strtoupper($data_key);
                                            break;
                                        }
                                        
                                $ndata[$data_key]	=	$data[$keys[$k]];
                            }
                            //For fixing & spliting bug
                            $data		=	preg_replace('/\&/', '&amp;', $ndata);
                            $keys		=	array_keys($data);
                        }
                        // Array Data
                        array_push($db_output['data'], $data);
                        // Xml Data
                        $xml_output			.=	"<row>";
                        for ($k=0; $k<count($keys); $k++) {
                            $xml_output		.=	"<".$keys[$k].">".$data[$keys[$k]]."</".$keys[$k].">";
                        }
                        $xml_output			.=	"</row>";
                    };
                    $xml_output				.=	"</data>";
                }
                $db_output['status']	=	"success";
            } else {
                $db_output['status']	=	"err3";
            }
        } else {
            $db_output['status']	=	"err2";
        }
        
       
            
        $db_output['errno']		=	mysqli_errno($DB);
        $db_output['errtxt']	=	mysqli_error($DB);
        $xml_output				.=	"<status>".$db_output['status']."</status>";
        $xml_output				.=	"<errno>".$db_output['errno']."</errno>";
        $xml_output				.=	"<errtxt>".urlencode($db_output['errtxt'])."</errtxt>";
        $xml_output				.=	"</$SN>";
        return ($type == "xml")?$xml_output:(($type=="both")?array('arr'=>$db_output, 'xml'=>$xml_output):$db_output);
    }
}

function getSettingsValue($data)
{
    if ($data != "" || is_array($data)) {
        if (is_array($data)) {
            $sett_value	=	array();
            foreach ($data as $val) {
                array_push($sett_value, "'$val'");
            }
            $sett_value	=	@join(", ", $sett_value);
        } else {
            $sett_value	=	"'$data'";
        }
        
        $sql			=	"select * from tbl_settings where name in ($sett_value)";
        $duties			=	getMySQLData($sql, null, null, null, "select");
        $count			=	$duties['count'];
        $duties			=	$duties['data'];
        $return 		=	array();
        if ($count > 0) {
            foreach ($duties as $value) {
                $return[$value['name']]	=	$value['value'];
            }
        }
        return $return;
    }
}

        
function getSettingsData($set)
{
    if ($set) {
        $sql	=	"select * from tbl_settings where name='$set'";
        $data	=	getMySQLData($sql, null, null, null, "select");
        
        if ($data['status'] == "success") {
            return array($data['data'][0]['value'], $data['data'][0]['auto_inc']);
        } else {
            return $data['status'];
        }
    }
}

//requesting function
function getRegisterNo($regType, $no, $cid='', $cno=1)
{
    global $numMonth;
    
    $finalReg	=	'';
    $YYYY		=	date("Y") - ((date("m") > 3)?0:1);
    $YY			=	date("y") - ((date("m") > 3)?0:1);
    $DD			=	date("d");
    $M			=	$numMonth[date("m")+0];
    
    if ($regType != "" && $no > 0) {
        $regSplit	=	preg_split("/[|]/", $regType, PREG_NO_ERROR);
        $regName	=	'';
        
        foreach ($regSplit as $obj) {
            switch ($obj) {
                case "YYYY":
                case "YY":
                case "DD":
                case "M":
                    $regName	.=	$$obj;
                break;
                case "cid":
                    $regName	.=	$cid;
                break;
                case "@":
                    $regName	.=	chr(64 + $cno);
                break;
                default:
                    preg_match("/[\{\}]/", $obj, $noMatches);
                    if (count($noMatches) > 0) {
                        $obj	=	preg_replace("/[\{\}]/", "", $obj);
                        $obj	=	$obj + 0;
                        
                        // Generate Serial No
                        if ($obj > 0) {
                            $noZ	=	"";
                            for ($gr=0; $gr <= ($obj-strlen($no))-1; $gr++) {
                                $noZ .= "0";
                            }
                            $obj	=	$noZ.$no;
                        }
                    }
                    $regName	.=	$obj;
                break;
            }
        }
        
        /*$regName	=	$regSplit[0];
        $noZero		=	$regSplit[1];

        // Generate Reg Pattern
        $regName	=	preg_replace("/[YYYY]/", date("Y"), $regName);
        $regName	=	preg_replace("/[YY]/", date("y"), $regName);

        // Generate Serial No
        if($noZero > 0){
            $noZ	= "";
            for($gr=0; $gr <= ($noZero-strlen($no))-1; $gr++){
                $noZ .= "0";
            }
            $noZero	= $noZ.$no;
        }*/
    }
    
    return $regName;
}

function sendEmail($to, $cc_address, $subject, $message, $attachment, $send_add = null, $send_pass = null, $send_user = null)
{
    if ($send_user == null) {
        $send_user	=	$sender_user;
    }
    if ($send_add == null) {
        $send_add	=	$sender_add;
    }
    if ($send_pass == null) {
        $send_pass	=	$sender_pass;
    }
    require_once('PHPMailerAutoload.php');
    $output 	= 	"success";
    $mail 		= 	new PHPMailer;
    try {
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug 	= 	0;
        //Ask for HTML-friendly debug output
        //$mail->Debugoutput = 'html';
        $mail->SMTPOptions 	= 	array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ));
        $mail->Host 		= 	'smtp.gmail.com';
        $mail->Port 		= 	587;
        $mail->SMTPSecure 	= 	'tls';
        $mail->SMTPAuth 	= 	true;
        $mail->Username 	= 	$send_user;
        $mail->Password 	= 	$send_pass;
        $mail->setFrom($send_add);
        if ($to == null || $to == '') {
            throw new phpmailerException('No "To" address Provided!');
        }
        if (is_array($to)) {
            for ($i=0;$i<count($to);$i++) {
                $mail->addAddress($to[$i], '');
            }
        } else {
            $mail->addAddress($to, '');
        }
        if (is_array($cc_address)) {
            for ($i=0;$i<count($cc_address);$i++) {
                $mail->addCC($cc_address[$i], '');
            }
        } elseif ($cc_address != null && $cc_address != "") {
            $mail->addCC($cc_address, '');
        }
        $mail->IsHTML(true);
        $mail->Subject 		= 	$subject;
        $mail->Body 		= 	$message;
        if ($attachment != null && $attachment != "") {
            if (is_array($attachment)) {
                foreach ($attachment as $thefile) {
                    if (!($mail->addAttachment($thefile))) {
                        throw new phpmailerException('Unable to attach the file: ' . $mail->ErrorInfo);
                    }
                }
                $mail->send();
            } elseif ($mail->addAttachment($attachment)) {
                $mail->send();
                unlink($attachment);
            } else {
                throw new phpmailerException('Unable to attach the file: ' . $mail->ErrorInfo);
            }
        } else {
            $mail->send();
        }
    } catch (phpmailerException $e) {
        $output		=	$e->errorMessage();
    } catch (Exception $e) {
        $output 	=  	$e->getMessage();
    }
    return $output;
}


//handling get request
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $id = $_GET['id'];
    $sql = "select issref,cmpdname,cmpdrefno,'0' as isexternal,(dr.currrec - ifnull(mq.receiptqty,0)) as currrec,          di.cmpdid,dr.sno, dr.defrecdate, DATE_FORMAT(dr.defrecdate,'%d-%b-%Y') as defrecdatef
        from tbl_deflash_reciept dr
            inner join tbl_deflash_issue di on dr.defissref = di.sno
            inner join tbl_component tc on di.cmpdid=tc.cmpdId
            left join ( select mdlrref, sum(receiptqty) as receiptqty from (select mdlrref, receiptqty from tbl_moulding_quality where status > 0 and isExternal = 0 group by qualityref)tmq group by mdlrref) mq on mq.mdlrref = dr.sno
        where dr.status = 1 and (dr.currrec - ifnull(mq.receiptqty,0)) > 0 and dr.inspissdate <= CURRENT_DATE and dr.inspissdate != '0000-00-00'
        UNION ALL
        select cr.planId as issref,cmpdname,cmpdrefno,'1' as isexternal,(cr.recvqty - ifnull(mq.receiptqty,0)) as currrec,cr.cmpdid,cr.sno, cr.invdate as defrecdate, DATE_FORMAT(cr.invdate,'%d-%b-%Y') as defrecdatef
            from tbl_component_recv cr
                inner join tbl_component tc on cr.cmpdid=tc.cmpdId
                left join ( select mdlrref, sum(receiptqty) as receiptqty from (select mdlrref, receiptqty from tbl_moulding_quality where status > 0 and isExternal = 1 group by qualityref)tmq group by mdlrref) mq on mq.mdlrref = cr.sno
            where cr.status = 1 and (cr.recvqty - ifnull(mq.receiptqty,0)) > 0 and cr.inspissdate <= CURRENT_DATE and cr.inspissdate != '0000-00-00'					
        order by defrecdate desc,issref ";
    $row = mysqli_query($DB, $sql);
    $data = null;
    while ($result = mysqli_fetch_assoc($row)) {
        if ($result['issref'] == $id) {
            $data = $result;
        }
    }
    //constructing data
    if ($data != null) {
        //obtaining rejection list
        $rejectionList = array();
        $sql2	=	"select distinct t1.sno, t1.rej_type, t1.rej_short_name
        from tbl_rejection t1, tbl_component_rejection t2
        where t1.sno=t2.cmpdRejNo and t2.cmpdId='".$data['cmpdid']."'";
        $row = mysqli_query($DB, $sql2);
        while ($result = mysqli_fetch_assoc($row)) {
            array_push($rejectionList, $result);
        }

        //obataing user list
        // $userList = array();
        // $sql1 = "select fullName from tbl_users where status>0 and userDesignation = 'Inspector'";
        // $row = mysqli_query($DB, $sql1);
        // while ($result = mysqli_fetch_assoc($row)) {
        //     array_push($userList, $result['fullName']);
        // }

        $array = array("response" => $data, "rejectionList" => sizeof($rejectionList)>0? $rejectionList : null);
        http_response_code(200);
        echo json_encode($array);
    } else {
        http_response_code(204);
        echo null;
    }
}


//handling post request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (sizeof($data) == 9) {
        $codeArray  =   getSettingsData("mouldqal");
        $codeNo		=   getRegisterNo($codeArray[0], $codeArray[1]);
        $cmpdid		=	$data['cmpdid'];
        $recqty		=	str2num($data['recqty']);
        $appqty		=	str2num($data['appqty']);
        $planId		=	$data['planid'];
        $mdlrref	=	$data['mdlrref'];
        $isExternal	=	$data['isexternal'];
        $inspector	=	$data['inspector'];
        $inspDate	=	$data['inspdate'];
        $result		=	getMySQLData('select userId, fullName from tbl_users where userNo='.$inspector, null, null, null, "select");
        $usrId      =   $result['data'][0]['userId'];
        $inspector  =   $result['data'][0]['fullName'];
        // echo $userId;
        // return 0;

        if($usrId == NULL || $usrId == '') {
                http_response_code(400);
                echo json_encode(array("response" => "User Id is wrong"));
        } else {


            $sql		=   " insert into tbl_moulding_quality(mdlrref, isExternal, inspector, receiptqty, appqty, rejcode, rejval, qualityref, qualitydate, planref, cmpdId, entry_on, entry_by) values ";

            $reworkSql	=	"";
            $reworkQty	=	0;
            $rejTotVal	=	0;

            if (count($data['rejection']) > 0) {
                foreach ($data['rejection'] as $key => $val) {
                    $rejVal	=	str2num($data['rejection'][$key]['value']);
                    $sql	.=	 " ( '$mdlrref', '$isExternal', '$inspector', '$recqty', '$appqty', '".$val['rej_short_name']."', '$rejVal', '$codeNo', '$inspDate' , '$planId', '$cmpdid', now(), '$usrId' ) ";
                    if ($key < count($data['rejection'])-1) {
                        $sql	.=	" , ";
                    }
                    if ($val['rej_short_name'] == 'REWORK') {
                        $reworkSql 		=	" insert into tbl_rework(reworkref, planid, qualref, inspdate, cmpdid, quantity, entry_on, entry_by) values ";
                        $rwrkCodeArray  =   getSettingsData("reworkCode");
                        $rwrkCodeNo		=   getRegisterNo($rwrkCodeArray[0], $rwrkCodeArray[1]);
                        $reworkQty		=	$rejVal;
                        $reworkSql		.=	" ( '$rwrkCodeNo', '$planId', '$codeNo', '$inspDate', '$cmpdid', '$rejVal', now(), '$usrId' ) ";
                    } else {
                        $rejTotVal	+=	$rejVal;
                    }
                }
            } else {
                $sql	.=	 " ( '$mdlrref', '$isExternal', '$inspector', '$recqty', '$appqty', '', 0, '$codeNo', '$inspDate' , '$planId', '$cmpdid', now(), '$usrId' ) ";
            }
        
             $res	=	getMySQLData($sql);
        
            if ($res['status'] == 'success') {
                //update settings
                getMySQLData("update tbl_settings set auto_inc='".($codeArray[1]+1)."' where name='mouldqal'");
                
                //update mould store
                $sql_planref		=	getMySQLData("select planref from tbl_mould_store where planref='$planId' and cmpdId = '$cmpdid' and status = 1", "arr", null, null, "select");
                $plan_dtls			=	$sql_planref['data'][0];
                $exPlanRef			=	$plan_dtls['planref'];
                if ($exPlanRef == $planId) {
                    $res		=	getMySQLData("update tbl_mould_store set avlQty = avlQty + $appqty where planref='$planId' and cmpdId = '$cmpdid' and status = 1");
                } else {
                    $pSql		=   " insert into tbl_mould_store( planref, cmpdId, avlQty) values ('$planId','$cmpdid','$appqty')";
                    $res		=	getMySQLData($pSql);
                }
                if ($res['status'] == 'success') {
                    //create rework table/update settings
                    if ($reworkSql != "") {
                        $rewrkRes = getMySQLData($reworkSql);
                        if ($rewrkRes['status'] == 'success') {
                            getMySQLData("update tbl_settings set auto_inc='".($rwrkCodeArray[1]+1)."' where name='reworkCode'");
                        }
                    }
                    //update tbl_deflash_receipt to close if necessary (i.e status = 2)
                    $inspQtySQL		=	"";
                    if ($isExternal == 0) {
                        $inspQtySQL	=	"select currrec,sum(receiptqty) as receiptqty
                                            from (select mdlrref,receiptqty from tbl_moulding_quality where status > 0 and isExternal = 0 and mdlrref ='$mdlrref' group by qualityref) tmq
                                                inner join tbl_deflash_reciept tdr on tdr.sno = tmq.mdlrref
                                            where tdr.status > 0 ";
                    } else {
                        $inspQtySQL	=	"select recvqty as currrec,sum(receiptqty) as receiptqty
                                            from (select mdlrref,receiptqty from tbl_moulding_quality where status > 0 and isExternal = 1 and mdlrref ='$mdlrref' group by qualityref) tmq
                                                inner join tbl_component_recv tcr on tcr.sno = tmq.mdlrref
                                            where tcr.status > 0 ";
                    }
                    $sql_inspQty	=	getMySQLData($inspQtySQL, "arr", null, null, "select");
                    $inspQty_dtls	=	$sql_inspQty['data'][0];
                    $exAvlQty		=	str2num($inspQty_dtls['currrec']);
                    $exInspQty		=	str2num($inspQty_dtls['receiptqty']);
                    if ($exInspQty >= $exAvlQty) {
                        if ($isExternal == 0) {
                            getMySQLData("update tbl_deflash_reciept set status='2' where sno ='$mdlrref'");
                        } else {
                            getMySQLData("update tbl_component_recv set status='2' where sno ='$mdlrref'");
                        }
                    }
                    //close tbl_moulding_receive/rework table if all items inspected
                    if ($isExternal == 0) {
                        $moulRefSQL			= "select di.mouldref,di.issqty,sum(receiptqty) as receiptqty
                                                    from tbl_deflash_issue di
                                                        inner join tbl_deflash_reciept dr on di.sno = dr.defissref and dr.status > 0
                                                        inner join (select mdlrref,receiptqty from tbl_moulding_quality where status > 0 and isExternal = 0 group by qualityref) mq on mq.mdlrref = dr.sno
                                                where di.status > 0 and di.sno = ( select defissref  from tbl_deflash_reciept where sno ='$mdlrref' and status > 0)";
                        
                        $sql_issMouldRef	=	getMySQLData($moulRefSQL, "arr", null, null, "select");
                        $issMouldRef_dtls	=	$sql_issMouldRef['data'][0];
                        $exissMouldRef		=	$issMouldRef_dtls['mouldref'];
                        $exIssQty			=	str2num($issMouldRef_dtls['issqty']);
                        $totRecptQty		=	str2num($issMouldRef_dtls['receiptqty']);
                        if ($totRecptQty > 0.98 * $exIssQty) {
                            if (strripos($planId, '-rt') == strlen($planId) - 3) {
                                getMySQLData("update tbl_rework set status='4' where reworkref = '$exissMouldRef'");
                            } else {
                                getMySQLData("update tbl_moulding_receive set status='6' where modRecRef  = '$planId'");
                            }
                        }
                    }
                    // Send alert if rejection is more than  5%
                    $sql_comp	=	getMySQLData("select cmpdname,cmpdrefno from tbl_component where cmpdId = '$cmpdid'", "arr", null, null, "select");
                    $comp_dtls	=	$sql_comp['data'][0];
                    $inspQty	=	$recqty - $reworkQty;
                    if ($rejTotVal > ($inspQty * ($compRejPer/100))) {
                        $rejPer		=	round((($rejTotVal/$inspQty)*100), 2);
                        $sql_comp	=	getMySQLData("select cmpdname,cmpdrefno from tbl_component where cmpdId = '$cmpdid'", "arr", null, null, "select");
                        $comp_dtls	=	$sql_comp['data'][0];
                        $compName	=	$comp_dtls['cmpdname'];
                        $compDesc	=	$comp_dtls['cmpdrefno'];
                        $sql_rate	=	getMySQLData("select poRate from tbl_customer_cmpd_po_rate where cmpdId = '$cmpdid' order by update_on desc limit 1", "arr", null, null, "insert");
                        $rate_dtls	=	$sql_rate['data'][0];
                        $poRate		=	$rate_dtls['poRate'];
                        $rejSum		=	$rejTotVal * $poRate;
                        $pstatus 	= 	sendEmail(array_merge($dev_grp_email, $prod_grp_email, $cpd_grp_email), $quality_grp_email, "Rejection is ".$rejPer."% (Value Rs:".$rejSum.") for: ".$planId."(".$compName.")", "Please note: The Total rejection for the Key : ".$planId." for ".$compName."(".$compDesc.") is ".$rejTotVal." for inspected quantity of  ".$inspQty, "");
                    }
                    http_response_code(202);
                    echo json_encode(array("response" => $codeNo));
                // echo null;
                } else {
                    http_response_code(400);
                    echo json_encode(array("response" => $res['status']));
                    // echo null;
                }
            } else {
                http_response_code(400);
                echo json_encode(array("response" => $res['status']));
                // echo null;
            }
        }

        // echo json_encode(array($data, "als" => $codeArray, "codeno" => $codeNo, "userId" => $usrId));
        
    } else {
        http_response_code(400);
        echo json_encode(array("response" => true));
        // echo null;
    }
}
