<?php

class InstaPosting {
    public $url = 'https://instagram.com/api/v1/';
    public $uploads_folder = 'uploads/';
    public $is_auth = false;
    private $sig;
    private $agent;
    private $guid;
    private $device_id;
    private $data;
    private $auth;
    
    /**
     *   Generate data for auth
     *
     *   @param string $login username (string)
     *   @param string $pass password (string)
     */
    function __construct($login, $pass) {
        $this->agent        = $this->GenerateUserAgent();
        $this->guid         = $this->GenerateGuid();
        $this->device_id    = "android-".$this->guid;
        $this->data         = '{"device_id":"'.$this->device_id.'","guid":"'.$this->guid.'","username":"'.$login.'","password":"'.$pass.'","Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
        $this->sig          = $this->GenerateSignature();
        $this->data         = 'signed_body='.$this->sig.'.'.urlencode($this->data).'&ig_sig_key_version=4';
        $this->Login();
    }
    
    /**
     *   Auth user
     */
    public function Login() {
        $this->auth = $this->SendRequest('accounts/login/', true, false);
        if($this->auth[0] != 200) {
            throw new Exception("Wrong auth data or this ip is banned", 1);
        }
        else {
            $this->is_auth = true;
        }
    }
    
    /**
     *   Posting image on Instagram
     *
     *   @param string $imageurl image url (string)
     *   @param string $comment post description (string)
     */
    public function PostImage($imageurl, $comment = '') {
        if($this->is_auth) {
            $content = file_get_contents($imageurl);
            if($content) {
                $filename = md5(time()) . '.jpg';
                $fp = fopen(__DIR__ . '/../' . $this->uploads_folder . $filename, 'w');
                fwrite($fp, $content);
                fclose($fp);
                
                $filepath = __DIR__ . '/../' . $this->uploads_folder . $filename;
                if(file_exists($filepath)) {
                    $this->data = $this->GetPostData($this->uploads_folder . $filename);
                    $send_request = $this->SendRequest('media/upload/', true, true);
                    $send_request_decode = json_decode($send_request[1], true);
                    if($send_request[0] == 200 && $send_request_decode['status'] == 'ok') {
                        $comment = preg_replace("/\r|\n/", "", $comment);
                        $media_id = $send_request_decode['media_id'];
                        $this->data = '{"device_id":"'.$this->device_id.'",
                            "guid":"'.$this->guid.'",
                            "media_id":"'.$media_id.'",
                            "caption":"'.trim($comment).'",
                            "device_timestamp":"'.time().'",
                            "source_type":"5",
                            "filter_type":"0",
                            "extra":"{}",
                            "Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
                        $this->sig = $this->GenerateSignature();
                        $this->data = 'signed_body='.$this->sig.'.'.urlencode($this->data).'&ig_sig_key_version=4';
                        $send_request_2 = $this->SendRequest('media/configure/', true, true);
                        if($send_request_2[0] == 200) {
                            echo 'Post has been published!';
                        }
                        else {
                            throw new Exception("Status isn't okay.", 1);
                            
                        }
                    }
                    
                   unlink($filepath);
                    
                }
                else {
                    throw new Exception("Request failed, there's a chance that this proxy/ip is blocked.", 1);
                }
            }
            else {
                throw new Exception("Image is not found.", 1);
            }
        }
        else {
            throw new Exception("You are not logged in.", 1);
        }
    }
    
    /**
     *   Curl requests method
     *
     *   @param string $url instagram api method url (string)
     *   @param string $post request type (boolean)
     *   @param string $cookies get cookie file or create it (boolean)
     */
    public function SendRequest($url, $post, $cookies) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if($post) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
        }

        if($cookies) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        } else {
                curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
        }

        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

       return array($http, $response);
    }

    /**
     *   Generating guid string for request
     */
    public function GenerateGuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(16384, 20479),
                       mt_rand(32768, 49151),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535));
    }

    /**
     *   Generating user agent string for request
     */
    public function GenerateUserAgent() {
        $resolutions = array('720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320');
        $versions = array('GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100');
        $dpis = array('120', '160', '320', '240');

        $ver = $versions[array_rand($versions)];
        $dpi = $dpis[array_rand($dpis)];
        $res = $resolutions[array_rand($resolutions)];

        return 'Instagram 4.'.mt_rand(1,2).'.'.mt_rand(0,2).' Android ('.mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.$dpi.'; '.$res.'; samsung; '.$ver.'; '.$ver.'; smdkc210; en_US)';
     }

    /**
     *   Generating signature string for request
     */
    public function GenerateSignature() {
        return hash_hmac('sha256', $this->data, 'b4a23f5e39b5929e0666ac5de94c89d1618a2916');
    }
    
    /**
     *   Generate data for the request with image
     *
     *   @param string $filename copied image name (string)
     */
    public function GetPostData($filename) {
        if(!$filename) {
                throw new Exception("The image doesn't exist ".$filename, 1);
        } else {
                $post_data = array('device_timestamp' => time(), 'photo' => '@'.$filename);
                return $post_data;
        }
    }
}