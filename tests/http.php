<?php
$base='http://127.0.0.1:8020';
function httpRequest(string $path,string $method='GET',array $parameters=[]):array {
 global $base;
 $options=['ignore_errors'=>true,'follow_location'=>0,'timeout'=>10];
 if($method==='POST')$options+=['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>http_build_query($parameters)];
 $body=file_get_contents($base.$path,false,stream_context_create(['http'=>$options]));
 $headers=$http_response_header??[];
 if($body===false||!preg_match('~^HTTP/\S+ (\d+)~',$headers[0]??'',$match))throw new RuntimeException('No usable HTTP response for '.$method.' '.$path);
 return ['status'=>(int)$match[1],'body'=>$body,'headers'=>$headers];
}
foreach(['/' => 200,'/en/'=>200,'/booking/'=>200,'/api/v1/pages?locale=en-AT'=>200,'/sitemap.xml'=>200,'/manage/login?_locale=en'=>200,'/missing-page/'=>404,'/manage/calendar'=>302] as $path=>$expected){
 $response=httpRequest($path);
 if($response['status']!==$expected)throw new RuntimeException('Expected '.$expected.' for GET '.$path.', got '.$response['status']);
 if($expected===200&&str_contains($response['body'],'Symfony Exception'))throw new RuntimeException('Exception rendered on '.$path);
}
$db=new PDO('sqlite:'.dirname(__DIR__).'/var/care.db');
$before=(int)$db->query('SELECT COUNT(*) FROM appointment')->fetchColumn();
foreach(['missing'=>[],'forged'=>['_token'=>'forged-token']] as $label=>$token){
 $response=httpRequest('/booking/','POST',$token+['name'=>'Demo','email'=>'demo@example.test','consent'=>'yes']);
 if($response['status']!==403)throw new RuntimeException('Expected 403 for '.$label.' booking CSRF token; got '.$response['status'].' (a redirect or server error is not a pass).');
}
if((int)$db->query('SELECT COUNT(*) FROM appointment')->fetchColumn()!==$before)throw new RuntimeException('Rejected CSRF requests changed appointments');
echo "PASS: actual HTTP frontend, login, API, sitemap, 404, unauthenticated calendar redirect; missing and forged booking tokens return 403 without creating appointments.\n";
