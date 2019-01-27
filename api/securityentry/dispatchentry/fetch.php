<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../../config/dbconnection.php");

//handling get request
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $doc = explode('~', $_GET['invid']);
    $type = $doc[0];
    $sql = "select docId from tbl_despatch where docId = '".$doc[1]."' and docType = '".$doc[0]."'";
    $row = mysqli_query($DB, $sql);
    $data = null;
    while ($result = mysqli_fetch_assoc($row)) {
        $data = $result['docId'];
    }
    $list = null;
    if ($data != $doc[1]) {
        switch ($doc[0]) {
            case 'inv':
                $sql	=	"select '".$doc[0]."' as doctype, tis.invId, invConsignee, sum(invqty) as invqty, concat(invName,' - ', invDesc) as partNumber, DATE_FORMAT(invDate, '%d-%b-%Y') as invDate, invGrandTotal, if(invtype = 'cmpd' and cmpdstdpckqty > 0,ceil(sum(invqty)/cmpdstdpckqty),count(*)) as numPacks
                                    from tbl_invoice_sales tis								
                                        inner join tbl_invoice_sales_items tisi on tis.invId=tisi.invId and tisi.invtype != 'mix'
                                        left join tbl_component tc on tc.cmpdId = tisi.invCode											
                                    where tis.invId = '".$doc[1]."' and tis.status > 0  group by tis.invId";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    $list = $result;
                }
            break;
            case 'dc':
                $sql	=	"select '".$doc[0]."' as doctype, tdc.dcId as invId, dcConsignee as invConsignee, sum(dcqty) as invqty, concat(dcName,' - ', dcDesc) as partNumber, DATE_FORMAT(dcDate, '%d-%b-%Y') as invDate, dcAssessValue as invGrandTotal, count(*) as numPacks
                                    from tbl_invoice_dc tdc								
                                        inner join tbl_invoice_dc_items tdci on tdc.dcId=tdci.dcId 
                                    where tdc.dcId = '".$doc[1]."' and tdc.status > 0 group by tdc.dcId";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    $list = $result;
                }
            break;
            case 'mold':
                $sql	=	"select '".$doc[0]."' as doctype, mdIssRef as invId, operator as invConsignee, sum(qtyIss) as invqty, 'Rubber Compound' as partNumber, DATE_FORMAT(issueDate, '%d-%b-%Y') as invDate, sum(qtyIss*rate) as invGrandTotal, count(*) as numPacks
                                    from tbl_moulding_issue 								
                                    where mdIssRef = '".$doc[1]."' and status > 0 group by mdIssRef";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    $list = $result;
                }
            break;
            case 'trim':
                $sql	=	"select '".$doc[0]."' as doctype, defissref as invId, operator as invConsignee, sum(issqty) as invqty, concat(cmpdName,' - ', cmpdRefNo) as partNumber, DATE_FORMAT(issdate, '%d-%b-%Y') as invDate, sum(issqty*rate) as invGrandTotal, count(*) as numPacks
                                    from tbl_deflash_issue tdi
                                        inner join tbl_component tc on tc.cmpdId = tdi.cmpdid
                                    where defissref = '".$doc[1]."' and tdi.status > 0 group by defissref";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    $list = $result;
                }
            break;
            case 'tool':
                $sql	=	"select '".$doc[0]."' as doctype, ttn_ref as invId, transferee_userName as invConsignee, '1' as invqty, tool_ref as partNumber, DATE_FORMAT(ttn_date, '%d-%b-%Y') as invDate, '0' as invGrandTotal, '1' as numPacks
                                    from tbl_tool_transfer 
                                    where ttn_ref = '".$doc[1]."' and status > 0 ";
                $row = mysqli_query($DB, $sql);
                while ($result = mysqli_fetch_assoc($row)) {
                    $list = $result;
                }
            break;
        }//switch end
    }

    //constructing data
    if ($data != $doc[1] && sizeof($doc) == 2) {
        http_response_code(200);
        $array = array("response" => $list);
        echo json_encode($array);
    } else {
        http_response_code(204);
        echo null;
    }
}

//handling post request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (sizeof($data) == 2) {
        $totalPlans = sizeof($data['planList']);
        $pickUpBy = $data['pickUp']['by'];
        $pickUpVehicle = $data['pickUp']['vehicle'];
        $docId = array();
        $docType = array();
        $totalQty = array();
        $numPacks = array();
        foreach ($data['planList'] as $key => $value) {
            array_push($docId, $value["docId"]);
            array_push($docType, $value["docType"]);
            array_push($totalQty, $value["totalQty"]);
            array_push($numPacks, $value["numPacks"]);
        }
        //pushing into db
        $sql_ins_items	=	"insert into tbl_despatch (docId, docType, totalQty, packQty, vehNum, despPerson, despOn) values";
        for ($lp=0;$lp<count($docId);$lp++) {
            $sql_ins_items	.=	"('".$docId[$lp]."', '".$docType[$lp]."', '".$totalQty[$lp]."', '".$numPacks[$lp]."','$pickUpVehicle','$pickUpBy',now())";
            if ($lp < count($docId)-1) {
                $sql_ins_items .= ",";
            }
        }
    
        $row = mysqli_query($DB, $sql_ins_items);
        if ($row) {
            http_response_code(202);
            echo json_encode(array("response" => true));
        } else {
            http_response_code(400);
            echo false;
        }
    } else {
        http_response_code(400);
        echo json_encode(array("response" => false));
    }
}
