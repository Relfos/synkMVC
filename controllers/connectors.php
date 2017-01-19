<?php

class ConnectorsController extends Controller {

	function __construct($context)
	{
	   parent::__construct($context);
	   
	   $this->token = $context->loadVar('api_token', '');	
	}

   function login($context)
   {
       $email = $context->loadVarFromRequest('email', '');
	   $password = $context->loadVarFromRequest('password', '');
	   $company = $context->loadVarFromRequest('company', '');

	   //var_dump($_REQUEST);	   die();
	   
        $url = 'login?company=' . $company . '&usermail=' . $email . '&password=' . urlencode($password);

        $result = $this->api_get($context, $url);
        if ($result->hasError) {
            $context->warning = $this->getErrorDescription($result);
        }
		else
        if (isset($result->token)) {
            $this->setToken($result->token);
			
			$context->changeView('synk');
        }   	   
		else
		{
			$context->warning = "API token missing!";	
		}
		
		$this->render($context);
   }
   
	public function synk($context)
	{
		$progress = new ProgressBar();
		$max = 6;
		for ($i=0; $i<=$max; $i++)
		{
			$progress->update($i, $max);			
			sleep(1);
		}
		
		$context->changeView('result');
		$this->render($context);
	}
   
    public function syncContent($context, $classname, $parent_id) {
        $key_name = getKeyForContent($classname);
        $apiname = getAPI_Endpoint($classname);
        $desckeys = getMatchMap($classname);

        // get external content from server
        $url = "moloni/" . $apiname . "/getall";

        if (isset($parent_id) && $parent_id !== false) {
            $url = appendArgument($url, 'id_cat', $parent_id);
        }

        $content = $this->api_get($context, $url);

        if ($content->hasError || !isset($content->data)) {
            return false;
        }

        $propmap = getPropertyMap($classname);
        //var_dump($propmap);

        $translations = loadTranslations($classname, $propmap);

        //var_dump($content);
        // get internal content list
        $content_bean = BeanFactory::getBean($classname);
        $old_content = $content_bean->get_full_list();

        $key_crm = $key_name;

        $missing = array();
        $imported = array();
        $exported = array();
        $mapped = array();

        $mapPair = getMapPairs($classname);
        $map_internal = $mapPair[0];
        $map_external = $mapPair[1];

        // loop through all local content and add it to the exported array
        $len = count($old_content);
        for ($j = 0; $j < $len; $j++) {
            $record = $old_content[$j];

            if (isset($parent_id) && $parent_id !== false && strcmp($record->aos_product_category_id, $parent_id) != 0) {
                continue;
            }

            if (!isset($record->id)) {
                continue;
            }

            $obj = (object) [];
            $obj->classname = $classname;
            $obj->data = $record;
            $obj->id = $record->id;
            $obj->description = $this->getObjectDescription($obj, $desckeys, $translations);

            // check if object has valid key, if yes, add it to exported array, otherwise add it to missing array
            if (!isset($record->$key_crm) || strlen($record->$key_crm) <= 0) {
                if (defined('SYNK_DEBUG')) {
                    echo "MISS: " . $record->name . "<br>";
                }

                // weird hack, some empty objects appear sometimes...
                if (strlen($obj->description) > 0) {
                    $obj->missing = $key_crm;
                    array_push($missing, $obj);
                }

                continue;
            } else {
                if (defined('SYNK_DEBUG')) {
                    echo "CRM: " . $record->name . "<br>";
                }

                $exported[$record->$key_crm] = $obj;
            }
        }

        // loop through all new content and search for matches
        // any matches found are removed from exported array
        // new content is added to imported array

        $len = count($content->data);
        for ($i = 0; $i < $len; $i++) {
            $obj = (object) [];
            $obj->classname = $classname;
            $external_data = $content->data[$i];

            //var_dump($external_data);

            if (defined('SYNK_DEBUG')) {
                echo "SERVER: " . $external_data["name"] . "<br>";
            }

            //  if the content is already on the CRM, remove it from the exported array
            if (array_key_exists($external_data->$key_crm, $exported)) {
                unset($exported[$external_data->$key_crm]);
                continue;
            }

            $record = (object) [];

            foreach ($propmap as $prop_crm => $prop_external) {
                if (isset($external_data->$prop_crm)) {
                    $record->$prop_crm = urldecode($external_data->$prop_external);
                }
            }

            //HACK!! The CRM code should fill this field automatically, but for some reason it is empty
            $record->aos_product_category_id = $parent_id;

            $obj->data = $record;
            $obj->description = $this->getObjectDescription($obj, $desckeys, $translations);
            $obj->id = create_guid();

            if (isset($external_data->$map_external)) {
                $obj->externalKey = $external_data->$map_external;
            }

            array_push($imported, $obj);
        }

        // loop through all missing matches and try pair them with imported objs
        $merges = array();
        $len = count($missing);
        $matchmap = getMatchMap($classname);
        $i = 0;
        while ($i < $len) {
            $matched = false;
            $len2 = count($imported);
            for ($j = 0; $j < $len2; $j++) {
                if (contentMatch($missing[$i]->data, $imported[$j]->data, $matchmap)) {
                    if (defined('SYNK_DEBUG')) {
                        echo "MATCHED: " . $missing[$i]->data->name . "<br>";
                    }

                    // copy missing fields
                    foreach ($propmap as $prop_crm => $prop_external) {
                        if (isset($missing[$i]->data->$prop_crm) && strlen($missing[$i]->data->$prop_crm) > 0) {
                            continue;
                        }

                        if (!isset($imported[$j]->data->$prop_crm) || strlen($imported[$j]->data->$prop_crm) <= 0) {
                            continue;
                        }

                        $missing[$i]->data->$prop_crm = $imported[$j]->data->$prop_crm;
                    }

                    array_push($merges, $missing[$i]);

                    array_splice($missing, $i, 1);
                    array_splice($imported, $j, 1);

                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                $len--;
            } else {
                $i++;
            }
        }

        $result = array();
        $result["missing"] = $missing;
        $result["merges"] = $merges;
        $result["exported"] = $exported;
        $result["imported"] = $imported;
        $result["mapped"] = $mapped;

        return $result;
    }
   
	public function setToken($token)
	{
		$this->token = $token;
		$_SESSION['api_token'] = $token;	   
	}
   
    public function api_get($context, $endpoint) {
        $result = $this->api_call($context, $endpoint, array());
        return $result;
    }

    public function api_set($context, $endpoint, $obj) {
        $fields = get_object_vars($obj);
        foreach ($fields as $property => $value) {
            $endpoint = appendArgument($endpoint, $property, $value);
        }

        return $this->api_call($context, $endpoint, array());
    }

    public function api_post($context, $endpoint, $fields) {
        return $this->api_call($context, $endpoint, $fields);
    }

    public function api_call($context, $endpoint, $fields) {
        $api_server = "http://93.108.142.102:8080";
        //$api_server = "crm.synkdata.com:8080";
        $url = $api_server . "/api/2016/" . $endpoint;

        if (strlen($this->token) > 0) {
            $url = appendArgument($url, 'token', $this->token);
        }

		//echo("URL: ".$url);	   die();
        
        //  Initiate curl
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (count($fields) > 0) {
            //url-ify the data for the POST
            $fields_string = '';
            foreach ($fields as $key => $value) {
                $fields_string .= $key . '=' . urlencode($value) . '&';
            }
            rtrim($fields_string, '&');

            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        } else {
            $fields_string = '';
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);
        // Execute
        $content = curl_exec($ch);
        // Closing
        curl_close($ch);

        $hasError = ($content === false);

        if ($hasError) {
            $content = '{"error":{"type":"connection","desc":"Connection failure."}}';
        }

        $result = json_decode($content);
        if ($result === NULL) {
            $result = (object) [];
        }

        $hasError = $hasError || isset($result->error) || isset($result->errors);
        $result->hasError = $hasError;

        //$export = str_replace("stdClass::__set_state(array(", '(', var_export($result, true));
        $export = $content;
		$context->log("API.URL:" . $url . "\nFIELDS:" . $fields_string . "\nRESULT:" . $export . "\n");


        return $result;
    }
	
	function getErrorDescription($obj) {
		if (isset($obj->error) && isset($obj->error->desc)) {
			return $obj->error->desc;
		}

		if (isset($obj->results) && isset($obj->results[0]) && isset($obj->results[0]->errors) && isset($obj->results[0]->errors[0])) {
			return $obj->results[0]->errors[0]->msg;
		}

		if (isset($obj->errors) && isset($obj->errors[0])) {
			return $obj->errors[0]->msg;
		}

		return "Unknown error.";
	}
	
} 


?>