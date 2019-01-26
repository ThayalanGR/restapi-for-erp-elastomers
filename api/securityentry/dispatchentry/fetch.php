<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../../config/dbconnection.php");

if (isset($_GET['invid'])) {
    $doc = explode('~', $_GET['invid']);
    $type = $doc[0];
    $sql = "select docId from tbl_despatch where docId = '".$doc[1]."' and docType = '".$doc[0]."'";
    $row = mysqli_query($DB, $sql);
    $data = null;
    while ($result = mysqli_fetch_assoc($row)) {
        $data = $result['docId'];
    }
    $list = array();
    if ($data = $doc[1]) {
        switch ($doc[0]) {
            case 'inv':
                $sql	=	"select '".$doc[0]."' as doctype, tis.invId, invConsignee, sum(invqty) as invqty, concat(invName,' - ', invDesc) as partNumber, DATE_FORMAT(invDate, '%d-%b-%Y') as invDate, invGrandTotal, if(invtype = 'cmpd' and cmpdstdpckqty > 0,ceil(sum(invqty)/cmpdstdpckqty),count(*)) as numPacks
                                    from tbl_invoice_sales tis								
                                        inner join tbl_invoice_sales_items tisi on tis.invId=tisi.invId and tisi.invtype != 'mix'
                                        left join tbl_component tc on tc.cmpdId = tisi.invCode											
                                    where tis.invId = '".$doc[1]."' and tis.status > 0  group by tis.invId";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    array_push($list, $result);
                }
            break;
            case 'dc':
                $sql	=	"select '".$doc[0]."' as doctype, tdc.dcId as invId, dcConsignee as invConsignee, sum(dcqty) as invqty, concat(dcName,' - ', dcDesc) as partNumber, DATE_FORMAT(dcDate, '%d-%b-%Y') as invDate, dcAssessValue as invGrandTotal, count(*) as numPacks
                                    from tbl_invoice_dc tdc								
                                        inner join tbl_invoice_dc_items tdci on tdc.dcId=tdci.dcId 
                                    where tdc.dcId = '".$doc[1]."' and tdc.status > 0 group by tdc.dcId";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    array_push($list, $result);
                }
            break;
            case 'mold':
                $sql	=	"select '".$doc[0]."' as doctype, mdIssRef as invId, operator as invConsignee, sum(qtyIss) as invqty, 'Rubber Compound' as partNumber, DATE_FORMAT(issueDate, '%d-%b-%Y') as invDate, sum(qtyIss*rate) as invGrandTotal, count(*) as numPacks
                                    from tbl_moulding_issue 								
                                    where mdIssRef = '".$doc[1]."' and status > 0 group by mdIssRef";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    array_push($list, $result);
                }
            break;
            case 'trim':
                $sql	=	"select '".$doc[0]."' as doctype, defissref as invId, operator as invConsignee, sum(issqty) as invqty, concat(cmpdName,' - ', cmpdRefNo) as partNumber, DATE_FORMAT(issdate, '%d-%b-%Y') as invDate, sum(issqty*rate) as invGrandTotal, count(*) as numPacks
                                    from tbl_deflash_issue tdi
                                        inner join tbl_component tc on tc.cmpdId = tdi.cmpdid
                                    where defissref = '".$doc[1]."' and tdi.status > 0 group by defissref";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    array_push($list, $result);
                }
            break;
            case 'tool':
                $sql	=	"select '".$doc[0]."' as doctype, ttn_ref as invId, transferee_userName as invConsignee, '1' as invqty, tool_ref as partNumber, DATE_FORMAT(ttn_date, '%d-%b-%Y') as invDate, '0' as invGrandTotal, '1' as numPacks
                                    from tbl_tool_transfer 
                                    where ttn_ref = '".$doc[1]."' and status > 0 ";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    array_push($list, $result);
                }
            break;
        }//switch end
    }

    //constructing data
    if ($data != null) {
        $array = array("response" => $list);
        echo json_encode($array);
    } else {
        $array = array("response" => false);
        echo json_encode($array);
    }
}
