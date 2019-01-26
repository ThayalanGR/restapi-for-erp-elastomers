<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include("../../config/dbconnection.php");

//handling get request
if (isset($_GET['id'])) {
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
if (isset($_POST['status'])) {
    http_response_code(200);
    $array = array("response" => true);
    echo json_encode($array);
}
