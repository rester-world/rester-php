<?php
$response_body = array(
    'success'=>false,
    'msg'=>'',
    'warning'=>[],
    'error'=>[],
    'data'=>''
);

try
{
    include_once('./rester/common.php');
    cfg::init();
    $response_body['data'] = rester::run();
    $response_body['msg'] = implode(',', rester::msg());
    $response_body['error'] = rester::error();
    if(rester::isSuccess()) $response_body['success'] = true;
}
catch (Exception $e)
{
    $response_body['error'][] = $e->getMessage();
}

// print response code & response header
rester::run_headers();
echo json_encode($response_body);

