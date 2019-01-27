<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../config/dbconnection.php");

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
        $userList = array();
        $sql1 = "select fullName from tbl_users where status>0 and userDesignation = 'Inspector'";
        $row = mysqli_query($DB, $sql1);
        while ($result = mysqli_fetch_assoc($row)) {
            array_push($userList, $result['fullName']);
        }
        $array = array("response" => $data, "rejectionList" => sizeof($rejectionList)>0? $rejectionList : null , "userList" =>  $userList);
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
