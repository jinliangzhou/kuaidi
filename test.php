<?php

file_put_contents('log', file_get_contents('php://input')."\n", FILE_APPEND);
// echo file_get_contents('php://input');
 // file_put_contents('log', 'iiiiiiii', FILE_APPEND);
$str = file_get_contents('php://input');
$str = str_replace("<![CDATA[","",$str);
$str = str_replace("]]>","",$str);
$xml = simplexml_load_string($str);
$json = json_encode($xml);

$return = json_decode($json,true);

    function func_fetch_url(&$url, $header ) {
        if ( empty($url['url']) ) {
            return false;
        }

        $ch = curl_init();
        if ($ch) {
            curl_setopt_array($ch, get_curl_options($url, $header));

            $response = curl_exec($ch);
            $str = get_curl_response($ch, $url, $response);

            curl_close($ch);
            unset($ch);

            return $str;
        }
        return false;
    }

    function get_curl_options($url, $header) {
        $opt = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_ENCODING => '',
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,

        );

        $opt[CURLOPT_URL] = $url['url'];

        if ( isset($url['cookie']) ) {
            $opt[CURLOPT_HEADER] = true;

            if ( $url['cookie'] != '' ) {
                $opt[CURLOPT_COOKIE] = $url['cookie'];
            }
        }

        if ( isset($url['followlocation']) && $url['followlocation'] === false ) {
            $opt[CURLOPT_FOLLOWLOCATION] = false;
        }

        if ( isset($url['post']) ) {
            $opt[CURLOPT_POST] = true;
            $opt[CURLOPT_POSTFIELDS] = $url['post'];
        }

        if ( !empty($url['proxy']) ) {
            $opt[CURLOPT_PROXY] = $url['proxy'];
            // $opt[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0; //浣跨敤浠ｇ悊鐨勬椂鍊欑敤杩欎釜鍘绘姄鍙栨暟鎹紝鏇寸埥
        }

        if ( isset($url['timeout']) ) {
            $opt[CURLOPT_TIMEOUT] = $url['timeout'];
        }

        if ( isset($url['connecttimeout']) ) {
            $opt[CURLOPT_CONNECTTIMEOUT] = $url['connecttimeout'];
        }

        if ( isset($url['sslversion']) ) { // sslversion
            $opt[CURLOPT_SSLVERSION] = $url['sslversion'];
        }

        if ( !empty($header) ) {
            $opt[CURLOPT_HTTPHEADER] = $header;
        }

        return $opt;
    }

    function get_curl_response($ch, &$url, $str) {
        $ch_info = curl_getinfo($ch);

        if ( isset($url['cookie']) ) {
            $head_size = $ch_info['header_size'];
            $head = substr($str, 0, $head_size);
            preg_match_all('/^Set-Cookie: (.*?);/m', $head, $m);
            if (isset($m[1])) {
                $url['cookie'] = implode(';', $m[1]);
            }

            $body = substr($str, $head_size);
        } else {
            $body = $str;
        }
            
        $url['http_code'] = $ch_info['http_code'];
        $url['curl_code'] = curl_errno($ch);
        $url['curl_error'] = curl_error($ch);

        if ( $url['http_code'] == 302 ) {
            $url['url'] = $ch_info['redirect_url'];
        }

        return $body;
    }

    function func_get_cut_string($string, $str1, $str2, $type = 4, $mode = true) {
            $str = $string;

            if ( !empty($str1) ) {
                switch ($type) {
                    case 1:
                    case 2:
                        $str1_pos = strpos($string, $str1);
                        break;
                    case 3:
                    case 4:
                        $str1_pos = strrpos($string, $str1);
                        break;
                    default:
                        # code...
                        break;
                }
                if ($str1_pos !== false) {
                    $str = substr($string, $str1_pos + strlen($str1));
                } else {
                    if (!$mode) {
                        $str = '';
                        return $str;
                    }
                }
            }
                
            if ( !empty($str2) ) {
                switch ($type) {
                    case 1:
                    case 3:
                        $str2_pos = strpos($str, $str2);
                        break;
                    case 2:
                    case 4:
                        $str2_pos = strrpos($str, $str2);
                        break;
                    default:
                        # code...
                        break;
                }
                if ($str2_pos !== false) {
                    $str = substr($str, 0, $str2_pos);
                }
            }
            
            return $str;
        }
    if (array_key_exists('Event', $return)) {
        if ($return['Event'] == 'subscribe') {
            $new = '欢迎关注快递信息查询公众号，请输入您的快递单号！';
        } else {
           $new = '欢迎下次再用！';
        }
        
    } else {
        $text = $return['Content'];
        $textlen = strlen($text);
        preg_match_all('/([0-9a-zA-Z]+)/', $text,$match);
        $len = 0;
        foreach ($match[1] as $key => $value) {
           $len += strlen($value);
        }
        if ($textlen == $len) {
            // 1,获取需求接口
            $url['url'] = 'http://www.kuaidi100.com/autonumber/autoComNum?resultv2=1&text='.$text;

            $html = func_fetch_url($url, '');
            $resinfo = json_decode($html,true);
            $com = $resinfo['auto'][0]['comCode'];
            // echo $com;

            $url['url'] = 'http://www.kuaidi100.com/query?type='.$com.'&postid='.$text.'&id=1&valicode=&temp=0.6161710431267386';

            $html = func_fetch_url($url, '');
             file_put_contents('log', $html, FILE_APPEND);
            $detail = json_decode($html,true);
            $new = '';
            if (empty($detail['data'])) {
               $new = '暂未查询到你的快递信息';
            } else {
               foreach ($detail['data'] as $k => $val) {
                   $new .= $val['time'] .' '.$val['context'] . "\n";
               }
            }
        } else {
           $new = '请输入正确的快递单号!';
        }
        
        
        
    }
    
    
    // echo $html;
    $time = time();
    $requstxml = '
     <xml>
     <ToUserName><![CDATA['.$return['FromUserName'].']]></ToUserName>
     <FromUserName><![CDATA['.$return['ToUserName'].']]></FromUserName>
     <CreateTime>'.$time.'</CreateTime>
     <MsgType><![CDATA[text]]></MsgType>
     <Content><![CDATA['.$new.']]></Content>
     </xml>';
     echo $requstxml;

