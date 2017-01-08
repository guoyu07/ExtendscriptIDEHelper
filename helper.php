<?php
   //$content_api = 'http://yearbook.github.io/esdocs/source/Photoshop/contents.json';
    $content_api = 'http://yearbook.github.io/esdocs/source/Javascript/contents.json';
function dd($data)
{
   var_dump($data);
   die;
}
function curl($api)
{;
    $ch = curl_init();
// 设置URL和相应的选项
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
//curl_setopt($ch, CURLOPT_HEADER, false);
// 抓取URL并把它传递给浏览器
    $s_content  = curl_exec($ch);
    curl_close($ch);
    return $s_content;

}
set_time_limit(0);
$s_content = curl($content_api);
//关闭cURL资源，并且释放系统资源
//curl_close($ch);
    $categorys = json_decode($s_content,true);
    foreach($categorys as $cate)
    {
        $cate_arr = explode(' ',$cate['category']);
        foreach($cate_arr as &$ca)
        {
            $ca = ucfirst($ca);
        }
        $category_name = implode('',$cate_arr);
        $category_name_dir = './'.$category_name;
        if(!file_exists($category_name_dir))
        {
           mkdir($category_name_dir);
        }
        $objects = $cate['objects'];
        foreach($objects as $object)
        {
            $object_file = $category_name_dir.'/'.$object.'.php';
            if(!file_exists($object_file))
            {
                file_put_contents($object_file,'');
            }
//            if(empty(file_get_contents($object_file)))
//            {
                $class_api = "http://yearbook.github.io/esdocs/source/Javascript/classes/".$object.".json";
            //http://yearbook.github.io/esdocs/source/Javascript/contents.json
                $class_content = curl($class_api);
                $content = (new Content($class_content))->make();
                file_put_contents($object_file,$content);
//            }
        }
    }

    class Content {
        private $content = '';
        public  function __construct($class_content)
        {
            $this->content  = json_decode($class_content,true);
        }

        public function  make()
        {
            $languageToken = $this->getLanguageToken();
            $classToken = $this->getClassToken();
//            $classProps = $this->getClassProperties();
            $classPropsToken = '';
            $instancePropsToken = '';
            $instanceMethodsToken = '';
            if(!empty($this->getClassProperties()))
            {
                $classPropsToken = $this->getClassPropertiesToken();
            }
            if(!empty($instanceProps = $this->getInstanceProperties()))
            {
                $instancePropsToken = $this->getInstancePropertiesToken();
            }
            if(!empty($this->getInstanceMethods()))
            {
                $instanceMethodsToken = $this->getInstanceMethodsToken();
            }
            $closeToken = $this->getCloseToken();
            return $languageToken.$classToken.$classPropsToken.$instancePropsToken.$instanceMethodsToken.$closeToken;
        }

        public function getInstancePropertiesToken()
        {
            $token = '';
            foreach($this->getInstanceProperties() as $property)
            {
                $token .= $this->getPropertyCommentToken($property);
                $token .= $this->getInstancePropertyToken($property);
            }
            return $token;

        }
        public function getInstanceMethodsToken()
        {
            $token = '';
            foreach($this->getInstanceMethods() as $property)
            {
                $token .= $this->getMethodCommentToken($property);
                $token .= $this->getMethodToken($property);
            }
            return $token;

        }

        public function getMethodCommentToken($property)
        {
            $token =  "/**"."\r\n"."*@desc ".$property['description']."\r\n";
            if(!empty($property['parameters']))
            {
                foreach($property['parameters'] as $parameter)
                {
                    $token .= "*@var ".$parameter['type']." $".$parameter['name'] ."  ".$parameter['description']."\r\n";
                }
            }
            $token  .= "*/\r\n";
            return $token;
        }
        public function getMethodToken($property)
        {
            $token = "public function ".$property['name'].'( ';
            if(!empty($property['parameters']))
            {
                foreach($property['parameters'] as $parameter)
                {
                    if($parameter['type'] != 'String' && $parameter['type'] != 'int')
                    {
                        $token .= ' '.$parameter['type'];
                    }
                    $token .= ' $'.$property['name'].',';
                }
            }
            $token = trim($token,',');
            $token .=" ){}\r\n\r\n";
            return $token;
        }
        public function getCloseToken()
        {
           return "\r\n}\r\n";
        }
        public function getClassPropertiesToken()
        {
            $token = '';
            foreach($this->getClassProperties() as $property)
            {
                $token .= $this->getPropertyCommentToken($property);
                $token .= $this->getClassPropertyToken($property);
            }
            return $token;
        }

        public function getPropertyCommentToken($property)
        {
            return "/**"."\r\n"."*@desc ".$property['description']."\r\n*@var ".$property['type']." $".$property['name']."\r\n*/\r\n";
        }
        public function getClassPropertyToken($property)
        {
            return "const ".$property['name'].' = '. $property['value'].";\r\n\r\n";
        }
        public function getInstancePropertyToken($property)
        {
            $token = '';
            if(!empty($property['readonly']))
            {
               $token .= 'private ';
            }
            else
            {
                $token .= 'public ';
            }
            $token .= '$'.$property['name'];
            if(isset($property['value']))
            {
                $token .= ' = '.$property['value'];
            }
            return $token." ; \r\n\r\n";
        }
        public function getLanguageToken()
        {
            return "<?php \r\n";
        }
        public function getClassToken()
        {
            return  "class ".$this->getClassName()."\r\n{\r\n";
        }
        public function parse()
        {
        }

        public function getClassName()
        {
           return $this->content['name'];
        }
        public function isDynamic()
        {
           return !empty($this->content['dynamic']);
        }
        public function getInstanceProperties()
        {
           return $this->content['elements']['instance']['properties'];
        }
        public function getInstanceMethods()
        {
            return $this->content['elements']['instance']['methods'];
        }

        public function getClassProperties()
        {
            return $this->content['elements']['class']['properties'];
        }

    }
