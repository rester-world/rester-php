<?php
$response_body = array(
    'success'=>false,
    'msg'=>'',
    'data'=>''
);

try
{
    include_once('./rester/common.php');
    $response_body['data'] = rester::run();
    $response_body['msg'] = implode(',', rester::msg());
    if(rester::isSuccess()) $response_body['success'] = true;
}
catch (Exception $e)
{
    $response_body['msg'] = $e->getMessage();
}

// print response code & response header
rester::run_headers();

echo json_encode($response_body);

