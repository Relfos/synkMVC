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
		
		$total = 0;
		$total += $this->retrieveEntities($context, $progress, array(
				'route' => 'customers',
				'entity' => 'client',
				'key' => 'synk_nif_c',
				'propmap' => array(
						'email1' => 'email',
						'website' => 'website',
						'name' => 'name',
						'synk_nif_c' => 'vat',
						'billing_address_street' => 'address',
						'billing_address_city' => 'city',
						'billing_address_postalcode' => 'zip_code',
						'country_code' => 'country',
						'synk_discount_c' => 'discount'
					),
				'normalize' => array ('name' => true, 'city' => true, 'address'=> true)
			));

  
		$context->changeView('result');
		$context->importTotal = $total;
		$this->render($context);
	}
   
    public function retrieveEntities($context, $progress, $options) {
        $apiname = $options['route'];
		$entityClass = $options['entity'];
		$propmap = $options['propmap'];
		$normalize = $options['normalize'];
		$prop_key = $options['key'];

        // get external content from server
        $url = "moloni/" . $apiname . "/getall";

        /*if (isset($parent_id) && $parent_id !== false) {
            $url = appendArgument($url, 'id_cat', $parent_id);
        }*/

        $content = $this->api_get($context, $url);

        if ($content->hasError || !isset($content->data)) {
            return false;
        }

		$entity = $context->database->createEntity($context, $entityClass);
		$field = $entity->findField($propmap[$prop_key]);

        $imported = array();
		
        // loop through all new content 
        $len = count($content->data);
        for ($i = 0; $i < $len; $i++) 
		{
			$progress->update($i, $len - 1);			
            $external_data = $content->data[$i];
            //var_dump($external_data);
			
			if (!isset($external_data->$prop_key)) {
				continue;
			}
				
			$entityID = $external_data->$prop_key;
			$condition = $propmap[$prop_key].' = '. $field->encodeValue($context, $entityID);
			
			$entity = $context->database->fetchEntity($context, $entityClass, $condition);
						
			if (!$entity->exists)
			{
				foreach ($propmap as $prop_external => $prop_internal) {
					if (isset($external_data->$prop_external)) {
						
						$value = urldecode($external_data->$prop_external);
						
						if (array_key_exists ($prop_internal, $normalize))
						{
							$value = fixNameCase($value);							
						}						
						
						$entity->$prop_internal = $value;
					}
				}
				$entity->save($context);			
				$imported[] = $entity;
			}
        }
		
		return count($imported);
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
		$api_server = $context->config->synkServer;        
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