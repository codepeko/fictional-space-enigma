<?php

if(!function_exists('get_recent_pastes'))
{
    function get_recent_pastes()
    {
        return \Cache::remember('recent_pastes',60,function(){
            return \App\Models\Paste::with('language')->where('status', 1)->where(function ($query) {
                $query->orWhereNull('user_id');
                $query->orWhereHas('user', function ($user) {
                    $user->whereIn('status', [0, 1]);
                });
            })->where(function ($query) {
                $query->where('expire_time', '>', \Carbon\Carbon::now())->orWhereNull('expire_time');
            })->orderBy('created_at', 'desc')->limit(config('settings.recent_pastes_limit'))->get(['title', 'syntax', 'slug', 'created_at', 'password', 'expire_time', 'views']);
        });
    }
}

if(!function_exists('curl_post_contents'))
{
    function curl_post_contents($url,$params)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);  

        // In real life you should use something like:
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $server_output = curl_exec($ch);
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close ($ch);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
                
        if($header['errno'] != 0) die('cURL Error - bad url, timeout, redirect loop');

        if ($header['http_code'] != 200) die('cURL Error - no page, no permissions, no service');

        // Further processing ...
        return $server_output;
    }
}

if(!function_exists('curl_get_contents'))
{
    function curl_get_contents($url)
    {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;

        if($header['errno'] != 0) die('cURL Error - bad url, timeout, redirect loop');

        if ($header['http_code'] != 200) die('cURL Error - no page, no permissions, no service');

        return $header['content'];
    }
}


if(!function_exists('short_urls'))
{
    function short_urls($urls,$short_service,$api_token)
    {
        $shortned_urls = [];
        if($short_service == 'fc.lc')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://fc.lc/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'exe.io')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://exe.io/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'shrinkearn')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://shrinkearn.com/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'clk.sh')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://clk.sh/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'adshort.co')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://adshort.co/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'tmearn')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://tmearn.net/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }       
        elseif($short_service == 'uii.io')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://uii.io/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'mitly.us')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://mitly.us/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'adbull.me')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://adbull.me/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'short.pe')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://short.pe/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'cpmlink.net')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://cpmlink.net/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }          
        elseif($short_service == 'shrtfly.com')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://shrtfly.com/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'cutwin.com')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://cutwin.com/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }          
        elseif($short_service == 'short.am')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://short.am/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'short.es')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://short.es/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'adf.ly')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://adf.ly/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }         
        elseif($short_service == 'al.ly')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://al.ly/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'link.codepeko.com')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "https://link.codepeko.com/api?api={$api_token}&url={$long_url}";
                $result = @json_decode(curl_get_contents($api_url),TRUE);
                if($result["status"] === 'error') {
                    $shortned_urls[$url] = $url;
                } else {
                    $shortned_urls[$url] = $result["shortenedUrl"];
                }                
            }
        }        
        elseif($short_service == 'ouo.io')
        {
            foreach ($urls as $url) {
                $long_url = urlencode($url);
                $api_url = "http://ouo.io/api/{$api_token}?s={$long_url}";
                try
                {
                    $result = json_decode(curl_get_contents($api_url),TRUE);
                }
                catch(\Exception $e)
                {
                    $result = $url;
                }
                
                $shortned_urls[$url] = $result;            
            }
        }
        else{
            foreach ($urls as $url) 
            {
                $shortned_urls[$url] = $url;               
            }
        }
        return $shortned_urls;
    }
}